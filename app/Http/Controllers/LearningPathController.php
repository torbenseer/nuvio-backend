<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListLearningPathsRequest;
use App\Http\Resources\LearningPathDetailResource;
use App\Http\Resources\LearningPathSummaryResource;
use App\Models\LearningPath;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LearningPathController extends Controller
{
    public function index(ListLearningPathsRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

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

        return LearningPathSummaryResource::collection($paths);
    }

    public function show(LearningPath $learningPath): LearningPathDetailResource
    {
        abort_unless($learningPath->active, 404);

        return LearningPathDetailResource::make(
            $learningPath->load(['pathNodes.learningNode'])
        );
    }
}
