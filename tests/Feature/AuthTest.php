<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_endpoint_returns_ok()
    {
        $response = $this->getJson('/api/ping');
        $response->assertStatus(200)
                ->assertJson(['message' => 'API works!']);
    }

    public function test_register_creates_user()
    {
        $payload = [
            'name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'age' => 30
        ];

        $response = $this->postJson('/api/register', $payload);
        $response->assertStatus(201)
                ->assertJsonStructure(['message', 'user' => ['id', 'name', 'email', 'age', 'role']]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'teszt@example.com',
        ]);
    }

    public function test_login_with_valid_email_returns_jwt_token()
    {
        $user = User::factory()->create([
            'email' => 'validuser@example.com',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'validuser@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message', 
                     'user' => ['id', 'name', 'email', 'age', 'role'], 
                     'access' => ['token', 'token_type', 'expires_in']
                 ]);
    }

    public function test_login_with_invalid_email()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(401)
                 ->assertJson(['message' => 'Invalid email']);
    }
}
