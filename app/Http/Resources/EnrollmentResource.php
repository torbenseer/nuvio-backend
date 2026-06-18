<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, int|string|null>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'learning_path_id' => $this->learning_path_id,
            'status' => $this->status,
            'self_assessment' => $this->self_assessment,
            'started_at' => $this->started_at?->toJSON(),
        ];
    }
}
