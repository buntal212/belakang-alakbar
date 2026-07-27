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

    public function indexall()
    {
        $jabatan = request('jabatan');
        $tglpembayaran = request('tglpembayaran');

        $pembayaran = Pembayaran::query()
            ->leftJoin('tagihan_h as t', 't.notagihan', '=', 'pembayaran.notagihan')
            ->leftJoin('gu_r as g', 'g.nospj', '=', 'pembayaran.nopembayaran')
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
                DB::raw(
                    'CAST(NULL AS CHAR CHARACTER SET utf8mb4) '
                    . 'COLLATE utf8mb4_unicode_ci as kode_penerima'
                ),
                DB::raw(
                    'CAST(NULL AS CHAR CHARACTER SET utf8mb4) '
                    . 'COLLATE utf8mb4_unicode_ci as diberikan_kepada'
                ),
                DB::raw("'LS' as asal"),
            )
            ->where('pembayaran.flag', '2')
            ->where('pembayaran.jabatan', $jabatan)
            ->where('pembayaran.tgl', '<=', $tglpembayaran)
            ->whereNull('g.nogu');

        $pengembalianSisaPanjar = DB::table('pengembaliansisapanjar as p')
            ->leftJoin('gu_r as g', 'g.nospj', '=', 'p.notrans')
            ->leftJoin('panjar as pj', 'pj.notrans', '=', 'p.nopanjar')
            ->leftJoin('users as u', function ($join) {
                $join->on(
                    DB::raw('u.kode COLLATE utf8mb4_unicode_ci'),
                    '=',
                    DB::raw('pj.ditujukanke COLLATE utf8mb4_unicode_ci')
                );
            })
            ->selectRaw(
                "p.id,
                p.notrans as nopembayaran,
                p.tgl,
                p.nopanjar as notagihan,
                NULL as penyedia,
                '2' as jenispembayaran,
                p.jabatan,
                p.unit,
                p.userentry as user,
                p.totalpanjar as saldo,
                0 as sisapembayaran,
                p.sisapanjar as nominal,
                '2' as flag,
                NULL as tgl_verif,
                NULL as user_verif,
                NULL as alasan,
                p.created_at,
                NULL as updated_at,
                NULL as tagihan_id,
                p.tgl as tgl_tagihan,
                'Pengembalian Sisa Panjar' as kegiatan_tagihan,
                NULL as kode_penyedia,
                p.unit as kode_unit,
                p.jabatan as kode_jabatan,
                p.totalpembayaran as total_belanja,
                0 as total_diskon,
                0 as total_pajak,
                p.sisapanjar as total_tagihan,
                CONVERT(pj.ditujukanke USING utf8mb4)
                    COLLATE utf8mb4_unicode_ci as kode_penerima,
                CONVERT(COALESCE(u.name, pj.ditujukanke, '-') USING utf8mb4)
                    COLLATE utf8mb4_unicode_ci as diberikan_kepada,
                'PANJAR' as asal"
            )
            ->where('p.jabatan', $jabatan)
            ->where('p.tgl', '<=', $tglpembayaran)
            ->whereNull('g.nogu');

        $data = DB::query()
            ->fromSub($pembayaran->unionAll($pengembalianSisaPanjar), 'transaksi')
            ->orderByDesc('created_at')
            ->get();

        $query = Pembayaran::hydrate(
            $data->map(fn ($item) => (array) $item)->all()
        )->load([
            'rinci.akun',
            'penyedia',
            'unit',
            'jabatan'
        ]);

        return new JsonResponse($query);
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

        try {
            DB::beginTransaction();
            if (!$notrans) {
                if ($validated['jabatan'] == 'J000004') {
                    DB::select('call pembayaranpengeluaranyayasan(@nomor)');
                    $nomor = DB::table('counter')->select('pembayaranpengeluaranyayasan')->first();
                    $flag = 'PK';
                    $notrans = FormatingHelper::pembayaran($nomor->pembayaranpengeluaranyayasan, $flag);
                } else if ($validated['jabatan'] == 'J000005') {
                    DB::select('call pembayaranpengeluarantk(@nomor)');
                    $nomor = DB::table('counter')->select('pembayaranpengeluarantk')->first();
                    $flag = 'TK';
                    $notrans = FormatingHelper::pembayaran($nomor->pembayaranpengeluarantk, $flag);
                } else if ($validated['jabatan'] == 'J000006') {
                    DB::select('call pembayaranpengeluaransd(@nomor)');
                    $nomor = DB::table('counter')->select('pembayaranpengeluaransd')->first();
                    $flag = 'SD';
                    $notrans = FormatingHelper::pembayaran($nomor->pembayaranpengeluaransd, $flag);
                } else {
                    DB::select('call pembayaranpengeluaransmp(@nomor)');
                    $nomor = DB::table('counter')->select('pembayaranpengeluaransmp')->first();
                    $flag = 'SMP';
                    $notrans = FormatingHelper::pembayaran($nomor->pembayaranpengeluaransmp, $flag);
                }
            }
            $user = Auth::user();
            $cek = Pembayaran::where('notagihan', $validated['notagihan'])->where('flag','1')->count();
            if ($cek > 0) {
                return new JsonResponse(
                    [
                        'message' => 'Maaf No. Tagihanan '. $validated['notagihan'] .' Sudah Diajukan dan belum dapat tindak lanjut...!!!'
                    ],500
                );
            }
            // $belumterbayar = DB::table('pembayaran')
            //                 ->where('jabatan', $validated['jabatan'])
            //                 ->where('flag', 1)
            //                 ->sum('nominal');
            // $saldoalokasi = $validated['saldo'] - $belumterbayar ;
            // // return $saldoalokasi.' || '.$validated['saldo'].' || '.$belumterbayar;
            // if ($validated['jumlahpembayaran'] > $saldoalokasi) {
            //     return new JsonResponse([
            //          'message' => 'Saldo Alokasi Anda Tidak Mencukupi...!!! Sisa Saldo Alokasi Anda sebesar ' . FormatingHelper::rupiah($saldoalokasi)
            //     ], 500);
            // }

            // if ($validated['jumlahpembayaran'] > $validated['sisapembayaran']) {
            //     return new JsonResponse([
            //         'message' => 'Pembayaran Terlalu Banyak...!!!'
            //     ], 500);
            // }
            $simpan = Pembayaran::updateOrCreate(
                [
                    'nopembayaran' => $notrans
                ],
                [
                    'tgl' => date('Y-m-d'),
                    'notagihan' => $validated['notagihan'],
                    'unit' => $validated['unit'],
                    'jabatan' => $validated['jabatan'],
                    'jenispembayaran' => $validated['jenispembayaran'],
                    'penyedia' => $validated['penyedia'],
                    'saldo' => $validated['saldo'],
                    'sisapembayaran' => $validated['sisapembayaran'],
                    'nominal' => $validated['jumlahpembayaran'],
                    'flag' => '1',
                    'user' => $user->kode,
                ]
            );
            // $saldo = SaldoController::saldo($validated['jabatan'], $validated['jenispembayaran'], $validated['jumlahpembayaran']);
            DB::commit();
            $data = self::getnotrans($notrans);
            return new JsonResponse(
                [
                    'data' => $data,
                    // 'saldo' => $saldoalokasi,
                    'message' => 'Data berhasil disimpan'
                ]
            );
        } catch (\Exception $e) {
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
            $cek = Pembayaran::where('id', $request->id)
                    ->where('flag','<>', '1')
                    ->count();
            if ($cek > 0) {
                return response()->json([
                    'message' => 'Data Sudah di verif'
                ], 404);
            }
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
            // $saldo = SaldoController::saldokembali($data->jabatan, $data->jenispembayaran, $data->nominal);
            DB::commit();

            return response()->json([
                'data' => $data,
                // 'saldo' => $saldo,
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
