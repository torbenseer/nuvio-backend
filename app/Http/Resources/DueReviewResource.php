<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DueReviewResource extends JsonResource
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'learning_node_id' => $this->learning_node_id,
            'task_id' => $this->task_id,
            'due_at' => $this->due_at?->toJSON(),
            'estimated_minutes' => $this->task?->estimated_minutes
                ?? $this->learningNode?->estimated_minutes
                ?? 5,
        ];
    }
}
