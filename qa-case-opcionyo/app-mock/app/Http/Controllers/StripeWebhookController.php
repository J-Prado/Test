<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Flow B — Stripe webhook. Verifies the signature, then updates the
     * subscription status in the DB from the event.
     *
     * Signature valid + known event -> 200
     * Signature invalid             -> 400
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException) {
            return response()->json(['message' => 'Firma inválida.'], 400);
        }

        $object = $event->data->object ?? null;

        match ($event->type) {
            'customer.subscription.updated',
            'customer.subscription.created' => $this->syncStatus($object->id ?? null, $object->status ?? null),
            'customer.subscription.deleted' => $this->syncStatus($object->id ?? null, Subscription::STATUS_CANCELED),
            'invoice.payment_failed' => $this->syncStatus($object->subscription ?? null, Subscription::STATUS_PAST_DUE),
            default => null,
        };

        return response()->json(['received' => true]);
    }

    private function syncStatus(?string $stripeId, ?string $status): void
    {
        if (! $stripeId || ! $status) {
            return;
        }

        Subscription::where('stripe_id', $stripeId)->update(['status' => $status]);
    }
}
