<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Newsletter\NewsletterFacade;

class VerificationController extends Controller
{
    /**
     * Resend email verification link.
     */
    public function resend(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Verification link sent to your email',
        ]);
    }

    /**
     * Verify email address (called from email link).
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        // Verify the hash
        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'error' => 'Invalid verification link',
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
                'verified' => true,
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Remove "Unverified" tag from Mailchimp
        try {
            NewsletterFacade::removeTags(['Unverified'], $user->email);
        } catch (\Exception $e) {
            // Log error but don't fail verification
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'verified' => true,
        ]);
    }

    /**
     * Check if user's email is verified.
     */
    public function status(): JsonResponse
    {
        $user = Auth::user();

        return response()->json([
            'verified' => $user->hasVerifiedEmail(),
            'email' => $user->email,
        ]);
    }
}

