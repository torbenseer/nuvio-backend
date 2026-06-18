<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningPathSummaryResource extends JsonResource
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'subject' => $this->subject?->name,
            'estimated_minutes' => $this->estimated_minutes,
            'node_count' => $this->pathNodes
                ->filter(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
                ->count(),
        ];
    }
}
