<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $version = $this->activeVersion()->firstOrFail();

        return [
            'id' => $this->id,
            'task_version_id' => $version->id,
            'type' => $this->type,
            'prompt' => $version->prompt,
            'input' => $version->input_schema,
            'estimated_minutes' => $this->estimated_minutes,
        ];
    }
}
