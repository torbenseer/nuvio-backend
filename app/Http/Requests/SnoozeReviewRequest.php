<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SnoozeReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review && $this->user() && $review->user_id === $this->user()->id;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'minutes' => ['required', 'integer', 'min:15', 'max:1440'],
        ];
    }
}
