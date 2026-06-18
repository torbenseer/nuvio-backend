<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningPathDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'nodes' => $this->pathNodes
                ->filter(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
                ->map(fn ($pathNode): array => [
                    'id' => $pathNode->learningNode->id,
                    'title' => $pathNode->learningNode->title,
                    'position' => $pathNode->position,
                ])
                ->values()
                ->all(),
        ];
    }
}
