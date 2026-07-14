<?php

use App\Models\Subscription;
use App\Models\User;
use App\Services\StripeService;
use Stripe\Exception\CardException;

/*
|--------------------------------------------------------------------------
| Flow B — Payment with Stripe (sandbox / test mode)
|--------------------------------------------------------------------------
| - Successful payment with a test card
| - Declined card
| - Stripe webhook updates the subscription status in the DB
|
| The Stripe SDK is swapped for a Mockery double so these tests are fast and
| deterministic and run in CI with no network. An OPT-IN test that hits the
| real Stripe sandbox lives at the bottom (skipped unless a real sk_test_ key
| is provided).
*/

/**
 * Build a validly-signed Stripe webhook request and send it.
 * Signs the raw body exactly like Stripe does (t=<ts>,v1=<hmac-sha256>).
 */
function postStripeWebhook(string $payload): \Illuminate\Testing\TestResponse
{
    $secret = (string) config('services.stripe.webhook_secret');
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return test()->call(
        'POST',
        '/api/stripe/webhook',
        server: [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ],
        content: $payload,
    );
}

it('activates the subscription after a successful payment', function () {
    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('charge')
            ->once()
            ->andReturn(['id' => 'pi_success_123', 'status' => 'succeeded']);
    });

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/pay', ['payment_method' => 'pm_card_visa', 'amount' => 5000])
        ->assertCreated()
        ->assertJsonPath('subscription.status', Subscription::STATUS_ACTIVE);

    $this->assertDatabaseHas('subscriptions', [
        'user_id' => $user->id,
        'stripe_id' => 'pi_success_123',
        'status' => Subscription::STATUS_ACTIVE,
    ]);
});

it('returns 402 and creates no subscription when the card is declined', function () {
    $this->mock(StripeService::class, function ($mock) {
        $mock->shouldReceive('charge')
            ->once()
            ->andThrow(new CardException('Your card was declined.'));
    });

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/pay', ['payment_method' => 'pm_card_chargeDeclined', 'amount' => 5000])
        ->assertStatus(402);

    $this->assertDatabaseCount('subscriptions', 0);
});

it('updates subscription status from a signed Stripe webhook', function () {
    Subscription::factory()->create([
        'stripe_id' => 'sub_test_123',
        'status' => Subscription::STATUS_PENDING,
    ]);

    $payload = json_encode([
        'id' => 'evt_test_1',
        'object' => 'event',
        'type' => 'customer.subscription.updated',
        'data' => ['object' => [
            'id' => 'sub_test_123',
            'object' => 'subscription',
            'status' => 'active',
        ]],
    ]);

    postStripeWebhook($payload)->assertOk();

    $this->assertDatabaseHas('subscriptions', [
        'stripe_id' => 'sub_test_123',
        'status' => Subscription::STATUS_ACTIVE,
    ]);
});

it('rejects a webhook with an invalid signature', function () {
    $payload = json_encode(['id' => 'evt_x', 'type' => 'customer.subscription.updated', 'data' => ['object' => []]]);

    $this->call(
        'POST',
        '/api/stripe/webhook',
        server: ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef', 'CONTENT_TYPE' => 'application/json'],
        content: $payload,
    )->assertStatus(400);
});

/*
 * OPT-IN integration test — real Stripe sandbox.
 * Skipped automatically unless STRIPE_SECRET is a real test key (sk_test_...).
 */
it('charges a real Stripe test card in sandbox mode', function () {
    $secret = (string) config('services.stripe.secret');
    if (! str_starts_with($secret, 'sk_test_') || str_contains($secret, 'dummy')) {
        $this->markTestSkipped('Set a real STRIPE_SECRET (sk_test_...) to run the sandbox test.');
    }

    $user = User::factory()->create();

    // pm_card_visa is Stripe's always-successful test PaymentMethod.
    $this->actingAs($user, 'sanctum')
        ->postJson('/api/pay', ['payment_method' => 'pm_card_visa', 'amount' => 5000])
        ->assertCreated();
})->group('integration', 'stripe');
