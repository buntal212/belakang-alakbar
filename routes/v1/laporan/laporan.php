<?php

use App\Http\Controllers\Api\Laporan\LaporanPengeluaranController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('laporan')->group(function () {
    Route::get('/pengeluaran', [LaporanPengeluaranController::class, 'pengeluaran']);
    Route::get('/utang', [LaporanPengeluaranController::class, 'utang']);
    Route::get('/buku-kas', [LaporanPengeluaranController::class, 'bukuKas']);
});
