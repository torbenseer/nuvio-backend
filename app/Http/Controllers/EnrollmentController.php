<?php

namespace App\Http\Controllers;

use App\Http\Resources\EnrollmentResource;
use App\Models\Enrollment;
use App\Models\LearningPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EnrollmentController extends Controller
{
    public function __invoke(Request $request, LearningPath $learningPath): JsonResponse
    {
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

        return EnrollmentResource::make($enrollment)->response()->setStatusCode(200);
    }
}
