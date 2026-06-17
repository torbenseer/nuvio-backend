<?php

use Illuminate\Http\Request;
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
});
