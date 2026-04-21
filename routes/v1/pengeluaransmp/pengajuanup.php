<?php

use App\Http\Controllers\Api\Pengeluaransmp\PengajuanUpController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pengeluaransmp/pengajuanup'
], function () {
    Route::get('/get-list', [PengajuanUpController::class, 'index']);
    Route::post('/simpan', [PengajuanUpController::class, 'store']);
    Route::post('/delete', [PengajuanUpController::class, 'destroy']);
    Route::post('/terimaUang', [PengajuanUpController::class, 'terimaUang']);
});
