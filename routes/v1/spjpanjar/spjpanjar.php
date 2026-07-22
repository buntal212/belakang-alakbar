<?php


use App\Http\Controllers\Api\SpjPanjar\SpjPanjarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'spjpanjar/spjpanjar'
], function () {
    Route::get('/get-list', [SpjPanjarController::class, 'index']);
    // Route::get('/get-listall', [PembayaranController::class, 'indexall']);
    Route::post('/simpanheder', [SpjPanjarController::class, 'storeheder']);
    Route::post('/simpan-rinci', [SpjPanjarController::class, 'storerinci']);
    Route::post('/hapus-rinci', [SpjPanjarController::class, 'hapusrinci']);
});
