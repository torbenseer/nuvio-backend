<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;

class UserPreferenceController extends Controller
{
    public function __invoke(UpdateUserPreferenceRequest $request): UserPreferenceResource
    {
        $validated = $request->validated();

        $request->user()->forceFill($validated)->save();

        return UserPreferenceResource::make($request->user());
    }
}
