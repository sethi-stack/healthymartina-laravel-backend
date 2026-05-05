<?php

namespace App\Http\Controllers\Api\V1\Calendars;

use App\Http\Controllers\Controller;
use App\Http\Controllers\CalendarController as LegacyCalendarController;
use App\Models\Calendar;
use App\Models\CalendarExportJob;
use App\Models\Categoria;
use App\Models\ListaIngredientes;
use App\Models\Receta;
use App\Services\Calendar\ExternalPdfExportService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use iio\libmergepdf\Merger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CalendarPdfController extends Controller
{
    private function prepareExportRuntimeLimits(): void
    {
        // Export is CPU/memory heavy (Dompdf + merge). Raise limits only for this request path.
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);
    }

    public function download(Request $request)
    {
        $this->prepareExportRuntimeLimits();
        $startedAt = microtime(true);
        try {
            $validated = $request->validate([
                'calendar'       => 'required|integer',
                'export_param'   => 'required|array|min:1',
                'export_param.*' => 'integer|in:1,2,4',
                'template'       => 'nullable|in:classic,modern,bold,basic,advanced',
                'hero_recipe_id' => 'nullable|integer',
                'selected_recipes' => 'nullable|array',
                'selected_recipes.*' => 'integer',
            ]);

            $calendar = Auth::user()->calendars()->findOrFail($validated['calendar']);

            $payload = $this->buildExportPayload(
                $calendar,
                $validated['export_param'],
                $this->normalizeTemplate($validated['template'] ?? 'classic'),
                $validated['hero_recipe_id'] ?? null,
                $validated['selected_recipes'] ?? []
            );

            $pdfBinary = $this->renderExportPdf($payload);
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            Log::info('calendar_export.download.success', [
                'user_id' => Auth::id(),
                'calendar_id' => $calendar->id,
                'template' => $payload['template'] ?? null,
                'export_params' => $validated['export_param'] ?? [],
                'selected_recipe_count' => count($validated['selected_recipes'] ?? []),
                'included_recipe_pages' => count($payload['recipePages'] ?? []),
                'has_hero' => !empty($payload['heroRecipe']),
                'output_bytes' => strlen($pdfBinary),
                'duration_ms' => $durationMs,
            ]);

            $tmpExportDir = storage_path('app/exports/final');
            if (!is_dir($tmpExportDir)) {
                mkdir($tmpExportDir, 0775, true);
            }

            $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($calendar->title ?? 'calendario'));
            $tmpFilePath = $tmpExportDir . DIRECTORY_SEPARATOR . $safeName . '_' . uniqid('', true) . '.pdf';
            file_put_contents($tmpFilePath, $pdfBinary);
            unset($pdfBinary);

            return response()->download(
                $tmpFilePath,
                ($calendar->title ?? 'calendario') . '.pdf',
                ['Content-Type' => 'application/pdf']
            )->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            Log::error('calendar_export.download.failed', [
                'user_id' => Auth::id(),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al exportar calendario: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function email(Request $request)
    {
        $this->prepareExportRuntimeLimits();
        $startedAt = microtime(true);
        $validated = $request->validate([
            'calendar'       => 'required|integer',
            'export_param'   => 'required|array|min:1',
            'export_param.*' => 'integer|in:1,2,4',
            'template'       => 'nullable|in:classic,modern,bold,basic,advanced',
            'hero_recipe_id' => 'nullable|integer',
            'selected_recipes' => 'nullable|array',
            'selected_recipes.*' => 'integer',
            'recipient_email_address' => 'nullable|email',
        ]);

        $calendar = Auth::user()->calendars()->findOrFail($validated['calendar']);
        $recipient = $validated['recipient_email_address'] ?? Auth::user()->email;

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'Su correo eléctronico no es valido....',
            ], 422);
        }

        $payload = $this->buildExportPayload(
            $calendar,
            $validated['export_param'],
            $this->normalizeTemplate($validated['template'] ?? 'classic'),
            $validated['hero_recipe_id'] ?? null,
            $validated['selected_recipes'] ?? []
        );

        $pdfBinary = $this->renderExportPdf($payload);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        Log::info('calendar_export.email.rendered', [
            'user_id' => Auth::id(),
            'calendar_id' => $calendar->id,
            'recipient' => $recipient,
            'template' => $payload['template'] ?? null,
            'export_params' => $validated['export_param'] ?? [],
            'selected_recipe_count' => count($validated['selected_recipes'] ?? []),
            'included_recipe_pages' => count($payload['recipePages'] ?? []),
            'has_hero' => !empty($payload['heroRecipe']),
            'output_bytes' => strlen($pdfBinary),
            'duration_ms' => $durationMs,
        ]);
        $fileName = ($calendar->title ?? 'calendario') . '.pdf';
        $title = '¡Tu plan de alimentación esta listo!';

        $mailData = [
            'email' => $recipient,
            'title' => $title,
            'filename' => $calendar->title ?? 'calendario',
            'current_time' => todaySpanishDay(),
        ];

        Mail::send('email.send-calendario-mail', $mailData, function ($message) use ($mailData, $pdfBinary, $fileName) {
            $message->to($mailData['email'], $mailData['email'])
                ->subject($mailData['title'])
                ->attachData($pdfBinary, $fileName);
        });

        Mail::send('email.delivery-email', [
            'type' => 'Calendario',
            'meal_type' => 'Plan de alimentación',
            'to' => $mailData['email'],
            'title' => $mailData['title'],
            'current_time' => todaySpanishDay(),
        ], function ($message) use ($mailData, $pdfBinary, $fileName) {
            $message->to(Auth::user()->bemail, Auth::user()->bemail)
                ->subject('Tu plan de alimentación fue entregado')
                ->attachData($pdfBinary, $fileName);
        });

        return response()->json([
            'success' => true,
            'message' => '¡El envío de correo ha concluido con éxito!',
        ]);
    }

    public function startJob(Request $request, ExternalPdfExportService $externalPdfExportService)
    {
        $validated = $request->validate([
            'calendar'       => 'required|integer',
            'export_param'   => 'required|array|min:1',
            'export_param.*' => 'integer|in:1,2,4',
            'template'       => 'nullable|in:classic,modern,bold,basic,advanced',
            'hero_recipe_id' => 'nullable|integer',
            'selected_recipes' => 'nullable|array',
            'selected_recipes.*' => 'integer',
        ]);

        $calendar = Auth::user()->calendars()->findOrFail($validated['calendar']);

        $job = CalendarExportJob::create([
            'job_id' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'calendar_id' => $calendar->id,
            'status' => 'queued',
            'progress' => 0,
            'request_payload' => $validated,
            'status_payload' => null,
        ]);

        try {
            $payload = $this->buildExternalServicePayload($calendar, $validated);
            Log::info('calendar_export.external_payload.summary', [
                'job_id' => $job->job_id,
                'calendar_id' => $calendar->id,
                'template' => $payload['template'] ?? null,
                'export_param' => $payload['export_param'] ?? [],
                'selected_recipes_count' => count($payload['selected_recipes'] ?? []),
                'recipe_pages_count' => count($payload['recipePages'] ?? []),
                'nutrition_days_count' => count($payload['nutritionByDay'] ?? []),
                'lista_categories_count' => count($payload['listaData']['categories'] ?? []),
            ]);
            $externalPayload = [
                'job_id' => $job->job_id,
                'user_id' => Auth::id(),
                'calendar_id' => $calendar->id,
                'request_payload' => $validated,
                'payload' => $payload,
            ];
            $serviceResponse = $externalPdfExportService->enqueue($externalPayload);

            $job->external_job_id = (string) ($serviceResponse['job_id'] ?? $job->job_id);
            $job->status = (string) ($serviceResponse['status'] ?? 'queued');
            $job->save();
        } catch (\Throwable $e) {
            $job->status = 'failed';
            $job->error_message = $e->getMessage();
            $job->save();

            Log::error('calendar_export.job.start_failed', [
                'job_id' => $job->job_id,
                'user_id' => Auth::id(),
                'calendar_id' => $calendar->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($job->status === 'failed') {
            return response()->json([
                'success' => false,
                'job_id' => $job->job_id,
                'status' => $job->status,
                'message' => 'No se pudo iniciar la exportación asíncrona.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'job_id' => $job->job_id,
            'status' => $job->status,
        ]);
    }

    public function renderForExternalService(Request $request)
    {
        $token = (string) config('pdf_export.internal_token');
        $incoming = (string) $request->header('X-Internal-Token', '');
        if (empty($token) || !hash_equals($token, $incoming)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized internal render request.',
            ], 401);
        }

        $this->prepareExportRuntimeLimits();

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'calendar_id' => 'required|integer',
            'request_payload' => 'required|array',
            'request_payload.export_param' => 'required|array|min:1',
            'request_payload.export_param.*' => 'integer|in:1,2,4',
            'request_payload.template' => 'nullable|in:classic,modern,bold,basic,advanced',
            'request_payload.hero_recipe_id' => 'nullable|integer',
            'request_payload.selected_recipes' => 'nullable|array',
            'request_payload.selected_recipes.*' => 'integer',
        ]);

        Auth::onceUsingId((int) $validated['user_id']);
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to impersonate user for internal render.',
            ], 422);
        }

        $req = $validated['request_payload'];
        $calendar = Auth::user()->calendars()->findOrFail((int) $validated['calendar_id']);

        $payload = $this->buildExportPayload(
            $calendar,
            $req['export_param'],
            $this->normalizeTemplate($req['template'] ?? 'classic'),
            $req['hero_recipe_id'] ?? null,
            $req['selected_recipes'] ?? []
        );

        $pdfBinary = $this->renderExportPdf($payload);

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="calendar-' . $calendar->id . '.pdf"',
        ]);
    }

    public function jobStatus(string $jobId, ExternalPdfExportService $externalPdfExportService)
    {
        $job = CalendarExportJob::query()
            ->where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if (!empty($job->external_job_id) && in_array($job->status, ['queued', 'processing'], true)) {
            try {
                $status = $externalPdfExportService->status($job->external_job_id);
                $this->hydrateJobFromExternalStatus($job, $status, $externalPdfExportService);
            } catch (\Throwable $e) {
                Log::warning('calendar_export.job.status_sync_failed', [
                    'job_id' => $job->job_id,
                    'external_job_id' => $job->external_job_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'job_id' => $job->job_id,
            'status' => $job->status,
            'progress' => $job->progress,
            'error' => $job->error_message,
            'error_message' => $job->error_message,
            'file_url' => $job->file_url,
            'message' => $job->status_payload['message'] ?? $job->status_payload['stage']['message'] ?? null,
            'stage' => $job->status_payload['stage'] ?? null,
            'counters' => $job->status_payload['counters'] ?? null,
        ]);
    }

    public function jobDownload(string $jobId, ExternalPdfExportService $externalPdfExportService)
    {
        $job = CalendarExportJob::query()
            ->where('job_id', $jobId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($job->status !== 'completed') {
            return response()->json([
                'success' => false,
                'message' => 'El PDF no está listo todavía.',
            ], 409);
        }

        if (!$job->external_job_id) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró referencia del archivo exportado.',
            ], 404);
        }

        try {
            $download = $externalPdfExportService->downloadBinary((string) $job->external_job_id);
            $filename = ($job->calendar_id ? 'calendar-' . $job->calendar_id : 'calendario') . '.pdf';

            return response($download['body'], 200, [
                'Content-Type' => $download['content_type'] ?: 'application/pdf',
                'Content-Disposition' => $download['content_disposition'] ?: ('attachment; filename="' . $filename . '"'),
            ]);
        } catch (\Throwable $e) {
            Log::error('calendar_export.job.download_failed', [
                'job_id' => $job->job_id,
                'external_job_id' => $job->external_job_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo descargar el archivo exportado.',
            ], 502);
        }
    }

    private function hydrateJobFromExternalStatus(
        CalendarExportJob $job,
        array $status,
        ExternalPdfExportService $externalPdfExportService
    ): void {
        $job->status = (string) ($status['status'] ?? $job->status);
        $job->progress = (int) ($status['progress'] ?? $job->progress);
        $job->status_payload = $status;
        $job->error_message = $status['error_message'] ?? $job->error_message;
        $job->file_path = $status['file_path'] ?? $job->file_path;
        $job->file_size = isset($status['file_size']) ? (int) $status['file_size'] : $job->file_size;

        if ($job->status === 'processing' && !$job->started_at) {
            $job->started_at = now();
        }

        if ($job->status === 'completed') {
            $job->completed_at = now();
            $job->file_url = $externalPdfExportService->downloadUrl((string) $job->external_job_id);
        }

        if ($job->status === 'failed' && !$job->completed_at) {
            $job->completed_at = now();
        }

        $job->save();
    }

    private function buildExternalServicePayload(Calendar $calendar, array $validated): array
    {
        $exportParams = $validated['export_param'] ?? [];
        $template = $this->normalizeTemplate($validated['template'] ?? 'classic');
        $mainSchedule = json_decode($calendar->main_schedule, true) ?? [];
        $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
        $mainServings = json_decode($calendar->main_servings, true) ?? [];
        $sidesServings = json_decode($calendar->sides_servings, true) ?? [];
        $mainRacion = json_decode($calendar->main_racion, true) ?? [];
        $sidesRacion = json_decode($calendar->sides_racion, true) ?? [];
        $mainLeftovers = json_decode($calendar->main_leftovers, true) ?? [];
        $sidesLeftovers = json_decode($calendar->sides_leftovers, true) ?? [];
        $labels = json_decode($calendar->labels, true) ?? [];
        [$calendarRecipeIds] = $this->collectCalendarRecipeIds($mainSchedule, $sidesSchedule, $mainServings, $sidesServings);
        $selected = array_values(array_filter(array_map('intval', $validated['selected_recipes'] ?? [])));
        $heroRecipeId = !empty($validated['hero_recipe_id']) ? (int) $validated['hero_recipe_id'] : null;
        if (empty($selected)) {
            $selected = $calendarRecipeIds;
        }
        if (empty($selected) && !empty($calendarRecipeIds)) {
            $selected = $calendarRecipeIds;
        }
        if ($heroRecipeId) {
            $selected = array_values(array_filter($selected, fn ($id) => (int) $id !== $heroRecipeId));
        }
        $recipeIdsToLoad = array_values(array_unique(array_filter(array_merge($calendarRecipeIds, $selected, $heroRecipeId ? [$heroRecipeId] : []))));
        $recipes = empty($recipeIdsToLoad)
            ? collect()
            : Receta::query()
                ->whereIn('id', $recipeIdsToLoad)
                ->get()
                ->keyBy('id');

        $recipesMap = [];
        foreach ($recipes as $id => $recipe) {
            $recipesMap[(string) $id] = [
                'id' => (int) $recipe->id,
                'titulo' => (string) ($recipe->titulo ?? ''),
                'imagen_principal' => (string) ($recipe->imagen_principal ?? ''),
            ];
        }

        $recipePages = [];
        foreach ($selected as $recipeId) {
            $recipe = $recipes[$recipeId] ?? null;
            if (!$recipe) {
                continue;
            }

            $portion = $recipe->getPorciones()['cantidad'] ?? 1;
            $ingredients = $this->scaleRecipeIngredients($recipe, $portion);
            $nutrition = $this->scaleRecipeNutrition($recipe, $portion);
            $nutritionRows = [];
            foreach (($nutrition['info'] ?? []) as $nutrient) {
                if (!empty($nutrient['mostrar'])) {
                    $nutritionRows[] = [
                        'nombre' => $nutrient['nombre'] ?? 'Nutriente',
                        'cantidad' => isset($nutrient['cantidad']) ? round((float) $nutrient['cantidad'], 2) : null,
                        'unidad_medida' => $nutrient['unidad_medida'] ?? '',
                    ];
                }
            }

            $recipePages[] = [
                'recipe' => [
                    'id' => (int) $recipe->id,
                    'titulo' => (string) ($recipe->titulo ?? ''),
                    'imagen_principal' => (string) ($recipe->imagen_principal ?? ''),
                    'instrucciones' => array_values($recipe->getInstrucciones() ?? []),
                    'tips' => (string) ($recipe->tips ?? ''),
                ],
                'ingredients' => array_map(function ($ingredient) {
                    return [
                        'ingrediente' => $ingredient['ingrediente'] ?? ($ingredient['nombre'] ?? 'Ingrediente'),
                        'cantidad' => $ingredient['cantidad'] ?? null,
                        'medida' => $ingredient['medida'] ?? ($ingredient['unidad'] ?? ''),
                        'unidad' => $ingredient['unidad'] ?? ($ingredient['medida'] ?? ''),
                    ];
                }, $ingredients),
                'nutrition' => $nutritionRows,
            ];
        }

        $nutritionByDay = [];
        if (in_array(4, $exportParams, true)) {
            $legacy = new LegacyCalendarController();
            $dayMap = [
                'day_1' => 'Lunes', 'day_2' => 'Martes', 'day_3' => 'Miércoles', 'day_4' => 'Jueves',
                'day_5' => 'Viernes', 'day_6' => 'Sábado', 'day_7' => 'Domingo',
            ];
            foreach ($dayMap as $dayKey => $dayLabel) {
                $nutrition = $legacy->nutriInfo($dayKey, (int) $calendar->id, true);
                $rows = [];
                foreach ((array) $nutrition as $entry) {
                    if (!is_array($entry) || count($entry) < 6) {
                        continue;
                    }
                    $rows[] = [
                        'id' => isset($entry[0]) ? (int) $entry[0] : null,
                        'nombre' => isset($entry[1]) ? (string) $entry[1] : 'Nutriente',
                        'unidad_medida' => isset($entry[2]) ? (string) $entry[2] : '',
                        'cantidad' => isset($entry[3]) ? round((float) $entry[3], 2) : 0,
                        'porcentaje' => isset($entry[4]) ? round((float) $entry[4], 2) : null,
                        'color' => isset($entry[5]) ? (string) $entry[5] : '',
                    ];
                }
                $nutritionByDay[] = [
                    'day_key' => $dayKey,
                    'label' => $labels['days'][$dayKey] ?? $dayLabel,
                    'rows' => $rows,
                ];
            }
        }

        $listaPayload = ['categories' => [], 'taken_ids' => []];
        if (in_array(2, $exportParams, true)) {
            $listaData = $this->buildListaData($calendar);
            $listaPayload = [
                'taken_ids' => $listaData['taken_ids'] ?? [],
                'categories' => array_map(function ($cat) {
                    return [
                        'name' => $cat['name'] ?? ($cat['nombre'] ?? 'Categoría'),
                        'items' => array_map(function ($item) {
                            return [
                                'ingrediente_id' => $item['ingrediente_id'] ?? null,
                                'nombre' => $item['nombre'] ?? ($item['ingrediente'] ?? 'Ingrediente'),
                                'cantidad' => $item['cantidad'] ?? null,
                                'unidad' => $item['unidad'] ?? '',
                            ];
                        }, $cat['items'] ?? []),
                    ];
                }, $listaData['categories'] ?? []),
            ];
        }

        return [
            'template' => $template,
            'export_param' => $exportParams,
            'hero_recipe_id' => $heroRecipeId,
            'heroRecipe' => $heroRecipeId && isset($recipesMap[(string) $heroRecipeId]) ? $recipesMap[(string) $heroRecipeId] : null,
            'selected_recipes' => $selected,
            'recipePages' => $recipePages,
            'nutritionByDay' => $nutritionByDay,
            'listaData' => $listaPayload,
            'calendar_snapshot' => [
                'id' => (int) $calendar->id,
                'title' => (string) ($calendar->title ?? 'calendario'),
                'labels' => $labels,
                'main_schedule' => $mainSchedule,
                'sides_schedule' => $sidesSchedule,
                'main_servings' => $mainServings,
                'sides_servings' => $sidesServings,
                'main_racion' => $mainRacion,
                'sides_racion' => $sidesRacion,
                'main_leftovers' => $mainLeftovers,
                'sides_leftovers' => $sidesLeftovers,
                'recipe_ids' => $calendarRecipeIds,
                'recipes_map' => $recipesMap,
            ],
        ];
    }

    /**
     * Build lista (shopping list) data.
     * Uses the same getRelatedIngrediente() helper as ListaController.
     */
    private function buildListaData(Calendar $calendar): array
    {
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        $takenIds = $takenIngredients->pluck('ingred_id')->toArray();

        $categorias = Categoria::orderBy('sort', 'ASC')->get();

        $manualItems = ListaIngredientes::where('calendario_id', $calendar->id)->get();

        $byCategory = [];
        foreach ($categorias as $category) {
            $items = getRelatedIngrediente($calendar->id, $category->id, 'list');
            if (!empty($items)) {
                $byCategory[] = [
                    'id'    => $category->id,
                    'nombre'=> $category->nombre,
                    'name'  => $category->nombre,
                    'items' => collect($items)->map(function ($item) use ($category) {
                        return [
                            'ingrediente_id' => $item['ingrediente_id'] ?? null,
                            'categoria_id'   => $item['categoria_id'] ?? $category->id,
                            'nombre'         => $item['ingrediente'] ?? ($item['nombre'] ?? ($item['name'] ?? 'Ingrediente')),
                            'cantidad'       => $item['cantidad'] ?? null,
                            'unidad'         => $item['medida'] ?? ($item['unidad'] ?? ''),
                            'taken'          => false,
                        ];
                    })->toArray(),
                ];
            }
        }

        if ($manualItems->count()) {
            $byCategory[] = [
                'name'  => 'Otros',
                'items' => $manualItems->map(fn($i) => [
                    'ingrediente_id' => $i->id,
                    'nombre'   => $i->nombre,
                    'cantidad' => $i->cantidad,
                    'unidad'   => $i->unidad_medida,
                    'taken'    => false,
                ])->toArray(),
            ];
        }

        $ingredientsCount = collect($byCategory)->sum(function ($category) {
            return count($category['items'] ?? []);
        });

        return [
            'categories' => $byCategory,
            'taken_ids'  => $takenIds,
            'manual_items' => $manualItems,
            'ingredients_count' => $ingredientsCount,
        ];
    }

    private function normalizeTemplate(string $template): string
    {
        return match ($template) {
            'basic' => 'classic',
            'advanced' => 'bold',
            'classic', 'modern', 'bold' => $template,
            default => 'classic',
        };
    }

    private function buildExportPayload(Calendar $calendar, array $exportParams, string $template, $heroRecipeId, array $selectedRecipeIds): array
    {
        $mainSchedule = json_decode($calendar->main_schedule, true) ?? [];
        $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
        $mainServings = json_decode($calendar->main_servings, true) ?? [];
        $sidesServings = json_decode($calendar->sides_servings, true) ?? [];
        $mainRacion = json_decode($calendar->main_racion, true) ?? [];
        $sidesRacion = json_decode($calendar->sides_racion, true) ?? [];
        $labels = json_decode($calendar->labels, true) ?? [];

        $dayLabels = $labels['days'] ?? [
            'day_1' => 'Lunes',
            'day_2' => 'Martes',
            'day_3' => 'Miércoles',
            'day_4' => 'Jueves',
            'day_5' => 'Viernes',
            'day_6' => 'Sábado',
            'day_7' => 'Domingo',
        ];
        $mealLabels = $labels['meals'] ?? [
            'meal_1' => 'Desayuno',
            'meal_2' => 'Lunch',
            'meal_3' => 'Comida',
            'meal_4' => 'Snack',
            'meal_5' => 'Cena',
            'meal_6' => 'Otros',
        ];

        [$calendarRecipeIds, $recipeMeta] = $this->collectCalendarRecipeIds($mainSchedule, $sidesSchedule, $mainServings, $sidesServings);

        $selectedRecipeIds = array_values(array_filter(array_map('intval', $selectedRecipeIds ?? [])));
        if (empty($selectedRecipeIds)) {
            $selectedRecipeIds = $calendarRecipeIds;
        }

        $heroRecipeId = $heroRecipeId ? (int) $heroRecipeId : null;
        if ($heroRecipeId) {
            $selectedRecipeIds = array_values(array_filter($selectedRecipeIds, fn ($id) => (int) $id !== $heroRecipeId));
        }

        $recipeIdsToLoad = array_values(array_unique(array_filter(array_merge($calendarRecipeIds, $selectedRecipeIds, $heroRecipeId ? [$heroRecipeId] : []))));
        $recipes = empty($recipeIdsToLoad)
            ? collect()
            : Receta::whereIn('id', $recipeIdsToLoad)->get()->keyBy('id');

        $recipePages = [];
        foreach ($selectedRecipeIds as $recipeId) {
            $recipe = $recipes[$recipeId] ?? null;
            if (!$recipe) {
                continue;
            }

            $portion = $recipeMeta[$recipeId]['portion'] ?? $recipe->getPorciones()['cantidad'] ?? 1;
            $nutrition = $this->scaleRecipeNutrition($recipe, $portion);
            $recipePages[] = [
                'recipe' => $recipe,
                'portion' => $portion,
                'ingredients' => $this->scaleRecipeIngredients($recipe, $portion),
                'nutrition' => $nutrition,
            ];
        }

        $heroRecipe = $heroRecipeId ? ($recipes[$heroRecipeId] ?? null) : null;

        $days = [];
        foreach ($mainSchedule as $dayKey => $meals) {
            $hasAnyRecipe = false;
            $mealRows = [];
            foreach ($meals as $mealKey => $mainId) {
                $sideId = $sidesSchedule[$dayKey][$mealKey] ?? null;
                if (!$mainId && !$sideId) {
                    continue;
                }
                $hasAnyRecipe = true;
                $mealRows[$mealKey] = [
                    'label' => $mealLabels[$mealKey] ?? $mealKey,
                    'main' => $mainId && isset($recipes[$mainId]) ? $recipes[$mainId] : null,
                    'side' => $sideId && isset($recipes[$sideId]) ? $recipes[$sideId] : null,
                    'racion' => $mainRacion[$dayKey][$mealKey] ?? 1,
                    'sRacion' => $sidesRacion[$dayKey][$mealKey] ?? 1,
                ];
            }

            if ($hasAnyRecipe) {
                $days[$dayKey] = [
                    'label' => $dayLabels[$dayKey] ?? $dayKey,
                    'meals' => $mealRows,
                ];
            }
        }

        $nutritionByDay = [];
        if (in_array(4, $exportParams)) {
            foreach ($mainSchedule as $dayKey => $meals) {
                $totalCal = 0;
                foreach ($meals as $mealKey => $recipeId) {
                    if ($recipeId && isset($recipes[$recipeId])) {
                        $racion = $mainRacion[$dayKey][$mealKey] ?? 1;
                        $recipeCal = (int) ($recipes[$recipeId]->calories ?? 0);
                        $recipePort = max(1, (int) ($recipes[$recipeId]->porciones ?? ($recipes[$recipeId]->getPorciones()['cantidad'] ?? 1)));
                        $totalCal += ($recipeCal / $recipePort) * $racion;
                    }

                    $sideId = $sidesSchedule[$dayKey][$mealKey] ?? null;
                    if ($sideId && isset($recipes[$sideId])) {
                        $sRacion = $sidesRacion[$dayKey][$mealKey] ?? 1;
                        $recipeCal = (int) ($recipes[$sideId]->calories ?? 0);
                        $recipePort = max(1, (int) ($recipes[$sideId]->porciones ?? ($recipes[$sideId]->getPorciones()['cantidad'] ?? 1)));
                        $totalCal += ($recipeCal / $recipePort) * $sRacion;
                    }
                }

                if (isset($days[$dayKey])) {
                    $nutritionByDay[$dayKey] = [
                        'label' => $dayLabels[$dayKey] ?? $dayKey,
                        'calories' => round($totalCal),
                    ];
                }
            }
        }

        $listaData = in_array(2, $exportParams) ? $this->buildListaData($calendar) : null;
        return [
            'calendar' => $calendar,
            'template' => $template,
            'exportParams' => $exportParams,
            'days' => $days,
            'recipes' => $recipes,
            'recipes_list' => $recipes->values(),
            'nutritionByDay' => $nutritionByDay,
            'listaData' => $listaData,
            'mainSchedule' => $mainSchedule,
            'sidesSchedule' => $sidesSchedule,
            'mainServings' => $mainServings,
            'mainRacion' => $mainRacion,
            'sidesRacion' => $sidesRacion,
            'mainLeftovers' => json_decode($calendar->main_leftovers, true) ?? [],
            'sidesLeftovers' => json_decode($calendar->sides_leftovers, true) ?? [],
            'labels' => $labels,
            'heroRecipe' => $heroRecipe,
            'recipePages' => $recipePages,
            'calendarRecipes' => $calendarRecipeIds,
            'taken_ingredientes' => $listaData['taken_ids'] ?? [],
            'categorias' => $listaData['categories'] ?? [],
            'lista_ingredientes' => $listaData['manual_items'] ?? [],
            'ingredients_count' => $listaData['ingredients_count'] ?? 0,
            'placeholderImage' => public_path('img/recetas/imagen-receta-principal.jpg'),
            'placeholderImageSrc' => $this->buildPlaceholderSvgDataUri(),
        ];
    }

    private function renderExportPdf(array $payload): string
    {
        $template = $payload['template'] ?? 'classic';
        if ($template === 'bold') {
            return $this->renderLegacyBoldExportPdf($payload);
        }
        $paper = $template === 'modern' ? 'landscape' : 'portrait';
        return $this->mergeSectionsFromTempFiles(function (Merger $merger, string $tmpDir, array &$files) use ($payload, $template, $paper) {
            if (!empty($payload['heroRecipe'])) {
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.calendar.hero-cover',
                    [
                        'calendar' => $payload['calendar'],
                        'user' => Auth::user(),
                        'template' => $template,
                        'recipe' => $payload['heroRecipe'],
                        'placeholderImage' => $payload['placeholderImage'],
                    ],
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'hero'
                );
            }

            foreach ($payload['recipePages'] as $index => $recipePage) {
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.calendar.recipe-page',
                    [
                        'calendar' => $payload['calendar'],
                        'user' => Auth::user(),
                        'template' => $template,
                        'recipe' => $recipePage['recipe'],
                        'portion' => $recipePage['portion'],
                        'ingredients' => $recipePage['ingredients'],
                        'placeholderImage' => $payload['placeholderImage'],
                    ],
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'recipe_' . $index
                );
            }

            $this->renderViewToTempFileAndAdd(
                $merger,
                "pdf.calendar.{$template}",
                array_merge($payload, [
                    'user' => Auth::user(),
                ]),
                'a4',
                $paper,
                $tmpDir,
                $files,
                'calendar'
            );
        });
    }

    private function renderLegacyBoldExportPdf(array $payload): string
    {
        $calendar = $payload['calendar'];
        $exportParams = $payload['exportParams'] ?? [];
        $placeholderImage = $payload['placeholderImage'] ?? public_path('img/recetas/imagen-receta-principal.jpg');
        // Avoid loading all recipes in memory during export.
        $recipesList = collect($payload['recipes_list'] ?? [])->sortBy('free')->values();
        $boldContext = $this->buildLegacyBoldContext($payload);
        return $this->mergeSectionsFromTempFiles(function (Merger $merger, string $tmpDir, array &$files) use ($payload, $boldContext, $calendar, $exportParams, $placeholderImage, $recipesList) {
            if (!empty($payload['heroRecipe'])) {
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.bold.bold-rectia-cover',
                    array_merge($boldContext, [
                        'calendar' => $calendar,
                        'calendario' => $calendar,
                        'recipe' => $payload['heroRecipe'],
                        'receta_cover_img_src' => $this->resolveRecipeImage($payload['heroRecipe']->imagen_principal ?? null, $placeholderImage),
                    ]),
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'hero'
                );
            }

            foreach ($payload['recipePages'] as $index => $recipePage) {
                $recipe = $recipePage['recipe'];
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.bold.calendar-bold-recipe',
                    array_merge($boldContext, [
                        'calendar' => $calendar,
                        'calendario' => $calendar,
                        'recipe' => $recipe,
                        'porcion' => $recipePage['portion'],
                        'recipe_ingredients_data' => $boldContext['recipe_ingredients_data'] ?? [],
                        'export_param' => $exportParams,
                    ]),
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'recipe_' . $index
                );
            }

            if (in_array(1, $exportParams, true)) {
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.bold.bold-calendario-potrait',
                    array_merge($boldContext, [
                        'calendar' => $calendar,
                        'calendario' => $calendar,
                        'recipes_list' => $recipesList,
                        'placeholderImage' => $placeholderImage,
                        'placeholderImageSrc' => $this->buildPlaceholderSvgDataUri(),
                    ]),
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'calendar'
                );
            }

            if (in_array(4, $exportParams, true)) {
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.bold.calendar-bold-nutri',
                    array_merge($boldContext, [
                        'calendar' => $calendar,
                        'calendario' => $calendar,
                        'recipes_list' => $recipesList,
                    ]),
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'nutrition'
                );
            }

            if (in_array(2, $exportParams, true)) {
                $this->renderViewToTempFileAndAdd(
                    $merger,
                    'pdf.bold.calendar-bold-lista',
                    array_merge($boldContext, [
                        'calendar' => $calendar,
                        'calendario' => $calendar,
                    ]),
                    'a4',
                    'portrait',
                    $tmpDir,
                    $files,
                    'lista'
                );
            }
        });
    }

    private function mergeSectionsFromTempFiles(callable $builder): string
    {
        $tmpDir = storage_path('app/exports/tmp/' . uniqid('calendar_', true));
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $files = [];
        $merger = new Merger();
        $startedAt = microtime(true);
        try {
            $builder($merger, $tmpDir, $files);
            $merged = $merger->merge();
            Log::info('calendar_export.merge.success', [
                'user_id' => Auth::id(),
                'tmp_dir' => $tmpDir,
                'chunk_count' => count($files),
                'output_bytes' => strlen($merged),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ]);
            return $merged;
        } catch (\Throwable $e) {
            Log::error('calendar_export.merge.failed', [
                'user_id' => Auth::id(),
                'tmp_dir' => $tmpDir,
                'chunk_count' => count($files),
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            foreach ($files as $file) {
                if (is_string($file) && file_exists($file)) {
                    @unlink($file);
                }
            }
            if (is_dir($tmpDir)) {
                @rmdir($tmpDir);
            }
        }
    }

    private function renderViewToTempFileAndAdd(
        Merger $merger,
        string $view,
        array $data,
        string $paper,
        string $orientation,
        string $tmpDir,
        array &$files,
        string $prefix
    ): void {
        $pdfBinary = PDF::loadView($view, $data)->setPaper($paper, $orientation)->output();
        $path = $tmpDir . DIRECTORY_SEPARATOR . $prefix . '_' . uniqid('', true) . '.pdf';
        file_put_contents($path, $pdfBinary);
        $files[] = $path;
        $merger->addFile($path);
    }

    private function buildLegacyBoldContext(array $payload): array
    {
        $calendar = $payload['calendar'];
        $labels = $payload['labels'] ?? [];
        $mainSchedule = $payload['mainSchedule'] ?? [];
        $sidesSchedule = $payload['sidesSchedule'] ?? [];
        $mainLeftovers = $payload['mainLeftovers'] ?? [];
        $sidesLeftovers = $payload['sidesLeftovers'] ?? [];
        $mainServings = $payload['mainServings'] ?? [];
        $sidesServings = $payload['sidesServings'] ?? [];
        $mainRacion = $payload['mainRacion'] ?? [];
        $sidesRacion = $payload['sidesRacion'] ?? [];
        $recipesMap = $payload['recipes'] ?? collect();
        $recipesList = $payload['recipes_list'] ?? collect($recipesMap)->values();
        $recipeImages = collect($recipesMap)->mapWithKeys(function ($recipe) use ($payload) {
            return [
                $recipe->id => $this->resolveRecipeImage(
                    $recipe->imagen_principal ?? null,
                    $payload['placeholderImage'] ?? public_path('img/recetas/imagen-receta-principal.jpg')
                ),
            ];
        })->all();

        [$calendarRecipeIds, $recipeMeta] = $this->collectCalendarRecipeIds($mainSchedule, $sidesSchedule, $mainServings, $sidesServings);
        [$recipeIngredientsByRecipe, $recipeIngredientsFlat] = $this->buildLegacyRecipeIngredientMaps($payload['recipePages'] ?? []);

        return [
            'calendar' => $calendar,
            'calendario' => $calendar,
            'cLabels' => $labels,
            'cMains' => $mainSchedule,
            'cSides' => $sidesSchedule,
            'cMainLeftovers' => $mainLeftovers,
            'cMainServings' => $mainServings,
            'cSideLeftovers' => $sidesLeftovers,
            'cSideServings' => $sidesServings,
            'cMracion' => $mainRacion,
            'cSracion' => $sidesRacion,
            'cRecipes' => $recipesMap,
            'recipes_list' => $recipesList,
            'recipe_images' => $recipeImages,
            'calendarRecipeIds' => $calendarRecipeIds,
            'recipeMeta' => $recipeMeta,
            'nutritionals_info' => $this->getUserNutritionalsInfo(),
            'nutri_info' => $this->buildLegacyNutritionInfo((int) $calendar->id),
            'recipe_ingredients_data' => $recipeIngredientsByRecipe,
            'recipe_ingredients' => $recipeIngredientsFlat,
            'taken_ingredientes' => collect($payload['taken_ingredientes'] ?? [])->values()->all(),
            'categorias' => collect($payload['categorias'] ?? [])->map(function ($category) {
                if (is_array($category)) {
                    return (object) [
                        'id' => $category['id'] ?? null,
                        'nombre' => $category['nombre'] ?? ($category['name'] ?? ''),
                    ];
                }

                return $category;
            })->filter(fn ($category) => !empty($category->id) || !empty($category->nombre))->values()->all(),
            'lista_ingredientes' => collect($payload['lista_ingredientes'] ?? [])->values()->all(),
            'ingredients_count' => $payload['ingredients_count'] ?? 0,
            'placeholderImage' => $payload['placeholderImage'] ?? public_path('img/recetas/imagen-receta-principal.jpg'),
        ];
    }

    private function buildLegacyRecipeIngredientMaps(array $recipePages): array
    {
        $byRecipe = [];
        $flat = new \stdClass();

        foreach ($recipePages as $recipePage) {
            $recipe = $recipePage['recipe'] ?? null;
            $ingredients = $recipePage['ingredients'] ?? [];
            if (!$recipe) {
                continue;
            }

            $recipeMap = [];
            foreach ($ingredients as $ingredient) {
                $uid = $ingredient['ingred_uid'] ?? null;
                if (!$uid) {
                    continue;
                }

                $formatted = $this->formatLegacyRecipeIngredient($ingredient);
                $recipeMap[$uid] = $formatted;
                $flat->{$uid} = $formatted;
            }

            $byRecipe[$recipe->id] = $recipeMap;
        }

        return [$byRecipe, $flat];
    }

    private function formatLegacyRecipeIngredient(array $ingredient): string
    {
        $quantity = $ingredient['cantidad'] ?? '';
        $measure = '';

        if ($quantity !== '' && is_numeric($quantity) && (float) $quantity > 1) {
            $measure = $ingredient['medida_plural'] ?? ($ingredient['medida'] ?? '');
        } else {
            $measure = $ingredient['medida'] ?? '';
        }

        $parts = [];
        if ($quantity !== '') {
            $parts[] = $quantity;
        }
        if ($measure !== '') {
            $parts[] = mb_strtolower($measure);
        }

        return trim(implode(' ', $parts));
    }

    private function getUserNutritionalsInfo(): array
    {
        $nutritionalPreferences = DB::table('nutritional_preferences')->where('user_id', Auth::user()->id)->first();
        if (!empty($nutritionalPreferences->nutritional_info)) {
            $info = json_decode($nutritionalPreferences->nutritional_info, true) ?: [];
        } else {
            $info = config()->get('constants.nutritients') ?? [];
        }

        return array_map(function ($item) {
            return (object) $item;
        }, array_values($info));
    }

    private function buildLegacyNutritionInfo(int $calendarId): array
    {
        $legacy = new LegacyCalendarController();

        return [
            'day_1' => $legacy->nutriInfo('day_1', $calendarId, true),
            'day_2' => $legacy->nutriInfo('day_2', $calendarId, true),
            'day_3' => $legacy->nutriInfo('day_3', $calendarId, true),
            'day_4' => $legacy->nutriInfo('day_4', $calendarId, true),
            'day_5' => $legacy->nutriInfo('day_5', $calendarId, true),
            'day_6' => $legacy->nutriInfo('day_6', $calendarId, true),
            'day_7' => $legacy->nutriInfo('day_7', $calendarId, true),
        ];
    }

    private function resolveRecipeImage(?string $src, string $placeholderImage): string
    {
        if (empty($src)) {
            return $placeholderImage;
        }

        if (preg_match('/^https?:\/\//i', $src)) {
            // Do not probe remote images synchronously (slow + timeout risk on bulk export).
            return $src;
        }

        if (preg_match('/^data:image\//i', $src)) {
            return $src;
        }

        $local = ltrim($src, '/');
        foreach ([public_path($local), public_path('storage/' . $local)] as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        return $placeholderImage;
    }

    private function buildPlaceholderSvgDataUri(): string
    {
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
  <rect width="120" height="120" rx="10" ry="10" fill="#ececec"/>
  <rect x="18" y="18" width="84" height="84" rx="8" ry="8" fill="#f8f8f8" stroke="#cfcfcf" stroke-width="2"/>
  <circle cx="47" cy="50" r="10" fill="#c9c9c9"/>
  <path d="M28 88 L48 64 L62 76 L74 58 L92 88 Z" fill="#d6d6d6"/>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    private function collectCalendarRecipeIds(array $mainSchedule, array $sidesSchedule, array $mainServings, array $sidesServings): array
    {
        $ordered = [];
        $meta = [];

        foreach ($mainSchedule as $dayKey => $meals) {
            foreach ($meals as $mealKey => $recipeId) {
                if ($recipeId && !isset($meta[$recipeId])) {
                    $portion = $mainServings[$dayKey][$mealKey] ?? 1;
                    $ordered[] = (int) $recipeId;
                    $meta[(int) $recipeId] = [
                        'portion' => $portion,
                        'day' => $dayKey,
                        'meal' => $mealKey,
                        'type' => 'main',
                    ];
                }

                $sideId = $sidesSchedule[$dayKey][$mealKey] ?? null;
                if ($sideId && !isset($meta[$sideId])) {
                    $portion = $sidesServings[$dayKey][$mealKey] ?? 1;
                    $ordered[] = (int) $sideId;
                    $meta[(int) $sideId] = [
                        'portion' => $portion,
                        'day' => $dayKey,
                        'meal' => $mealKey,
                        'type' => 'side',
                    ];
                }
            }
        }

        return [$ordered, $meta];
    }

    private function scaleRecipeIngredients(Receta $recipe, float|int $portion): array
    {
        $ingredients = $recipe->getIngredientes(true);
        $basePortion = max(1, (float) ($recipe->getPorciones()['cantidad'] ?? 1));
        $ratio = max(0, (float) $portion) / $basePortion;

        return array_map(function (array $ingredient) use ($ratio) {
            if (isset($ingredient['cantidad']) && is_numeric($ingredient['cantidad'])) {
                $scaled = (float) $ingredient['cantidad'] * $ratio;
                $ingredient['cantidad'] = rtrim(rtrim(number_format($scaled, 2, '.', ''), '0'), '.');
            }

            return $ingredient;
        }, $ingredients);
    }

    private function scaleRecipeNutrition(Receta $recipe, float|int $portion): array
    {
        $nutrition = $recipe->getInformacionNutrimental();
        $basePortion = max(1, (float) ($recipe->getPorciones()['cantidad'] ?? 1));
        $ratio = max(0, (float) $portion) / $basePortion;

        if (!isset($nutrition['info']) || !is_array($nutrition['info'])) {
            return $nutrition;
        }

        foreach ($nutrition['info'] as $nutrientId => $nutrientInfo) {
            if (isset($nutrientInfo['cantidad']) && is_numeric($nutrientInfo['cantidad'])) {
                $nutrition['info'][$nutrientId]['cantidad'] = $nutrientInfo['cantidad'] * $ratio;
            }

            if (isset($nutrientInfo['porcentaje']) && is_numeric($nutrientInfo['porcentaje'])) {
                $nutrition['info'][$nutrientId]['porcentaje'] = $nutrientInfo['porcentaje'] * $ratio;
            }
        }

        return $nutrition;
    }
}
