<?php

use App\Http\Controllers\Api\Master\KodebelanjaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/kodebelanja'
], function () {
    Route::get('/getkodebelanja', [KodebelanjaController::class, 'index']);
    Route::post('/simpan', [KodebelanjaController::class, 'store']);
     Route::post('/delete', [KodebelanjaController::class, 'destroy']);
});
