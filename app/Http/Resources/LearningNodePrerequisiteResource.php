<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LearningNodePrerequisiteResource extends JsonResource
{
    /**
     * @return array<string, int|string>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->sourceNode->id,
            'title' => $this->sourceNode->title,
            'relation' => 'prerequisite',
        ];
    }
}
