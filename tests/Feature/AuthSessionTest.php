<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_cookie_endpoint_is_available_for_spa_login_flow(): void
    {
        $this->get('/sanctum/csrf-cookie')
            ->assertNoContent();
    }

    public function test_login_authenticates_user_and_returns_profile_defaults(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Learner',
            'email' => 'ada@example.com',
        ]);

        $this->postJson('/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'name' => 'Ada Learner',
                    'email' => 'ada@example.com',
                    'locale' => 'de',
                    'timezone' => 'Europe/Berlin',
                ],
            ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ada@example.com',
        ]);

        $this->postJson('/login', [
            'email' => 'ada@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The provided credentials are incorrect.',
            ]);

        $this->assertGuest();
    }

    public function test_logout_ends_the_web_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertNoContent();

        $this->assertGuest();
    }
}
