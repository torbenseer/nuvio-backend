<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitTaskAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attempt = $this->route('taskAttempt');

        return $attempt && $this->user() && $attempt->user_id === $this->user()->id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'answer' => ['sometimes', 'array'],
            'answer.value' => ['required_with:answer', 'numeric'],
            'result' => ['sometimes', 'in:unsure,skipped'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->has('answer') === $this->has('result')) {
                $validator->errors()->add('answer', 'Provide exactly one of answer or result.');
            }
        });
    }
}
