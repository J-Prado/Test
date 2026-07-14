<?php

namespace Database\Factories;

use App\Models\Slot;
use App\Models\Specialist;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Slot>
 */
class SlotFactory extends Factory
{
    public function definition(): array
    {
        return [
            'specialist_id' => Specialist::factory(),
            'starts_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'status' => Slot::STATUS_AVAILABLE,
            'appointment_id' => null,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Slot::STATUS_BOOKED,
        ]);
    }
}
