<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewAnswerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $review = $this->resource['review'];
        $attempt = $this->resource['attempt'];
        $graded = $this->resource['graded'];
        $previousStatus = $this->resource['previous_status'];
        $mastery = $this->resource['mastery'];

        return [
            'review_id' => $review->id,
            'attempt_id' => $attempt->id,
            'result' => $graded['result'],
            'feedback_key' => $graded['feedback_key'],
            'feedback_text' => $graded['feedback_text'],
            'status' => $review->status,
            'next_due_at' => $review->due_at?->toJSON(),
            'interval_days' => $review->interval_days,
            'mastery' => [
                'learning_node_id' => $review->learning_node_id,
                'previous_status' => $previousStatus,
                'status' => $mastery->status,
            ],
            'review_scheduled' => $review->status === 'scheduled',
            'next_state' => $review->status === 'completed' ? 'retained' : 'review_scheduled',
            'mastery_transition' => [
                'previous_status' => $previousStatus,
                'status' => $mastery->status,
            ],
        ];
    }
}
