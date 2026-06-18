<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnswerReviewRequest;
use App\Http\Requests\ListDueReviewsRequest;
use App\Http\Requests\SnoozeReviewRequest;
use App\Http\Resources\DueReviewResource;
use App\Http\Resources\ReviewAnswerResource;
use App\Http\Resources\ReviewDetailResource;
use App\Http\Resources\SnoozedReviewResource;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\TaskAttempt;
use App\Services\Review\ReviewScheduler;
use App\Services\Tasks\TaskGrader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function due(ListDueReviewsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $limit = (int) ($validated['limit'] ?? 3);

        $reviews = Review::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'scheduled')
            ->where('due_at', '<=', Carbon::now())
            ->with(['learningNode', 'task'])
            ->orderBy('due_at')
            ->limit($limit)
            ->get();

        return DueReviewResource::collection($reviews)
            ->additional([
                'meta' => [
                    'returned' => $reviews->count(),
                    'cap' => $limit,
                ],
            ]);
    }

    public function show(Request $request, Review $review): ReviewDetailResource
    {
        abort_unless($review->user_id === $request->user()->id, 403);

        return ReviewDetailResource::make($review);
    }

    public function snooze(SnoozeReviewRequest $request, Review $review): SnoozedReviewResource
    {
        if ($review->status !== 'scheduled') {
            abort(409, 'Review is not snoozable.');
        }

        $validated = $request->validated();

        $review->forceFill([
            'due_at' => Carbon::now()->addMinutes((int) $validated['minutes']),
        ])->save();

        return SnoozedReviewResource::make($review);
    }

    public function answer(
        AnswerReviewRequest $request,
        Review $review,
        TaskGrader $grader,
        ReviewScheduler $reviews,
    ): ReviewAnswerResource {
        if (! in_array($review->status, ['scheduled'], true)) {
            abort(409, 'Review is not answerable.');
        }

        $version = $review->taskVersion ?? $review->task->activeVersion()->firstOrFail();
        $validated = $request->validated();

        if ($request->has('result')) {
            $graded = [
                'result' => $validated['result'],
                'feedback_key' => $validated['result'] === 'unsure' ? 'review_unsure' : 'review_skipped',
                'feedback_text' => $validated['result'] === 'unsure'
                    ? 'Nicht sicher? Passt. Nuvio plant eine kurze Wiederholung.'
                    : 'Für jetzt übersprungen. Nuvio nimmt es später wieder auf.',
            ];
            $answer = null;
        } else {
            $graded = $grader->grade($version, $validated['answer']);
            $answer = $validated['answer'];
        }

        [$attempt, $previousStatus, $mastery] = DB::transaction(function () use ($answer, $graded, $request, $review, $reviews, $version): array {
            $attempt = TaskAttempt::query()->create([
                'user_id' => $request->user()->id,
                'task_id' => $review->task_id,
                'task_version_id' => $version->id,
                'review_id' => $review->id,
                'status' => 'submitted',
                'result' => $graded['result'],
                'answer' => $answer,
                'feedback_key' => $graded['feedback_key'],
                'feedback_text' => $graded['feedback_text'],
                'submitted_at' => Carbon::now(),
            ]);

            $previousStatus = MasteryState::query()
                ->where('user_id', $request->user()->id)
                ->where('learning_node_id', $review->learning_node_id)
                ->value('status') ?? 'unknown';

            $reviews->recordReviewOutcome($request->user(), $review, $graded['result']);
            $review->refresh();

            $mastery = MasteryState::query()
                ->where('user_id', $request->user()->id)
                ->where('learning_node_id', $review->learning_node_id)
                ->firstOrFail();

            return [$attempt, $previousStatus, $mastery];
        });

        return ReviewAnswerResource::make([
            'review' => $review,
            'attempt' => $attempt,
            'graded' => $graded,
            'previous_status' => $previousStatus,
            'mastery' => $mastery,
        ]);
    }
}
