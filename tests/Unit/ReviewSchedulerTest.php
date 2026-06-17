<?php

namespace Tests\Unit;

use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use App\Services\Review\ReviewScheduler;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewSchedulerTest extends TestCase
{
    use RefreshDatabase;

    public function test_weak_task_outcomes_create_or_update_one_review_due_tomorrow(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();
        $scheduler = app(ReviewScheduler::class);

        foreach (['incorrect', 'unsure', 'skipped'] as $result) {
            $review = $scheduler->recordTaskOutcome($user, $task, $result);

            $this->assertNotNull($review);
            $this->assertSame('scheduled', $review->status);
            $this->assertTrue($review->due_at->equalTo(Carbon::now()->addDay()));
            $this->assertSame(1, Review::query()->where('user_id', $user->id)->count());
        }
    }

    public function test_correct_task_outcome_practices_node_without_review(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();

        $review = app(ReviewScheduler::class)->recordTaskOutcome($user, $task, 'correct');

        $this->assertNull($review);
        $this->assertDatabaseHas('mastery_states', [
            'user_id' => $user->id,
            'status' => 'practiced',
        ]);
    }
}
