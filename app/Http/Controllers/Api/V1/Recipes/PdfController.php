<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    private function buildRecipePdf(Receta $recipe, $nutritionalsInfo, $user)
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

        // Legacy parity: use Advanced/Bold recipe template by default.
        $viewData = [
            'recipe' => $recipe,
            'nutritionals_info' => $nutritionalsInfo,
            'export_param' => [3, 4], // include tips + nutrition blocks
            'recipe_ingredients_data' => [],
            'recipe_image_src' => $recipeImageSrc,
        ];

        return PDF::loadView('pdf.bold.calendar-bold-recipe', $viewData)->setPaper('a4', 'portrait');
    }

    /**
     * Generate and download recipe PDF.
     */
    public function download(int $recipeId)
    {
        $recipe = Receta::findOrFail($recipeId);
        $user = Auth::user();
        
        $nutritionals = DB::table('nutritional_preferences')
            ->where('user_id', $user->id)
            ->first();

        $nutritionals_info = $nutritionals
            ? json_decode($nutritionals->nutritional_info)
            : config()->get('constants.nutritients');

        $pdf = $this->buildRecipePdf($recipe, $nutritionals_info, $user);

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
        ]);

        $user = Auth::user();
        $recipe = Receta::findOrFail($recipeId);
        
        $recipient_email = $validated['recipient_email_address'] ?? $user->email;

        if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico del destinatario no es válido',
            ], 422);
        }

        $nutritionals = DB::table('nutritional_preferences')
            ->where('user_id', $user->id)
            ->first();

        $nutritionals_info = $nutritionals
            ? json_decode($nutritionals->nutritional_info)
            : config()->get('constants.nutritients');

        $pdf = $this->buildRecipePdf($recipe, $nutritionals_info, $user);

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
