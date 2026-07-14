<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Flow B — subscribe with a test card.
    public function subscribe(Request $request, StripeGateway $stripe): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string'],
            'plan' => ['sometimes', 'string', 'max:50'],
        ]);

        $user = $request->user();
        $plan = $data['plan'] ?? 'monthly';

        $result = $stripe->charge($user, $data['payment_method']);

        // A subscription record exists in both cases; only the status differs.
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'plan' => $plan,
                'status' => Subscription::STATUS_INCOMPLETE,
                'stripe_customer_id' => $result['customer_id'],
            ]
        );

        if (! $result['approved']) {
            return response()->json([
                'error' => 'card_declined',
                'decline_code' => $result['decline_code'] ?? 'generic_decline',
            ], 402);
        }

        // Simulate Stripe confirming the payment asynchronously (fake mode).
        $stripe->maybeAutoConfirm($subscription);

        return response()->json([
            'subscription' => $subscription->fresh(),
            'stripe_customer_id' => $result['customer_id'],
            'requires_webhook_confirmation' => true,
        ], 201);
    }

    // Read-back used by the UI and QA assertions.
    public function show(User $user): JsonResponse
    {
        $subscription = $user->subscription;

        if (! $subscription) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json(['subscription' => $subscription]);
    }
}
