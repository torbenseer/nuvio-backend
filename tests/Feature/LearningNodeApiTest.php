<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\NodeRelation;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskVersion;
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

    public function test_learning_node_routes_require_authentication(): void
    {
        $this->seed(DatabaseSeeder::class);
        $node = LearningNode::query()->firstOrFail();

        $this->getJson('/api/nodes')
            ->assertUnauthorized();

        $this->getJson("/api/nodes/{$node->id}")
            ->assertUnauthorized();

        $this->getJson("/api/nodes/{$node->id}/tasks")
            ->assertUnauthorized();

        $this->getJson("/api/nodes/{$node->id}/prerequisites")
            ->assertUnauthorized();
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

    public function test_learning_node_tasks_list_returns_active_tasks_for_active_node(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->where('slug', 'solve-linear-equations')->firstOrFail();
        $task = Task::query()->where('slug', 'solve-2x-plus-3-equals-11')->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}/tasks")
            ->assertOk()
            ->json();

        $this->assertSame([
            [
                'id' => $task->id,
                'type' => 'numeric',
                'difficulty' => 1,
                'estimated_minutes' => 5,
            ],
        ], $response['data']);
        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    public function test_learning_node_tasks_return_not_found_for_inactive_or_missing_nodes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $node->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}/tasks")
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/nodes/999999/tasks')
            ->assertNotFound();
    }

    public function test_learning_node_tasks_list_omits_inactive_tasks(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->where('slug', 'solve-linear-equations')->firstOrFail();
        $activeTask = Task::query()->where('slug', 'solve-2x-plus-3-equals-11')->firstOrFail();
        $inactiveTask = Task::query()->create([
            'slug' => 'inactive-node-task',
            'type' => 'numeric',
            'difficulty' => 2,
            'estimated_minutes' => 7,
            'active' => false,
        ]);

        $inactiveTask->learningNodes()->attach($node->id, ['is_primary' => true]);

        $response = $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}/tasks")
            ->assertOk()
            ->json('data');

        $this->assertSame([$activeTask->id], array_column($response, 'id'));
    }

    public function test_learning_node_tasks_do_not_expose_answers_progress_pressure_or_gamification_fields(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->where('slug', 'solve-linear-equations')->firstOrFail();
        $task = Task::query()->where('slug', 'solve-2x-plus-3-equals-11')->firstOrFail();

        TaskVersion::query()->create([
            'task_id' => $task->id,
            'version' => 2,
            'prompt' => 'Löse x + 1 = 5.',
            'input_schema' => ['kind' => 'number'],
            'answer_schema' => ['correct_value' => 4, 'tolerance' => 0],
            'explanation' => 'Ziehe 1 ab.',
            'active' => false,
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}/tasks")
            ->assertOk()
            ->assertJsonMissingPath('data.0.slug')
            ->assertJsonMissingPath('data.0.prompt')
            ->assertJsonMissingPath('data.0.input')
            ->assertJsonMissingPath('data.0.answer')
            ->assertJsonMissingPath('data.0.answer_schema')
            ->assertJsonMissingPath('data.0.accepted_values')
            ->assertJsonMissingPath('data.0.tolerance')
            ->assertJsonMissingPath('data.0.correct_value')
            ->assertJsonMissingPath('data.0.explanation')
            ->assertJsonMissingPath('data.0.task_version_id')
            ->assertJsonMissingPath('data.0.progress')
            ->assertJsonMissingPath('data.0.pressure')
            ->assertJsonMissingPath('data.0.xp')
            ->assertJsonMissingPath('data.0.badge')
            ->assertJsonMissingPath('data.0.achievement')
            ->assertJsonMissingPath('data.0.streak')
            ->assertJsonMissingPath('data.0.reward_level')
            ->json();

        $this->assertSame([
            'id',
            'type',
            'difficulty',
            'estimated_minutes',
        ], array_keys($response['data'][0]));
        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    public function test_learning_node_prerequisites_list_returns_active_prerequisite_relations(): void
    {
        $user = User::factory()->create();
        $prerequisite = LearningNode::query()->create([
            'slug' => 'evaluate-expressions',
            'type' => 'skill',
            'title' => 'Terme auswerten',
            'description' => null,
            'active' => true,
        ]);
        $target = LearningNode::query()->create([
            'slug' => 'solve-equations',
            'type' => 'skill',
            'title' => 'Gleichungen lösen',
            'description' => null,
            'active' => true,
        ]);
        $inactivePrerequisite = LearningNode::query()->create([
            'slug' => 'inactive-prerequisite',
            'type' => 'skill',
            'title' => 'Inactive Prerequisite',
            'description' => null,
            'active' => false,
        ]);

        NodeRelation::query()->create([
            'source_node_id' => $prerequisite->id,
            'target_node_id' => $target->id,
            'type' => 'prerequisite',
        ]);
        NodeRelation::query()->create([
            'source_node_id' => $inactivePrerequisite->id,
            'target_node_id' => $target->id,
            'type' => 'prerequisite',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/nodes/{$target->id}/prerequisites")
            ->assertOk()
            ->json();

        $this->assertSame([
            [
                'id' => $prerequisite->id,
                'title' => 'Terme auswerten',
                'relation' => 'prerequisite',
            ],
        ], $response['data']);
        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    public function test_learning_node_prerequisites_return_not_found_for_inactive_or_missing_nodes(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $node = LearningNode::query()->firstOrFail();
        $node->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->getJson("/api/nodes/{$node->id}/prerequisites")
            ->assertNotFound();

        $this->actingAs($user)
            ->getJson('/api/nodes/999999/prerequisites')
            ->assertNotFound();
    }

    public function test_learning_node_prerequisites_response_uses_prerequisite_shape_only(): void
    {
        $user = User::factory()->create();
        $prerequisite = LearningNode::query()->create([
            'slug' => 'arithmetic-foundations',
            'type' => 'skill',
            'title' => 'Rechnen mit Grundoperationen',
            'description' => 'Diese Beschreibung bleibt aus der Prerequisite-Liste heraus.',
            'active' => true,
        ]);
        $target = LearningNode::query()->create([
            'slug' => 'linear-equations',
            'type' => 'skill',
            'title' => 'Lineare Gleichungen',
            'description' => null,
            'active' => true,
        ]);

        NodeRelation::query()->create([
            'source_node_id' => $prerequisite->id,
            'target_node_id' => $target->id,
            'type' => 'prerequisite',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/nodes/{$target->id}/prerequisites")
            ->assertOk()
            ->assertJsonMissingPath('data.0.slug')
            ->assertJsonMissingPath('data.0.type')
            ->assertJsonMissingPath('data.0.description')
            ->assertJsonMissingPath('data.0.progress')
            ->assertJsonMissingPath('data.0.mastery')
            ->assertJsonMissingPath('data.0.pressure')
            ->assertJsonMissingPath('data.0.xp')
            ->assertJsonMissingPath('data.0.badge')
            ->assertJsonMissingPath('data.0.achievement')
            ->assertJsonMissingPath('data.0.streak')
            ->assertJsonMissingPath('data.0.reward_level')
            ->json();

        $this->assertSame([
            'id',
            'title',
            'relation',
        ], array_keys($response['data'][0]));
        $this->assertLearningNodeResponseContainsNoExcludedFields($response);
    }

    private function assertLearningNodeResponseContainsNoExcludedFields(array $response): void
    {
        $json = strtolower((string) json_encode($response));

        foreach ([
            'answer',
            'accepted_value',
            'correct_value',
            'canonical_solution',
            'correctness',
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
