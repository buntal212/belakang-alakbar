<?php

use App\Http\Controllers\Api\SaldoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'saldo'
], function () {
    Route::get('/get-list', [SaldoController::class, 'index']);
});
