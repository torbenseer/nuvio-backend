<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetTodayModeRequest extends FormRequest
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
            'mode' => ['required', 'string', 'in:red,yellow,green'],
        ];
    }
}
