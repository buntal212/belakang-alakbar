<?php

use App\Http\Controllers\Api\Master\UsersController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/user'
], function () {
    Route::get('/getlist', [UsersController::class, 'index']);
    Route::get('/getlistall', [UsersController::class, 'indexall']);
    Route::post('/simpan', [UsersController::class, 'store']);
    Route::post('/delete', [UsersController::class, 'destroy']);
});
