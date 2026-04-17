<?php

namespace App\Http\Controllers\Api\Pengeluaransd;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pengeluaranyayasan\Pengajuanup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PengajuanUpController extends Controller
{
    public function index()
    {
        $query = Pengajuanup::with(
            [
                'unit'
            ]
        )->where('unit','U003')
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
        $kode = $request->no_pengajuan ?? null;
        $validated = $request->validate([
            'tgl' => 'required',
            'nilai_pengajuan' => 'required'
        ], [

            'tgl.required' => 'Tanggal harus di isi',
            'nilai_pengajuan.required' => 'Nilai Pengajuan harus di isi',
        ]);

        try {
            DB::beginTransaction();
                if (!$kode) {
                    DB::select('call pengajuanupbendsd(@nomor)');
                    $nomor = DB::table('counter')->select('pengajuanupbendsd')->first();
                    $semester = 'S1';
                    $kode = FormatingHelper::notrans($nomor->pengajuanupbendsd, 'UP', $semester,'SD');
                }
                $cek = Pengajuanup::where('no_pengajuan', $kode)->where('flaging','!=','1')->count();
                if($cek > 0){
                    return new JsonResponse(['message' => 'Data Sudah DiVerif!!!'],500);
                }
                $user = Auth::user();
                $data = Pengajuanup::updateOrCreate(
                    [
                        'no_pengajuan' => $kode
                    ],[
                        'tgl' => $validated['tgl'],
                        'unit' => 'U003',
                        'user' => $user->kode,
                        'nilai_pengajuan' =>$validated['nilai_pengajuan']
                    ]

                );
            DB::commit();
                $result = Pengajuanup::with(
                [
                    'unit'
                ])->
                where('no_pengajuan', $kode)->get();
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

    public function destroy(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [

            'id.required' => 'Data Tidak Bisa Dihapus,karena Tidak mempunyai ID!!!',
        ]);

        try {
            DB::beginTransaction();
                $data  = Pengajuanup::find($validated['id']);

                if (!$data) {
                    return response()->json([
                        'status' => 'ERROR',
                        'message' => 'Data tidak ditemukan'
                    ], 404);
                }
                $cek = Pengajuanup::where('id', $validated['id'])->where('flaging','!=','1')->count();
                if($cek > 0){
                    return new JsonResponse(['message' => 'Data Sudah DiVerif!!!'],500);
                }

                // HAPUS PERMANEN
                $data->delete();
            DB::commit();
                return new JsonResponse([
                    'data' => $data ,
                    'status' => 'OK',
                    'message' => 'Data berhasil dihapus'
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
