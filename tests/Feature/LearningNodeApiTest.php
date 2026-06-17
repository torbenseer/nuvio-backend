<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LearningNodeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_nodes_list_returns_active_nodes_only(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        LearningNode::query()->create([
            'slug' => 'inactive-skill',
            'type' => 'skill',
            'title' => 'Inactive Skill',
            'description' => null,
            'active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/nodes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'solve-linear-equations')
            ->assertJsonPath('data.0.type', 'skill')
            ->assertJsonPath('data.0.title', 'Lineare Gleichungen lösen')
            ->json();

        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    public function test_learning_nodes_list_supports_skill_type_filter(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/nodes?type=skill')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'solve-linear-equations')
            ->assertJsonPath('data.0.type', 'skill')
            ->json();

        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    public function test_learning_nodes_list_supports_active_subject_filter(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $science = Subject::query()->create([
            'slug' => 'science',
            'name' => 'Science',
            'description' => 'Science foundations.',
            'active' => true,
        ]);
        $scienceNode = LearningNode::query()->create([
            'slug' => 'science-skill',
            'type' => 'skill',
            'title' => 'Science Skill',
            'description' => null,
            'active' => true,
        ]);

        $scienceNode->subjects()->attach($science);

        $response = $this->actingAs($user)
            ->getJson('/api/nodes?subject=german-math')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'solve-linear-equations')
            ->json();

        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    public function test_learning_nodes_type_filter_must_be_supported(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/nodes?type=concept')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_learning_nodes_subject_filter_must_reference_active_subject(): void
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
            ->getJson('/api/nodes?subject=inactive-subject')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');

        $this->actingAs($user)
            ->getJson('/api/nodes?subject=missing-subject')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');
    }

    public function test_inactive_or_missing_learning_node_details_return_not_found(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $node->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}")
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/nodes/999999')
            ->assertNotFound();
    }

    public function test_learning_node_detail_includes_subject_memberships(): void
    {
        $user = User::factory()->create();
        $math = Subject::query()->create([
            'slug' => 'math',
            'name' => 'Math',
            'description' => null,
            'active' => true,
        ]);
        $science = Subject::query()->create([
            'slug' => 'science',
            'name' => 'Science',
            'description' => null,
            'active' => true,
        ]);
        $node = LearningNode::query()->create([
            'slug' => 'multi-subject-skill',
            'type' => 'skill',
            'title' => 'Multi Subject Skill',
            'description' => 'Shared by multiple subjects.',
            'active' => true,
        ]);

        $node->subjects()->attach([$science->id, $math->id]);

        $response = $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $node->id)
            ->assertJsonPath('data.slug', 'multi-subject-skill')
            ->assertJsonPath('data.type', 'skill')
            ->assertJsonPath('data.description', 'Shared by multiple subjects.')
            ->assertJsonPath('data.subjects.0', 'Math')
            ->assertJsonPath('data.subjects.1', 'Science')
            ->json();

        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    private function assertLearningNodeResponseContainsNoExcludedFields(array $response): void
    {
        $json = strtolower((string) json_encode($response));

        foreach ([
            'answer',
            'correct_value',
            'tolerance',
            'explanation',
            'task_version',
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
