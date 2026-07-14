<?php

namespace Database\Seeders;

use App\Models\Slot;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // A known demo patient so the UI can be used immediately.
        User::updateOrCreate(
            ['email' => 'paciente@opcionyo.test'],
            ['name' => 'Paciente Demo', 'password' => Hash::make('password123')]
        );

        $specialists = [
            ['name' => 'Dra. Ana Ríos', 'specialty' => 'Psicología clínica'],
            ['name' => 'Dr. Luis Peña', 'specialty' => 'Nutrición'],
            ['name' => 'Dra. Marta Gil', 'specialty' => 'Terapia de pareja'],
        ];

        // Seed each specialist with a few upcoming available slots.
        $base = Carbon::tomorrow()->setTime(15, 0);

        foreach ($specialists as $i => $data) {
            $specialist = Specialist::updateOrCreate(['name' => $data['name']], $data);

            for ($d = 0; $d < 3; $d++) {
                Slot::updateOrCreate(
                    [
                        'specialist_id' => $specialist->id,
                        'starts_at' => (clone $base)->addDays($d)->addHours($i),
                    ],
                    ['status' => Slot::STATUS_AVAILABLE, 'appointment_id' => null]
                );
            }
        }
    }
}
