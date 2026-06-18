<?php

use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LearningNodeController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TaskAttemptController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TodayController;
use App\Http\Controllers\TodayModeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPreferenceController;
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
    Route::get('/user', UserController::class);
    Route::put('/user/preferences', UserPreferenceController::class);
    Route::get('/today', TodayController::class);
    Route::post('/today/mode', TodayModeController::class);
    Route::get('/learning-paths', [LearningPathController::class, 'index']);
    Route::get('/learning-paths/{learningPath}', [LearningPathController::class, 'show']);
    Route::get('/nodes', [LearningNodeController::class, 'index']);
    Route::get('/nodes/{learningNode}', [LearningNodeController::class, 'show']);
    Route::get('/nodes/{learningNode}/tasks', [LearningNodeController::class, 'tasks']);
    Route::get('/nodes/{learningNode}/prerequisites', [LearningNodeController::class, 'prerequisites']);
    Route::post('/learning-paths/{learningPath}/start', EnrollmentController::class);
    Route::get('/tasks/{task}', TaskController::class);
    Route::post('/task-attempts/start', [TaskAttemptController::class, 'start']);
    Route::post('/task-attempts/{taskAttempt}/submit', [TaskAttemptController::class, 'submit']);
    Route::get('/reviews/due', [ReviewController::class, 'due']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::post('/reviews/{review}/snooze', [ReviewController::class, 'snooze']);
    Route::post('/reviews/{review}/answer', [ReviewController::class, 'answer']);
    Route::get('/progress/summary', [ProgressController::class, 'summary']);
    Route::get('/progress/paths/{learningPath}', [ProgressController::class, 'path']);
});
