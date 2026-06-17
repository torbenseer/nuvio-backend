<?php

namespace App\Services\Today;

use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;

class TodaySelector
{
    private const RED_MODE_MAX_MINUTES = 15;

    /**
     * @return list<array<string, mixed>>
     */
    public function actionsFor(User $user): array
    {
        $actions = [];

        foreach ($this->dueReviews($user) as $review) {
            $actions[] = [
                'type' => 'review',
                'title' => $this->reviewTitle($review),
                'estimated_minutes' => $review->task?->estimated_minutes
                    ?? $review->learningNode?->estimated_minutes
                    ?? 5,
                'priority' => count($actions) + 1,
                'target' => [
                    'type' => 'review',
                    'id' => $review->id,
                ],
            ];
        }

        if ($user->energy_mode !== 'red' && count($actions) >= 3) {
            return $this->limitedActions($actions, $user);
        }

        $enrollment = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with('learningPath.pathNodes.learningNode.tasks.activeVersion')
            ->first();

        if (! $enrollment) {
            $path = LearningPath::query()->where('active', true)->orderBy('id')->first();

            if (! $path) {
                return $actions;
            }

            $actions[] = [
                'type' => 'start_path',
                'title' => $path->title,
                'estimated_minutes' => $path->estimated_minutes,
                'priority' => count($actions) + 1,
                'target' => [
                    'type' => 'learning_path',
                    'id' => $path->id,
                ],
            ];

            return $this->limitedActions($actions, $user);
        }

        $task = $this->nextTask($enrollment, $user);

        if ($task) {
            $actions[] = [
                'type' => 'task',
                'title' => $this->taskTitle($task),
                'estimated_minutes' => $task->estimated_minutes,
                'priority' => count($actions) + 1,
                'target' => [
                    'type' => 'task',
                    'id' => $task->id,
                ],
            ];
        }

        return $this->limitedActions($actions, $user);
    }

    /**
     * @return iterable<Review>
     */
    private function dueReviews(User $user): iterable
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('status', 'scheduled')
            ->where('due_at', '<=', now())
            ->with(['learningNode', 'task'])
            ->orderBy('due_at')
            ->get();
    }

    private function nextTask(Enrollment $enrollment, User $user): ?Task
    {
        foreach ($enrollment->learningPath->pathNodes as $pathNode) {
            $tasks = $pathNode->learningNode->tasks->where('active', true);
            $task = $this->bestTask($tasks, $user->energy_mode === 'red');

            if ($task) {
                return $task;
            }
        }

        return null;
    }

    private function bestTask(iterable $tasks, bool $preferShort): ?Task
    {
        $rankedTasks = collect($tasks)->sortBy(
            fn (Task $task): string => sprintf(
                '%010d-%010d-%010d',
                $task->difficulty,
                $task->estimated_minutes,
                $task->id,
            ),
        );

        if ($preferShort) {
            $shortTask = $rankedTasks
                ->filter(fn (Task $task): bool => $this->isShortAction($task->estimated_minutes))
                ->first();

            if ($shortTask) {
                return $shortTask;
            }
        }

        return $rankedTasks->first();
    }

    /**
     * @param list<array<string, mixed>> $actions
     * @return list<array<string, mixed>>
     */
    private function limitedActions(array $actions, User $user): array
    {
        if ($user->energy_mode === 'red') {
            $shortActions = array_values(array_filter(
                $actions,
                fn (array $action): bool => $this->isShortAction($action['estimated_minutes'] ?? null),
            ));

            if ($shortActions !== []) {
                $actions = $shortActions;
            }
        }

        $limitedActions = array_slice($actions, 0, 3);

        return array_values(array_map(
            function (array $action, int $index): array {
                $action['priority'] = $index + 1;

                return $action;
            },
            $limitedActions,
            array_keys($limitedActions),
        ));
    }

    private function isShortAction(mixed $minutes): bool
    {
        return is_numeric($minutes) && (int) $minutes <= self::RED_MODE_MAX_MINUTES;
    }

    private function reviewTitle(Review $review): string
    {
        $nodeTitle = $review->learningNode?->title;

        if ($nodeTitle) {
            return "{$nodeTitle} kurz auffrischen";
        }

        return 'Kurz auffrischen';
    }

    private function taskTitle(Task $task): string
    {
        $node = $task->learningNodes()
            ->wherePivot('is_primary', true)
            ->first()
            ?? $task->learningNodes()->first();

        if ($node) {
            return "{$node->title} üben";
        }

        return 'Ein kleiner nächster Schritt';
    }
}
