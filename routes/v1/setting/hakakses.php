<?php

use App\Http\Controllers\Api\Setting\HakAksesController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('setting/hak-akses')->group(function () {
    Route::get('/users', [HakAksesController::class, 'users']);
    Route::get('/navigation', [HakAksesController::class, 'navigation']);
    Route::get('/users/{user}', [HakAksesController::class, 'show']);
    Route::put('/users/{user}', [HakAksesController::class, 'update']);
});
