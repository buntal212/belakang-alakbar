<?php

use App\Http\Controllers\Api\Master\KelasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/kelas',
], function () {
    Route::get('/getkelas', [KelasController::class, 'index']);
    Route::get('/getall', [KelasController::class, 'all']);
    Route::post('/simpan', [KelasController::class, 'store']);
    Route::post('/delete', [KelasController::class, 'destroy']);
});
