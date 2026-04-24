<?php

use App\Http\Controllers\Api\Pergeserankas\PergeserankasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pergeserankas/pergeserankas'
], function () {
    Route::get('/get-list', [PergeserankasController::class, 'index']);
    Route::post('/simpan', [PergeserankasController::class, 'store']);
});
