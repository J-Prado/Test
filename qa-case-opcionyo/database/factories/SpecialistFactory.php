<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Specialist>
 */
class SpecialistFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Dr. '.fake()->name(),
            'specialty' => fake()->randomElement([
                'Psicología clínica',
                'Nutrición',
                'Terapia de pareja',
                'Medicina general',
            ]),
        ];
    }
}
