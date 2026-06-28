<?php

namespace App\Http\Controllers\Api\Penerimaanyayasan;

use App\Http\Controllers\Api\SaldoController;
use App\Http\Controllers\Controller;
use App\Models\Pengajuangu\PengajuanguHeder;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifikasiGuController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $search = request('search');
        $dateFrom = request('dateFrom');
        $dateTo = request('dateTo');

        $query = PengajuanguHeder::query()
            ->with([
                'rinci.pembayaran',
                'rinci.penyedia',
                'unit',
                'jabatan'
            ])
            ->when($jabatan, function ($q) use ($jabatan) {
                $q->where('jabatan', $jabatan);
            })
            ->when($dateFrom && $dateTo, function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('tgl', [$dateFrom, $dateTo]);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('nogu', 'like', "%$search%");
            })
            ->where('flag', '2')->orWhere('flag', '3')->orWhere('flag', '4')->orWhere('flag', '5')
            ->orderBy('created_at', 'desc');

        $data = $query->simplePaginate(request('per_page', 10));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_pengajuan' => 'required',
            'dari' => 'required',
            'tujuan' => 'required',
            'nilai_pengajuan' => 'required'
        ], [

            'no_pengajuan.required' => 'Tanggal harus di isi',
            'dari.required' => 'Jabatan Pengirim Tidak Boleh Kosong',
            'tujuan.required' => 'Jabatan Yang Dituju Tidak Boleh Kosong',
            'nilai_pengajuan.required' => 'Nilai Persetujuan harus di isi',
        ]);

        try {
            DB::beginTransaction();

                $user = Auth::user();
                $verif = PengajuanguHeder::where('nogu', $validated['no_pengajuan'])->first();
                $verif->flag = '3';
                $verif->tgl_verif_ben_penerimaan = date('Y-m-d');
                $verif->user_verif_ben_penerimaan = $user->kode;
                $verif->save();

                $saldo = SaldoController::saldokembali($validated['tujuan'],'1',$validated['nilai_pengajuan']);
            DB::commit();
                $result = self::getlistpengajuangubynotrans($validated['no_pengajuan']);

                return new JsonResponse([
                    'data' => $result,
                    'saldo' => $saldo,
                    'message' => 'Data berhasil disimpan'
                ]);

        }catch (\Exception $e) {
            DB::rollBack();
                return new JsonResponse([
                    'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),

                ], 410);
        }
    }

    public function getlistpengajuangubynotrans($notrans)
    {
        $query = PengajuanguHeder::query()
            ->with([
                'rinci.pembayaran',
                'rinci.penyedia',
                'unit',
                'jabatan'
            ])
            ->where('nogu', $notrans)
            ->get();

        return $query;
    }
}
