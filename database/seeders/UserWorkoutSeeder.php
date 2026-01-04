<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Workout;
use App\Models\UserWorkout;
use Carbon\Carbon;

class UserWorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('role', 'student')->take(3)->get();
        $workouts = Workout::all();

        // User 1: két edzésprogram
        UserWorkout::create([
            'user_id' => $users[0]->id,
            'workout_id' => $workouts[0]->id,
            'progress' => 75,
            'last_done' => Carbon::now()->subDays(2),
            'completed_at' => null,
        ]);

        UserWorkout::create([
            'user_id' => $users[0]->id,
            'workout_id' => $workouts[1]->id,
            'progress' => 25,
            'last_done' => Carbon::now()->subDays(5),
            'completed_at' => null,
        ]);

        // User 2: egy befejezett edzésprogram
        UserWorkout::create([
            'user_id' => $users[1]->id,
            'workout_id' => $workouts[0]->id,
            'progress' => 100,
            'last_done' => Carbon::now()->subDay(),
            'completed_at' => Carbon::now()->subDay(),
        ]);

        // User 3: egyik sem teljesített még
        UserWorkout::create([
            'user_id' => $users[2]->id,
            'workout_id' => $workouts[2]->id,
            'progress' => 0,
            'last_done' => null,
            'completed_at' => null,
        ]);
    }
}
