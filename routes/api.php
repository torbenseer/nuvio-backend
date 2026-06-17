<?php

use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Services\Today\TodaySelector;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

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

    Route::get('/today', function (Request $request, TodaySelector $selector): array {
        return [
            'data' => $selector->actionsFor($request->user()),
            'meta' => [
                'limit' => 3,
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

});
