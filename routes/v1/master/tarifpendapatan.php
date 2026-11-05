<?php

use App\Http\Controllers\Api\Master\TarifPendapatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/tarifpendapatan',
], function () {
    Route::get('/gettarifpendapatan', [TarifPendapatanController::class, 'index']);
    Route::get('/kelas', [TarifPendapatanController::class, 'kelas']);
    Route::get('/jenispendapatan', [TarifPendapatanController::class, 'jenisPendapatan']);
    Route::post('/simpan', [TarifPendapatanController::class, 'store']);
    Route::post('/delete', [TarifPendapatanController::class, 'destroy']);
});
