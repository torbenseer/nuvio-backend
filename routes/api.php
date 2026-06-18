<?php

use App\Http\Controllers\LearningNodeController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\TodayModeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\Task;
use App\Models\TaskAttempt;
use App\Models\TaskVersion;
use App\Services\Progress\ProgressSummary;
use App\Services\Progress\PathProgress;
use App\Services\Review\ReviewScheduler;
use App\Services\Tasks\TaskGrader;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/status', function (): array {
    return [
        'data' => [
            'name' => config('app.name'),
            'status' => 'ok',
        ],
        'meta' => [
            'api_version' => 'v1',
        ],
    ];
});

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/user', UserController::class);
    Route::put('/user/preferences', UserPreferenceController::class);
    Route::get('/today', TodayController::class);
    Route::post('/today/mode', TodayModeController::class);
    Route::get('/learning-paths', [LearningPathController::class, 'index']);
    Route::get('/learning-paths/{learningPath}', [LearningPathController::class, 'show']);
    Route::get('/nodes', [LearningNodeController::class, 'index']);
    Route::get('/nodes/{learningNode}', [LearningNodeController::class, 'show']);
    Route::get('/nodes/{learningNode}/tasks', [LearningNodeController::class, 'tasks']);
    Route::get('/nodes/{learningNode}/prerequisites', [LearningNodeController::class, 'prerequisites']);

    Route::post('/learning-paths/{learningPath}/start', function (Request $request, LearningPath $learningPath): array {
        abort_unless($learningPath->active, 404);

        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'learning_path_id' => $learningPath->id,
            ],
            [
                'status' => 'active',
                'started_at' => Carbon::now(),
            ],
        );

        if ($enrollment->status !== 'active') {
            $enrollment->forceFill([
                'status' => 'active',
                'started_at' => $enrollment->started_at ?? Carbon::now(),
            ])->save();
        }

        return [
            'data' => [
                'id' => $enrollment->id,
                'learning_path_id' => $enrollment->learning_path_id,
                'status' => $enrollment->status,
                'started_at' => $enrollment->started_at?->toJSON(),
            ],
        ];
    });

    Route::get('/tasks/{task}', function (Task $task): array {
        abort_unless($task->active, 404);

        $version = $task->activeVersion()->firstOrFail();

        return [
            'data' => [
                'id' => $task->id,
                'task_version_id' => $version->id,
                'type' => $task->type,
                'prompt' => $version->prompt,
                'input' => $version->input_schema,
                'estimated_minutes' => $task->estimated_minutes,
            ],
        ];
    });

    Route::post('/task-attempts/start', function (Request $request): array {
        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
            'task_version_id' => ['required', 'integer', 'exists:task_versions,id'],
        ]);

        $task = Task::query()->where('active', true)->findOrFail($validated['task_id']);
        $taskVersion = TaskVersion::query()
            ->where('active', true)
            ->where('task_id', $task->id)
            ->findOrFail($validated['task_version_id']);

        $attempt = TaskAttempt::query()->create([
            'user_id' => $request->user()->id,
            'task_id' => $task->id,
            'task_version_id' => $taskVersion->id,
            'status' => 'started',
        ]);

        return [
            'data' => [
                'id' => $attempt->id,
                'task_id' => $attempt->task_id,
                'task_version_id' => $attempt->task_version_id,
                'status' => $attempt->status,
            ],
        ];
    });

    Route::post('/task-attempts/{taskAttempt}/submit', function (
        Request $request,
        TaskAttempt $taskAttempt,
        TaskGrader $grader,
        ReviewScheduler $reviews,
    ): array {
        abort_unless($taskAttempt->user_id === $request->user()->id, 403);

        if ($taskAttempt->status !== 'started') {
            abort(409, 'Attempt was already submitted.');
        }

        $hasAnswer = $request->has('answer');
        $hasResult = $request->has('result');

        if ($hasAnswer === $hasResult) {
            throw ValidationException::withMessages([
                'answer' => ['Provide exactly one of answer or result.'],
            ]);
        }

        if ($hasResult) {
            $validated = $request->validate([
                'result' => ['required', 'in:unsure,skipped'],
            ]);

            $graded = [
                'result' => $validated['result'],
                'feedback_key' => $validated['result'] === 'unsure' ? 'marked_unsure_review' : 'skipped_review',
                'feedback_text' => $validated['result'] === 'unsure'
                    ? 'Nicht sicher? Passt. Nuvio plant eine kurze Wiederholung.'
                    : 'Für jetzt übersprungen. Nuvio nimmt es später wieder auf.',
            ];
            $answer = null;
        } else {
            $validated = $request->validate([
                'answer' => ['required', 'array'],
                'answer.value' => ['required', 'numeric'],
            ]);

            $graded = $grader->grade($taskAttempt->taskVersion, $validated['answer']);
            $answer = $validated['answer'];
        }

        [$review, $node, $mastery] = DB::transaction(function () use ($answer, $graded, $request, $reviews, $taskAttempt): array {
            $review = $reviews->recordTaskOutcome($request->user(), $taskAttempt->task, $graded['result'], $taskAttempt->taskVersion);

            $taskAttempt->forceFill([
                'status' => 'submitted',
                'result' => $graded['result'],
                'answer' => $answer,
                'feedback_key' => $graded['feedback_key'],
                'feedback_text' => $graded['feedback_text'],
                'submitted_at' => Carbon::now(),
            ])->save();

            $node = $taskAttempt->task->learningNodes()->wherePivot('is_primary', true)->firstOrFail();
            $mastery = MasteryState::query()
                ->where('user_id', $request->user()->id)
                ->where('learning_node_id', $node->id)
                ->firstOrFail();

            return [$review, $node, $mastery];
        });

        $response = [
            'id' => $taskAttempt->id,
            'result' => $taskAttempt->result,
            'feedback_key' => $taskAttempt->feedback_key,
            'feedback_text' => $taskAttempt->feedback_text,
            'mastery' => [
                'learning_node_id' => $node->id,
                'status' => $mastery->status,
            ],
            'review_created' => $review !== null,
            'review_scheduled' => $review !== null,
            'next_state' => $review !== null ? 'review_scheduled' : 'practiced',
        ];

        if ($review) {
            $response['review_id'] = $review->id;
        }

        return ['data' => $response];
    });

    Route::get('/reviews/due', function (Request $request): array {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);
        $limit = (int) ($validated['limit'] ?? 3);

        $reviews = Review::query()
            ->where('user_id', $request->user()->id)
            ->where('status', 'scheduled')
            ->where('due_at', '<=', Carbon::now())
            ->with(['learningNode', 'task'])
            ->orderBy('due_at')
            ->limit($limit)
            ->get();

        return [
            'data' => $reviews->map(fn (Review $review): array => [
                'id' => $review->id,
                'learning_node_id' => $review->learning_node_id,
                'task_id' => $review->task_id,
                'due_at' => $review->due_at?->toJSON(),
                'estimated_minutes' => $review->task?->estimated_minutes
                    ?? $review->learningNode?->estimated_minutes
                    ?? 5,
            ])->all(),
            'meta' => [
                'returned' => $reviews->count(),
                'cap' => $limit,
            ],
        ];
    });

    Route::get('/reviews/{review}', function (Request $request, Review $review): array {
        abort_unless($review->user_id === $request->user()->id, 403);

        $task = $review->task;
        $version = $review->taskVersion ?? $task->activeVersion()->firstOrFail();

        return [
            'data' => [
                'id' => $review->id,
                'learning_node' => [
                    'id' => $review->learningNode->id,
                    'title' => $review->learningNode->title,
                ],
                'task' => [
                    'id' => $task->id,
                    'task_version_id' => $version->id,
                    'type' => $task->type,
                    'prompt' => $version->prompt,
                    'input' => $version->input_schema,
                    'estimated_minutes' => $task->estimated_minutes,
                ],
                'due_at' => $review->due_at?->toJSON(),
            ],
        ];
    });

    Route::post('/reviews/{review}/snooze', function (Request $request, Review $review): array {
        abort_unless($review->user_id === $request->user()->id, 403);

        if ($review->status !== 'scheduled') {
            abort(409, 'Review is not snoozable.');
        }

        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:15', 'max:1440'],
        ]);

        $review->forceFill([
            'due_at' => Carbon::now()->addMinutes((int) $validated['minutes']),
        ])->save();

        return [
            'data' => [
                'id' => $review->id,
                'due_at' => $review->due_at?->toJSON(),
                'status' => $review->status,
            ],
        ];
    });

    Route::post('/reviews/{review}/answer', function (
        Request $request,
        Review $review,
        TaskGrader $grader,
        ReviewScheduler $reviews,
    ): array {
        abort_unless($review->user_id === $request->user()->id, 403);

        if (! in_array($review->status, ['scheduled'], true)) {
            abort(409, 'Review is not answerable.');
        }

        $hasAnswer = $request->has('answer');
        $hasResult = $request->has('result');

        if ($hasAnswer === $hasResult) {
            throw ValidationException::withMessages([
                'answer' => ['Provide exactly one of answer or result.'],
            ]);
        }

        $version = $review->taskVersion ?? $review->task->activeVersion()->firstOrFail();

        if ($hasResult) {
            $validated = $request->validate([
                'result' => ['required', 'in:unsure,skipped'],
            ]);

            $graded = [
                'result' => $validated['result'],
                'feedback_key' => $validated['result'] === 'unsure' ? 'review_unsure' : 'review_skipped',
                'feedback_text' => $validated['result'] === 'unsure'
                    ? 'Nicht sicher? Passt. Nuvio plant eine kurze Wiederholung.'
                    : 'Für jetzt übersprungen. Nuvio nimmt es später wieder auf.',
            ];
            $answer = null;
        } else {
            $validated = $request->validate([
                'answer' => ['required', 'array'],
                'answer.value' => ['required', 'numeric'],
            ]);

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

        return [
            'data' => [
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
            ],
        ];
    });

    Route::get('/progress/summary', function (Request $request, ProgressSummary $summary): array {
        return [
            'data' => $summary->forUser($request->user()),
        ];
    });

    Route::get('/progress/paths/{learningPath}', function (
        Request $request,
        LearningPath $learningPath,
        PathProgress $progress,
    ): array {
        abort_unless($learningPath->active, 404);

        return [
            'data' => $progress->forUserAndPath($request->user(), $learningPath),
        ];
    });
});
