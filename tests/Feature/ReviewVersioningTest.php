<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_review_uses_the_task_version_that_created_the_review(): void
    {
        Carbon::setTestNow('2026-06-17 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $task = Task::query()->firstOrFail();
        $originalVersion = TaskVersion::query()->firstOrFail();

        $attempt = $this->actingAs($user)
            ->postJson('/api/task-attempts/start', [
                'task_id' => $task->id,
                'task_version_id' => $originalVersion->id,
            ])
            ->assertOk()
            ->json('data');

        $reviewId = $this->postJson("/api/task-attempts/{$attempt['id']}/submit", [
            'answer' => ['value' => 3],
        ])
            ->assertOk()
            ->assertJsonPath('data.result', 'incorrect')
            ->json('data.review_id');

        $originalVersion->forceFill(['active' => false])->save();
        TaskVersion::query()->create([
            'task_id' => $task->id,
            'version' => 2,
            'prompt' => 'Löse 3x = 27.',
            'input_schema' => ['kind' => 'number'],
            'answer_schema' => ['correct_value' => 9, 'tolerance' => 0],
            'explanation' => 'Teile durch 3.',
            'active' => true,
        ]);

        $this->getJson("/api/reviews/{$reviewId}")
            ->assertOk()
            ->assertJsonPath('data.task.task_version_id', $originalVersion->id)
            ->assertJsonPath('data.task.prompt', 'Löse 2x + 3 = 11.')
            ->assertJsonMissingPath('data.task.answer_schema');

        $this->postJson("/api/reviews/{$reviewId}/answer", [
            'answer' => ['value' => 4],
        ])
            ->assertOk()
            ->assertJsonPath('data.result', 'correct')
            ->assertJsonPath('data.status', 'completed');
    }
}
