<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetTodayModeRequest;
use App\Http\Resources\TodayModeResource;

class TodayModeController extends Controller
{
    public function __invoke(SetTodayModeRequest $request): TodayModeResource
    {
        $validated = $request->validated();

        $request->user()->forceFill([
            'energy_mode' => $validated['mode'],
        ])->save();

        return TodayModeResource::make($request->user());
    }
}
