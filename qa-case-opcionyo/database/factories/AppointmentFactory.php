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
            'slot_id' => Slot::factory(),
            'patient_id' => User::factory(),
            'status' => Appointment::STATUS_BOOKED,
        ];
    }
}
