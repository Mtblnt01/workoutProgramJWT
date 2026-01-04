<?php

namespace Tests\Feature;

use App\Models\Workout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser($user)
    {
        $token = Auth::guard('api')->login($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_workout_index_requires_authentication()
    {
        $response = $this->getJson('/api/workouts');
        $response->assertStatus(401);
    }

    public function test_workout_index_returns_list_of_workouts()
    {
        $user = User::factory()->create();
        
        Workout::create(['title' => 'Workout A', 'description' => 'Desc A', 'difficulty' => 'easy']);
        Workout::create(['title' => 'Workout B', 'description' => 'Desc B', 'difficulty' => 'medium']);

        $response = $this->actingAsUser($user)->getJson('/api/workouts');

        $response->assertStatus(200)
                 ->assertJsonStructure(['workouts' => [
                     '*' => ['id', 'title', 'description', 'difficulty']
                 ]]);
    }

    public function test_admin_can_create_workout()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $payload = [
            'title' => 'New Workout',
            'description' => 'Test workout',
            'difficulty' => 'medium',
        ];

        $response = $this->actingAsUser($admin)->postJson('/api/workouts', $payload);

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Workout created successfully']);

        $this->assertDatabaseHas('workouts', ['title' => 'New Workout']);
    }

    public function test_student_cannot_create_workout()
    {
        $student = User::factory()->create(['role' => 'student']);

        $payload = [
            'title' => 'New Workout',
            'description' => 'Test workout',
            'difficulty' => 'medium',
        ];

        $response = $this->actingAsUser($student)->postJson('/api/workouts', $payload);

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized. Admin access required.']);
    }

    public function test_user_can_enroll_in_a_workout()
    {
        $user = User::factory()->create();
        $workout = Workout::create(['title' => 'Enroll Test', 'description' => 'Desc', 'difficulty' => 'easy']);

        $response = $this->actingAsUser($user)->postJson("/api/workouts/{$workout->id}/enroll");

        $response->assertStatus(201)
                 ->assertJson(['message' => 'Enrolled successfully']);

        $this->assertDatabaseHas('user_workouts', [
            'user_id' => $user->id,
            'workout_id' => $workout->id,
        ]);
    }

    public function test_user_can_complete_an_enrolled_workout()
    {
        $user = User::factory()->create();
        $workout = Workout::create(['title' => 'Complete Test', 'description' => 'Desc', 'difficulty' => 'hard']);
        
        $user->workouts()->attach($workout->id, ['progress' => 0]);

        $response = $this->actingAsUser($user)->postJson("/api/workouts/{$workout->id}/complete");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Workout marked as completed']);

        $this->assertDatabaseHas('user_workouts', [
            'user_id' => $user->id,
            'workout_id' => $workout->id,
            'progress' => 100,
        ]);
    }
}
