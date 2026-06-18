<?php

namespace App\Services\Progress;

use App\Models\LearningPath;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\User;

class PathProgress
{
    /**
     * @return array<string, mixed>
     */
    public function forUserAndPath(User $user, LearningPath $path): array
    {
        $path->load(['pathNodes.learningNode']);

        $pathNodes = $path->pathNodes
            ->filter(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
            ->values();

        $nodeIds = $pathNodes
            ->map(fn ($pathNode): int => $pathNode->learning_node_id)
            ->all();

        $masteryByNode = MasteryState::query()
            ->where('user_id', $user->id)
            ->whereIn('learning_node_id', $nodeIds)
            ->pluck('status', 'learning_node_id');

        $reviewDueNodeIds = Review::query()
            ->where('user_id', $user->id)
            ->where('status', 'scheduled')
            ->whereIn('learning_node_id', $nodeIds)
            ->pluck('learning_node_id')
            ->flip();

        $counts = [
            'unknown' => 0,
            'practiced' => 0,
            'review_due' => 0,
            'retained' => 0,
        ];

        $nodes = $pathNodes
            ->map(function ($pathNode) use (&$counts, $masteryByNode, $reviewDueNodeIds): array {
                $status = $reviewDueNodeIds->has($pathNode->learning_node_id)
                    ? 'review_due'
                    : (string) ($masteryByNode[$pathNode->learning_node_id] ?? 'unknown');

                if (! array_key_exists($status, $counts)) {
                    $status = 'unknown';
                }

                $counts[$status]++;

                return [
                    'id' => $pathNode->learningNode->id,
                    'title' => $pathNode->learningNode->title,
                    'status' => $status,
                ];
            })
            ->all();

        return [
            'learning_path_id' => $path->id,
            'title' => $path->title,
            'node_counts' => $counts,
            'nodes' => $nodes,
        ];
    }
}
