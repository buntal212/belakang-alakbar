<?php

use App\Http\Controllers\Api\Tagihan\TagihanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'tagihan/tagihan'
], function () {
    Route::get('/get-list', [TagihanController::class, 'index']);
    Route::post('/simpan-heder', [TagihanController::class, 'storeheder']);
    Route::post('/simpan-rinci', [TagihanController::class, 'storerinci']);
    Route::post('/hapus-rinci', [TagihanController::class, 'hapusrinci']);
});
