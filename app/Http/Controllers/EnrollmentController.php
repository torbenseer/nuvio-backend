<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartLearningPathRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\LearningPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class EnrollmentController extends Controller
{
    public function __invoke(StartLearningPathRequest $request, LearningPath $learningPath): JsonResponse
    {
        abort_unless($learningPath->active, 404);

        $validated = $request->validated();
        $selfAssessment = $validated['self_assessment'] ?? null;

        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'learning_path_id' => $learningPath->id,
            ],
            [
                'status' => 'active',
                'self_assessment' => $selfAssessment,
                'started_at' => Carbon::now(),
            ],
        );

        if ($enrollment->status !== 'active' || $selfAssessment !== null) {
            $enrollment->forceFill([
                'status' => 'active',
                'self_assessment' => $selfAssessment ?? $enrollment->self_assessment,
                'started_at' => $enrollment->started_at ?? Carbon::now(),
            ])->save();
        }

        return EnrollmentResource::make($enrollment)->response()->setStatusCode(200);
    }
}
