<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\Review;
use App\Models\Task;
use App\Models\TaskVersion;
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
        }

        $this->assertTodayResponseContainsNoExcludedFields($response);
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

    public function test_red_mode_prefers_short_action_when_short_and_long_actions_are_available(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create([
            'energy_mode' => 'red',
        ]);
        $path = LearningPath::query()->firstOrFail();
        $node = LearningNode::query()->firstOrFail();
        $longTask = Task::query()->firstOrFail();

        Task::query()->update(['estimated_minutes' => 25]);
        $longTask->forceFill(['difficulty' => 1])->save();

        $shortTask = Task::query()->create([
            'slug' => 'red-mode-short-linear-equation',
            'type' => 'numeric',
            'difficulty' => 2,
            'estimated_minutes' => 10,
            'active' => true,
        ]);
        $shortTask->learningNodes()->syncWithoutDetaching([
            $node->id => ['is_primary' => true],
        ]);
        $this->createTaskVersion($shortTask);

        Review::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
            'task_id' => $longTask->id,
            'status' => 'scheduled',
            'due_at' => Carbon::now()->subMinute(),
            'interval_days' => 1,
        ]);

        Enrollment::query()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/today')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'task')
            ->assertJsonPath('data.0.estimated_minutes', 10)
            ->assertJsonPath('data.0.target.id', $shortTask->id)
            ->json();

        $this->assertTodayResponseContainsNoExcludedFields($response);
    }

    public function test_red_mode_falls_back_to_best_available_action_when_no_short_action_exists(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create([
            'energy_mode' => 'red',
        ]);
        $path = LearningPath::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        Task::query()->update(['estimated_minutes' => 25]);
        $task->forceFill(['difficulty' => 1])->save();

        Enrollment::query()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'status' => 'active',
            'started_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/today')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'task')
            ->assertJsonPath('data.0.estimated_minutes', 25)
            ->assertJsonPath('data.0.target.id', $task->id)
            ->json();

        $this->assertTodayResponseContainsNoExcludedFields($response);
    }

    public function test_red_mode_today_still_returns_at_most_three_actions(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create([
            'energy_mode' => 'red',
        ]);
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
            ->assertJsonPath('meta.limit', 3)
            ->json();

        $this->assertTodayResponseContainsNoExcludedFields($response);
    }

    public function test_today_rejects_query_filters(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/today?mode=red')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('mode');

        $this->actingAs($user)
            ->getJson('/api/today?limit=1')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    private function createTaskVersion(Task $task): void
    {
        TaskVersion::query()->create([
            'task_id' => $task->id,
            'version' => 1,
            'prompt' => 'Löse x + 1 = 2.',
            'input_schema' => ['kind' => 'number'],
            'answer_schema' => ['correct_value' => 1, 'tolerance' => 0],
            'explanation' => 'Ziehe 1 ab.',
            'active' => true,
        ]);
    }

    private function assertTodayResponseContainsNoExcludedFields(array $response): void
    {
        $excludedKeys = [
            'mode',
            'reason',
            'hidden_due_reviews',
            'overdue_count',
            'backlog_count',
            'missed_days',
            'catch_up',
            'debt',
            'pressure_state',
            'xp',
            'badge',
            'achievement',
            'streak',
            'rank',
            'reward_level',
        ];

        foreach ($excludedKeys as $key) {
            $this->assertArrayNotHasKey($key, $response['meta'] ?? []);
        }

        foreach ($response['data'] ?? [] as $action) {
            foreach ($excludedKeys as $key) {
                $this->assertArrayNotHasKey($key, $action);
            }
        }
    }
}
