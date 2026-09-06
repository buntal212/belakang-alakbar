<?php

use App\Http\Controllers\Api\Pembayaran\PembayaranController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('pembayaran-ls')->group(function () {
    Route::get('/get-list', [PembayaranController::class, 'indexLsRoute']);
    Route::post('/simpan', [PembayaranController::class, 'simpanLsRoute']);
    Route::post('/delete', [PembayaranController::class, 'hapusLsRoute']);
});
