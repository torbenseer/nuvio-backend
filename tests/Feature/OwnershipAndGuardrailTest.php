<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\Review;
use App\Models\Task;
use App\Models\TaskAttempt;
use App\Models\TaskVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OwnershipAndGuardrailTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_owned_attempts_and_reviews_are_isolated(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $task = Task::query()->firstOrFail();
        $version = TaskVersion::query()->firstOrFail();
        $node = LearningNode::query()->firstOrFail();

        $attempt = TaskAttempt::query()->create([
            'user_id' => $owner->id,
            'task_id' => $task->id,
            'task_version_id' => $version->id,
            'status' => 'started',
        ]);

        $review = Review::query()->create([
            'user_id' => $owner->id,
            'learning_node_id' => $node->id,
            'task_id' => $task->id,
            'status' => 'scheduled',
            'due_at' => Carbon::now(),
            'interval_days' => 1,
        ]);

        $this->actingAs($other)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                'answer' => ['value' => 4],
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->getJson("/api/reviews/{$review->id}")
            ->assertForbidden();
    }

    public function test_v1_responses_do_not_expose_forbidden_pressure_fields_or_copy(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $json = $this->actingAs($user)->getJson('/api/today')->assertOk()->getContent();

        foreach ([
            'xp',
            'badge',
            'streak',
            'hidden_due_reviews',
            'catch up',
            'behind',
            'failed',
            'lost progress',
            'level unlocked',
            'reward',
            'returning_after_break',
            'mastery_score',
            'percent_complete',
            'overdue_count',
            'missed_days',
            'catch_up_required',
            'pressure_reason',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($json));
        }
    }

    public function test_return_after_long_break_does_not_expose_debt_or_catch_up_language(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        Review::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
            'task_id' => $task->id,
            'status' => 'scheduled',
            'due_at' => Carbon::now()->subDays(14),
            'interval_days' => 1,
        ]);

        $json = $this->actingAs($user)->getJson('/api/today')->assertOk()->getContent();

        foreach ([
            'missed_days',
            'catch_up_required',
            'overdue_count',
            'hidden_due_reviews',
            'debt',
            'rückstand',
            'verpasst',
            'nachholen',
            'überfällig',
            'tagesziel',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, strtolower($json));
        }
    }

    public function test_unsure_and_skip_schedule_review_with_neutral_next_state(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();
        $version = TaskVersion::query()->firstOrFail();

        foreach (['unsure', 'skipped'] as $result) {
            $attempt = TaskAttempt::query()->create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'task_version_id' => $version->id,
                'status' => 'started',
            ]);

            $this->actingAs($user)
                ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                    'result' => $result,
                ])
                ->assertOk()
                ->assertJsonPath('data.result', $result)
                ->assertJsonPath('data.review_scheduled', true)
                ->assertJsonPath('data.next_state', 'review_scheduled')
                ->assertJsonMissingPath('data.debt')
                ->assertJsonMissingPath('data.catch_up_required');
        }
    }

    public function test_completed_review_cannot_be_answered_again(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        $review = Review::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
            'task_id' => $task->id,
            'status' => 'completed',
            'due_at' => null,
            'interval_days' => null,
            'completed_at' => Carbon::now(),
        ]);

        $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/answer", [
                'answer' => ['value' => 4],
            ])
            ->assertConflict();
    }
}
