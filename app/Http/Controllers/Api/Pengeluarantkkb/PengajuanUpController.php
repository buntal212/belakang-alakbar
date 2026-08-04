<?php

namespace App\Http\Controllers\Api\Pengeluarantkkb;

use App\Helpers\Formating\FormatingHelper;
use App\Events\SaldoUpdated;
use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\Pengeluaranyayasan\Pengajuanup;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PengajuanUpController extends Controller
{
    public function index()
    {
        $query = Pengajuanup::with(
            [
                'unit',
                'jabatan'
            ]
        )->where('unit','U002')
        ->orderBy('created_at','desc');

        if (request('search')) {
            $search = request('search');

            $query->where('no_pengajuan', 'like', "%{$search}%");
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);

    }

    public function store(Request $request)
    {
        $kode = $request->no_pengajuan ?? null;
        $validated = $request->validate([
            'tgl' => 'required',
            'nilai_pengajuan' => 'required',
            'jabatan' => 'required'
        ], [

            'tgl.required' => 'Tanggal harus di isi',
            'nilai_pengajuan.required' => 'Nilai Pengajuan harus di isi',
            'jabatan.required' => 'Jabatan harus di isi',
        ]);

        try {
            DB::beginTransaction();
                if (!$kode) {
                    DB::select('call pengajuanupbendtktb(@nomor)');
                    $nomor = DB::table('counter')->select('pengajuanupbendtktb')->first();
                    $semester = 'S1';
                    $kode = FormatingHelper::notrans($nomor->pengajuanupbendtktb, 'UP', $semester,'TK');
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
                        'unit' => 'U002',
                        'user' => $user->kode,
                        'jabatan' => $validated['jabatan'],
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

    public function terimaUang(Request $request)
    {
        $validated = $request->validate([
            'no_pengajuan' => 'required',
            'nilai_pengajuan' => 'required',
            'unitpengirim' => 'required',
            'unitpenerima' => 'required',
        ], [
            'no_pengajuan.required' => 'No. Pengajuan Harus di isi',
            'nilai_pengajuan.required' => 'Nilai Persetujuan Harus Diisi...!!!',
            'unitpengirim.required' => 'Unit Pengirim Tidak Boleh Kosong...!!!',
            'unitpenerima.required' => 'Unit Penerima Tidak Boleh Kosong...!!!',
        ]);

        try {
            DB::beginTransaction();
                $cek = Pengajuanup::where('no_pengajuan', $validated['no_pengajuan'])->where('flaging','!=','2')->count();
                if($cek > 0){
                    return new JsonResponse(['message' => 'UP ini Belum DiVerif!!!'],500);
                }

                $user = Auth::user();
                $data = Pengajuanup::updateOrCreate(
                    [
                        'no_pengajuan' => $validated['no_pengajuan']
                    ],[
                        'tgl_terima' => date('Y-m-d'),
                    ]

                );
                $pengirim = Saldo::where('pemilik', $validated['unitpengirim'])
                    ->where('jenis', 'Bank')
                    ->lockForUpdate()
                    ->first();

                $pengirim->nominal -= (int) $validated['nilai_pengajuan'];
                $pengirim->save();

                $penerima = Saldo::where('pemilik', $validated['unitpenerima'])
                    ->where('jenis', 'Bank')
                    ->lockForUpdate()
                    ->first();

                $penerima->nominal += (int) $validated['nilai_pengajuan'];
                $penerima->save();
            DB::commit();
                self::broadcastSaldo($validated['unitpengirim']);
                self::broadcastSaldo($validated['unitpenerima']);
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

    private static function broadcastSaldo(string $pemilik): void
    {
        $saldo = Saldo::where('pemilik', $pemilik)->orderBy('jenis')->get();

        broadcast(new SaldoUpdated([
            'pemilik' => $pemilik,
            'data' => $saldo,
        ]));
    }
}
