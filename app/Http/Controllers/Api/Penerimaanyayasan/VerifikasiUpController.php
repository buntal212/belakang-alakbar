<?php

namespace App\Http\Controllers\Api\Penerimaanyayasan;

use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\Pengeluaranyayasan\Pengajuanup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class VerifikasiUpController extends Controller
{
    public function index()
    {
        $query = Pengajuanup::with(
            [
                'unit',
                'jabatan'
            ]
        )->where('unit','U001')
        ->orderBy('created_at','desc');

        // if (request('search')) {
        //     $search = request('search');

        //     $query->where(function ($q) use ($search) {
        //         $q->where('username', 'like', "%$search%")
        //         ->orWhere('name', 'like', "%$search%");
        //     });
        // }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_pengajuan' => 'required',
            'nilai_pengajuan' => 'required'
        ], [

            'no_pengajuan.required' => 'Tanggal harus di isi',
            'nilai_pengajuan.required' => 'Nilai Persetujuan harus di isi',
        ]);

        try {
            DB::beginTransaction();
                $cek_saldo = Saldo::where('pemilik','J000003')->where('jenis', 'Bank')->first();
                if($validated['nilai_pengajuan'] > $cek_saldo->nominal){
                    return new JsonResponse(['message' => 'Saldo Tidak Mencukupi...!!!'],500);
                }
                $user = Auth::user();
                $data = Pengajuanup::updateOrCreate(
                    [
                        'no_pengajuan' => $validated['no_pengajuan']
                    ],[
                        'flaging' => '2',
                        'tgl_flag' => date('Y-m-d'),
                        'unit_flag' => 'U001',
                        'jabatan_flag' => 'J000003',
                        'user_flag' => $user->kode,
                    ]

                );
            DB::commit();
                $result = Pengajuanup::with(
                    [
                        'unit',
                        'jabatan'
                    ]
                )
                ->where('no_pengajuan', $validated['no_pengajuan'])->get();
                return new JsonResponse([
                    'data' => $result,
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

    public function tolak(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [
            'id.required' => 'Id harus di isi',
        ]);

        try {
            DB::beginTransaction();
                $user = Auth::user();
                $data = Pengajuanup::updateOrCreate(
                    [
                        'id' => $validated['id']
                    ],[
                        'flaging' => '3',
                        'tgl_flag' => date('Y-m-d'),
                        'unit_flag' => 'U002',
                        'user_flag' => $user->kode,
                    ]

                );
            DB::commit();
                $result = Pengajuanup::with(
                    [
                        'unit'
                    ]
                )
                ->where('id', $validated['id'])->get();
                return new JsonResponse([
                    'data' => $result,
                    'status' => 'OK',
                    'message' => 'Data berhasil ditolak'
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
}
