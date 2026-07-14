<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan' => 'monthly',
            'status' => Subscription::STATUS_INCOMPLETE,
            'stripe_customer_id' => 'cus_'.fake()->unique()->bothify('##########'),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Subscription::STATUS_ACTIVE,
        ]);
    }
}
