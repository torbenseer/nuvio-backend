<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodayModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_mode_endpoint_rejects_unauthenticated_requests(): void
    {
        $this->postJson('/api/today/mode', [
            'mode' => 'yellow',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_today_mode(): void
    {
        $user = User::factory()->create([
            'energy_mode' => 'yellow',
        ]);

        $this->actingAs($user)
            ->postJson('/api/today/mode', [
                'mode' => 'green',
            ])
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    'mode' => 'green',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'energy_mode' => 'green',
        ]);
    }

    public function test_today_mode_requires_allowed_mode(): void
    {
        $user = User::factory()->create([
            'energy_mode' => 'yellow',
        ]);

        $this->actingAs($user)
            ->postJson('/api/today/mode', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode']);

        $this->actingAs($user)
            ->postJson('/api/today/mode', [
                'mode' => 'blue',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode']);

        $this->assertSame('yellow', $user->refresh()->energy_mode);
    }
}
