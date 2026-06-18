<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningNodeDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'subjects' => $this->subjects
                ->map(fn ($subject): string => $subject->name)
                ->values()
                ->all(),
        ];
    }
}
