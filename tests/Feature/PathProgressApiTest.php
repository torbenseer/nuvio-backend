<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\LearningPathNode;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\Subject;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PathProgressApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_path_progress_returns_ordered_node_statuses_and_counts(): void
    {
        $user = User::factory()->create();
        [$path, $firstNode, $secondNode, $thirdNode] = $this->createPathWithThreeNodes();
        $task = Task::query()->create([
            'slug' => 'progress-task',
            'type' => 'numeric',
            'difficulty' => 1,
            'estimated_minutes' => 5,
            'active' => true,
        ]);

        MasteryState::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $firstNode->id,
            'status' => 'retained',
        ]);
        MasteryState::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $secondNode->id,
            'status' => 'practiced',
        ]);
        Review::query()->create([
            'user_id' => $user->id,
            'learning_node_id' => $secondNode->id,
            'task_id' => $task->id,
            'status' => 'scheduled',
            'due_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/progress/paths/{$path->id}")
            ->assertOk()
            ->assertJsonPath('data.learning_path_id', $path->id)
            ->assertJsonPath('data.title', 'Progress Path')
            ->assertJsonPath('data.node_counts.unknown', 1)
            ->assertJsonPath('data.node_counts.practiced', 0)
            ->assertJsonPath('data.node_counts.review_due', 1)
            ->assertJsonPath('data.node_counts.retained', 1)
            ->assertJsonPath('data.nodes.0.id', $firstNode->id)
            ->assertJsonPath('data.nodes.0.status', 'retained')
            ->assertJsonPath('data.nodes.1.id', $secondNode->id)
            ->assertJsonPath('data.nodes.1.status', 'review_due')
            ->assertJsonPath('data.nodes.2.id', $thirdNode->id)
            ->assertJsonPath('data.nodes.2.status', 'unknown')
            ->json();

        $this->assertPathProgressResponseContainsNoExcludedFields($response);
    }

    public function test_path_progress_isolated_to_authenticated_user_state(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        [$path, $node] = $this->createPathWithThreeNodes();
        $task = Task::query()->create([
            'slug' => 'other-user-progress-task',
            'type' => 'numeric',
            'difficulty' => 1,
            'estimated_minutes' => 5,
            'active' => true,
        ]);

        MasteryState::query()->create([
            'user_id' => $other->id,
            'learning_node_id' => $node->id,
            'status' => 'retained',
        ]);
        Review::query()->create([
            'user_id' => $other->id,
            'learning_node_id' => $node->id,
            'task_id' => $task->id,
            'status' => 'scheduled',
            'due_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson("/api/progress/paths/{$path->id}")
            ->assertOk()
            ->assertJsonPath('data.node_counts.unknown', 3)
            ->assertJsonPath('data.node_counts.review_due', 0)
            ->assertJsonPath('data.node_counts.retained', 0)
            ->assertJsonPath('data.nodes.0.status', 'unknown');
    }

    public function test_path_progress_returns_not_found_for_inactive_or_missing_paths(): void
    {
        [$path] = $this->createPathWithThreeNodes();
        $user = User::factory()->create();
        $path->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->getJson("/api/progress/paths/{$path->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/progress/paths/999999')
            ->assertNotFound();
    }

    public function test_path_progress_requires_authentication(): void
    {
        [$path] = $this->createPathWithThreeNodes();

        $this->getJson("/api/progress/paths/{$path->id}")
            ->assertUnauthorized();
    }

    public function test_path_progress_omits_inactive_nodes(): void
    {
        $user = User::factory()->create();
        [$path, $activeNode, $inactiveNode, $thirdNode] = $this->createPathWithThreeNodes();
        $inactiveNode->forceFill(['active' => false])->save();

        $response = $this->actingAs($user)
            ->getJson("/api/progress/paths/{$path->id}")
            ->assertOk()
            ->json('data.nodes');

        $this->assertSame([$activeNode->id, $thirdNode->id], array_column($response, 'id'));
    }

    private function createPathWithThreeNodes(): array
    {
        $subject = Subject::query()->create([
            'slug' => 'math',
            'name' => 'Math',
            'description' => null,
            'active' => true,
        ]);
        $path = LearningPath::query()->create([
            'subject_id' => $subject->id,
            'slug' => 'progress-path',
            'title' => 'Progress Path',
            'type' => 'subject_path',
            'estimated_minutes' => 20,
            'active' => true,
        ]);
        $firstNode = LearningNode::query()->create([
            'slug' => 'first-progress-node',
            'type' => 'skill',
            'title' => 'First Progress Node',
            'description' => null,
            'active' => true,
        ]);
        $secondNode = LearningNode::query()->create([
            'slug' => 'second-progress-node',
            'type' => 'skill',
            'title' => 'Second Progress Node',
            'description' => null,
            'active' => true,
        ]);
        $thirdNode = LearningNode::query()->create([
            'slug' => 'third-progress-node',
            'type' => 'skill',
            'title' => 'Third Progress Node',
            'description' => null,
            'active' => true,
        ]);

        foreach ([$firstNode, $secondNode, $thirdNode] as $index => $node) {
            LearningPathNode::query()->create([
                'learning_path_id' => $path->id,
                'learning_node_id' => $node->id,
                'position' => $index + 1,
            ]);
        }

        return [$path, $firstNode, $secondNode, $thirdNode];
    }

    private function assertPathProgressResponseContainsNoExcludedFields(array $response): void
    {
        $json = strtolower((string) json_encode($response));

        foreach ([
            'percent_complete',
            'mastery_score',
            'xp',
            'badge',
            'achievement',
            'streak',
            'rank',
            'reward_level',
            'catch_up',
            'debt',
            'overdue_count',
            'lost_progress',
            'collection',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
    }
}
