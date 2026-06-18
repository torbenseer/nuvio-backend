<?php

namespace App\Http\Controllers;

use App\Http\Resources\PathProgressResource;
use App\Http\Resources\ProgressSummaryResource;
use App\Models\LearningPath;
use App\Services\Progress\PathProgress;
use App\Services\Progress\ProgressSummary;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function summary(Request $request, ProgressSummary $summary): ProgressSummaryResource
    {
        return ProgressSummaryResource::make($summary->forUser($request->user()));
    }

    public function path(Request $request, LearningPath $learningPath, PathProgress $progress): PathProgressResource
    {
        abort_unless($learningPath->active, 404);

        return PathProgressResource::make($progress->forUserAndPath($request->user(), $learningPath));
    }
}
