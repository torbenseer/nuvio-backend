<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TodaySelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_returns_at_most_three_actions_without_backlog_meta(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        for ($i = 0; $i < 4; $i++) {
            Review::query()->create([
                'user_id' => $user->id,
                'learning_node_id' => $node->id,
                'task_id' => $task->id,
                'status' => 'scheduled',
                'due_at' => Carbon::now()->subMinutes($i + 1),
                'interval_days' => 1,
            ]);
        }

        $response = $this->actingAs($user)
            ->getJson('/api/today')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissingPath('meta.hidden_due_reviews')
            ->json();

        foreach ($response['data'] as $action) {
            $this->assertSame('review', $action['type']);
            $this->assertArrayNotHasKey('reason', $action);
            $this->assertArrayNotHasKey('mode', $action);
        }
    }

    public function test_due_review_is_prioritized_before_new_task(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        Enrollment::query()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        Review::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
            'task_id' => $task->id,
            'status' => 'scheduled',
            'due_at' => Carbon::now()->subMinute(),
            'interval_days' => 1,
        ]);

        $this->actingAs($user)
            ->getJson('/api/today')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'review')
            ->assertJsonPath('data.0.title', 'Lineare Gleichungen lösen kurz auffrischen')
            ->assertJsonPath('data.1.type', 'task');
    }

    public function test_task_action_title_is_concrete_not_generic(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();

        Enrollment::query()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/today')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'task')
            ->assertJsonPath('data.0.title', 'Lineare Gleichungen lösen üben');
    }
}
