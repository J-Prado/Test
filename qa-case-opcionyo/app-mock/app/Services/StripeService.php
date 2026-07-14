<?php

namespace App\Services;

use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\StripeClient;

/**
 * Thin wrapper around the Stripe SDK.
 *
 * The point of this class is testability: controllers depend on StripeService,
 * not on the concrete Stripe SDK. In the fast unit/feature suite we swap this
 * out for a Mockery double (no network). The opt-in sandbox test uses the real
 * implementation against Stripe test mode.
 */
class StripeService
{
    private StripeClient $client;

    public function __construct(?StripeClient $client = null)
    {
        // Lazily build the real client so the app boots without Stripe keys.
        $this->client = $client ?? new StripeClient((string) config('services.stripe.secret'));
    }

    /**
     * Create and confirm a PaymentIntent.
     *
     * @return array{id: string, status: string}
     *
     * @throws CardException     when the card is declined
     * @throws ApiErrorException on any other Stripe error
     */
    public function charge(string $paymentMethod, int $amount, string $currency = 'usd'): array
    {
        $intent = $this->client->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'payment_method' => $paymentMethod,
            'confirm' => true,
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
        ]);

        return [
            'id' => $intent->id,
            'status' => $intent->status,
        ];
    }
}
