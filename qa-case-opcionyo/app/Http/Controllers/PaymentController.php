<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\CardException;

class PaymentController extends Controller
{
    /**
     * Flow B — Charge a card and activate the subscription.
     *
     * Success   -> 201 with subscription active.
     * Declined  -> 402 with a friendly message (card declined).
     */
    public function pay(Request $request, StripeService $stripe): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string'], // e.g. pm_card_visa
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $result = $stripe->charge($data['payment_method'], $data['amount']);
        } catch (CardException $e) {
            return response()->json([
                'message' => 'El pago fue rechazado.',
                'stripe_code' => $e->getStripeCode(),
            ], 402);
        }

        if ($result['status'] !== 'succeeded') {
            return response()->json([
                'message' => 'El pago no pudo completarse.',
                'status' => $result['status'],
            ], 402);
        }

        $subscription = Subscription::updateOrCreate(
            ['stripe_id' => $result['id']],
            [
                'user_id' => $request->user()->id,
                'status' => Subscription::STATUS_ACTIVE,
            ]
        );

        return response()->json([
            'message' => 'Pago exitoso.',
            'subscription' => $subscription->only(['id', 'stripe_id', 'status']),
        ], 201);
    }
}
