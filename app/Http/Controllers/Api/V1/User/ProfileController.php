<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get user profile.
     */
    public function show(): UserResource
    {
        return new UserResource(Auth::user()->load(['preference']));
    }

    /**
     * Update user profile (personal + business info).
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            // Personal info
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            // Business info
            'bname' => 'sometimes|nullable|string|max:255',
            'profession' => 'sometimes|nullable|string|max:255',
            'bemail' => 'sometimes|nullable|email|max:255',
            'website' => 'sometimes|nullable|url|max:255',
            'color' => 'sometimes|nullable|string|max:20',
        ]);

        $user->update($validated);

        return response()->json([
            'user' => new UserResource($user->fresh()->load(['preference'])),
            'message' => 'Profile updated successfully',
        ]);
    }

    /**
     * Update password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect',
                'errors' => [
                    'current_password' => ['The current password is incorrect.']
                ]
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Upload profile photo.
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = Auth::user();

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->getOriginal('image')) {
                Storage::disk('gcs')->delete($user->getOriginal('image'));
            }
            $path = $request->file('photo')->store('users/profile_pictures', 'gcs');
            $user->update(['image' => $path]);
        }

        return response()->json([
            'user' => new UserResource($user->fresh()->load(['preference'])),
            'message' => 'Photo uploaded successfully',
        ]);
    }

    /**
     * Delete profile photo.
     */
    public function deletePhoto(): JsonResponse
    {
        $user = Auth::user();

        if ($user->getOriginal('image')) {
            Storage::disk('gcs')->delete($user->getOriginal('image'));
        }

        $user->update(['image' => null]);

        return response()->json([
            'user' => new UserResource($user->fresh()->load(['preference'])),
            'message' => 'Photo deleted successfully',
        ]);
    }

    /**
     * Upload business logo.
     */
    public function uploadBusinessPhoto(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = Auth::user();

        if ($request->hasFile('photo')) {
            // Delete old logo if exists
            if ($user->getOriginal('bimage')) {
                Storage::disk('gcs')->delete($user->getOriginal('bimage'));
            }
            $path = $request->file('photo')->store('users/business_logos', 'gcs');
            $user->update(['bimage' => $path]);
        }

        return response()->json([
            'user' => new UserResource($user->fresh()->load(['preference'])),
            'message' => 'Business photo uploaded successfully',
        ]);
    }

    /**
     * Delete business logo.
     */
    public function deleteBusinessPhoto(): JsonResponse
    {
        $user = Auth::user();

        if ($user->getOriginal('bimage')) {
            Storage::disk('gcs')->delete($user->getOriginal('bimage'));
        }

        $user->update(['bimage' => null]);

        return response()->json([
            'user' => new UserResource($user->fresh()->load(['preference'])),
            'message' => 'Business photo deleted successfully',
        ]);
    }

    /**
     * Delete account.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'Password is incorrect',
                'errors' => [
                    'password' => ['The password is incorrect.']
                ]
            ], 422);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete user
        $user->delete();

        return response()->json([
            'message' => 'Account deleted successfully',
        ]);
    }
}
