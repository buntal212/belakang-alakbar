<?php

use App\Events\SaldoUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/broadcast-test', function (Request $request) {
    abort_unless(app()->environment('local', 'testing'), 404);

    $data = $request->validate([
        'pemilik' => ['required', 'string', 'max:100'],
    ]);

    broadcast(new SaldoUpdated([
        'pemilik' => $data['pemilik'],
        'data' => [],
        'message' => 'Tes Laravel Reverb',
        'time' => now()->toDateTimeString(),
    ]));

    return response()->json([
        'success' => true,
        'message' => 'Broadcast terkirim',
    ]);
})->middleware('auth:sanctum');
