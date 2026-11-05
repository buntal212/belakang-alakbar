<?php
use App\Http\Controllers\Api\Master\SiswaController;
use Illuminate\Support\Facades\Route;
Route::group(['middleware' => 'auth:sanctum', 'prefix' => 'master/siswa'], function () {
    Route::get('/getsiswa', [SiswaController::class, 'index']); Route::get('/kelas', [SiswaController::class, 'kelas']); Route::get('/angkatan', [SiswaController::class, 'angkatan']); Route::get('/diskon', [SiswaController::class, 'diskon']); Route::get('/pendapatan', [SiswaController::class, 'pendapatan']); Route::post('/simpan', [SiswaController::class, 'store']); Route::post('/delete', [SiswaController::class, 'destroy']);
});
