<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Get user profile.
     */
    public function show(): UserResource
    {
        return new UserResource(Auth::user()->load(['preference', 'membresia']));
    }

    /**
     * Update user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'telefono' => 'sometimes|nullable|string|max:20',
            'fecha_nacimiento' => 'sometimes|nullable|date',
        ]);

        $user->update($validated);

        return response()->json([
            'user' => new UserResource($user->fresh()),
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
        $validated = $request->validate([
            'photo' => 'required|image|max:2048', // 2MB max
        ]);

        $user = Auth::user();
        
        // Handle photo upload (implementation depends on storage setup)
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('profiles', 'gcs');
            $user->update(['imagen_principal' => $path]);
        }

        return response()->json([
            'user' => new UserResource($user->fresh()),
            'message' => 'Photo uploaded successfully',
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

