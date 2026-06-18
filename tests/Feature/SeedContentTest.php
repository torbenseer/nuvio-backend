<?php

namespace Tests\Feature;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\NodeRelation;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskVersion;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_algebra_foundations_seed_has_b4_breadth_and_prerequisites(): void
    {
        $this->seed(DatabaseSeeder::class);

        $subject = Subject::query()->where('slug', 'german-math')->firstOrFail();
        $path = LearningPath::query()
            ->where('slug', 'algebra-foundations')
            ->with('pathNodes.learningNode.tasks.versions')
            ->firstOrFail();

        $this->assertSame($subject->id, $path->subject_id);
        $this->assertSame(15, $path->estimated_minutes);
        $this->assertCount(3, $path->pathNodes);

        $orderedSlugs = $path->pathNodes
            ->map(fn ($pathNode): string => $pathNode->learningNode->slug)
            ->all();

        $this->assertSame([
            'solve-linear-equations',
            'solve-equations-with-parentheses',
            'model-linear-equations-from-text',
        ], $orderedSlugs);

        foreach ($path->pathNodes as $pathNode) {
            $node = $pathNode->learningNode;

            $this->assertSame('skill', $node->type);
            $this->assertTrue($node->active);
            $this->assertTrue($node->subjects()->whereKey($subject->id)->exists());
            $this->assertGreaterThanOrEqual(2, $node->tasks()->where('tasks.active', true)->count());
        }

        $this->assertDatabaseHas('node_relations', [
            'source_node_id' => LearningNode::query()->where('slug', 'solve-linear-equations')->value('id'),
            'target_node_id' => LearningNode::query()->where('slug', 'solve-equations-with-parentheses')->value('id'),
            'type' => 'prerequisite',
        ]);
        $this->assertDatabaseHas('node_relations', [
            'source_node_id' => LearningNode::query()->where('slug', 'solve-equations-with-parentheses')->value('id'),
            'target_node_id' => LearningNode::query()->where('slug', 'model-linear-equations-from-text')->value('id'),
            'type' => 'prerequisite',
        ]);
        $this->assertSame(2, NodeRelation::query()->where('type', 'prerequisite')->count());
    }

    public function test_seeded_tasks_have_valid_active_numeric_versions_and_node_links(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tasks = Task::query()->with(['learningNodes', 'activeVersion'])->where('active', true)->get();

        $this->assertCount(6, $tasks);

        foreach ($tasks as $task) {
            $this->assertSame('numeric', $task->type);
            $this->assertGreaterThanOrEqual(1, $task->difficulty);
            $this->assertGreaterThanOrEqual(1, $task->learningNodes->count());
            $this->assertNotNull($task->activeVersion);

            $version = $task->activeVersion;

            $this->assertSame(['kind' => 'number'], $version->input_schema);
            $this->assertArrayHasKey('correct_value', $version->answer_schema);
            $this->assertArrayHasKey('tolerance', $version->answer_schema);
            $this->assertTrue(is_numeric($version->answer_schema['correct_value']));
            $this->assertTrue(is_numeric($version->answer_schema['tolerance']));
            $this->assertNotSame('', trim($version->prompt));
            $this->assertNotSame('', trim($version->explanation));
            $this->assertSame(1, TaskVersion::query()->where('task_id', $task->id)->where('active', true)->count());
        }
    }
}
