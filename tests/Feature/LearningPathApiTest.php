<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\LearningPathNode;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningPathApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_paths_list_returns_active_paths_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $subject = Subject::query()->firstOrFail();

        LearningPath::query()->create([
            'subject_id' => $subject->id,
            'slug' => 'inactive-algebra-path',
            'title' => 'Inactive Algebra Path',
            'type' => 'subject_path',
            'estimated_minutes' => 15,
            'active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/learning-paths')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'algebra-foundations')
            ->assertJsonPath('data.0.subject', 'German Math')
            ->assertJsonPath('data.0.node_count', 1)
            ->json();

        $this->assertLearningPathResponseContainsNoExcludedFields($response);
    }

    public function test_learning_paths_list_supports_active_subject_filter(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $science = Subject::query()->create([
            'slug' => 'science',
            'name' => 'Science',
            'description' => 'Science foundations.',
            'active' => true,
        ]);

        LearningPath::query()->create([
            'subject_id' => $science->id,
            'slug' => 'science-foundations',
            'title' => 'Science Foundations',
            'type' => 'subject_path',
            'estimated_minutes' => 10,
            'active' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/learning-paths?subject=german-math')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'algebra-foundations')
            ->json();

        $this->assertLearningPathResponseContainsNoExcludedFields($response);
    }

    public function test_learning_paths_subject_filter_must_reference_active_subject(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        Subject::query()->create([
            'slug' => 'inactive-subject',
            'name' => 'Inactive Subject',
            'description' => null,
            'active' => false,
        ]);

        $this->actingAs($user)
            ->getJson('/api/learning-paths?subject=inactive-subject')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');

        $this->actingAs($user)
            ->getJson('/api/learning-paths?subject=missing-subject')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');
    }

    public function test_learning_path_detail_returns_ordered_nodes(): void
    {
        $user = User::factory()->create();
        $subject = Subject::query()->create([
            'slug' => 'math',
            'name' => 'Math',
            'description' => null,
            'active' => true,
        ]);
        $path = LearningPath::query()->create([
            'subject_id' => $subject->id,
            'slug' => 'ordered-path',
            'title' => 'Ordered Path',
            'type' => 'subject_path',
            'estimated_minutes' => 20,
            'active' => true,
        ]);
        $firstNode = LearningNode::query()->create([
            'slug' => 'first-node',
            'type' => 'skill',
            'title' => 'First Node',
            'description' => null,
            'active' => true,
        ]);
        $secondNode = LearningNode::query()->create([
            'slug' => 'second-node',
            'type' => 'skill',
            'title' => 'Second Node',
            'description' => null,
            'active' => true,
        ]);

        LearningPathNode::query()->create([
            'learning_path_id' => $path->id,
            'learning_node_id' => $secondNode->id,
            'position' => 2,
        ]);
        LearningPathNode::query()->create([
            'learning_path_id' => $path->id,
            'learning_node_id' => $firstNode->id,
            'position' => 1,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/learning-paths/{$path->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $path->id)
            ->assertJsonPath('data.title', 'Ordered Path')
            ->assertJsonPath('data.nodes.0.id', $firstNode->id)
            ->assertJsonPath('data.nodes.0.position', 1)
            ->assertJsonPath('data.nodes.1.id', $secondNode->id)
            ->assertJsonPath('data.nodes.1.position', 2)
            ->json();

        $this->assertLearningPathResponseContainsNoExcludedFields($response);
    }

    public function test_inactive_or_missing_learning_path_details_return_not_found(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();
        $path->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->getJson("/api/learning-paths/{$path->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/learning-paths/999999')
            ->assertNotFound();
    }

    private function assertLearningPathResponseContainsNoExcludedFields(array $response): void
    {
        $json = strtolower((string) json_encode($response));

        foreach ([
            'progress',
            'pressure',
            'xp',
            'badge',
            'achievement',
            'streak',
            'rank',
            'reward_level',
            'catch_up',
            'debt',
            'mastery_score',
            'percent_complete',
            'overdue_count',
            'lost_progress',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
    }
}
