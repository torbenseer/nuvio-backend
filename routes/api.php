<?php

use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\MasteryState;
use App\Models\Review;
use App\Models\Task;
use App\Models\TaskAttempt;
use App\Models\TaskVersion;
use App\Services\Progress\ProgressSummary;
use App\Services\Review\ReviewScheduler;
use App\Services\Tasks\TaskGrader;
use App\Services\Today\TodaySelector;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
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
    Route::get('/user', function (Request $request): array {
        $user = $request->user();

        return [
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => $user->locale ?? 'de',
                'timezone' => $user->timezone ?? 'Europe/Berlin',
            ],
        ];
    });

    Route::put('/user/preferences', function (Request $request): array {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:de,en'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $request->user()->forceFill($validated)->save();

        return [
            'data' => [
                'locale' => $validated['locale'],
                'timezone' => $validated['timezone'],
            ],
        ];
    });

    Route::get('/today', function (Request $request, TodaySelector $selector): array {
        return [
            'data' => $selector->actionsFor($request->user()),
            'meta' => [
                'limit' => 3,
            ],
        ];
    });

    Route::post('/today/mode', function (Request $request): array {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:red,yellow,green'],
        ]);

        $request->user()->forceFill([
            'energy_mode' => $validated['mode'],
        ])->save();

        return [
            'data' => [
                'mode' => $validated['mode'],
            ],
        ];
    });

    Route::get('/learning-paths', function (Request $request): array {
        $validated = $request->validate([
            'subject' => [
                'sometimes',
                'string',
                Rule::exists('subjects', 'slug')->where('active', true),
            ],
        ]);

        $paths = LearningPath::query()
            ->with(['subject', 'pathNodes.learningNode'])
            ->where('active', true)
            ->when($validated['subject'] ?? null, function ($query, string $subject): void {
                $query->whereHas('subject', function ($query) use ($subject): void {
                    $query->where('slug', $subject)->where('active', true);
                });
            })
            ->orderBy('id')
            ->get();

        return [
            'data' => $paths->map(fn (LearningPath $path): array => [
                'id' => $path->id,
                'slug' => $path->slug,
                'title' => $path->title,
                'subject' => $path->subject?->name,
                'estimated_minutes' => $path->estimated_minutes,
                'node_count' => $path->pathNodes
                    ->filter(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
                    ->count(),
            ])->all(),
        ];
    });

    Route::get('/learning-paths/{learningPath}', function (LearningPath $learningPath): array {
        abort_unless($learningPath->active, 404);

        $learningPath->load(['pathNodes.learningNode']);

        return [
            'data' => [
                'id' => $learningPath->id,
                'title' => $learningPath->title,
                'nodes' => $learningPath->pathNodes
                    ->filter(fn ($pathNode): bool => (bool) $pathNode->learningNode?->active)
                    ->map(fn ($pathNode): array => [
                        'id' => $pathNode->learningNode->id,
                        'title' => $pathNode->learningNode->title,
                        'position' => $pathNode->position,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    });

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
});
