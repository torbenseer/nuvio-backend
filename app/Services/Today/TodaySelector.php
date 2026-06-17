<?php

namespace App\Services\Today;

use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;

class TodaySelector
{
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

        if (count($actions) >= 3) {
            return $actions;
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

            return array_slice($actions, 0, 3);
        }

        $task = $this->nextTask($enrollment);

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

        return array_slice($actions, 0, 3);
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
            ->limit(3)
            ->get();
    }

    private function nextTask(Enrollment $enrollment): ?Task
    {
        foreach ($enrollment->learningPath->pathNodes as $pathNode) {
            $task = $pathNode->learningNode->tasks
                ->where('active', true)
                ->sortBy('difficulty')
                ->first();

            if ($task) {
                return $task;
            }
        }

        return null;
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
