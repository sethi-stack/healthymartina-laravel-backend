<?php

namespace App\Http\Controllers\Api\V1\Plans;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MealPlanPdfController extends Controller
{
    /**
     * Download meal plan as PDF.
     */
    public function download(int $id)
    {
        $user = Auth::user();

        $plan = Plan::where('id', $id)
            ->whereIn('tipo_id', [4, $user->role_id])
            ->firstOrFail();

        $calendar = $plan->plan_receta;
        
        if (!$calendar) {
            return response()->json([
                'success' => false,
                'message' => 'Plan does not have an associated calendar',
            ], 400);
        }

        // Temporarily set title for PDF
        $calendar->title = $plan->nombre;

        $pdf = PDF::loadView('pdf.calendario-pdf', ['calendar' => $calendar])
            ->setPaper('a4', 'landscape');

        return $pdf->download($plan->nombre . '.pdf');
    }
}


