<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartLearningPathRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'self_assessment' => [
                'sometimes',
                'nullable',
                Rule::in(['new', 'rough', 'confident']),
            ],
        ];
    }
}
