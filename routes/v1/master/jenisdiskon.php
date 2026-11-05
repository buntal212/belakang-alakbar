<?php

use App\Http\Controllers\Api\Master\JenisDiskonController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'master/jenisdiskon'], function () {
    Route::get('/getjenisdiskon', [JenisDiskonController::class, 'index']);
    Route::post('/simpan', [JenisDiskonController::class, 'store']);
    Route::post('/delete', [JenisDiskonController::class, 'destroy']);
});
