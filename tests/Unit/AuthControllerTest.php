<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_and_logs_in(): void
    {
        $payload = [
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'country' => '🇪🇸',
        ];

        $response = $this->postJson('/api/register', $payload);

        $response->assertStatus(200);
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'tester@example.com']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        User::create([
            'name' => 'Foo',
            'email' => 'foo@example.com',
            'password' => Hash::make('correct-pass'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'foo@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertStatus(401);
        $this->assertGuest();
    }
}
