<?php

namespace Tests\Unit;

use App\Services\Content\ContentValidator;
use Tests\TestCase;

class ContentValidatorTest extends TestCase
{
    public function test_valid_catalog_passes_content_validation(): void
    {
        $result = app(ContentValidator::class)->validateCatalog($this->validCatalog());

        $this->assertFalse($result->hasErrors());
    }

    public function test_rejects_duplicate_and_invalid_slugs(): void
    {
        $catalog = $this->validCatalog();
        $catalog['subjects'][] = [
            'slug' => 'german-math',
            'name' => 'Duplicate Math',
        ];
        $catalog['learning_nodes'][0]['slug'] = 'Bad Slug';

        $result = app(ContentValidator::class)->validateCatalog($catalog);

        $this->assertTrue($result->hasError('subjects.1.slug', 'duplicate'));
        $this->assertTrue($result->hasError('learning_nodes.0.slug', 'invalid_slug'));
    }

    public function test_rejects_missing_subject_and_node_references(): void
    {
        $catalog = $this->validCatalog();
        $catalog['learning_nodes'][0]['subjects'] = ['missing-subject'];
        $catalog['tasks'][0]['learning_nodes'] = ['missing-node'];

        $result = app(ContentValidator::class)->validateCatalog($catalog);

        $this->assertTrue($result->hasError('learning_nodes.0.subjects.0', 'missing_subject'));
        $this->assertTrue($result->hasError('tasks.0.learning_nodes.0', 'missing_learning_node'));
    }

    public function test_rejects_invalid_node_relations(): void
    {
        $catalog = $this->validCatalog();
        $catalog['node_relations'] = [
            ['from' => 'solve-linear-equations', 'to' => 'solve-linear-equations', 'type' => 'prerequisite'],
            ['from' => 'solve-linear-equations', 'to' => 'missing-node', 'type' => 'prerequisite'],
            ['from' => 'solve-linear-equations', 'to' => 'solve-equations-with-parentheses', 'type' => 'unlocks'],
        ];

        $result = app(ContentValidator::class)->validateCatalog($catalog);

        $this->assertTrue($result->hasError('node_relations.0.to', 'self_relation'));
        $this->assertTrue($result->hasError('node_relations.1.to', 'missing_learning_node'));
        $this->assertTrue($result->hasError('node_relations.2.type', 'unsupported_relation_type'));
    }

    public function test_rejects_invalid_learning_path_references_and_order(): void
    {
        $catalog = $this->validCatalog();
        $catalog['learning_paths'][0]['nodes'] = [
            ['slug' => 'solve-linear-equations', 'position' => 1],
            ['slug' => 'missing-node', 'position' => 1],
        ];

        $result = app(ContentValidator::class)->validateCatalog($catalog);

        $this->assertTrue($result->hasError('learning_paths.0.nodes.1.slug', 'missing_learning_node'));
        $this->assertTrue($result->hasError('learning_paths.0.nodes.1.position', 'duplicate_position'));
    }

    public function test_rejects_tasks_without_node_links_and_invalid_numeric_answer_schemas(): void
    {
        $catalog = $this->validCatalog();
        $catalog['tasks'][0]['learning_nodes'] = [];
        $catalog['tasks'][0]['versions'][0]['answer_schema'] = ['correct_value' => 'four'];

        $result = app(ContentValidator::class)->validateCatalog($catalog);

        $this->assertTrue($result->hasError('tasks.0.learning_nodes', 'required'));
        $this->assertTrue($result->hasError('tasks.0.versions.0.answer_schema.correct_value', 'numeric_required'));
        $this->assertTrue($result->hasError('tasks.0.versions.0.answer_schema.tolerance', 'numeric_required'));
    }

    public function test_rejects_missing_prompt_explanation_and_active_version_constraints(): void
    {
        $catalog = $this->validCatalog();
        $catalog['tasks'][0]['versions'][] = [
            'version' => 2,
            'prompt' => ' ',
            'answer_schema' => ['correct_value' => 4, 'tolerance' => 0],
            'explanation' => '',
            'active' => true,
        ];

        $result = app(ContentValidator::class)->validateCatalog($catalog);

        $this->assertTrue($result->hasError('tasks.0.versions', 'one_active_version_required'));
        $this->assertTrue($result->hasError('tasks.0.versions.1.prompt', 'required'));
        $this->assertTrue($result->hasError('tasks.0.versions.1.explanation', 'required'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validCatalog(): array
    {
        return [
            'subjects' => [
                [
                    'slug' => 'german-math',
                    'name' => 'German Math',
                ],
            ],
            'learning_nodes' => [
                [
                    'slug' => 'solve-linear-equations',
                    'type' => 'skill',
                    'title' => 'Lineare Gleichungen loesen',
                    'subjects' => ['german-math'],
                ],
                [
                    'slug' => 'solve-equations-with-parentheses',
                    'type' => 'skill',
                    'title' => 'Gleichungen mit Klammern loesen',
                    'subjects' => ['german-math'],
                ],
            ],
            'node_relations' => [
                [
                    'from' => 'solve-linear-equations',
                    'to' => 'solve-equations-with-parentheses',
                    'type' => 'prerequisite',
                ],
            ],
            'learning_paths' => [
                [
                    'slug' => 'algebra-foundations',
                    'title' => 'Algebra Foundations',
                    'type' => 'subject_path',
                    'subject' => 'german-math',
                    'nodes' => [
                        ['slug' => 'solve-linear-equations', 'position' => 1],
                        ['slug' => 'solve-equations-with-parentheses', 'position' => 2],
                    ],
                ],
            ],
            'tasks' => [
                [
                    'slug' => 'solve-2x-plus-3-equals-11',
                    'type' => 'numeric',
                    'learning_nodes' => ['solve-linear-equations'],
                    'versions' => [
                        [
                            'version' => 1,
                            'prompt' => 'Solve 2x + 3 = 11.',
                            'answer_schema' => ['correct_value' => 4, 'tolerance' => 0],
                            'explanation' => 'Subtract 3 and divide by 2.',
                            'active' => true,
                        ],
                    ],
                ],
            ],
        ];
    }
}
