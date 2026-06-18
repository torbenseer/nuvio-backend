<?php

namespace App\Console\Commands;

use App\Models\LearningNode;
use App\Models\LearningPath;
use App\Models\NodeRelation;
use App\Models\Subject;
use App\Models\Task;
use App\Services\Content\ContentValidator;
use Illuminate\Console\Command;

class ValidateContentCommand extends Command
{
    protected $signature = 'nuvio:content:validate';

    protected $description = 'Validate Nuvio seed content without importing or mutating it.';

    public function handle(ContentValidator $validator): int
    {
        $result = $validator->validateCatalog($this->catalogFromDatabase());

        if (! $result->hasErrors()) {
            $this->info('Content validation passed.');

            return self::SUCCESS;
        }

        $this->error('Content validation failed.');

        foreach ($result->errors() as $error) {
            $this->line(sprintf(
                '- %s [%s]: %s',
                $error['field'],
                $error['code'],
                $error['message'],
            ));
        }

        return self::FAILURE;
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogFromDatabase(): array
    {
        return [
            'subjects' => Subject::query()
                ->orderBy('slug')
                ->get()
                ->map(fn (Subject $subject): array => [
                    'slug' => $subject->slug,
                    'name' => $subject->name,
                ])
                ->all(),
            'learning_nodes' => LearningNode::query()
                ->with('subjects')
                ->orderBy('slug')
                ->get()
                ->map(fn (LearningNode $node): array => [
                    'slug' => $node->slug,
                    'type' => $node->type,
                    'title' => $node->title,
                    'subjects' => $node->subjects
                        ->pluck('slug')
                        ->values()
                        ->all(),
                ])
                ->all(),
            'node_relations' => NodeRelation::query()
                ->with(['sourceNode', 'targetNode'])
                ->orderBy('id')
                ->get()
                ->map(fn (NodeRelation $relation): array => [
                    'from' => $relation->sourceNode?->slug,
                    'to' => $relation->targetNode?->slug,
                    'type' => $relation->type,
                ])
                ->all(),
            'learning_paths' => LearningPath::query()
                ->with(['subject', 'pathNodes.learningNode'])
                ->orderBy('slug')
                ->get()
                ->map(fn (LearningPath $path): array => [
                    'slug' => $path->slug,
                    'title' => $path->title,
                    'type' => $path->type,
                    'subject' => $path->subject?->slug,
                    'nodes' => $path->pathNodes
                        ->sortBy('position')
                        ->map(fn ($pathNode): array => [
                            'slug' => $pathNode->learningNode?->slug,
                            'position' => $pathNode->position,
                        ])
                        ->values()
                        ->all(),
                ])
                ->all(),
            'tasks' => Task::query()
                ->with(['learningNodes', 'versions'])
                ->orderBy('slug')
                ->get()
                ->map(fn (Task $task): array => [
                    'slug' => $task->slug,
                    'type' => $task->type,
                    'learning_nodes' => $task->learningNodes
                        ->pluck('slug')
                        ->values()
                        ->all(),
                    'versions' => $task->versions
                        ->sortBy('version')
                        ->map(fn ($version): array => [
                            'version' => $version->version,
                            'prompt' => $version->prompt,
                            'answer_schema' => $version->answer_schema,
                            'explanation' => $version->explanation,
                            'active' => $version->active,
                        ])
                        ->values()
                        ->all(),
                ])
                ->all(),
        ];
    }
}
