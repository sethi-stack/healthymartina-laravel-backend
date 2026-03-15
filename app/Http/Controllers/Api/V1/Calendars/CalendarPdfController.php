<?php

namespace App\Http\Controllers\Api\V1\Calendars;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Categoria;
use App\Models\ListaIngredientes;
use App\Models\Receta;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CalendarPdfController extends Controller
{
    /**
     * Generate and download calendar PDF.
     *
     * export_param values:
     *   1 = Calendar schedule grid
     *   2 = Shopping list (lista)
     *   4 = Nutrition info (calories per day, using cached recipe data)
     */
    public function download(Request $request)
    {
        $validated = $request->validate([
            'calendar'       => 'required|integer',
            'export_param'   => 'required|array|min:1',
            'export_param.*' => 'integer|in:1,2,4',
        ]);

        $calendar     = Auth::user()->calendars()->findOrFail($validated['calendar']);
        $exportParams = $validated['export_param'];
        $user         = Auth::user();

        // Parse schedule JSON (decoded once, reused everywhere)
        $mainSchedule  = json_decode($calendar->main_schedule, true) ?? [];
        $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
        $mainServings  = json_decode($calendar->main_servings, true) ?? [];
        $mainRacion    = json_decode($calendar->main_racion, true) ?? [];
        $sidesRacion   = json_decode($calendar->sides_racion, true) ?? [];
        $labels        = json_decode($calendar->labels, true) ?? [];

        $dayLabels  = $labels['days']  ?? ['day_1' => 'Lunes', 'day_2' => 'Martes', 'day_3' => 'Miércoles', 'day_4' => 'Jueves', 'day_5' => 'Viernes', 'day_6' => 'Sábado', 'day_7' => 'Domingo'];
        $mealLabels = $labels['meals'] ?? ['meal_1' => 'Desayuno', 'meal_2' => 'Lunch', 'meal_3' => 'Comida', 'meal_4' => 'Snack', 'meal_5' => 'Cena', 'meal_6' => 'Otros'];

        // ── Collect ALL unique recipe IDs in ONE pass (no N+1) ──────────────
        $allRecipeIds = [];
        foreach ($mainSchedule as $meals) {
            foreach ($meals as $id) {
                if ($id) $allRecipeIds[$id] = true;
            }
        }
        foreach ($sidesSchedule as $meals) {
            foreach ($meals as $id) {
                if ($id) $allRecipeIds[$id] = true;
            }
        }
        $allRecipeIds = array_keys($allRecipeIds);

        // ── Single DB query for all recipes ─────────────────────────────────
        $recipes = Receta::whereIn('id', $allRecipeIds)
            ->select(['id', 'titulo', 'imagen_principal', 'nutrient_info'])
            ->selectSub(
                DB::table('receta_resultado')
                    ->select('cantidad')
                    ->whereColumn('receta_id', 'recetas.id')
                    ->where('active', 1)
                    ->limit(1),
                'porciones'
            )
            ->get()
            ->keyBy('id');

        // ── Build structured day/meal grid ──────────────────────────────────
        $days = [];
        foreach ($mainSchedule as $dayKey => $meals) {
            $hasAnyRecipe = false;
            $mealRows = [];
            foreach ($meals as $mealKey => $mainId) {
                $sideId = $sidesSchedule[$dayKey][$mealKey] ?? null;
                if (!$mainId && !$sideId) continue;
                $hasAnyRecipe = true;
                $racion = $mainRacion[$dayKey][$mealKey] ?? 1;
                $sRacion = $sidesRacion[$dayKey][$mealKey] ?? 1;
                $mealRows[$mealKey] = [
                    'label'   => $mealLabels[$mealKey] ?? $mealKey,
                    'main'    => $mainId ? ($recipes[$mainId] ?? null) : null,
                    'side'    => $sideId ? ($recipes[$sideId] ?? null) : null,
                    'racion'  => $racion,
                    'sRacion' => $sRacion,
                ];
            }
            if ($hasAnyRecipe) {
                $days[$dayKey] = [
                    'label' => $dayLabels[$dayKey] ?? $dayKey,
                    'meals' => $mealRows,
                ];
            }
        }

        // ── Nutrition: calories per day (uses cached $recipe->calories) ──────
        $nutritionByDay = [];
        if (in_array(4, $exportParams)) {
            foreach ($mainSchedule as $dayKey => $meals) {
                $totalCal = 0;
                foreach ($meals as $mealKey => $recipeId) {
                    if ($recipeId && isset($recipes[$recipeId])) {
                        $racion     = $mainRacion[$dayKey][$mealKey] ?? 1;
                        $servings   = $mainServings[$dayKey][$mealKey] ?? 1;
                        $recipeCal  = (int) ($recipes[$recipeId]->calories ?? 0);
                        $recipePort = max(1, (int) ($recipes[$recipeId]->porciones ?? 1));
                        // cal per serving × servings used
                        $totalCal += ($recipeCal / $recipePort) * $racion;
                    }
                    // Also add side recipe calories
                    $sideId = $sidesSchedule[$dayKey][$mealKey] ?? null;
                    if ($sideId && isset($recipes[$sideId])) {
                        $sRacion    = $sidesRacion[$dayKey][$mealKey] ?? 1;
                        $recipeCal  = (int) ($recipes[$sideId]->calories ?? 0);
                        $recipePort = max(1, (int) ($recipes[$sideId]->porciones ?? 1));
                        $totalCal += ($recipeCal / $recipePort) * $sRacion;
                    }
                }
                if (isset($days[$dayKey])) {
                    $nutritionByDay[$dayKey] = [
                        'label'    => $dayLabels[$dayKey] ?? $dayKey,
                        'calories' => round($totalCal),
                    ];
                }
            }
        }

        // ── Lista data ───────────────────────────────────────────────────────
        $listaData = null;
        if (in_array(2, $exportParams)) {
            $listaData = $this->buildListaData($calendar);
        }

        $pdf = PDF::loadView('pdf.calendar.basic', [
            'calendar'       => $calendar,
            'user'           => $user,
            'days'           => $days,
            'recipes'        => $recipes,
            'exportParams'   => $exportParams,
            'nutritionByDay' => $nutritionByDay,
            'listaData'      => $listaData,
        ])->setPaper('a4', 'portrait');

        return $pdf->download(($calendar->title ?? 'calendario') . '.pdf');
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
                    'name'  => $category->nombre,
                    'items' => $items,
                ];
            }
        }

        if ($manualItems->count()) {
            $byCategory[] = [
                'name'  => 'Otros',
                'items' => $manualItems->map(fn($i) => [
                    'nombre'   => $i->nombre,
                    'cantidad' => $i->cantidad,
                    'unidad'   => $i->unidad_medida,
                    'taken'    => false,
                ])->toArray(),
            ];
        }

        return [
            'categories' => $byCategory,
            'taken_ids'  => $takenIds,
        ];
    }
}
