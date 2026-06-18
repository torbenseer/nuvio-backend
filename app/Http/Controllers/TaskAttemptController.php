<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartTaskAttemptRequest;
use App\Http\Requests\SubmitTaskAttemptRequest;
use App\Http\Resources\StartedTaskAttemptResource;
use App\Http\Resources\TaskAttemptResultResource;
use App\Models\MasteryState;
use App\Models\Task;
use App\Models\TaskAttempt;
use App\Models\TaskVersion;
use App\Services\Review\ReviewScheduler;
use App\Services\Tasks\TaskGrader;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TaskAttemptController extends Controller
{
    public function start(StartTaskAttemptRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        return StartedTaskAttemptResource::make($attempt)->response()->setStatusCode(200);
    }

    public function submit(
        SubmitTaskAttemptRequest $request,
        TaskAttempt $taskAttempt,
        TaskGrader $grader,
        ReviewScheduler $reviews,
    ): TaskAttemptResultResource {
        if ($taskAttempt->status !== 'started') {
            abort(409, 'Attempt was already submitted.');
        }

        if ($request->has('result')) {
            $validated = $request->validated();

            $graded = [
                'result' => $validated['result'],
                'feedback_key' => $validated['result'] === 'unsure' ? 'marked_unsure_review' : 'skipped_review',
                'feedback_text' => $validated['result'] === 'unsure'
                    ? 'Nicht sicher? Passt. Nuvio plant eine kurze Wiederholung.'
                    : 'Für jetzt übersprungen. Nuvio nimmt es später wieder auf.',
            ];
            $answer = null;
        } else {
            $validated = $request->validated();

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

        return TaskAttemptResultResource::make($response);
    }
}
