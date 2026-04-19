<?php

use App\Http\Controllers\Api\Penerimaanyayasan\VerifikasiUpController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'penerimaanyayasan/verifikasiup'
], function () {
    Route::get('/get-list', [VerifikasiUpController::class, 'index']);
    Route::post('/simpan', [VerifikasiUpController::class, 'store']);
    Route::post('/tolak', [VerifikasiUpController::class, 'tolak']);
});
