<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListLearningNodesRequest;
use App\Http\Resources\LearningNodeDetailResource;
use App\Http\Resources\LearningNodePrerequisiteResource;
use App\Http\Resources\LearningNodeSummaryResource;
use App\Http\Resources\LearningNodeTaskResource;
use App\Models\LearningNode;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LearningNodeController extends Controller
{
    public function index(ListLearningNodesRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $nodes = LearningNode::query()
            ->where('active', true)
            ->when($validated['subject'] ?? null, function ($query, string $subject): void {
                $query->whereHas('subjects', function ($query) use ($subject): void {
                    $query->where('slug', $subject)->where('active', true);
                });
            })
            ->when($validated['type'] ?? null, function ($query, string $type): void {
                $query->where('type', $type);
            })
            ->orderBy('id')
            ->get();

        return LearningNodeSummaryResource::collection($nodes);
    }

    public function show(LearningNode $learningNode): LearningNodeDetailResource
    {
        abort_unless($learningNode->active, 404);

        return LearningNodeDetailResource::make(
            $learningNode->load(['subjects' => function ($query): void {
                $query->where('active', true)->orderBy('name');
            }])
        );
    }

    public function tasks(LearningNode $learningNode): AnonymousResourceCollection
    {
        abort_unless($learningNode->active, 404);

        $tasks = $learningNode->tasks()
            ->where('tasks.active', true)
            ->orderBy('tasks.id')
            ->get();

        return LearningNodeTaskResource::collection($tasks);
    }

    public function prerequisites(LearningNode $learningNode): AnonymousResourceCollection
    {
        abort_unless($learningNode->active, 404);

        $relations = $learningNode->prerequisiteRelations()
            ->with('sourceNode')
            ->whereHas('sourceNode', function ($query): void {
                $query->where('active', true);
            })
            ->orderBy('source_node_id')
            ->get();

        return LearningNodePrerequisiteResource::collection($relations);
    }
}
