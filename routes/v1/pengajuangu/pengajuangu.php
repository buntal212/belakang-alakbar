<?php

use App\Http\Controllers\Api\Pengajuangu\PengajuanguController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pengajuangu/pengajuangu'
], function () {
    Route::get('/get-list', [PengajuanguController::class, 'index']);
    Route::get('/get-listall', [PengajuanguController::class, 'indexall']);
    Route::post('/simpan-heder', [PengajuanguController::class, 'simpanheder']);
    Route::post('/simpan-rinci', [PengajuanguController::class, 'simpanrinci']);
    Route::post('/delete', [PengajuanguController::class, 'hapus']);
     Route::post('/hapus-rinci', [PengajuanguController::class, 'hapusrinci']);
});
