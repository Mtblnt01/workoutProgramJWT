<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use App\Models\UserWorkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkoutController extends Controller
{
    /**
     * GET /workouts
     */
    public function index()
    {
        $workouts = Workout::select('id', 'title', 'description', 'difficulty')->get();

        return response()->json([
            'workouts' => $workouts
        ]);
    }

    /**
     * GET /workouts/{workout}
     */
    public function show(Workout $workout)
    {
        $students = $workout->users()
            ->select('name', 'email')
            ->withPivot('progress', 'last_done')
            ->get()
            ->map(function ($user) {
                return [
                    'name' => $user->name,
                    'email' => $user->email,
                    'progress' => $user->pivot->progress,
                    'last_done' => $user->pivot->last_done,
                ];
            });

        return response()->json([
            'workout' => [
                'title' => $workout->title,
                'description' => $workout->description,
                'difficulty' => $workout->difficulty,
            ],
            'students' => $students
        ]);
    }

    /**
     * POST /workouts - CSAK ADMIN
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'difficulty' => 'required|in:easy,medium,hard',
        ]);

        $workout = Workout::create([
            'title' => $request->title,
            'description' => $request->description,
            'difficulty' => $request->difficulty,
        ]);

        return response()->json([
            'message' => 'Workout created successfully',
            'workout' => [
                'id' => $workout->id,
                'title' => $workout->title,
                'description' => $workout->description,
                'difficulty' => $workout->difficulty,
            ]
        ], 201);
    }

    /**
     * PUT /workouts/{workout} - CSAK ADMIN
     */
    public function update(Request $request, Workout $workout)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'difficulty' => 'sometimes|in:easy,medium,hard',
        ]);

        $workout->update($request->only(['title', 'description', 'difficulty']));

        return response()->json([
            'message' => 'Workout updated successfully',
            'workout' => [
                'id' => $workout->id,
                'title' => $workout->title,
                'description' => $workout->description,
                'difficulty' => $workout->difficulty,
            ]
        ]);
    }

    /**
     * DELETE /workouts/{workout} - CSAK ADMIN
     */
    public function destroy(Workout $workout)
    {
        $workout->delete();

        return response()->json([
            'message' => 'Workout deleted successfully'
        ]);
    }

    /**
     * POST /workouts/{workout}/enroll
     */
    public function enroll(Workout $workout)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        if ($user->workouts()->where('workout_id', $workout->id)->exists()) {
            return response()->json(['message' => 'Already enrolled'], 422);
        }

        $user->workouts()->attach($workout->id, [
            'progress' => 0,
            'last_done' => null
        ]);

        return response()->json(['message' => 'Enrolled successfully'], 201);
    }

    /**
     * POST /workouts/{workout}/complete
     */
    public function complete(Workout $workout)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();

        $record = UserWorkout::where('user_id', $user->id)
            ->where('workout_id', $workout->id)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Not enrolled'], 404);
        }

        $record->update([
            'progress' => 100,
            'last_done' => now(),
            'completed_at' => now()
        ]);

        return response()->json([
            'message' => 'Workout marked as completed'
        ]);
    }
}
