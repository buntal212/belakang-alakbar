<?php


use App\Http\Controllers\Api\Pengeluaranyayasan\PanjarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:sanctum',
    'prefix' => 'pengeluaranyayasan/panjar'
], function () {
    Route::get('/get-list', [PanjarController::class, 'index']);
    Route::post('/simpan', [PanjarController::class, 'store']);
    Route::post('/delete', [PanjarController::class, 'delete']);
     Route::get('/get-list-all', [PanjarController::class, 'indexall']);

});
