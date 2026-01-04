<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Workout;

class WorkoutSeeder extends Seeder
{
    public function run(): void
    {
        Workout::create([
            'title' => 'Kezdő Full Body',
            'description' => 'Teljes test edzés kezdőknek. 3x hetente ajánlott.',
            'difficulty' => 'easy',
        ]);

        Workout::create([
            'title' => 'Haladó erősítő',
            'description' => 'Intenzív erősítő edzés haladóknak. Súlyzós gyakorlatok.',
            'difficulty' => 'hard',
        ]);

        Workout::create([
            'title' => 'Cardio mix',
            'description' => 'Vegyes kardió edzés. Futás, ugrókötelezés, burpee.',
            'difficulty' => 'medium',
        ]);
    }
}
