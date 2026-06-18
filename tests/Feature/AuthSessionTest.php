<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    private const FRONTEND_ORIGIN = 'http://localhost:3000';

    public function test_csrf_cookie_endpoint_is_available_for_spa_login_flow(): void
    {
        $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Referer' => self::FRONTEND_ORIGIN.'/login',
        ])->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN')
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_spa_login_authenticates_user_and_authenticated_api_requests_from_frontend_origin(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Learner',
            'email' => 'ada@example.com',
        ]);

        $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Referer' => self::FRONTEND_ORIGIN.'/login',
        ])->postJson('/login', [
            'email' => 'ada@example.com',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
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

        $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Referer' => self::FRONTEND_ORIGIN.'/today',
        ])->getJson('/api/user')
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
            ->assertJsonPath('data.email', 'ada@example.com');

        $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Referer' => self::FRONTEND_ORIGIN.'/settings',
        ])->putJson('/api/user/preferences', [
            'locale' => 'en',
            'timezone' => 'America/New_York',
        ])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.timezone', 'America/New_York');
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
            ->withHeaders([
                'Origin' => self::FRONTEND_ORIGIN,
                'Referer' => self::FRONTEND_ORIGIN.'/settings',
            ])->post('/logout')
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true')
            ->assertNoContent();

        $this->assertGuest();
    }

    public function test_cors_preflight_allows_configured_frontend_origin_with_credentials(): void
    {
        $this->withHeaders([
            'Origin' => self::FRONTEND_ORIGIN,
            'Access-Control-Request-Method' => 'PUT',
            'Access-Control-Request-Headers' => 'Content-Type, X-XSRF-TOKEN',
        ])->options('/api/user/preferences')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', self::FRONTEND_ORIGIN)
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_cors_preflight_does_not_allow_unconfigured_origins(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://evil.test',
            'Access-Control-Request-Method' => 'PUT',
            'Access-Control-Request-Headers' => 'Content-Type, X-XSRF-TOKEN',
        ])->options('/api/user/preferences')
            ->assertNoContent();

        $this->assertNotSame(
            'http://evil.test',
            $response->headers->get('Access-Control-Allow-Origin'),
        );
    }
}
