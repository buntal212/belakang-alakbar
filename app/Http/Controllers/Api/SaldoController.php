<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use Illuminate\Http\JsonResponse;

class SaldoController extends Controller
{
    public function index()
    {
        $pemiliik = request('jabatan');
        $data = Saldo::where('pemilik', $pemiliik)->orderBy('jenis')->get();
        return new JsonResponse($data);
    }
}
