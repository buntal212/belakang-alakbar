<?php


use App\Http\Controllers\Api\VerifikasiPembayaran\VerivikasiPembayaranController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pengelolahkeuangan/verifikasibelanja'
], function () {
    Route::get('/get-list', [VerivikasiPembayaranController::class, 'index']);
    Route::post('/tolak', [VerivikasiPembayaranController::class, 'tolak']);
     Route::post('/terima', [VerivikasiPembayaranController::class, 'terima']);
});
