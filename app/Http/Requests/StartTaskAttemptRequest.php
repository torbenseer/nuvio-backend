<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartTaskAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'task_version_id' => ['required', 'integer', 'exists:task_versions,id'],
        ];
    }
}
