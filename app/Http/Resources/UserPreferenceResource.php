<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return [
            'locale' => $this->locale ?? 'de',
            'timezone' => $this->timezone ?? 'Europe/Berlin',
        ];
    }
}
