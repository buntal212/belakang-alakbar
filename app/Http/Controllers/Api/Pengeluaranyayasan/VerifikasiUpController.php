<?php

namespace App\Http\Controllers\Api\Pengeluaranyayasan;

use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\Pengeluaranyayasan\Pengajuanup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nette\Utils\Json;
use Symfony\Component\HttpFoundation\JsonResponse;

class VerifikasiUpController extends Controller
{
    public function index()
    {
        $query = Pengajuanup::with([
            'unit',
            'jabatan'
        ])
        ->where('unit','!=','U001')
        ->orderBy('created_at', 'desc');

        // 🔥 FILTER SEARCH
        if (request('search')) {

            $search = request('search');

            $query->where(function ($q) use ($search) {

                $q->where('no_pengajuan', 'like', "%{$search}%");

            });
        }

        // 🔥 FILTER TANGGAL
        if (request('dateFrom') && request('dateTo')) {

            $query->whereBetween('tgl', [
                request('dateFrom'),
                request('dateTo')
            ]);
        }
        // 🔥 FILTER STATUS
        if (request()->filled('statusverif')) {

            $query->where('flaging', request('statusverif'));
        }

        if (request()->filled('unit')) {

            $query->where('jabatan', request('unit'));
        }


        // 🔥 PAGINATION
        $data = $query->simplePaginate(
            request('per_page', 10)
        );

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'no_pengajuan' => 'required',
            // 'no_pengajuan' => 'required'
        ], [

            'no_pengajuan.required' => 'Tanggal harus di isi',
            // 'no_pengajuan.required' => 'Nilai Persetujuan harus di isi'
        ]);

        try {
            DB::beginTransaction();
                $belumverif = Pengajuanup::where('flaging', '1')->where('jabatan','!=','J000004')->sum('nilai_pengajuan');
                $saldo = Saldo::where('pemilik','J000004')->where('jenis','Bank')->first();
                if($belumverif > $saldo->nominal){
                    return new JsonResponse(['message' => 'Saldo Tidak Mencukupi'],500);
                }
                $user = Auth::user();
                $data = Pengajuanup::updateOrCreate(
                    [
                        'no_pengajuan' => $validated['no_pengajuan']
                    ],[
                        'flaging' => '2',
                        'tgl_flag' => date('Y-m-d'),
                        'unit_flag' => 'U002',
                        'jabatan_flag' => 'J000004',
                        'user_flag' => $user->kode,
                    ]

                );
            DB::commit();
                $result = Pengajuanup::with(
                    [
                        'unit'
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
        return $request->alasan;
        $validated = $request->validate([
            'id' => 'required',
            'alasan' => 'required',
            'jabatan_flag' => 'required',
        ], [
            'id.required' => 'Id harus di isi',
            'alasan.required' => 'Alasan harus di isi',
            'jabatan_flag.required' => 'Jabatan harus di isi',
        ]);

        try {
            DB::beginTransaction();
                $user = Auth::user();
                $data = Pengajuanup::find($validated['id']);

                $data->flaging = '3';
                $data->alasantolak = $validated['alasan'];
                $data->tgl_flag = now();
                $data->unit_flag = 'U002';
                $data->jabatan_flag = $validated['jabatan_flag'];
                $data->user_flag = $user->kode;

                $data->save();

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
