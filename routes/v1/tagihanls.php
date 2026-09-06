<?php

use App\Http\Controllers\Api\Tagihan\TagihanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('tagihan-ls')->group(function () {
    Route::get('/get-list', [TagihanController::class, 'indexLsRoute']);
    Route::get('/get-listall', [TagihanController::class, 'indexallLsRoute']);
    Route::post('/simpan-heder', [TagihanController::class, 'storeHederLsRoute']);
    Route::post('/simpan-rinci', [TagihanController::class, 'storeRinciLsRoute']);
    Route::post('/hapus-rinci', [TagihanController::class, 'hapusRinciLsRoute']);
    Route::post('/hapus-heder', [TagihanController::class, 'hapusHederLsRoute']);
});
