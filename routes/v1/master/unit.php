<?php

use App\Http\Controllers\Api\Master\UnitController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/unit'
], function () {
    Route::get('/getunit', [UnitController::class, 'index']);
    Route::post('/simpan', [UnitController::class, 'store']);
    Route::post('/delete', [UnitController::class, 'destroy']);
});
