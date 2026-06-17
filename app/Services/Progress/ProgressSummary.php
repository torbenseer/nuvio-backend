<?php

namespace App\Services\Progress;

use App\Models\Enrollment;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\User;

class ProgressSummary
{
    /**
     * @return array<string, int>
     */
    public function forUser(User $user): array
    {
        $states = MasteryState::query()
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'active_paths' => Enrollment::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->count(),
            'practiced_nodes' => (int) ($states['practiced'] ?? 0),
            'review_due_nodes' => (int) ($states['review_due'] ?? 0),
            'retained_nodes' => (int) ($states['retained'] ?? 0),
            'reviews_due' => Review::query()
                ->where('user_id', $user->id)
                ->where('status', 'scheduled')
                ->where('due_at', '<=', now())
                ->count(),
        ];
    }
}
