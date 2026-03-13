<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PreferencesController extends Controller
{
    /**
     * Get user preferences.
     */
    public function show(): JsonResponse
    {
        $user = Auth::user()->load('preference');
        $pref = $user->preference;

        return response()->json([
            'unit_measure' => $user->unit_measure,
            'theme' => $user->theme,
            'weekly_reminders' => $pref ? (bool) $pref->weekly_reminders : false,
            'new_updates' => $pref ? (bool) $pref->new_updates : false,
            'mentions' => $pref ? (bool) $pref->mentions : false,
        ]);
    }

    /**
     * Update user preferences.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_measure' => 'sometimes|nullable|in:metric,us',
            'theme' => 'sometimes|nullable|in:light,dark',
            'weekly_reminders' => 'sometimes|boolean',
            'new_updates' => 'sometimes|boolean',
            'mentions' => 'sometimes|boolean',
        ]);

        $user = Auth::user();

        // Update user fields
        $userFields = array_intersect_key($validated, array_flip(['unit_measure', 'theme']));
        if (!empty($userFields)) {
            $user->update($userFields);
        }

        // Upsert notification preferences
        $prefFields = array_intersect_key($validated, array_flip(['weekly_reminders', 'new_updates', 'mentions']));
        if (!empty($prefFields)) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id],
                $prefFields
            );
        }

        $user->refresh()->load('preference');
        $pref = $user->preference;

        return response()->json([
            'unit_measure' => $user->unit_measure,
            'theme' => $user->theme,
            'weekly_reminders' => $pref ? (bool) $pref->weekly_reminders : false,
            'new_updates' => $pref ? (bool) $pref->new_updates : false,
            'mentions' => $pref ? (bool) $pref->mentions : false,
            'message' => 'Preferences updated successfully',
        ]);
    }
}
