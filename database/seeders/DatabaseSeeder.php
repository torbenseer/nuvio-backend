<?php

namespace Database\Seeders;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\LearningPathNode;
use App\Models\NodeRelation;
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

        $linearEquations = LearningNode::query()->updateOrCreate(
            ['slug' => 'solve-linear-equations'],
            [
                'type' => 'skill',
                'title' => 'Lineare Gleichungen lösen',
                'description' => 'Einfache lineare Gleichungen mit einer Variablen lösen.',
                'active' => true,
            ],
        );

        $parenthesesEquations = LearningNode::query()->updateOrCreate(
            ['slug' => 'solve-equations-with-parentheses'],
            [
                'type' => 'skill',
                'title' => 'Gleichungen mit Klammern lösen',
                'description' => 'Lineare Gleichungen mit einfachen Klammern sicher umformen.',
                'active' => true,
            ],
        );

        $wordProblemEquations = LearningNode::query()->updateOrCreate(
            ['slug' => 'model-linear-equations-from-text'],
            [
                'type' => 'skill',
                'title' => 'Lineare Gleichungen aus Texten aufstellen',
                'description' => 'Kurze Alltagssituationen als lineare Gleichung modellieren.',
                'active' => true,
            ],
        );

        foreach ([$linearEquations, $parenthesesEquations, $wordProblemEquations] as $node) {
            $node->subjects()->syncWithoutDetaching([$subject->id]);
        }

        $path = LearningPath::query()->updateOrCreate(
            ['slug' => 'algebra-foundations'],
            [
                'subject_id' => $subject->id,
                'title' => 'Algebra Foundations',
                'type' => 'subject_path',
                'estimated_minutes' => 15,
                'intro_explanations' => [
                    'new' => [
                        'title' => 'Lineare Gleichungen sind kleine Rückwärtsrätsel.',
                        'body' => 'Du suchst die Zahl, die eine Aussage wahr macht: 2x + 3 = 11. Erst entfernst du, was x stört, dann bleibt x allein.',
                        'usefulness' => 'Du kannst unbekannte Werte ausrechnen, statt sie zu raten.',
                    ],
                    'rough' => [
                        'title' => 'Du kennst die Idee wahrscheinlich schon: beide Seiten bleiben im Gleichgewicht.',
                        'body' => 'Der Trick ist, jeden Schritt auf beiden Seiten gleich zu machen. So wird aus einer vollen Gleichung nach und nach x = ...',
                        'usefulness' => 'Du rechnest sauberer und erkennst schneller, welcher Schritt als Nächstes passt.',
                    ],
                    'confident' => [
                        'title' => 'Hier geht es nicht um die Regel, sondern um Tempo und Sicherheit.',
                        'body' => 'Du prüfst, ob du Gleichungen ohne Umweg umformen kannst und wo Klammern oder Textaufgaben dich kurz ausbremsen.',
                        'usefulness' => 'Du machst die Grundlagen automatisch genug für schwierigere Aufgaben.',
                    ],
                ],
                'active' => true,
            ],
        );

        foreach ([
            [$linearEquations, 1],
            [$parenthesesEquations, 2],
            [$wordProblemEquations, 3],
        ] as [$node, $position]) {
            LearningPathNode::query()->updateOrCreate(
                [
                    'learning_path_id' => $path->id,
                    'learning_node_id' => $node->id,
                ],
                ['position' => $position],
            );
        }

        foreach ([
            [$linearEquations, $parenthesesEquations],
            [$parenthesesEquations, $wordProblemEquations],
        ] as [$source, $target]) {
            NodeRelation::query()->updateOrCreate(
                [
                    'source_node_id' => $source->id,
                    'target_node_id' => $target->id,
                    'type' => 'prerequisite',
                ],
            );
        }

        foreach ([
            [
                'node' => $linearEquations,
                'slug' => 'solve-2x-plus-3-equals-11',
                'difficulty' => 1,
                'prompt' => 'Löse 2x + 3 = 11.',
                'correct_value' => 4,
                'explanation' => 'Ziehe zuerst 3 ab und teile dann durch 2.',
            ],
            [
                'node' => $linearEquations,
                'slug' => 'solve-x-minus-5-equals-7',
                'difficulty' => 1,
                'prompt' => 'Löse x - 5 = 7.',
                'correct_value' => 12,
                'explanation' => 'Addiere 5 auf beiden Seiten.',
            ],
            [
                'node' => $parenthesesEquations,
                'slug' => 'solve-3-times-x-plus-2-equals-15',
                'difficulty' => 2,
                'prompt' => 'Löse 3(x + 2) = 15.',
                'correct_value' => 3,
                'explanation' => 'Teile zuerst durch 3 und ziehe dann 2 ab.',
            ],
            [
                'node' => $parenthesesEquations,
                'slug' => 'solve-4x-minus-2x-plus-6-equals-18',
                'difficulty' => 2,
                'prompt' => 'Löse 4x - 2x + 6 = 18.',
                'correct_value' => 6,
                'explanation' => 'Fasse zuerst 4x - 2x zu 2x zusammen.',
            ],
            [
                'node' => $wordProblemEquations,
                'slug' => 'model-ticket-price-total-18',
                'difficulty' => 2,
                'prompt' => 'Ein Ticket kostet x Euro. Drei Tickets kosten zusammen 18 Euro. Wie groß ist x?',
                'correct_value' => 6,
                'explanation' => 'Die Situation passt zu 3x = 18, also x = 6.',
            ],
            [
                'node' => $wordProblemEquations,
                'slug' => 'model-savings-after-fee',
                'difficulty' => 3,
                'prompt' => 'Nach einer Gebühr von 4 Euro bleiben 16 Euro übrig. Wie viel Geld war vorher da?',
                'correct_value' => 20,
                'explanation' => 'Wenn x - 4 = 16 gilt, addierst du 4 und erhältst x = 20.',
            ],
        ] as $definition) {
            $this->seedNumericTask($definition);
        }
    }

    /**
     * @param array{node: LearningNode, slug: string, difficulty: int, prompt: string, correct_value: int|float, explanation: string} $definition
     */
    private function seedNumericTask(array $definition): void
    {
        $task = Task::query()->updateOrCreate(
            ['slug' => $definition['slug']],
            [
                'type' => 'numeric',
                'difficulty' => $definition['difficulty'],
                'estimated_minutes' => 5,
                'active' => true,
            ],
        );

        $task->learningNodes()->syncWithoutDetaching([
            $definition['node']->id => ['is_primary' => true],
        ]);

        TaskVersion::query()->updateOrCreate(
            [
                'task_id' => $task->id,
                'version' => 1,
            ],
            [
                'prompt' => $definition['prompt'],
                'input_schema' => ['kind' => 'number'],
                'answer_schema' => ['correct_value' => $definition['correct_value'], 'tolerance' => 0],
                'explanation' => $definition['explanation'],
                'active' => true,
            ],
        );
    }
}
