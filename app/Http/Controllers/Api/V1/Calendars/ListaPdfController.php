<?php

namespace App\Http\Controllers\Api\V1\Calendars;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Categoria;
use App\Models\ListaIngredientes;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ListaPdfController extends Controller
{
    /**
     * Generate and download lista PDF.
     */
    public function download(Request $request, int $calendarId)
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $user = Auth::user();

        // Get ingredients data
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        $categorias = Categoria::orderBy('sort', 'ASC')->get();
        $listaIngredientes = ListaIngredientes::where('calendario_id', $calendar->id)->get();

        // Get recipe ingredients from request (JSON)
        $recipeIngredients = $request->has('lista_ingredients')
            ? json_decode($request->lista_ingredients)
            : (object)[];

        // Calculate ingredients count
        $ingredientsCount = (count((array) $recipeIngredients) + $listaIngredientes->count()) - count($takenIngredients);

        // Professional users (role_id == 3) get themed PDFs
        if ($user->role_id == 3) {
            $theme = $user->theme ?? 3;

            $viewMap = [
                1 => 'pdf.classic.classic-lista',
                2 => 'pdf.modern.modern-lista',
                3 => 'pdf.bold.bold-lista',
            ];

            $view = $viewMap[$theme] ?? 'pdf.bold.bold-lista';

            $pdf = PDF::loadView($view, [
                'ingredients_count' => $ingredientsCount,
                'recipe_ingredients' => $recipeIngredients,
                'calendario' => $calendar,
                'categorias' => $categorias,
                'taken_ingredientes' => $takenIngredients,
                'lista_ingredientes' => $listaIngredientes,
            ]);
        } else {
            // Free users get standard PDF
            $pdf = PDF::loadView('pdf.lista-pdf', [
                'ingredients_count' => $ingredientsCount,
                'recipe_ingredients' => $recipeIngredients,
                'calendario' => $calendar,
                'categorias' => $categorias,
                'taken_ingredientes' => $takenIngredients,
                'lista_ingredientes' => $listaIngredientes,
            ]);
        }

        return $pdf->download($calendar->title . '.pdf');
    }

    /**
     * Email lista PDF.
     */
    public function email(Request $request, int $calendarId)
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $user = Auth::user();

        $validated = $request->validate([
            'recipient_email' => 'nullable|email',
            'lista_ingredients' => 'required|json',
            'plantillas' => 'nullable|string',
        ]);

        // Default to user's email if not provided
        $recipientEmail = $validated['recipient_email'] ?? $user->email;

        // Validate email format
        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico del destinatario no es válido',
            ], 422);
        }

        // Get ingredients data
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        $categorias = Categoria::orderBy('sort', 'ASC')->get();
        $listaIngredientes = ListaIngredientes::where('calendario_id', $calendar->id)->get();

        // Get recipe ingredients from request
        $recipeIngredients = json_decode($validated['lista_ingredients']);

        // Calculate ingredients count
        $ingredientsCount = (count((array) $recipeIngredients) + $listaIngredientes->count()) - count($takenIngredients);

        // Generate PDF based on user role
        if ($user->role_id == 3) {
            $theme = $user->theme ?? 3;

            $viewMap = [
                1 => 'pdf.classic.classic-lista',
                2 => 'pdf.modern.modern-lista',
                3 => 'pdf.bold.bold-lista',
            ];

            $view = $viewMap[$theme] ?? 'pdf.bold.bold-lista';

            $pdf = PDF::loadView($view, [
                'ingredients_count' => $ingredientsCount,
                'recipe_ingredients' => $recipeIngredients,
                'calendario' => $calendar,
                'categorias' => $categorias,
                'taken_ingredientes' => $takenIngredients,
                'lista_ingredientes' => $listaIngredientes,
            ]);
        } else {
            $pdf = PDF::loadView('pdf.lista-pdf', [
                'ingredients_count' => $ingredientsCount,
                'recipe_ingredients' => $recipeIngredients,
                'calendario' => $calendar,
                'categorias' => $categorias,
                'taken_ingredientes' => $takenIngredients,
                'lista_ingredientes' => $listaIngredientes,
            ]);
        }

        // Prepare email data
        $emailData = [
            'email' => $recipientEmail,
            'title' => '¡Tu lista está lista!',
            'filename' => $calendar->title,
            'current_time' => todaySpanishDay(),
            'plantillas' => !empty($validated['plantillas']) 
                ? utf8_decode(urldecode($validated['plantillas'])) 
                : '',
        ];

        // Send email to recipient
        try {
            Mail::send('email.send-lista-mail', $emailData, function ($message) use ($emailData, $pdf) {
                $message->to($emailData['email'], $emailData['email'])
                    ->subject($emailData['title'])
                    ->attachData($pdf->output(), $emailData['filename'] . '.pdf');
            });

            // Send delivery confirmation to user's business email
            if ($user->bemail) {
                Mail::send('email.delivery-email', [
                    'type' => 'Lista',
                    'meal_type' => 'Tu lista',
                    'to' => $emailData['email'],
                    'title' => $emailData['title'],
                    'current_time' => todaySpanishDay(),
                ], function ($message) use ($user, $emailData, $pdf) {
                    $message->to($user->bemail, $user->bemail)
                        ->subject('Tu lista de compras fue entregada')
                        ->attachData($pdf->output(), $emailData['filename'] . '.pdf');
                });
            }

            return response()->json([
                'success' => true,
                'message' => 'Se envió por mail exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Email lista as HTML (without PDF attachment).
     */
    public function emailHtml(Request $request, int $calendarId)
    {
        $calendar = Calendar::where('id', $calendarId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $user = Auth::user();

        $validated = $request->validate([
            'lista_ingredients' => 'required|json',
        ]);

        // Get ingredients data
        $takenIngredients = DB::table('lista_ingrediente_taken')
            ->where('calendario_id', $calendar->id)
            ->get();

        $categorias = Categoria::orderBy('sort', 'ASC')->get();
        $listaIngredientes = ListaIngredientes::where('calendario_id', $calendar->id)->get();

        // Get recipe ingredients from request
        $recipeIngredients = json_decode($validated['lista_ingredients']);

        $emailData = [
            'subject' => '¡Tu lista de "' . $calendar->title . '" está lista!',
            'email' => $user->email,
            'categorias' => $categorias,
            'calendario' => $calendar,
            'recipe_ingredients' => $recipeIngredients,
            'lista_ingredientes' => $listaIngredientes,
            'taken_ingredientes' => $takenIngredients,
        ];

        try {
            Mail::send('email.lista-email', $emailData, function ($message) use ($emailData) {
                $message->to($emailData['email'])
                    ->subject($emailData['subject']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Lista enviada por correo electrónico exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage(),
            ], 500);
        }
    }
}

