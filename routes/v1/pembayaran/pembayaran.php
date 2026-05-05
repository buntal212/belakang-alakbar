<?php

use App\Http\Controllers\Api\Pembayaran\PembayaranController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pembayaran/pembayaran'
], function () {
    Route::get('/get-list', [PembayaranController::class, 'index']);
    Route::get('/get-listall', [PembayaranController::class, 'indexall']);
    Route::post('/simpan', [PembayaranController::class, 'simpan']);
    Route::post('/delete', [PembayaranController::class, 'hapus']);
});
