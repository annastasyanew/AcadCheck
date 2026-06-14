<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_an_api_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Budi Akademik',
            'email' => 'budi@example.com',
            'password' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Register berhasil.')
            ->assertJsonPath('user.email', 'budi@example.com')
            ->assertJsonPath('user.role', User::ROLE_USER)
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'budi@example.com',
            'role' => User::ROLE_USER,
            'is_active' => true,
        ]);
    }

    public function test_active_user_can_login_view_profile_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => 'active@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse
            ->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->json('token');

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $this->postJson('/api/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Akun tidak aktif.');
    }
}
