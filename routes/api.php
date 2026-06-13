<?php

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
