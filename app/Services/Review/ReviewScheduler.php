<?php

namespace App\Services\Review;

use App\Models\LearningNode;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\Task;
use App\Models\TaskVersion;
use App\Models\User;
use Illuminate\Support\Carbon;

class ReviewScheduler
{
    public function recordTaskOutcome(User $user, Task $task, string $result, ?TaskVersion $taskVersion = null): ?Review
    {
        $node = $this->primaryNode($task);
        $taskVersion ??= $task->activeVersion()->first();

        if ($result === 'correct') {
            $this->setMasteryStatus($user, $node, 'practiced');

            return null;
        }

        $this->setMasteryStatus($user, $node, 'review_due');

        return $this->createOrUpdateReview($user, $node, $task, $taskVersion);
    }

    public function recordReviewOutcome(User $user, Review $review, string $result): void
    {
        $review->last_attempted_at = Carbon::now();

        if ($result === 'correct') {
            $review->status = 'completed';
            $review->due_at = null;
            $review->interval_days = null;
            $review->completed_at = Carbon::now();
            $review->save();

            $this->setMasteryStatus($user, $review->learningNode, 'retained');

            return;
        }

        $review->status = 'scheduled';
        $review->due_at = Carbon::now()->addDay();
        $review->interval_days = 1;
        $review->lapses += 1;
        $review->completed_at = null;
        $review->save();

        $this->setMasteryStatus($user, $review->learningNode, 'review_due');
    }

    private function createOrUpdateReview(User $user, LearningNode $node, Task $task, ?TaskVersion $taskVersion): Review
    {
        $review = Review::query()
            ->where('user_id', $user->id)
            ->where('learning_node_id', $node->id)
            ->where('task_id', $task->id)
            ->where('status', 'scheduled')
            ->first();

        if (! $review) {
            $review = new Review([
                'user_id' => $user->id,
                'learning_node_id' => $node->id,
                'task_id' => $task->id,
            ]);
        }

        $review->status = 'scheduled';
        $review->task_version_id = $taskVersion?->id;
        $review->due_at = Carbon::now()->addDay();
        $review->interval_days = 1;
        $review->completed_at = null;
        $review->save();

        return $review;
    }

    private function primaryNode(Task $task): LearningNode
    {
        return $task->learningNodes()
            ->wherePivot('is_primary', true)
            ->firstOrFail();
    }

    private function setMasteryStatus(User $user, LearningNode $node, string $status): MasteryState
    {
        $state = MasteryState::query()->firstOrNew([
            'user_id' => $user->id,
            'learning_node_id' => $node->id,
        ]);

        $state->status = $status;
        $state->last_practiced_at = Carbon::now();
        $state->retained_at = $status === 'retained' ? Carbon::now() : $state->retained_at;
        $state->save();

        return $state;
    }
}
