<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\Review;
use App\Models\Task;
use App\Models\TaskAttempt;
use App\Models\TaskVersion;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuthenticatedApiMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_learner_api_routes_reject_unauthenticated_requests(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $owner = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();
        $node = LearningNode::query()->firstOrFail();
        $task = Task::query()->firstOrFail();
        $version = TaskVersion::query()->firstOrFail();
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
            'task_version_id' => $version->id,
            'status' => 'scheduled',
            'due_at' => Carbon::now(),
            'interval_days' => 1,
        ]);

        $routes = [
            ['GET', '/api/user'],
            ['PUT', '/api/user/preferences', ['locale' => 'de', 'timezone' => 'Europe/Berlin']],
            ['GET', '/api/today'],
            ['POST', '/api/today/mode', ['mode' => 'yellow']],
            ['GET', '/api/learning-paths'],
            ['GET', "/api/learning-paths/{$path->id}"],
            ['POST', "/api/learning-paths/{$path->id}/start"],
            ['GET', '/api/nodes'],
            ['GET', "/api/nodes/{$node->id}"],
            ['GET', "/api/nodes/{$node->id}/tasks"],
            ['GET', "/api/nodes/{$node->id}/prerequisites"],
            ['GET', "/api/tasks/{$task->id}"],
            ['POST', '/api/task-attempts/start', [
                'task_id' => $task->id,
                'task_version_id' => $version->id,
            ]],
            ['POST', "/api/task-attempts/{$attempt->id}/submit", ['answer' => ['value' => 4]]],
            ['GET', '/api/reviews/due'],
            ['GET', "/api/reviews/{$review->id}"],
            ['POST', "/api/reviews/{$review->id}/snooze", ['minutes' => 60]],
            ['POST', "/api/reviews/{$review->id}/answer", ['answer' => ['value' => 4]]],
            ['GET', '/api/progress/summary'],
            ['GET', "/api/progress/paths/{$path->id}"],
        ];

        foreach ($routes as $route) {
            [$method, $uri] = $route;
            $payload = $route[2] ?? [];

            $response = match ($method) {
                'GET' => $this->getJson($uri),
                'POST' => $this->postJson($uri, $payload),
                'PUT' => $this->putJson($uri, $payload),
            };

            $response->assertUnauthorized();
        }
    }
}
