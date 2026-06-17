<?php

namespace Database\Seeders;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\LearningPathNode;
use App\Models\Subject;
use App\Models\Task;
use App\Models\TaskVersion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'locale' => 'de',
            'timezone' => 'Europe/Berlin',
        ]);

        $subject = Subject::query()->updateOrCreate(
            ['slug' => 'german-math'],
            [
                'name' => 'German Math',
                'description' => 'Deutsche Mathematik-Grundlagen.',
                'active' => true,
            ],
        );

        $node = LearningNode::query()->updateOrCreate(
            ['slug' => 'solve-linear-equations'],
            [
                'type' => 'skill',
                'title' => 'Lineare Gleichungen lösen',
                'description' => 'Einfache lineare Gleichungen mit einer Variablen lösen.',
                'active' => true,
            ],
        );

        $node->subjects()->syncWithoutDetaching([$subject->id]);

        $path = LearningPath::query()->updateOrCreate(
            ['slug' => 'algebra-foundations'],
            [
                'subject_id' => $subject->id,
                'title' => 'Algebra Foundations',
                'type' => 'subject_path',
                'estimated_minutes' => 5,
                'active' => true,
            ],
        );

        LearningPathNode::query()->updateOrCreate(
            [
                'learning_path_id' => $path->id,
                'learning_node_id' => $node->id,
            ],
            ['position' => 1],
        );

        $task = Task::query()->updateOrCreate(
            ['slug' => 'solve-2x-plus-3-equals-11'],
            [
                'type' => 'numeric',
                'difficulty' => 1,
                'estimated_minutes' => 5,
                'active' => true,
            ],
        );

        $task->learningNodes()->syncWithoutDetaching([
            $node->id => ['is_primary' => true],
        ]);

        TaskVersion::query()->updateOrCreate(
            [
                'task_id' => $task->id,
                'version' => 1,
            ],
            [
                'prompt' => 'Löse 2x + 3 = 11.',
                'input_schema' => ['kind' => 'number'],
                'answer_schema' => ['correct_value' => 4, 'tolerance' => 0],
                'explanation' => 'Ziehe zuerst 3 ab und teile dann durch 2.',
                'active' => true,
            ],
        );
    }
}
