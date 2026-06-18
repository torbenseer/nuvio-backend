<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskAttempt;
use App\Models\TaskVersion;
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
        $task = Task::query()->firstOrFail();
        $otherTask = Task::query()->create([
            'slug' => 'other-task',
            'type' => 'numeric',
            'difficulty' => 1,
            'estimated_minutes' => 5,
            'active' => true,
        ]);
        $otherVersion = TaskVersion::query()->create([
            'task_id' => $otherTask->id,
            'version' => 1,
            'prompt' => 'Löse x + 1 = 3.',
            'input_schema' => ['kind' => 'number'],
            'answer_schema' => ['correct_value' => 2, 'tolerance' => 0],
            'explanation' => 'Ziehe 1 ab.',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->postJson('/api/task-attempts/start', [
                'task_id' => 999,
                'task_version_id' => 999,
            ])
            ->assertUnprocessable();

        $this->actingAs($user)
            ->postJson('/api/task-attempts/start', [
                'task_id' => $task->id,
                'task_version_id' => $otherVersion->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task_version_id');
    }

    public function test_task_read_returns_not_found_for_inactive_missing_or_unversioned_tasks(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();

        $task->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->getJson("/api/tasks/{$task->id}")
            ->assertNotFound();

        $taskWithoutVersion = Task::query()->create([
            'slug' => 'task-without-active-version',
            'type' => 'numeric',
            'difficulty' => 1,
            'estimated_minutes' => 5,
            'active' => true,
        ]);
        TaskVersion::query()->create([
            'task_id' => $taskWithoutVersion->id,
            'version' => 1,
            'prompt' => 'Löse x + 1 = 2.',
            'input_schema' => ['kind' => 'number'],
            'answer_schema' => ['correct_value' => 1, 'tolerance' => 0],
            'explanation' => 'Ziehe 1 ab.',
            'active' => false,
        ]);

        $this->actingAs($user)
            ->getJson("/api/tasks/{$taskWithoutVersion->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/tasks/999999')
            ->assertNotFound();
    }

    public function test_task_version_must_be_active_when_starting_attempt(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();
        $inactiveVersion = TaskVersion::query()->create([
            'task_id' => $task->id,
            'version' => 2,
            'prompt' => 'Löse x + 1 = 3.',
            'input_schema' => ['kind' => 'number'],
            'answer_schema' => ['correct_value' => 2, 'tolerance' => 0],
            'explanation' => 'Ziehe 1 ab.',
            'active' => false,
        ]);

        $this->actingAs($user)
            ->postJson('/api/task-attempts/start', [
                'task_id' => $task->id,
                'task_version_id' => $inactiveVersion->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('task_version_id');
    }

    public function test_task_attempt_routes_require_authentication(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::factory()->create();
        $task = Task::query()->firstOrFail();
        $version = TaskVersion::query()->firstOrFail();
        $attempt = TaskAttempt::query()->create([
            'user_id' => $owner->id,
            'task_id' => $task->id,
            'task_version_id' => $version->id,
            'status' => 'started',
        ]);

        $this->getJson("/api/tasks/{$task->id}")
            ->assertUnauthorized();

        $this->postJson('/api/task-attempts/start', [
            'task_id' => $task->id,
            'task_version_id' => $version->id,
        ])->assertUnauthorized();

        $this->postJson("/api/task-attempts/{$attempt->id}/submit", [
            'answer' => ['value' => 4],
        ])->assertUnauthorized();
    }

    public function test_submit_attempt_requires_exactly_one_valid_answer_or_recovery_result(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $attempt = $this->createStartedAttempt($user);

        $this->actingAs($user)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer');

        $this->actingAs($user)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                'answer' => ['value' => 4],
                'result' => 'unsure',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer');

        $this->actingAs($user)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                'answer' => ['value' => 'vier'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('answer.value');

        $this->actingAs($user)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                'result' => 'correct',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('result');
    }

    public function test_submit_attempt_rejects_other_users_and_completed_attempts(): void
    {
        $this->seed(DatabaseSeeder::class);
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $attempt = $this->createStartedAttempt($owner);

        $this->actingAs($other)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                'answer' => ['value' => 4],
            ])
            ->assertForbidden();

        $attempt->forceFill(['status' => 'submitted'])->save();

        $this->actingAs($owner)
            ->postJson("/api/task-attempts/{$attempt->id}/submit", [
                'answer' => ['value' => 4],
            ])
            ->assertConflict();
    }

    private function createStartedAttempt(User $user): TaskAttempt
    {
        return TaskAttempt::query()->create([
            'user_id' => $user->id,
            'task_id' => Task::query()->firstOrFail()->id,
            'task_version_id' => TaskVersion::query()->firstOrFail()->id,
            'status' => 'started',
        ]);
    }
}
