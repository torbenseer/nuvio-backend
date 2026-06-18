<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $task = $this->task;
        $version = $this->taskVersion ?? $task->activeVersion()->firstOrFail();

        return [
            'id' => $this->id,
            'learning_node' => [
                'id' => $this->learningNode->id,
                'title' => $this->learningNode->title,
            ],
            'task' => [
                'id' => $task->id,
                'task_version_id' => $version->id,
                'type' => $task->type,
                'prompt' => $version->prompt,
                'input' => $version->input_schema,
                'estimated_minutes' => $task->estimated_minutes,
            ],
            'due_at' => $this->due_at?->toJSON(),
        ];
    }
}
