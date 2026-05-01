<?php

namespace App\Http\Controllers\Api\Pembayaran;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Api\SaldoController;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran\Pembayaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $search = request('search');

        $query = Pembayaran::query()
            ->leftJoin('tagihan_h as t', 't.notagihan', '=', 'pembayaran.notagihan')
            ->with([
                'rinci.akun',
                'penyedia',
                'unit',
                'jabatan'
            ])
            ->select(
                'pembayaran.*',
                't.id as tagihan_id',
                't.tgl as tgl_tagihan',
                't.kegiatan as kegiatan_tagihan',
                't.penyedia as kode_penyedia',
                't.unit as kode_unit',
                't.jabatan as kode_jabatan',
                't.jumlahbelanja as total_belanja',
                't.diskon as total_diskon',
                't.pajak as total_pajak',
                't.jumlahditagihkan as total_tagihan',
            )
            ->when($jabatan, function ($q) use ($jabatan) {
                $q->where('pembayaran.jabatan', $jabatan);
            })

            // 🔥 SEARCH
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('pembayaran.nopembayaran', 'like', "%$search%")
                    ->orWhere('pembayaran.notagihan', 'like', "%$search%")
                    ->orWhereHas('penyedia', function ($q3) use ($search) {
                        $q3->where('nama', 'like', "%$search%");
                    });
                });
            })

            ->orderBy('pembayaran.created_at', 'desc');

        $data = $query->simplePaginate(request('per_page', 10));

        return new JsonResponse($data);
    }

    public function simpan(Request $request)
    {
        $notrans = $request->notrans ?? null;
        $validated =  $request->validate([
            'notagihan' => 'required',
            'penyedia' => 'required',
            'jabatan' => 'required',
            'unit' => 'required',
            'jenispembayaran' => 'required',
            'saldo' => 'required',
            'sisapembayaran' => 'required',
            'jumlahpembayaran' => 'required',
        ], [
            'notagihan.required' => 'No. Tagihan Harus di isi',
            'penyedia.required' => 'Penyedia Harus Diisi...!!!',
            'jabatan.required' => 'Jabatan Tidak Boleh Kosong...!!!',
            'unit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'jenispembayaran.required' => 'Jenis Pembayaran Tidak Boleh Kosong...!!!',
            'saldo.required' => 'Saldo Tidak Boleh Kosong...!!!',
            'sisapembayaran.required' => 'Sisa Pembayaran Tidak Boleh Kosong...!!!',
            'jumlahpembayaran.required' => 'Jumlah Pembayaran Tidak Boleh Kosong...!!!',
        ]);

        try{
            DB::beginTransaction();
                if(!$notrans){
                    if($validated['jabatan'] == 'J000004'){
                        DB::select('call pembayaranpengeluaranyayasan(@nomor)');
                        $nomor = DB::table('counter')->select('pembayaranpengeluaranyayasan')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::pembayaran($nomor->pembayaranpengeluaranyayasan, $flag);
                    }else if($validated['jabatan'] == 'J000005'){
                        DB::select('call tagihanpengeluarantk(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluarantk')->first();
                        $flag = 'TK';
                        $notrans = FormatingHelper::pembayaran($nomor->tagihanpengeluarantk, $flag);
                    }else if($validated['jabatan'] == 'J000006'){
                        DB::select('call tagihanpengeluaransd(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluaransd')->first();
                        $flag = 'SD';
                        $notrans = FormatingHelper::pembayaran($nomor->tagihanpengeluaransd, $flag);
                    }else{
                        DB::select('call tagihanpengeluaransmp(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluaransmp')->first();
                        $flag = 'SMP';
                        $notrans = FormatingHelper::pembayaran($nomor->tagihanpengeluaransmp, $flag);
                    }
                }
                $user = Auth::user();
                if($validated['jumlahpembayaran'] > $validated['saldo']){
                    return new JsonResponse([
                        'message' => 'Saldo Anda Tidak Mencukupi...!!!'
                    ],500);
                }

                 if($validated['jumlahpembayaran'] > $validated['sisapembayaran']){
                    return new JsonResponse([
                        'message' => 'Pembayaran Terlalu Banyak...!!!'
                    ],500);
                }
                $simpan = Pembayaran::updateOrCreate(
                    [
                        'nopembayaran' => $notrans
                    ],[
                        'tgl' => date('Y-m-d'),
                        'notagihan' => $validated['notagihan'],
                        'unit' => $validated['unit'],
                        'jabatan' => $validated['jabatan'],
                        'jenispembayaran' => $validated['jenispembayaran'],
                        'penyedia' => $validated['penyedia'],
                        'saldo' => $validated['saldo'],
                        'sisapembayaran' => $validated['sisapembayaran'],
                        'nominal' => $validated['jumlahpembayaran'],
                        'user' => $user->kode,
                    ]
                );
                $saldo = SaldoController::saldo($validated['jabatan'],$validated['jenispembayaran'],$validated['jumlahpembayaran']);
            DB::commit();
                $data = self::getnotrans($notrans);
                return new JsonResponse(
                    [
                        'data' => $data,
                        'saldo' => $saldo,
                        'message' => 'Data berhasil disimpan'
                    ]);
        }catch(\Exception $e) {
            DB::rollBack();
                return new JsonResponse([
                    'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),

                ], 410);
        }
    }

    public function hapus(Request $request)
    {
        $validated = $request->validate([
                'id' => 'required',
                'nopembayaran' => 'required'
            ], [
                'id.required' => 'Data ini Tidak Bisa Dihapus,Karena tidak mempunyai ID',
                'nopembayaran.required' => 'No. Tagihan Harus di isi',
            ]);

        try {
            DB::beginTransaction();

            // 🔥 ambil data
            $data = Pembayaran::find($request->id);

            if (!$data) {
                return response()->json([
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // 🔥 kalau ada relasi rinci (optional)
            // $data->rinci()->delete();

            // 🔥 delete utama
            $data->delete();
            self::getnotrans($validated['nopembayaran']);
            $saldo = SaldoController::saldokembali($data->jabatan,$data->jenispembayaran,$data->nominal);
            DB::commit();

            return response()->json([
                'data' => $data,
                'saldo' => $saldo,
                'message' => 'Data berhasil dihapus',
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getnotrans($notrans)
    {
        $data = Pembayaran::query()
        ->leftJoin('tagihan_h as t', 't.notagihan', '=', 'pembayaran.notagihan')
        ->with([
            'rinci' => function ($q) {
                $q->with(['akun']);
            },
            'penyedia',
            'unit',
            'jabatan'
        ])
        ->where('pembayaran.nopembayaran', $notrans)
        ->select(
            'pembayaran.*',
            't.id as tagihan_id',
            't.tgl as tgl_tagihan',
            't.kegiatan as kegiatan_tagihan',
            't.penyedia as kode_penyedia',
            't.unit as kode_unit',
            't.jabatan as kode_jabatan',
            't.jumlahbelanja as total_belanja',
            't.diskon as total_diskon',
            't.pajak as total_pajak',
            't.jumlahditagihkan as total_tagihan',
        )
        ->orderBy('pembayaran.created_at', 'desc')
        ->get();

        return $data;
    }

}
