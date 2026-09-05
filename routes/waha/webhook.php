<?php

use App\Http\Controllers\Api\WahaWebhookController;
use App\Http\Controllers\Api\WahaController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook', [WahaWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/status', [WahaController::class, 'status']);
    Route::post('/session', [WahaController::class, 'createSession']);
    Route::post('/start', [WahaController::class, 'start']);
    Route::get('/qr', [WahaController::class, 'qr']);
    Route::post('/restart', [WahaController::class, 'restart']);
    Route::post('/logout', [WahaController::class, 'logout']);
    Route::delete('/session', [WahaController::class, 'destroy']);
    Route::post('/send-test', [WahaController::class, 'sendTest']);
});
