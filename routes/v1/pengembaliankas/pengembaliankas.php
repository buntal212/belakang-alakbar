<?php


use App\Http\Controllers\Api\Pengembaliankas\PengembaliankasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pengembaliankas/pengembaliankas'
], function () {
    Route::get('/get-list', [PengembaliankasController::class, 'index']);
    Route::post('/simpan', [PengembaliankasController::class, 'store']);

});
