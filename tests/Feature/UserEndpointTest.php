<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_endpoint_returns_profile_and_locale_defaults(): void
    {
        $user = User::factory()->create([
            'name' => 'Ada Learner',
            'email' => 'ada@example.com',
        ]);

        $this->actingAs($user)
            ->getJson('/api/user')
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
    }

    public function test_user_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_preferences_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->putJson('/api/user/preferences', [
            'locale' => 'de',
            'timezone' => 'Europe/Berlin',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_locale_and_timezone_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/user/preferences', [
                'locale' => 'en',
                'timezone' => 'America/New_York',
            ])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'locale' => 'en',
                    'timezone' => 'America/New_York',
                ],
            ]);

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.timezone', 'America/New_York');
    }

    public function test_preferences_endpoint_requires_supported_locale_and_iana_timezone(): void
    {
        $user = User::factory()->create([
            'locale' => 'de',
            'timezone' => 'Europe/Berlin',
        ]);

        $this->actingAs($user)
            ->putJson('/api/user/preferences', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale', 'timezone']);

        $this->actingAs($user)
            ->putJson('/api/user/preferences', [
                'locale' => 'fr',
                'timezone' => 'Berlin',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['locale', 'timezone']);

        $user->refresh();

        $this->assertSame('de', $user->locale);
        $this->assertSame('Europe/Berlin', $user->timezone);
    }
}
