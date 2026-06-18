<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\LearningPathNode;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
            ->assertJsonPath('data.0.node_count', 3)
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

    public function test_learning_path_routes_require_authentication(): void
    {
        $this->seed(DatabaseSeeder::class);
        $path = LearningPath::query()->firstOrFail();

        $this->getJson('/api/learning-paths')
            ->assertUnauthorized();

        $this->getJson("/api/learning-paths/{$path->id}")
            ->assertUnauthorized();

        $this->postJson("/api/learning-paths/{$path->id}/start")
            ->assertUnauthorized();
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
            'intro_explanations' => [
                'new' => [
                    'title' => 'First Node kurz greifen.',
                    'body' => 'Du bekommst die Grundidee in einem kleinen Schritt.',
                    'usefulness' => 'Du erkennst den nächsten Schritt.',
                ],
                'rough' => [
                    'title' => 'First Node wieder sortieren.',
                    'body' => 'Du ordnest bekanntes Wissen kurz ein.',
                    'usefulness' => 'Du findest die passende Übungsstelle.',
                ],
                'confident' => [
                    'title' => 'First Node festigen.',
                    'body' => 'Du prüfst die Grundlage ohne langen Anlauf.',
                    'usefulness' => 'Du machst Wissen abrufbarer.',
                ],
            ],
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
            ->assertJsonPath('data.intro_explanations.new.title', 'First Node kurz greifen.')
            ->assertJsonPath('data.intro_explanations.rough.title', 'First Node wieder sortieren.')
            ->assertJsonPath('data.intro_explanations.confident.title', 'First Node festigen.')
            ->json();

        $this->assertLearningPathResponseContainsNoExcludedFields($response);
    }

    public function test_learning_path_detail_returns_seeded_intro_explanations_for_all_self_assessments(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->where('slug', 'algebra-foundations')->firstOrFail();

        $response = $this->actingAs($user)
            ->getJson("/api/learning-paths/{$path->id}")
            ->assertOk()
            ->assertJsonPath('data.intro_explanations.new.title', 'Lineare Gleichungen sind kleine Rückwärtsrätsel.')
            ->assertJsonPath('data.intro_explanations.rough.usefulness', 'Du rechnest sauberer und erkennst schneller, welcher Schritt als Nächstes passt.')
            ->assertJsonPath('data.intro_explanations.confident.usefulness', 'Du machst die Grundlagen automatisch genug für schwierigere Aufgaben.')
            ->json();

        $this->assertArrayHasKey('new', $response['data']['intro_explanations']);
        $this->assertArrayHasKey('rough', $response['data']['intro_explanations']);
        $this->assertArrayHasKey('confident', $response['data']['intro_explanations']);
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

    public function test_start_learning_path_rejects_inactive_or_missing_paths(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();
        $path->forceFill(['active' => false])->save();

        $this->actingAs($user)
            ->postJson("/api/learning-paths/{$path->id}/start")
            ->assertNotFound();

        $this->actingAs($user)
            ->postJson('/api/learning-paths/999999/start')
            ->assertNotFound();
    }

    public function test_start_learning_path_returns_existing_active_enrollment_and_reactivates_paused_enrollment(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();

        $existing = Enrollment::query()->create([
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'status' => 'active',
            'started_at' => Carbon::now()->subDay(),
        ]);

        $this->actingAs($user)
            ->postJson("/api/learning-paths/{$path->id}/start")
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.self_assessment', null);

        $existing->forceFill(['status' => 'paused'])->save();

        $this->actingAs($user)
            ->postJson("/api/learning-paths/{$path->id}/start")
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.self_assessment', null);

        $this->assertSame(1, Enrollment::query()
            ->where('user_id', $user->id)
            ->where('learning_path_id', $path->id)
            ->count());
        $this->assertSame('active', $existing->refresh()->status);
    }

    public function test_start_learning_path_stores_and_updates_self_assessment_per_enrollment(): void
    {
        Carbon::setTestNow('2026-06-18 09:00:00');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();

        $this->actingAs($user)
            ->postJson("/api/learning-paths/{$path->id}/start", [
                'self_assessment' => 'new',
            ])
            ->assertOk()
            ->assertJsonPath('data.learning_path_id', $path->id)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.self_assessment', 'new')
            ->assertJsonPath('data.started_at', '2026-06-18T09:00:00.000000Z');

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $user->id,
            'learning_path_id' => $path->id,
            'self_assessment' => 'new',
        ]);

        $this->actingAs($user)
            ->postJson("/api/learning-paths/{$path->id}/start", [
                'self_assessment' => 'confident',
            ])
            ->assertOk()
            ->assertJsonPath('data.self_assessment', 'confident');

        $this->assertSame(1, Enrollment::query()
            ->where('user_id', $user->id)
            ->where('learning_path_id', $path->id)
            ->count());
        $this->assertSame('confident', Enrollment::query()
            ->where('user_id', $user->id)
            ->where('learning_path_id', $path->id)
            ->firstOrFail()
            ->self_assessment);
    }

    public function test_start_learning_path_rejects_invalid_self_assessment(): void
    {
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $path = LearningPath::query()->firstOrFail();

        $this->actingAs($user)
            ->postJson("/api/learning-paths/{$path->id}/start", [
                'self_assessment' => 'expert',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('self_assessment');
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
        ] as $token) {
            $this->assertDoesNotMatchRegularExpression('/(^|[^a-z])'.preg_quote($token, '/').'([^a-z]|$)/', $json);
        }
    }
}
