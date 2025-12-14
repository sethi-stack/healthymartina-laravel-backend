<?php

namespace App\Http\Controllers\Api\V1\Plans;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MealPlanController extends Controller
{
    /**
     * List available meal plans.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Free users only see plan ID 20
        if ($user->hasRole('free')) {
            $plans = Plan::where('id', 20)->get();
        } else {
            // Other users see plans matching their role
            $plans = Plan::whereNull('deleted_at')
                ->whereIn('tipo_id', [4, $user->role_id])
                ->get();
        }

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Get meal plan details with calendar.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $plan = Plan::where('id', $id)
            ->whereIn('tipo_id', [4, $user->role_id])
            ->firstOrFail();

        $calendar = $plan->plan_receta;

        return response()->json([
            'plan' => $plan,
            'calendar' => $calendar,
        ]);
    }

    /**
     * Copy meal plan to user's calendars with scaling.
     */
    public function copy(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'calendar_title' => 'required|string|max:255',
            'calendar_scale' => 'required|numeric|min:0.1|max:10',
        ]);

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

        // Calculate servings with scaling
        $servingsCalculated = $this->manipulateServings($calendar);
        $mainRecipes = (array) json_decode($calendar->main_schedule);
        $sideRecipes = (array) json_decode($calendar->sides_schedule);

        // Calculate main servings
        $mServings = [];
        foreach (json_decode($calendar->main_servings) as $daykey => $value) {
            foreach ($value as $mealkey => $value1) {
                $dayMeals = (array) $mainRecipes[$daykey];
                $sideAdd = 0;
                
                if (isset($servingsCalculated[1][$dayMeals[$mealkey]])) {
                    $sideAdd = $servingsCalculated[1][$dayMeals[$mealkey]];
                }
                
                if ($dayMeals[$mealkey]) {
                    $mServings[$daykey][$mealkey] = ($servingsCalculated[0][$dayMeals[$mealkey]] + $sideAdd) * $validated['calendar_scale'] * $value1;
                } else {
                    $mServings[$daykey][$mealkey] = 0;
                }
            }
        }

        // Calculate side servings
        $sServings = [];
        foreach (json_decode($calendar->sides_servings) as $daykey => $value) {
            foreach ($value as $mealkey => $value1) {
                $dayMeals = (array) $sideRecipes[$daykey];
                $mainAdd = 0;
                
                if (isset($servingsCalculated[0][$dayMeals[$mealkey]])) {
                    $mainAdd = $servingsCalculated[0][$dayMeals[$mealkey]];
                }
                
                if ($dayMeals[$mealkey]) {
                    $sServings[$daykey][$mealkey] = ($servingsCalculated[1][$dayMeals[$mealkey]] + $mainAdd) * $validated['calendar_scale'] * $value1;
                } else {
                    $sServings[$daykey][$mealkey] = 0;
                }
            }
        }

        // Create new calendar
        $newCalendar = Calendar::create([
            'user_id' => $user->id,
            'title' => $validated['calendar_title'],
            'main_schedule' => $calendar->main_schedule,
            'main_leftovers' => $calendar->main_leftovers,
            'main_servings' => json_encode($mServings),
            'main_racion' => json_encode(config('constants.main_racion')),
            'sides_schedule' => $calendar->sides_schedule,
            'sides_leftovers' => $calendar->sides_leftovers,
            'sides_servings' => json_encode($sServings),
            'sides_racion' => json_encode(config('constants.sides_racion')),
            'labels' => $calendar->labels,
        ]);

        return response()->json([
            'success' => true,
            'calendar' => $newCalendar,
            'message' => 'Meal plan copied to your calendars successfully',
        ], 201);
    }

    /**
     * Calculate servings for main and side dishes.
     * Handles leftovers and repeated meals.
     */
    private function manipulateServings($calendar): array
    {
        $mainMeals = (array) json_decode($calendar->main_schedule);
        $sideMeals = (array) json_decode($calendar->sides_schedule);
        $mainLeftovers = (array) json_decode($calendar->main_leftovers);
        $sideLeftovers = (array) json_decode($calendar->sides_leftovers);

        // Merge meals with leftovers
        $mergedMains = array_merge_recursive($mainMeals, $mainLeftovers);
        
        $mealsOnDaysMapping = [];
        foreach ($mergedMains as $day => $meals) {
            foreach ($meals as $meal => $obj) {
                $mealsOnDaysMapping[$obj[0]][$day] = $obj[1] ? 'Leftover' : 'No';
            }
        }

        $daysOfWeek = ['day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6', 'day_7'];
        $mMealsOnDays = [];
        
        foreach ($mealsOnDaysMapping as $recipeId => $days) {
            foreach ($daysOfWeek as $day) {
                $mMealsOnDays[$recipeId][] = isset($mealsOnDaysMapping[$recipeId][$day]) ? $mealsOnDaysMapping[$recipeId][$day] : -1;
            }
        }

        // Calculate servings for mains
        $mServings = [];
        foreach ($mMealsOnDays as $recipeId => $leftovers) {
            $counts = array_count_values($leftovers);
            if ($recipeId) {
                if (isset($counts['Leftover']) && $counts['Leftover'] > 0) {
                    $mServings[$recipeId] = (isset($counts['No']) ? $counts['No'] : 0) + (isset($counts['Leftover']) ? $counts['Leftover'] : 0);
                } else {
                    $mServings[$recipeId] = 1;
                }
            }
        }

        // Calculate servings for sides
        $mergedSides = array_merge_recursive($sideMeals, $sideLeftovers);
        $mealsOnDaysMapping = [];
        
        foreach ($mergedSides as $day => $meals) {
            foreach ($meals as $meal => $obj) {
                $mealsOnDaysMapping[$obj[0]][$day] = $obj[1] ? 'Leftover' : 'No';
            }
        }

        $sMealsOnDays = [];
        foreach ($mealsOnDaysMapping as $recipeId => $days) {
            foreach ($daysOfWeek as $day) {
                $sMealsOnDays[$recipeId][] = isset($mealsOnDaysMapping[$recipeId][$day]) ? $mealsOnDaysMapping[$recipeId][$day] : -1;
            }
        }

        $sServings = [];
        foreach ($sMealsOnDays as $recipeId => $leftovers) {
            $counts = array_count_values($leftovers);
            if ($recipeId) {
                if (isset($counts['Leftover']) && $counts['Leftover'] > 0) {
                    $sServings[$recipeId] = (isset($counts['No']) ? $counts['No'] : 0) + (isset($counts['Leftover']) ? $counts['Leftover'] : 0);
                } else {
                    $sServings[$recipeId] = 1;
                }
            }
        }

        return [$mServings, $sServings];
    }
}


