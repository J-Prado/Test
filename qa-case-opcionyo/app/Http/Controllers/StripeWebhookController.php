<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    // Flow B — webhook updates the subscription status in the DB.
    public function handle(Request $request): JsonResponse
    {
        $event = $this->resolveEvent($request);

        if ($event === null) {
            return response()->json(['error' => 'invalid_signature'], 400);
        }

        $type = $event['type'] ?? null;
        $object = $event['data']['object'] ?? [];
        $customerId = $object['customer'] ?? ($object['stripeCustomerId'] ?? null);

        $subscription = $customerId
            ? Subscription::where('stripe_customer_id', $customerId)->first()
            : null;

        if (! $subscription) {
            // Acknowledge so Stripe stops retrying unknown subscriptions.
            return response()->json(['received' => true, 'matched' => false]);
        }

        $statusByEvent = [
            'invoice.payment_succeeded' => Subscription::STATUS_ACTIVE,
            'customer.subscription.updated' => $object['status'] ?? Subscription::STATUS_ACTIVE,
            'invoice.payment_failed' => Subscription::STATUS_PAST_DUE,
            'customer.subscription.deleted' => Subscription::STATUS_CANCELED,
        ];

        if (isset($statusByEvent[$type])) {
            $subscription->update(['status' => $statusByEvent[$type]]);
        }

        return response()->json([
            'received' => true,
            'matched' => true,
            'status' => $subscription->fresh()->status,
        ]);
    }

    /**
     * Verify + decode the event with the real Stripe SDK when a signing secret
     * is configured; otherwise accept the JSON body as-is (fake mode).
     *
     * @return array<string, mixed>|null  null means signature verification failed
     */
    private function resolveEvent(Request $request): ?array
    {
        $signingSecret = config('services.stripe.webhook_secret');

        if ($signingSecret && config('services.stripe.secret')) {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $request->header('Stripe-Signature', ''),
                    $signingSecret
                );

                return json_decode(json_encode($event), true);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $request->json()->all();
    }
}
