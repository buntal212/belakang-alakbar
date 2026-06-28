<?php

use App\Http\Controllers\Api\Penerimaanyayasan\VerifikasiGuController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'penerimaanyayasan/verifikasigu'
], function () {
    Route::get('/get-list', [VerifikasiGuController::class, 'index']);
    Route::post('/simpan', [VerifikasiGuController::class, 'store']);
    // Route::post('/tolak', [VerifikasiUpController::class, 'tolak']);
});
