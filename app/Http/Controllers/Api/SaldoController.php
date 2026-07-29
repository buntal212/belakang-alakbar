<?php

namespace App\Http\Controllers\Api;

use App\Events\SaldoUpdated;
use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use Illuminate\Http\JsonResponse;

class SaldoController extends Controller
{
    public function index()
    {
        $pemilik = request('jabatan');
        $data = Saldo::where('pemilik', $pemilik)->orderBy('jenis')->get();
        return new JsonResponse($data);
    }

    public static function saldo($jabatan,$jenis, $nominal)
    {

        if($jenis === '1'){
            $keluar = Saldo::where('jenis', 'Bank')->where('pemilik', $jabatan)->first();
            // $masuk = Saldo::where('jenis', 'Tunai')->where('pemilik', $jabatan)->first();

        }else{
            $keluar = Saldo::where('jenis', 'Tunai')->where('pemilik', $jabatan)->first();
            // $masuk = Saldo::where('jenis', 'Bank')->where('pemilik', $jabatan)->first();

        }
       if (!$keluar) {
            throw new \Exception('Data saldo tidak ditemukan');
        }

        $keluar->decrement('nominal', $nominal);
        $data = Saldo::where('pemilik', $jabatan)->get();

        \Log::info('BROADCAST SALDO DIPANGGIL', [
            'channel' => 'saldo.' . $jabatan,
            'jenis' => $jenis,
            'data' => $data->toArray(),
        ]);

        broadcast(new SaldoUpdated([
            'pemilik' => $jabatan,
            'jenis' => $jenis,
            'data' => $data,
        ]));

        return $data;
    }

    public static function saldokembali($jabatan,$jenis, $nominal)
    {
        if($jenis === '1'){
            // $keluar = Saldo::where('jenis', 'Bank')->where('pemilik', $jabatan)->first();
            $masuk = Saldo::where('jenis', 'Bank')->where('pemilik', $jabatan)->first();

        }else{
            // $keluar = Saldo::where('jenis', 'Tunai')->where('pemilik', $jabatan)->first();
            $masuk = Saldo::where('jenis', 'Tunai')->where('pemilik', $jabatan)->first();

        }
       if (!$masuk) {
            throw new \Exception('Data saldo tidak ditemukan');
        }

        $masuk->increment('nominal', $nominal);
        $data = Saldo::where('pemilik', $jabatan)->get();

        broadcast(new SaldoUpdated([
            'pemilik' => $jabatan,
            'jenis' => $jenis,
            'data' => $data,
        ]));

        return $data;
    }

    public static function saldopanjarkeluar($jabatan, $nominal)
    {


       $keluar = Saldo::where('jenis', 'Panjar')->where('pemilik', $jabatan)->first();

       if (!$keluar) {
            throw new \Exception('Data saldo tidak ditemukan');
        }

        $keluar->decrement('nominal', $nominal);
        $data = Saldo::where('pemilik', $jabatan)->get();

        \Log::info('BROADCAST SALDO DIPANGGIL', [
            'channel' => 'saldo.' . $jabatan,
            // 'jenis' => $jenis,
            'data' => $data->toArray(),
        ]);

        broadcast(new SaldoUpdated([
            'pemilik' => $jabatan,
            // 'jenis' => $jenis,
            'data' => $data,
        ]));

        return $data;
    }

    public static function saldopanjarmasuk($jabatan, $nominal)
    {

        $masuk = Saldo::where('jenis', 'Panjar')->where('pemilik', $jabatan)->first();

       if (!$masuk) {
            throw new \Exception('Data saldo tidak ditemukan');
        }

        $masuk->increment('nominal', $nominal);
        $data = Saldo::where('pemilik', $jabatan)->get();

        broadcast(new SaldoUpdated([
            'pemilik' => $jabatan,
            'data' => $data,
        ]));

        return $data;
    }
}
