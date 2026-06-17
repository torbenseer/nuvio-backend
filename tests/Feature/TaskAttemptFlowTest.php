<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TaskAttemptFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_task_can_be_fetched_started_and_submitted_without_answer_leaks(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();

        $taskPayload = $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.answer_schema')
            ->assertJsonMissingPath('data.answer')
            ->json('data');

        $attempt = $this->postJson('/api/task-attempts/start', [
            'task_id' => $taskPayload['id'],
            'task_version_id' => $taskPayload['task_version_id'],
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'started')
            ->assertJsonPath('data.task_id', $taskPayload['id'])
            ->assertJsonPath('data.task_version_id', $taskPayload['task_version_id'])
            ->json('data');

        $this->postJson("/api/task-attempts/{$attempt['id']}/submit", [
            'answer' => ['value' => 4],
        ])
            ->assertOk()
            ->assertJsonPath('data.result', 'correct')
            ->assertJsonPath('data.feedback_key', 'numeric_correct')
            ->assertJsonPath('data.review_created', false)
            ->assertJsonPath('data.review_scheduled', false)
            ->assertJsonPath('data.next_state', 'practiced')
            ->assertJsonMissingPath('data.answer_schema')
            ->assertJsonMissingPath('data.answer')
            ->assertJsonMissingPath('data.mastery.mastery_score');
    }

    public function test_task_version_must_belong_to_task_when_starting_attempt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/task-attempts/start', [
                'task_id' => 999,
                'task_version_id' => 999,
            ])
            ->assertUnprocessable();
    }
}
