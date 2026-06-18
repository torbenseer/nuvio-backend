<?php

namespace App\Http\Requests;

use App\Models\TaskVersion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $matchesActiveTask = TaskVersion::query()
                ->where('id', $this->integer('task_version_id'))
                ->where('task_id', $this->integer('task_id'))
                ->where('active', true)
                ->exists();

            if (! $matchesActiveTask) {
                $validator->errors()->add('task_version_id', 'The selected task version is invalid for this task.');
            }
        });
    }
}
