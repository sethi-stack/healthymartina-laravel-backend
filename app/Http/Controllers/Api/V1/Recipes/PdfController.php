<?php

namespace App\Http\Controllers\Api\V1\Recipes;

use App\Http\Controllers\Controller;
use App\Models\Receta;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class PdfController extends Controller
{
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

        // Professional users get themed PDFs
        if ($user->role_id == 3) {
            $theme = $user->theme ?? 3;
            
            $viewMap = [
                1 => 'pdf.classic.classic-recipe',
                2 => 'pdf.modern.modern-recipe',
                3 => 'pdf.bold.bold-recipe',
            ];

            $view = $viewMap[$theme] ?? 'pdf.bold.bold-recipe';
            $pdf = PDF::loadView($view, [
                'recipe' => $recipe,
                'nutritionals_info' => $nutritionals_info
            ])->setPaper('a4', 'portrait');
        } else {
            // Free/basic users get standard PDF
            $pdf = PDF::loadView('pdf.recipe', ['recipe' => $recipe]);
        }

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

        // Generate PDF based on user theme
        if ($user->role_id == 3) {
            $theme = $user->theme ?? 3;
            
            $viewMap = [
                1 => 'pdf.classic.classic-recipe',
                2 => 'pdf.modern.modern-recipe',
                3 => 'pdf.bold.bold-recipe',
            ];

            $view = $viewMap[$theme] ?? 'pdf.bold.bold-recipe';
            $pdf = PDF::loadView($view, [
                'recipe' => $recipe,
                'nutritionals_info' => $nutritionals_info
            ])->setPaper('a4', 'portrait');
        } else {
            $pdf = PDF::loadView('pdf.recipe', ['recipe' => $recipe]);
        }

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

