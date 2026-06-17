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
}
