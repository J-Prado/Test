<?php

namespace Database\Seeders;

use App\Models\Slot;
use App\Models\Specialist;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // A known demo patient (handy for the live demo / manual checks).
        User::factory()->create([
            'name' => 'Paciente Demo',
            'email' => 'paciente@opcionyo.test',
        ]);

        // A specialist with a few open slots to book against.
        Specialist::factory()
            ->has(Slot::factory()->count(3), 'slots')
            ->create([
                'name' => 'Dra. Ana Ríos',
                'specialty' => 'Psicología',
            ]);
    }
}
