<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;

/**
 * Flow B gateway.
 *
 * With a STRIPE_SECRET configured it calls the real Stripe sandbox. Without one
 * (default) it runs in "fake mode": it recognizes the standard Stripe test
 * instruments so success/decline are deterministic and no network/keys are
 * needed — ideal for local dev and CI.
 */
class StripeGateway
{
    private const APPROVED = ['4242424242424242', 'tok_visa', 'pm_card_visa'];

    private const DECLINED = ['4000000000000002', 'tok_chargeDeclined', 'pm_card_chargeDeclined'];

    /**
     * @return array{approved: bool, customer_id: string, decline_code?: string}
     */
    public function charge(User $user, string $paymentMethod): array
    {
        $customerId = 'cus_'.$user->id;
        $secret = config('services.stripe.secret');

        if ($secret) {
            return $this->chargeReal($secret, $user, $paymentMethod, $customerId);
        }

        if (in_array($paymentMethod, self::DECLINED, true)) {
            return ['approved' => false, 'customer_id' => $customerId, 'decline_code' => 'generic_decline'];
        }

        if (in_array($paymentMethod, self::APPROVED, true)) {
            return ['approved' => true, 'customer_id' => $customerId];
        }

        return ['approved' => false, 'customer_id' => $customerId, 'decline_code' => 'unknown_payment_method'];
    }

    /**
     * Real Stripe sandbox charge via a PaymentIntent.
     *
     * @return array{approved: bool, customer_id: string, decline_code?: string}
     */
    private function chargeReal(string $secret, User $user, string $paymentMethod, string $fallbackCustomerId): array
    {
        try {
            $stripe = new \Stripe\StripeClient($secret);

            $customer = $stripe->customers->create([
                'email' => $user->email,
                'metadata' => ['user_id' => (string) $user->id],
            ]);

            $intent = $stripe->paymentIntents->create([
                'amount' => 1999,
                'currency' => 'usd',
                'customer' => $customer->id,
                'payment_method' => $paymentMethod,
                'confirm' => true,
                'automatic_payment_methods' => ['enabled' => true, 'allow_redirects' => 'never'],
            ]);

            return ['approved' => $intent->status === 'succeeded', 'customer_id' => $customer->id];
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'approved' => false,
                'customer_id' => $fallbackCustomerId,
                'decline_code' => $e->getDeclineCode() ?? 'card_declined',
            ];
        } catch (\Throwable $e) {
            return ['approved' => false, 'customer_id' => $fallbackCustomerId, 'decline_code' => 'stripe_error'];
        }
    }

    /**
     * In fake mode, simulate Stripe's asynchronous confirmation
     * (the invoice.payment_succeeded webhook) by activating right away.
     * Set STRIPE_AUTO_CONFIRM_MS to a negative value to require the webhook.
     */
    public function maybeAutoConfirm(Subscription $subscription): void
    {
        $autoConfirm = (int) config('services.stripe.auto_confirm_ms', 1500);

        if (! config('services.stripe.secret') && $autoConfirm >= 0) {
            $subscription->update(['status' => Subscription::STATUS_ACTIVE]);
        }
    }
}
