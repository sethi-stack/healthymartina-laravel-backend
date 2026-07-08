<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use App\Support\NutritionPreferenceSupport;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    private function getUserNutritionalsInfo($user): array
    {
        $nutritionals = DB::table('nutritional_preferences')
            ->where('user_id', $user->id)
            ->first();

        $storedNutritionInfo = !empty($nutritionals->nutritional_info)
            ? json_decode($nutritionals->nutritional_info, true)
            : null;

        return array_map(function ($item) {
            return (object) $item;
        }, array_values(NutritionPreferenceSupport::normalizeNutritionInfo($storedNutritionInfo)));
    }

    private function formatRecipeIngredient(array $ingredient): string
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

    private function buildRecipePdf(Receta $recipe, $nutritionalsInfo, $user, float|int $portion)
    {
        $recipeImageSrc = $recipe->imagen_principal;
        $rawImagePath = $recipe->getRawOriginal('imagen_principal');
        if (!empty($rawImagePath)) {
            try {
                $disk = config('filesystems.default', 'local');
                $binary = Storage::disk($disk)->get($rawImagePath);
                $extension = strtolower(pathinfo($rawImagePath, PATHINFO_EXTENSION));
                $mime = match ($extension) {
                    'png' => 'image/png',
                    'gif' => 'image/gif',
                    'webp' => 'image/webp',
                    default => 'image/jpeg',
                };
                $recipeImageSrc = 'data:' . $mime . ';base64,' . base64_encode($binary);
            } catch (\Throwable $e) {
                // Fallback to URL accessor when direct read is unavailable.
                $recipeImageSrc = $recipe->imagen_principal;
            }
        }

        $scaledIngredients = $this->scaleRecipeIngredients($recipe, $portion);
        $scaledNutrition = $this->scaleRecipeNutrition($recipe, $portion);
        $recipeIngredientsData = [];
        foreach ($scaledIngredients as $ingredient) {
            $uid = $ingredient['ingred_uid'] ?? null;
            if (!$uid) {
                continue;
            }
            $recipeIngredientsData[$recipe->id][$uid] = $this->formatRecipeIngredient($ingredient);
        }

        // Legacy parity: use Advanced/Bold recipe template by default.
        $viewData = [
            'recipe' => $recipe,
            'nutritionals_info' => $nutritionalsInfo,
            'export_param' => [3, 4], // include tips + nutrition blocks
            'porcion' => $portion,
            'portion' => $portion,
            'recipe_ingredients_data' => $recipeIngredientsData,
            'recipe_nutrition_data' => [$recipe->id => $scaledNutrition],
            'recipe_image_src' => $recipeImageSrc,
        ];

        return PDF::loadView('pdf.bold.calendar-bold-recipe', $viewData)->setPaper('a4', 'portrait');
    }

    /**
     * Generate and download recipe PDF.
     */
    public function download(Request $request, int $recipeId)
    {
        $validated = $request->validate([
            'portion' => 'nullable|numeric|min:1',
        ]);
        $recipe = Receta::findOrFail($recipeId);
        $user = Auth::user();
        $nutritionals_info = $this->getUserNutritionalsInfo($user);
        $portion = (float) ($validated['portion'] ?? $recipe->getPorciones()['cantidad'] ?? 1);

        $pdf = $this->buildRecipePdf($recipe, $nutritionals_info, $user, $portion);

        return $pdf->download($recipe->titulo . '.pdf');
    }

    /**
     * Generate and email recipe PDF.
     */
    public function email(Request $request, int $recipeId)
    {
        $validated = $request->validate([
            'recipient_email_address' => 'nullable|email',
            'plantillas' => 'nullable|string',
            'portion' => 'nullable|numeric|min:1',
        ]);

        $user = Auth::user();
        $recipe = Receta::findOrFail($recipeId);
        $portion = (float) ($validated['portion'] ?? $recipe->getPorciones()['cantidad'] ?? 1);
        
        $recipient_email = $validated['recipient_email_address'] ?? $user->email;

        if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico del destinatario no es válido',
            ], 422);
        }

        $nutritionals_info = $this->getUserNutritionalsInfo($user);

        $pdf = $this->buildRecipePdf($recipe, $nutritionals_info, $user, $portion);

        // Prepare email data
        $data = [
            'email' => $recipient_email,
            'title' => '¡Tu receta esta lista!',
            'recipe' => $recipe,
            'nutritionals_info' => $nutritionals_info,
            'current_time' => todaySpanishDay(),
            'plantillas' => isset($validated['plantillas']) 
                ? utf8_decode(urldecode($validated['plantillas'])) 
                : '',
        ];

        try {
            // Send recipe to recipient
            Mail::send('email.send-recipe', $data, function ($message) use ($data, $pdf) {
                $message->to($data['email'], $data['email'])
                    ->subject($data['title'])
                    ->attachData($pdf->output(), $data['recipe']->titulo . ".pdf");
            });

            // Send delivery confirmation to user
            Mail::send('email.delivery-email', [
                'type' => 'Receta',
                'meal_type' => 'Receta',
                'to' => $data['email'],
                'title' => $data['title'],
                'current_time' => todaySpanishDay()
            ], function ($message) use ($data, $pdf, $user) {
                $message->to($user->bemail, $user->bemail)
                    ->subject('Tu receta fue entregada.')
                    ->attachData($pdf->output(), $data['recipe']->titulo . ".pdf");
            });

            return response()->json([
                'success' => true,
                'message' => 'Se envio por mail exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage(),
            ], 500);
        }
    }
}
