<?php

namespace App\Http\Controllers\Api\V1\Subscriptions;

use App\Http\Controllers\Controller;
use App\Http\Resources\Subscription\MembresiaResource;
use App\Http\Resources\Subscription\SubscriptionResource;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Stripe\StripeClient;

class SubscriptionController extends Controller
{
    /**
     * Get all available membership plans.
     */
    public function plans(): AnonymousResourceCollection
    {
        $membresias = Membresia::where('activa', 1)
            ->with(['detalles', 'role'])
            ->orderBy('orden', 'desc')
            ->get();

        return MembresiaResource::collection($membresias);
    }

    /**
     * Get Stripe plans from API.
     */
    public function stripePlans(): JsonResponse
    {
        try {
            $stripe = new StripeClient(config('services.stripe.secret'));
            $plansRaw = $stripe->plans->all();
            $plans = $plansRaw->data;

            // Enrich plans with product information
            foreach ($plans as $plan) {
                $product = $stripe->products->retrieve($plan->product, []);
                $plan->product = $product;
            }

            return response()->json([
                'plans' => $plans,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve plans',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's current subscription.
     */
    public function current(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->subscribed('default')) {
            return response()->json([
                'subscribed' => false,
                'subscription' => null,
            ]);
        }

        $subscription = $user->subscription('default');

        return response()->json([
            'subscribed' => true,
            'subscription' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Create a setup intent for payment method.
     */
    public function setupIntent(): JsonResponse
    {
        try {
            $user = Auth::user();
            $intent = $user->createSetupIntent();

            return response()->json([
                'client_secret' => $intent->client_secret,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create setup intent',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'plan' => 'required|string', // Stripe price ID
        ]);

        $user = Auth::user();

        try {
            // Create or get Stripe customer
            $user->createOrGetStripeCustomer();
            
            // Add payment method
            $user->addPaymentMethod($validated['payment_method']);

            // Find membresia and update user role
            $membresia = Membresia::where('product', $validated['plan'])->first();
            
            if ($membresia && $membresia->role) {
                $user->role_id = $membresia->role->id;
                $user->save();
            }

            // Create subscription
            $subscription = $user->newSubscription('default', $validated['plan'])
                ->create($validated['payment_method']);

            return response()->json([
                'success' => true,
                'message' => 'Subscription created successfully',
                'subscription' => new SubscriptionResource($subscription),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create subscription',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update subscription plan.
     */
    public function updatePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => 'required|string', // New Stripe price ID
        ]);

        $user = Auth::user();

        if (!$user->subscribed('default')) {
            return response()->json([
                'error' => 'No active subscription found',
            ], 404);
        }

        try {
            $subscription = $user->subscription('default');
            $subscription->swap($validated['plan']);

            // Update role if membresia exists
            $membresia = Membresia::where('product', $validated['plan'])->first();
            if ($membresia && $membresia->role) {
                $user->role_id = $membresia->role->id;
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'subscription' => new SubscriptionResource($subscription->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to update subscription',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->subscribed('default')) {
            return response()->json([
                'error' => 'No active subscription found',
            ], 404);
        }

        try {
            $subscription = $user->subscription('default');
            $subscription->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully',
                'subscription' => new SubscriptionResource($subscription->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to cancel subscription',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resume cancelled subscription.
     */
    public function resume(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->subscribed('default')) {
            return response()->json([
                'error' => 'No subscription found',
            ], 404);
        }

        try {
            $subscription = $user->subscription('default');
            
            if (!$subscription->onGracePeriod()) {
                return response()->json([
                    'error' => 'Subscription cannot be resumed',
                ], 400);
            }

            $subscription->resume();

            return response()->json([
                'success' => true,
                'message' => 'Subscription resumed successfully',
                'subscription' => new SubscriptionResource($subscription->fresh()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to resume subscription',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}

