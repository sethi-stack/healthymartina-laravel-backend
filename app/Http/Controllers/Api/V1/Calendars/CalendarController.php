<?php

namespace App\Http\Controllers\Api\V1\Calendars;

use App\Http\Controllers\Controller;
use App\Http\Resources\Calendar\CalendarResource;
use App\Models\Calendar;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class CalendarController extends Controller
{
    /**
     * Display a listing of user's calendars.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $calendars = Auth::user()->calendars()
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return CalendarResource::collection($calendars);
    }

    /**
     * Store a newly created calendar.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'semanas' => 'nullable|integer|min:1|max:52',
            'calendario' => 'nullable|json',
            'data_semanal' => 'nullable|json',
        ]);

        $calendar = Auth::user()->calendars()->create([
            'title' => $validated['title'],
            'semanas' => $validated['semanas'] ?? 1,
            'estado' => 'active',
            'calendario' => $validated['calendario'] ?? null,
            'data_semanal' => $validated['data_semanal'] ?? null,
            // Initialize with default schedule structure from config
            'main_schedule' => json_encode(config('constants.schedule')),
            'main_leftovers' => json_encode(config('constants.leftovers')),
            'main_servings' => json_encode(config('constants.main_servings')),
            'main_racion' => json_encode(config('constants.main_racion')),
            'sides_schedule' => json_encode(config('constants.schedule')),
            'sides_leftovers' => json_encode(config('constants.leftovers')),
            'sides_servings' => json_encode(config('constants.sides_servings')),
            'sides_racion' => json_encode(config('constants.sides_racion')),
            'labels' => json_encode(config('constants.labels')),
        ]);

        return response()->json([
            'calendar' => new CalendarResource($calendar),
            'message' => 'Calendar created successfully',
        ], 201);
    }

    /**
     * Display the specified calendar.
     */
    public function show(int $id): CalendarResource
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);
        return new CalendarResource($calendar);
    }

    /**
     * Update the specified calendar.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'semanas' => 'sometimes|integer|min:1|max:52',
            'estado' => 'sometimes|in:active,archived',
            'calendario' => 'sometimes|json',
            'data_semanal' => 'sometimes|json',
        ]);

        $calendar->update($validated);

        return response()->json([
            'calendar' => new CalendarResource($calendar),
            'message' => 'Calendar updated successfully',
        ]);
    }

    /**
     * Remove the specified calendar.
     */
    public function destroy(int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);
        $calendar->delete();

        return response()->json([
            'message' => 'Calendar deleted successfully',
        ]);
    }

    /**
     * Copy an existing calendar.
     */
    public function copy(Request $request, int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $newCalendar = $calendar->replicate();
        $newCalendar->title = $validated['title'];
        $newCalendar->save();

        return response()->json([
            'calendar' => new CalendarResource($newCalendar),
            'message' => 'Calendar copied successfully',
        ], 201);
    }

    /**
     * Get calendar schedules as JSON (replaces getCalendarScheduleJson method).
     */
    public function schedules(): JsonResponse
    {
        $calendars = Calendar::select('id', 'main_schedule', 'sides_schedule')
            ->where('user_id', Auth::id())
            ->orderBy('id', 'DESC')
            ->get();

        $data = [];
        foreach ($calendars as $calendar) {
            $data[$calendar->id] = $calendar;
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Update calendar labels (day names or meal names).
     */
    public function updateLabels(Request $request, int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        $validated = $request->validate([
            'label_type' => 'required|in:days,meals',
            'label_name' => 'required|string',
            'days' => 'nullable|string|max:15',
            'meals' => 'nullable|string|max:15',
        ]);

        // Get existing labels from calendar, or use defaults from config
        $defaultLabels = config('constants.labels', [
            'days' => [
                'day_1' => 'Lunes',
                'day_2' => 'Martes',
                'day_3' => 'Miércoles',
                'day_4' => 'Jueves',
                'day_5' => 'Viernes',
                'day_6' => 'Sábado',
                'day_7' => 'Domingo',
            ],
            'meals' => [
                'meal_1' => 'Desayuno',
                'meal_2' => 'Lunch',
                'meal_3' => 'Comida',
                'meal_4' => 'Snack',
                'meal_5' => 'Cena',
                'meal_6' => 'Otros',
            ],
        ]);

        // Parse existing labels or use defaults
        $existingLabels = $calendar->labels
            ? (is_string($calendar->labels) ? json_decode($calendar->labels, true) : $calendar->labels)
            : null;

        // Merge with defaults - existing labels take precedence
        $labels = [
            'days' => array_merge(
                $defaultLabels['days'] ?? [],
                $existingLabels['days'] ?? []
            ),
            'meals' => array_merge(
                $defaultLabels['meals'] ?? [],
                $existingLabels['meals'] ?? []
            ),
        ];

        // Update only the specific label that was changed
        if ($validated['label_type'] === 'days') {
            $labels['days'][$validated['label_name']] = $validated['days'] ?? '';
        } else {
            $labels['meals'][$validated['label_name']] = $validated['meals'] ?? '';
        }

        $calendar->labels = json_encode($labels);
        $calendar->save();

        return response()->json([
            'type' => 'success',
            'message' => 'Calendario actualizado',
            'labels' => $labels,
        ]);
    }

    /**
     * Get nutritional information for a specific day.
     */
    public function getNutritionInfo(Request $request, int $id, string $dayId): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        // Get user's nutritional preferences
        $nutritionalInfo = \DB::table('nutritional_preferences')
            ->where('user_id', Auth::id())
            ->first();

        $visibleInfo = [];
        $filterInfo = [];

        if ($nutritionalInfo) {
            $info = json_decode($nutritionalInfo->nutritional_info);
        } else {
            $info = json_decode(json_encode(config('constants.nutritients', [])), false);
        }

        if ($info) {
            foreach ($info as $value) {
                if (isset($value->mostrar) && $value->mostrar == 1) {
                    $filterInfo[] = $value->id;
                }
                $visibleInfo[] = $value->id;
            }
        }

        // Get nutrition data for the day using helper function
        $nutritionData = [];
        if (function_exists('getDayNutritionData')) {
            $nutritionData = getDayNutritionData($dayId, $calendar, $visibleInfo, $filterInfo);
        }

        return response()->json([
            'success' => true,
            'day_id' => $dayId,
            'nutrition' => $nutritionData,
        ]);
    }

    /**
     * Add recipe to calendar.
     */
    public function addRecipe(Request $request, int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        $validated = $request->validate([
            'recetaid' => 'required|integer|exists:recetas,id',
            'mealtype' => 'required|in:main,side',
            'mealnum' => 'required|string',
            'daynum' => 'required|array',
            'daynum.*' => 'required|string',
            'porciones' => 'required|integer|min:1',
            'leftover' => 'nullable|boolean',
        ]);

        $mealnum = $validated['mealnum'];
        $daynum = $validated['daynum'];
        $allDays = ['day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6', 'day_7'];
        $removeDays = array_diff($allDays, $daynum);

        if ($validated['mealtype'] === 'main') {
            $mainSchedule = json_decode($calendar->main_schedule, true) ?? [];
            $mainServings = json_decode($calendar->main_servings, true) ?? [];
            $mainLeftovers = json_decode($calendar->main_leftovers, true) ?? [];

            // Add recipe to selected days
            foreach ($daynum as $dayKey) {
                $mainSchedule[$dayKey][$mealnum] = intval($validated['recetaid']);
                $mainServings[$dayKey][$mealnum] = intval($validated['porciones']);
                $mainLeftovers[$dayKey][$mealnum] = $validated['leftover'] ?? false;
            }

            // Remove recipe from unselected days (if it was previously assigned)
            if ($removeDays) {
                $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
                $sidesServings = json_decode($calendar->sides_servings, true) ?? [];
                $sidesLeftovers = json_decode($calendar->sides_leftovers, true) ?? [];

                foreach ($removeDays as $dayKey) {
                    if (isset($mainSchedule[$dayKey][$mealnum]) && $mainSchedule[$dayKey][$mealnum] == intval($validated['recetaid'])) {
                        $mainSchedule[$dayKey][$mealnum] = null;
                        $mainServings[$dayKey][$mealnum] = 1;
                        $mainLeftovers[$dayKey][$mealnum] = null;
                        $sidesSchedule[$dayKey][$mealnum] = null;
                        $sidesServings[$dayKey][$mealnum] = 1;
                        $sidesLeftovers[$dayKey][$mealnum] = null;
                    }
                }

                $calendar->sides_schedule = json_encode($sidesSchedule);
                $calendar->sides_servings = json_encode($sidesServings);
                $calendar->sides_leftovers = json_encode($sidesLeftovers);
            }

            $calendar->main_schedule = json_encode($mainSchedule);
            $calendar->main_servings = json_encode($mainServings);
            $calendar->main_leftovers = json_encode($mainLeftovers);
        } else {
            // Side recipe
            $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
            $sidesServings = json_decode($calendar->sides_servings, true) ?? [];
            $sidesLeftovers = json_decode($calendar->sides_leftovers, true) ?? [];

            // Add recipe to selected days
            foreach ($daynum as $dayKey) {
                $sidesSchedule[$dayKey][$mealnum] = intval($validated['recetaid']);
                $sidesServings[$dayKey][$mealnum] = intval($validated['porciones']);
                $sidesLeftovers[$dayKey][$mealnum] = $validated['leftover'] ?? false;
            }

            // Remove recipe from unselected days
            foreach ($removeDays as $dayKey) {
                if (isset($sidesSchedule[$dayKey][$mealnum]) && $sidesSchedule[$dayKey][$mealnum] == intval($validated['recetaid'])) {
                    $sidesSchedule[$dayKey][$mealnum] = null;
                    $sidesServings[$dayKey][$mealnum] = 1;
                    $sidesLeftovers[$dayKey][$mealnum] = null;
                }
            }

            $calendar->sides_schedule = json_encode($sidesSchedule);
            $calendar->sides_servings = json_encode($sidesServings);
            $calendar->sides_leftovers = json_encode($sidesLeftovers);
        }

        $calendar->save();

        return response()->json([
            'success' => true,
            'message' => 'Recipe added to calendar successfully',
            'calendar' => new CalendarResource($calendar),
        ]);
    }

    /**
     * Update recipe in calendar.
     */
    public function updateRecipe(Request $request, int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        $validated = $request->validate([
            'recetaid' => 'required|integer|exists:recetas,id',
            'mealtype' => 'required|in:main,side',
            'mealnum' => 'required|string',
            'daynum' => 'required|array',
            'daynum.*' => 'required|string',
            'porciones' => 'required|integer|min:1',
            'leftover' => 'nullable|boolean',
            'old_mealnum' => 'nullable|string',
            'old_daynum' => 'nullable|string',
        ]);

        // For update, use the same logic as addRecipe
        return $this->addRecipe($request, $id);
    }

    /**
     * Remove recipe from calendar.
     */
    public function removeRecipe(Request $request, int $id): JsonResponse
    {
        $calendar = Auth::user()->calendars()->findOrFail($id);

        $validated = $request->validate([
            'mealtype' => 'required|in:main,side',
            'mealnum' => 'required|string',
            'daynum' => 'required|string',
        ]);

        $mealnum = $validated['mealnum'];
        $daynum = $validated['daynum'];

        if ($validated['mealtype'] === 'main') {
            $mainSchedule = json_decode($calendar->main_schedule, true) ?? [];
            $mainServings = json_decode($calendar->main_servings, true) ?? [];
            $mainLeftovers = json_decode($calendar->main_leftovers, true) ?? [];
            $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
            $sidesServings = json_decode($calendar->sides_servings, true) ?? [];
            $sidesLeftovers = json_decode($calendar->sides_leftovers, true) ?? [];

            $mainSchedule[$daynum][$mealnum] = null;
            $mainServings[$daynum][$mealnum] = 1;
            $mainLeftovers[$daynum][$mealnum] = null;
            $sidesSchedule[$daynum][$mealnum] = null;
            $sidesServings[$daynum][$mealnum] = 1;
            $sidesLeftovers[$daynum][$mealnum] = null;

            $calendar->main_schedule = json_encode($mainSchedule);
            $calendar->main_servings = json_encode($mainServings);
            $calendar->main_leftovers = json_encode($mainLeftovers);
            $calendar->sides_schedule = json_encode($sidesSchedule);
            $calendar->sides_servings = json_encode($sidesServings);
            $calendar->sides_leftovers = json_encode($sidesLeftovers);
        } else {
            $sidesSchedule = json_decode($calendar->sides_schedule, true) ?? [];
            $sidesServings = json_decode($calendar->sides_servings, true) ?? [];
            $sidesLeftovers = json_decode($calendar->sides_leftovers, true) ?? [];

            $sidesSchedule[$daynum][$mealnum] = null;
            $sidesServings[$daynum][$mealnum] = 1;
            $sidesLeftovers[$daynum][$mealnum] = null;

            $calendar->sides_schedule = json_encode($sidesSchedule);
            $calendar->sides_servings = json_encode($sidesServings);
            $calendar->sides_leftovers = json_encode($sidesLeftovers);
        }

        $calendar->save();

        return response()->json([
            'success' => true,
            'message' => 'Recipe removed from calendar successfully',
            'calendar' => new CalendarResource($calendar),
        ]);
    }
}

