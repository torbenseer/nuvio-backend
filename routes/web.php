<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    if (! Auth::attempt($credentials)) {
        return response()->json([
            'message' => 'The provided credentials are incorrect.',
        ], 422);
    }

    $request->session()->regenerate();

    return response()->json([
        'data' => [
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'locale' => $request->user()->locale ?? 'de',
            'timezone' => $request->user()->timezone ?? 'Europe/Berlin',
        ],
    ]);
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->noContent();
})->middleware('auth');
