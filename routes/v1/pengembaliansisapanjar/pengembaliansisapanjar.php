<?php

use App\Http\Controllers\Api\Pengembaliansisapanjar\PengembaliansisapanjarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pengembaliansisapanjar/pengembaliansisapanjar'
], function () {
    Route::get('/get-list', [PengembaliansisapanjarController::class, 'index']);
    Route::post('/simpan', [PengembaliansisapanjarController::class, 'store']);
});
