<?php

use App\Http\Controllers\Api\Master\JabatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'master/jabatan'
], function () {
    Route::get('/getjabatan', [JabatanController::class, 'index']);
    Route::post('/simpan', [JabatanController::class, 'store']);
     Route::post('/delete', [JabatanController::class, 'destroy']);
});
