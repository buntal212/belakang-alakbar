<?php

use App\Http\Controllers\Api\Master\SumberdanaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/sumberdana'
], function () {
    Route::get('/getsumberdana', [SumberdanaController::class, 'index']);
    Route::post('/simpan', [SumberdanaController::class, 'store']);
     Route::post('/delete', [SumberdanaController::class, 'destroy']);
});
