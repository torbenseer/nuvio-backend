<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewDueApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_reviews_are_capped_and_do_not_expose_backlog_pressure(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        $oldest = $this->createReview($user, $node, $task, Carbon::now()->subHours(3));
        $second = $this->createReview($user, $node, $task, Carbon::now()->subHours(2));
        $this->createReview($user, $node, $task, Carbon::now()->subHour());
        $this->createReview($user, $node, $task, Carbon::now()->addHour());
        $this->createReview($other, $node, $task, Carbon::now()->subHours(4));

        $response = $this->actingAs($user)
            ->getJson('/api/reviews/due?limit=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $oldest->id)
            ->assertJsonPath('data.0.learning_node_id', $node->id)
            ->assertJsonPath('data.0.task_id', $task->id)
            ->assertJsonPath('data.0.estimated_minutes', 5)
            ->assertJsonPath('data.1.id', $second->id)
            ->assertJsonPath('meta.returned', 2)
            ->assertJsonPath('meta.cap', 2)
            ->assertJsonMissingPath('meta.hidden_due_reviews')
            ->assertJsonMissingPath('meta.overdue_count')
            ->json();

        $this->assertReviewDueResponseContainsNoExcludedFields($response);
    }

    public function test_due_reviews_default_to_small_cap_and_validate_limit(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();

        for ($i = 0; $i < 4; $i++) {
            $this->createReview($user, $node, $task, Carbon::now()->subMinutes($i + 1));
        }

        $this->actingAs($user)
            ->getJson('/api/reviews/due')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.returned', 3)
            ->assertJsonPath('meta.cap', 3);

        $this->actingAs($user)
            ->getJson('/api/reviews/due?limit=11')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('limit');
    }

    public function test_due_reviews_require_authentication(): void
    {
        $this->getJson('/api/reviews/due')
            ->assertUnauthorized();
    }

    public function test_snooze_moves_due_date_without_improving_mastery(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();
        $review = $this->createReview($user, $node, $task, Carbon::now()->subHour());

        MasteryState::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
            'status' => 'review_due',
            'last_practiced_at' => Carbon::now()->subDay(),
            'retained_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/snooze", [
                'minutes' => 60,
            ])
            ->assertOk()
            ->assertJsonPath('data.id', $review->id)
            ->assertJsonPath('data.due_at', '2026-06-18T10:00:00.000000Z')
            ->assertJsonPath('data.status', 'scheduled')
            ->json();

        $review->refresh();

        $this->assertSame('2026-06-18T10:00:00.000000Z', $review->due_at?->toJSON());
        $this->assertSame('review_due', MasteryState::query()->where('user_id', $user->id)->value('status'));
        $this->assertReviewDueResponseContainsNoExcludedFields($response);
    }

    public function test_snooze_validates_duration_and_ownership(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();
        $review = $this->createReview($owner, $node, $task, Carbon::now());

        $this->actingAs($owner)
            ->postJson("/api/reviews/{$review->id}/snooze", [
                'minutes' => 5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('minutes');

        $this->actingAs($owner)
            ->postJson("/api/reviews/{$review->id}/snooze", [
                'minutes' => 1441,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('minutes');

        $this->actingAs($other)
            ->postJson("/api/reviews/{$review->id}/snooze", [
                'minutes' => 60,
            ])
            ->assertForbidden();
    }

    public function test_snooze_requires_authentication_and_scheduled_review(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();
        $review = $this->createReview($user, $node, $task, Carbon::now());

        $this->postJson("/api/reviews/{$review->id}/snooze", [
            'minutes' => 60,
        ])->assertUnauthorized();

        $review->forceFill([
            'status' => 'completed',
            'due_at' => null,
            'completed_at' => Carbon::now(),
        ])->save();

        $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/snooze", [
                'minutes' => 60,
            ])
            ->assertConflict();
    }

    public function test_review_answer_requires_authentication(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();
        $review = $this->createReview($user, $node, $task, Carbon::now());

        $this->postJson("/api/reviews/{$review->id}/answer", [
            'answer' => ['value' => 4],
        ])->assertUnauthorized();
    }

    public function test_review_answer_requires_exactly_one_valid_answer_or_recovery_result(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();
        $review = $this->createReview($user, $node, $task, Carbon::now());

        $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/answer", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer');

        $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/answer", [
                'answer' => ['value' => 4],
                'result' => 'unsure',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer');

        $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/answer", [
                'answer' => ['value' => 'vier'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer.value');

        $this->actingAs($user)
            ->postJson("/api/reviews/{$review->id}/answer", [
                'result' => 'correct',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('result');
    }

    private function createReview(User $user, LearningNode $node, Task $task, Carbon $dueAt): Review
    {
        return Review::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
            'task_id' => $task->id,
            'status' => 'scheduled',
            'due_at' => $dueAt,
            'interval_days' => 1,
        ]);
    }

    private function assertReviewDueResponseContainsNoExcludedFields(array $response): void
    {
        $json = strtolower((string) json_encode($response));

        foreach ([
            'answer_schema',
            'answer',
            'accepted_value',
            'tolerance',
            'explanation',
            'hidden_due_reviews',
            'overdue_count',
            'missed_days',
            'catch_up',
            'debt',
            'xp',
            'badge',
            'achievement',
            'streak',
            'reward',
            'mastery_score',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
    }
}
