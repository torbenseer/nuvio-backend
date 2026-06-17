<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MvpLearningLoopTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_complete_the_v1_learning_loop(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $today = $this->actingAs($user)
            ->getJson('/api/today')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'start_path')
            ->assertJsonMissingPath('data.0.reason')
            ->assertJsonMissingPath('meta.hidden_due_reviews')
            ->json('data.0');

        $this->postJson("/api/learning-paths/{$today['target']['id']}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $taskAction = $this->getJson('/api/today')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'task')
            ->assertJsonMissingPath('data.0.reason')
            ->json('data.0');

        $task = $this->getJson("/api/tasks/{$taskAction['target']['id']}")
            ->assertOk()
            ->assertJsonMissingPath('data.answer_schema')
            ->assertJsonMissingPath('data.answer')
            ->json('data');

        $attempt = $this->postJson('/api/task-attempts/start', [
            'task_id' => $task['id'],
            'task_version_id' => $task['task_version_id'],
        ])->assertOk()->json('data');

        $reviewId = $this->postJson("/api/task-attempts/{$attempt['id']}/submit", [
            'answer' => ['value' => 3],
        ])
            ->assertOk()
            ->assertJsonPath('data.result', 'incorrect')
            ->assertJsonPath('data.review_created', true)
            ->assertJsonPath('data.review_scheduled', true)
            ->assertJsonPath('data.next_state', 'review_scheduled')
            ->assertJsonPath('data.mastery.status', 'review_due')
            ->assertJsonMissingPath('data.completion_state')
            ->assertJsonMissingPath('data.challenge_options')
            ->assertJsonMissingPath('data.mastery.mastery_score')
            ->json('data.review_id');

        $this->getJson('/api/progress/summary')
            ->assertOk()
            ->assertJsonPath('data.active_paths', 1)
            ->assertJsonPath('data.review_due_nodes', 1)
            ->assertJsonPath('data.retained_nodes', 0)
            ->assertJsonMissingPath('data.returning_after_break')
            ->assertJsonMissingPath('data.missed_day_count');

        Carbon::setTestNow('2026-06-18 09:00:00');

        $this->getJson('/api/today')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'review')
            ->assertJsonPath('data.0.target.id', $reviewId)
            ->assertJsonMissingPath('meta.hidden_due_reviews');

        $review = $this->getJson("/api/reviews/{$reviewId}")
            ->assertOk()
            ->assertJsonMissingPath('data.task.answer_schema')
            ->assertJsonMissingPath('data.task.answer')
            ->json('data');

        $this->postJson("/api/reviews/{$review['id']}/answer", [
            'answer' => ['value' => 4],
        ])
            ->assertOk()
            ->assertJsonPath('data.result', 'correct')
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.review_scheduled', false)
            ->assertJsonPath('data.next_state', 'retained')
            ->assertJsonPath('data.mastery.status', 'retained')
            ->assertJsonPath('data.mastery_transition.previous_status', 'review_due')
            ->assertJsonPath('data.mastery_transition.status', 'retained')
            ->assertJsonMissingPath('data.mastery_moment')
            ->assertJsonMissingPath('data.mastery.mastery_score');

        $this->getJson('/api/progress/summary')
            ->assertOk()
            ->assertJsonPath('data.active_paths', 1)
            ->assertJsonPath('data.retained_nodes', 1)
            ->assertJsonPath('data.review_due_nodes', 0);
    }
}
