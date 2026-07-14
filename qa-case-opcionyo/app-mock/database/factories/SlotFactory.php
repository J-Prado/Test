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
        $startsAt = now()->addDay()->setTime(fake()->numberBetween(8, 17), 0);

        return [
            'specialist_id' => Specialist::factory(),
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->addMinutes(50),
            'status' => Slot::STATUS_AVAILABLE,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Slot::STATUS_BOOKED,
        ]);
    }
}
