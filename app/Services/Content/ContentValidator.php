<?php

namespace App\Services\Content;

class ContentValidator
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * @param  array<string, mixed>  $catalog
     */
    public function validateCatalog(array $catalog): ContentValidationResult
    {
        $errors = [];

        $subjects = $this->records($catalog, 'subjects');
        $nodes = $this->records($catalog, 'learning_nodes');
        $relations = $this->records($catalog, 'node_relations');
        $paths = $this->records($catalog, 'learning_paths');
        $tasks = $this->records($catalog, 'tasks');

        $subjectSlugs = $this->validateSluggedRecords($subjects, 'subjects', $errors);
        $nodeSlugs = $this->validateSluggedRecords($nodes, 'learning_nodes', $errors);
        $this->validateSluggedRecords($paths, 'learning_paths', $errors);
        $this->validateSluggedRecords($tasks, 'tasks', $errors);

        $this->validateLearningNodes($nodes, $subjectSlugs, $errors);
        $this->validateNodeRelations($relations, $nodeSlugs, $errors);
        $this->validateLearningPaths($paths, $subjectSlugs, $nodeSlugs, $errors);
        $this->validateTasks($tasks, $nodeSlugs, $errors);

        return new ContentValidationResult($errors);
    }

    /**
     * @param  array<string, mixed>  $catalog
     * @return list<array<string, mixed>>
     */
    private function records(array $catalog, string $key): array
    {
        $records = $catalog[$key] ?? [];

        if (! is_array($records)) {
            return [];
        }

        return array_values(array_filter($records, 'is_array'));
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @param  list<array{field: string, code: string, message: string}>  $errors
     * @return array<string, true>
     */
    private function validateSluggedRecords(array $records, string $type, array &$errors): array
    {
        $seen = [];
        $validSlugs = [];

        foreach ($records as $index => $record) {
            $field = "{$type}.{$index}.slug";
            $slug = $record['slug'] ?? null;

            if (! is_string($slug) || trim($slug) === '') {
                $this->addError($errors, $field, 'required', 'Slug is required.');

                continue;
            }

            if (! preg_match(self::SLUG_PATTERN, $slug)) {
                $this->addError($errors, $field, 'invalid_slug', 'Slug must use lowercase letters, numbers, and hyphens.');

                continue;
            }

            if (isset($seen[$slug])) {
                $this->addError($errors, $field, 'duplicate', 'Slug must be unique in this content type.');

                continue;
            }

            $seen[$slug] = true;
            $validSlugs[$slug] = true;
        }

        return $validSlugs;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  array<string, true>  $subjectSlugs
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function validateLearningNodes(array $nodes, array $subjectSlugs, array &$errors): void
    {
        foreach ($nodes as $index => $node) {
            $subjects = $node['subjects'] ?? [];

            if (! is_array($subjects) || $subjects === []) {
                $this->addError($errors, "learning_nodes.{$index}.subjects", 'required', 'LearningNode must reference at least one Subject.');

                continue;
            }

            foreach (array_values($subjects) as $subjectIndex => $subjectSlug) {
                if (! is_string($subjectSlug) || ! isset($subjectSlugs[$subjectSlug])) {
                    $this->addError($errors, "learning_nodes.{$index}.subjects.{$subjectIndex}", 'missing_subject', 'Referenced Subject does not exist.');
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $relations
     * @param  array<string, true>  $nodeSlugs
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function validateNodeRelations(array $relations, array $nodeSlugs, array &$errors): void
    {
        foreach ($relations as $index => $relation) {
            $from = $relation['from'] ?? null;
            $to = $relation['to'] ?? null;
            $type = $relation['type'] ?? null;

            if ($type !== 'prerequisite') {
                $this->addError($errors, "node_relations.{$index}.type", 'unsupported_relation_type', 'MVP NodeRelation type must be prerequisite.');
            }

            if (! is_string($from) || ! isset($nodeSlugs[$from])) {
                $this->addError($errors, "node_relations.{$index}.from", 'missing_learning_node', 'Source LearningNode does not exist.');
            }

            if (! is_string($to) || ! isset($nodeSlugs[$to])) {
                $this->addError($errors, "node_relations.{$index}.to", 'missing_learning_node', 'Target LearningNode does not exist.');
            }

            if (is_string($from) && is_string($to) && $from === $to) {
                $this->addError($errors, "node_relations.{$index}.to", 'self_relation', 'NodeRelation cannot point to itself.');
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $paths
     * @param  array<string, true>  $subjectSlugs
     * @param  array<string, true>  $nodeSlugs
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function validateLearningPaths(array $paths, array $subjectSlugs, array $nodeSlugs, array &$errors): void
    {
        foreach ($paths as $pathIndex => $path) {
            $subject = $path['subject'] ?? null;

            if ($subject !== null && (! is_string($subject) || ! isset($subjectSlugs[$subject]))) {
                $this->addError($errors, "learning_paths.{$pathIndex}.subject", 'missing_subject', 'Referenced Subject does not exist.');
            }

            $nodes = $path['nodes'] ?? [];
            if (! is_array($nodes) || $nodes === []) {
                $this->addError($errors, "learning_paths.{$pathIndex}.nodes", 'required', 'LearningPath must contain at least one LearningNode.');

                continue;
            }

            $positions = [];
            foreach (array_values($nodes) as $nodeIndex => $node) {
                $nodeSlug = is_array($node) ? ($node['slug'] ?? null) : $node;
                $position = is_array($node) ? ($node['position'] ?? $nodeIndex + 1) : $nodeIndex + 1;

                if (! is_string($nodeSlug) || ! isset($nodeSlugs[$nodeSlug])) {
                    $this->addError($errors, "learning_paths.{$pathIndex}.nodes.{$nodeIndex}.slug", 'missing_learning_node', 'LearningPath references a missing LearningNode.');
                }

                if (! is_int($position) || $position < 1) {
                    $this->addError($errors, "learning_paths.{$pathIndex}.nodes.{$nodeIndex}.position", 'invalid_position', 'LearningPath node position must be a positive integer.');

                    continue;
                }

                if (isset($positions[$position])) {
                    $this->addError($errors, "learning_paths.{$pathIndex}.nodes.{$nodeIndex}.position", 'duplicate_position', 'LearningPath node positions must be unique.');
                }

                $positions[$position] = true;
            }

            if ($positions !== []) {
                $expected = range(1, count($positions));
                $actual = array_keys($positions);
                sort($actual);

                if ($actual !== $expected) {
                    $this->addError($errors, "learning_paths.{$pathIndex}.nodes", 'non_contiguous_order', 'LearningPath node positions must start at 1 and be contiguous.');
                }
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $tasks
     * @param  array<string, true>  $nodeSlugs
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function validateTasks(array $tasks, array $nodeSlugs, array &$errors): void
    {
        foreach ($tasks as $taskIndex => $task) {
            $type = $task['type'] ?? null;
            $learningNodes = $task['learning_nodes'] ?? [];
            $versions = $task['versions'] ?? [];

            if (! is_array($learningNodes) || $learningNodes === []) {
                $this->addError($errors, "tasks.{$taskIndex}.learning_nodes", 'required', 'Task must link to at least one LearningNode.');
            } else {
                foreach (array_values($learningNodes) as $nodeIndex => $nodeSlug) {
                    if (! is_string($nodeSlug) || ! isset($nodeSlugs[$nodeSlug])) {
                        $this->addError($errors, "tasks.{$taskIndex}.learning_nodes.{$nodeIndex}", 'missing_learning_node', 'Task references a missing LearningNode.');
                    }
                }
            }

            if (! is_array($versions) || $versions === []) {
                $this->addError($errors, "tasks.{$taskIndex}.versions", 'required', 'Task must have at least one TaskVersion.');

                continue;
            }

            $activeVersions = 0;
            foreach (array_values($versions) as $versionIndex => $version) {
                if (! is_array($version)) {
                    continue;
                }

                if (($version['active'] ?? false) === true) {
                    $activeVersions++;
                }

                $this->validateTaskVersion($version, $taskIndex, $versionIndex, $type, $errors);
            }

            if ($activeVersions !== 1) {
                $this->addError($errors, "tasks.{$taskIndex}.versions", 'one_active_version_required', 'Task must have exactly one active TaskVersion.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $version
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function validateTaskVersion(array $version, int $taskIndex, int $versionIndex, mixed $taskType, array &$errors): void
    {
        foreach (['prompt', 'explanation'] as $field) {
            if (! is_string($version[$field] ?? null) || trim((string) $version[$field]) === '') {
                $this->addError($errors, "tasks.{$taskIndex}.versions.{$versionIndex}.{$field}", 'required', ucfirst($field).' is required.');
            }
        }

        $answerSchema = $version['answer_schema'] ?? [];
        if (! is_array($answerSchema)) {
            $this->addError($errors, "tasks.{$taskIndex}.versions.{$versionIndex}.answer_schema", 'required', 'Answer schema is required.');

            return;
        }

        if ($taskType === 'numeric') {
            $this->validateNumericAnswerSchema($answerSchema, $taskIndex, $versionIndex, $errors);
        }
    }

    /**
     * @param  array<string, mixed>  $answerSchema
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function validateNumericAnswerSchema(array $answerSchema, int $taskIndex, int $versionIndex, array &$errors): void
    {
        $correctValue = $answerSchema['correct_value'] ?? $answerSchema['correctValue'] ?? null;
        $tolerance = $answerSchema['tolerance'] ?? null;

        if (! is_numeric($correctValue)) {
            $this->addError($errors, "tasks.{$taskIndex}.versions.{$versionIndex}.answer_schema.correct_value", 'numeric_required', 'Numeric answer schema requires a numeric correct value.');
        }

        if (! is_numeric($tolerance)) {
            $this->addError($errors, "tasks.{$taskIndex}.versions.{$versionIndex}.answer_schema.tolerance", 'numeric_required', 'Numeric answer schema requires a numeric tolerance.');
        }
    }

    /**
     * @param  list<array{field: string, code: string, message: string}>  $errors
     */
    private function addError(array &$errors, string $field, string $code, string $message): void
    {
        $errors[] = [
            'field' => $field,
            'code' => $code,
            'message' => $message,
        ];
    }
}
