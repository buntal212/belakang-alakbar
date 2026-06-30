<?php

use App\Events\DataUpdated;
use Illuminate\Support\Facades\Route;

Route::get('/broadcast-test', function () {
    broadcast(new DataUpdated([
        'message' => 'Halo dari Laravel Reverb',
        'time' => now()->toDateTimeString(),
    ]));

    return response()->json([
        'success' => true,
        'message' => 'Broadcast terkirim',
    ]);
});
