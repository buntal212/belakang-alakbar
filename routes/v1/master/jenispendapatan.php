<?php

use App\Http\Controllers\Api\Master\JenisPendapatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/jenispendapatan',
], function () {
    Route::get('/getjenispendapatan', [JenisPendapatanController::class, 'index']);
    Route::post('/simpan', [JenisPendapatanController::class, 'store']);
    Route::post('/delete', [JenisPendapatanController::class, 'destroy']);
});
