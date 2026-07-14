<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Slot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slot_id' => Slot::factory()->booked(),
            'status' => Appointment::STATUS_SCHEDULED,
        ];
    }
}
