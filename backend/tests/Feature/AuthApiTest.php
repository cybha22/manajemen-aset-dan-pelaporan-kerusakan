<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_access_profile_and_logout(): void
    {
        User::create([
            'name' => 'Admin Sarpras',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password' => Hash::make('secret123'),
        ]);

        $login = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'secret123',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'username']]);

        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('username', 'admin');

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout')
            ->assertOk();
    }

    public function test_admin_api_rejects_guest_requests(): void
    {
        $this->getJson('/api/tickets')
            ->assertUnauthorized();
    }
}
