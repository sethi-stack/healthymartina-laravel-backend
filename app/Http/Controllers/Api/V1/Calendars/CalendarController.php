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
            'nombre' => 'required|string|max:255',
            'semanas' => 'nullable|integer|min:1|max:52',
            'calendario' => 'nullable|json',
            'data_semanal' => 'nullable|json',
        ]);

        $calendar = Auth::user()->calendars()->create([
            'nombre' => $validated['nombre'],
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
            'nombre' => 'sometimes|string|max:255',
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
            'nombre' => 'required|string|max:255',
        ]);

        $newCalendar = $calendar->replicate();
        $newCalendar->nombre = $validated['nombre'];
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
}

