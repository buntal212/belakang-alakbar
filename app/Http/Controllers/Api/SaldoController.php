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
        $jenisSaldo = $jenis === '1' ? 'Bank' : 'Tunai';

        self::kurangiSaldo($jabatan, $jenisSaldo, $nominal);
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
        self::pastikanNominalPositif($nominal);

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
        self::kurangiSaldo($jabatan, 'Panjar', $nominal);
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

    private static function kurangiSaldo($jabatan, string $jenis, $nominal): void
    {
        $nominal = (float) $nominal;

        self::pastikanNominalPositif($nominal);

        $berhasil = Saldo::where('jenis', $jenis)
            ->where('pemilik', $jabatan)
            ->where('nominal', '>=', $nominal)
            ->decrement('nominal', $nominal);

        if ($berhasil === 0) {
            $saldo = Saldo::where('jenis', $jenis)
                ->where('pemilik', $jabatan)
                ->value('nominal');

            if ($saldo === null) {
                throw new \Exception('Data saldo tidak ditemukan');
            }

            throw new \Exception(
                "Saldo {$jenis} tidak mencukupi. Saldo tersedia Rp " .
                number_format((float) $saldo, 0, ',', '.')
            );
        }
    }

    private static function pastikanNominalPositif($nominal): void
    {
        if (!is_numeric($nominal) || (float) $nominal <= 0) {
            throw new \Exception('Nominal saldo harus berupa angka dan lebih dari nol');
        }
    }

    public static function saldopanjarmasuk($jabatan, $nominal)
    {
        self::pastikanNominalPositif($nominal);

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
