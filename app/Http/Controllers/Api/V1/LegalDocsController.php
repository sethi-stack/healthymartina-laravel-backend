<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PrivacyNotice;
use App\Models\TermsConditions;
use Illuminate\Http\JsonResponse;

class LegalDocsController extends Controller
{
    /**
     * Get current terms and conditions.
     */
    public function terms(): JsonResponse
    {
        $terms = TermsConditions::whereActive(1)->first();

        if (!$terms) {
            return response()->json([
                'error' => 'No active terms and conditions found',
            ], 404);
        }

        return response()->json([
            'id' => $terms->id,
            'title' => $terms->title,
            'content' => $terms->content,
            'version' => $terms->version,
            'effective_date' => $terms->effective_date,
            'updated_at' => $terms->updated_at,
        ]);
    }

    /**
     * Get current privacy notice.
     */
    public function privacy(): JsonResponse
    {
        $notice = PrivacyNotice::whereActive(1)->first();

        if (!$notice) {
            return response()->json([
                'error' => 'No active privacy notice found',
            ], 404);
        }

        return response()->json([
            'id' => $notice->id,
            'title' => $notice->title,
            'content' => $notice->content,
            'version' => $notice->version,
            'effective_date' => $notice->effective_date,
            'updated_at' => $notice->updated_at,
        ]);
    }

    /**
     * Accept terms and conditions (track user acceptance).
     */
    public function acceptTerms(): JsonResponse
    {
        $user = auth()->user();
        $terms = TermsConditions::whereActive(1)->first();

        if (!$terms) {
            return response()->json([
                'error' => 'No active terms and conditions found',
            ], 404);
        }

        // Update user's acceptance (you might want to add a field to users table)
        // For now, just return success
        
        return response()->json([
            'success' => true,
            'message' => 'Terms and conditions accepted',
            'accepted_version' => $terms->version,
        ]);
    }

    /**
     * Accept privacy notice (track user acceptance).
     */
    public function acceptPrivacy(): JsonResponse
    {
        $user = auth()->user();
        $notice = PrivacyNotice::whereActive(1)->first();

        if (!$notice) {
            return response()->json([
                'error' => 'No active privacy notice found',
            ], 404);
        }

        // Update user's acceptance
        
        return response()->json([
            'success' => true,
            'message' => 'Privacy notice accepted',
            'accepted_version' => $notice->version,
        ]);
    }
}

