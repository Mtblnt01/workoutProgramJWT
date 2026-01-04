<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsUser($user)
    {
        $token = Auth::guard('api')->login($user);
        return $this->withHeader('Authorization', 'Bearer ' . $token);
    }

    public function test_me_endpoint_requires_authentication()
    {
        $response = $this->getJson('/api/users/me');
        $response->assertStatus(401);
    }

    public function test_me_endpoint_returns_user_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAsUser($user)->getJson('/api/users/me');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email'],
                     'stats' => ['enrolledCourses', 'completedCourses']
                 ])
                 ->assertJsonPath('user.email', $user->email);
    }

    public function test_user_can_update_their_own_profile()
    {
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $newEmail = 'new@example.com';
        $newName = 'New Name';

        $response = $this->actingAsUser($user)->putJson('/api/users/me', [
            'name' => $newName,
            'email' => $newEmail,
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Profile updated successfully'])
                 ->assertJsonPath('user.name', $newName)
                 ->assertJsonPath('user.email', $newEmail);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $newName,
            'email' => $newEmail,
        ]);
    }

    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $userToDelete = User::factory()->create();

        $response = $this->actingAsUser($admin)->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully']);

        $this->assertSoftDeleted('users', ['id' => $userToDelete->id]);
    }

    public function test_student_cannot_delete_user()
    {
        $student = User::factory()->create(['role' => 'student']);
        $userToDelete = User::factory()->create();

        $response = $this->actingAsUser($student)->deleteJson("/api/users/{$userToDelete->id}");

        $response->assertStatus(403)
                 ->assertJson(['message' => 'Unauthorized. Admin access required.']);
    }
}
