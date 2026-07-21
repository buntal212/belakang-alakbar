<?php

namespace App\Http\Controllers\Api\Pengeluaranyayasan;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Api\SaldoController;
use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\Panjar\panjar;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PanjarController extends Controller
{
    public function index()
    {
       $query = panjar::with(
            [
                'unit',
                'jabatan',
                'user'
            ]
        )->where('jabatan',request('jabatan'))
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

    public function indexall()
    {
        $jabatan = request('jabatan');

        $data = Panjar::query()
            ->with([
                'unit',
                'jabatan',
                'user',
            ])
            ->withSum([
                'SpjPanjarH as total_terbayar',
            ], 'jumlahpembayaran')
            ->where('jabatan', $jabatan)

            // Jangan tampilkan panjar yang sudah ada di pengembaliansisapanjar
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('pengembaliansisapanjar')
                    ->whereColumn(
                        'pengembaliansisapanjar.nopanjar',
                        'panjar.notrans'
                    );
            })

            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $jumlahPanjar = (float) ($item->jumlahpanjar ?? 0);
                $totalTerbayar = (float) ($item->total_terbayar ?? 0);

                $item->total_terbayar = $totalTerbayar;
                $item->sisa_bayar = $jumlahPanjar - $totalTerbayar;

                return $item;
            })
            ->values();

        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        $notrans = $request->notrans ?? null;
        $validated = $request->validate([
            'notrans'       => 'nullable',
            'tgl'           => 'required|date',
            'unit'          => 'required',
            'jabatan'       => 'required',
            // 'kegiatan'      => 'required',
            'ditujukanke'   => 'required',
            'jumlahpanjar'  => 'required|numeric|gt:0',
        ], [

            'tgl.required'            => 'Tanggal harus diisi.',
            'tgl.date'                => 'Format tanggal tidak valid.',

            'unit.required'           => 'Unit harus dipilih.',

            'jabatan.required'        => 'Jabatan harus dipilih.',

            // 'kegiatan.required'       => 'Kegiatan harus dipilih.',

            'ditujukanke.required'    => 'Tujuan panjar harus dipilih.',

            'jumlahpanjar.required'   => 'Jumlah panjar harus diisi.',
            'jumlahpanjar.numeric'    => 'Jumlah panjar harus berupa angka.',
            'jumlahpanjar.gt'         => 'Jumlah panjar harus lebih dari 0.',
        ]);

        try {
            DB::beginTransaction();
                $ceksaldo = Saldo::where('pemilik', $validated['jabatan'])
                ->where('jenis', 'Tunai')
                ->value('nominal');

                if ($validated['jumlahpanjar'] > ($ceksaldo ?? 0)) {

                    DB::rollBack();

                    return new JsonResponse([
                        'status' => 'error',
                        'message' => 'Maaf saldo tidak mencukupi, saldo yang dimiliki adalah ' .
                            FormatingHelper::rupiah($ceksaldo ?? 0)
                    ], 500);
                }

                if(!$notrans){
                    DB::select('call panjar(@nomor)');
                    $nomor = DB::table('counter')->select('panjar')->first();
                    $notrans = FormatingHelper::notranspanjar($nomor->panjar, 'PJ');
                }

                $user = Auth::user();
                $data = panjar::updateOrCreate(
                    [
                        'notrans' => $notrans
                    ],[
                        'tgl' => $validated['tgl'],
                        // 'kegiatan' => $validated['kegiatan'],
                        'unit' => $validated['unit'],
                        'jabatan' => $validated['jabatan'],
                        'ditujukanke' => $validated['ditujukanke'],
                        'jumlahpanjar' => $validated['jumlahpanjar'],
                        'userentry' => $user->kode,
                    ]

                );
                SaldoController::saldo($validated['jabatan'],'2',$validated['jumlahpanjar']);
            DB::commit();
                $result = panjar::with(
                    [
                        'unit',
                        'jabatan',
                        'user'
                    ]
                )
                ->where('notrans', $notrans)->get();
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

     public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [

            'id.required' => 'Data Tidak Bisa Dihapus,karena Tidak mempunyai ID!!!',
        ]);

        try {
            DB::beginTransaction();
                $data  = panjar::find($validated['id']);

                if (!$data) {
                    return response()->json([
                        'status' => 'ERROR',
                        'message' => 'Data tidak ditemukan'
                    ], 404);
                }

                // HAPUS PERMANEN
                $data->delete();
                SaldoController::saldokembali($request->jabatan,'2',$request->jumlahpanjar);
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
