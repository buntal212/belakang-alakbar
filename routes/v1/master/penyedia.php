<?php

use App\Http\Controllers\Api\Master\PenyediaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/penyedia'
], function () {
    Route::get('/getpenyedia', [PenyediaController::class, 'index']);
    Route::post('/simpan', [PenyediaController::class, 'store']);
     Route::post('/delete', [PenyediaController::class, 'destroy']);
});
