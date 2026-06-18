<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetTodayRequest;
use App\Http\Resources\TodayActionResource;
use App\Services\Today\TodaySelector;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TodayController extends Controller
{
    public function __invoke(GetTodayRequest $request, TodaySelector $selector): AnonymousResourceCollection
    {
        return TodayActionResource::collection($selector->actionsFor($request->user()))
            ->additional([
                'meta' => [
                    'limit' => 3,
                ],
            ]);
    }
}
