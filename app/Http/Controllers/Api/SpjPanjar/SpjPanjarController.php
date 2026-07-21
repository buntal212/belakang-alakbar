<?php

namespace App\Http\Controllers\Api\SpjPanjar;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\SpjPanjar\spjpanjar_heder;
use App\Models\SpjPanjar\spjpanjar_rinci;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpjPanjarController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $query = spjpanjar_heder::select('spjpanjar_h.*','panjar.notrans as notagihan')
        ->leftJoin('panjar', 'panjar.notrans', '=', 'spjpanjar_h.nopanjar')
        ->with(
            [
                'rinci'=> function ($q) {
                     $q->with(['akun']);
                },
                'penyedia',
                'unit',
                'jabatan',
                'user'
            ]
        )
        ->where('spjpanjar_h.jabatan', $jabatan)
        ->orderBy('spjpanjar_h.created_at','desc');
        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }
    public function storeheder(Request $request)
    {
        $notrans = $request->notrans ?? null;
        $validated =  $request->validate([
            'nopanjar' => 'required',
            'tglspjpanjar' => 'required',
            'tglspj' => 'required',
            'jabatan' => 'required',
            'kodeunit' => 'required',
            'kodeditujukjanke' => 'required',
            'kegiatan' => 'required',
            'penyedia' => 'required',
            'totalpanjar' => 'required',
            'totalbelanja' => 'required',
            'diskon' => 'nullable',
            'pajak' => 'nullable',

        ], [
            'nopanjar.required' => 'No. Panjar Harus di isi',
            'tglspjpanjar.required' => 'Tanggal SPJ Panjar Harus di isi',
            'tglspj.required' => 'Tanggal Panjar Harus di isi',
            'jabatan.required' => 'Jabatan Harus Diisi...!!!',
            'kodeunit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'kegiatan.required' => 'Kegiatan Tidak Boleh Kosong...!!!',
            'penyedia.required' => 'Penyedia Tidak Boleh Kosong...!!!',
            'totalbelanja.required' => 'Total Belanja Tidak Boleh Kosong...!!!',
            'totalpanjar.required' => 'Total Tidak Boleh Kosong...!!!',
            'kodeditujukjanke.required' => 'Ditujukan ke Tidak Boleh Kosong...!!!',
        ]);

        try{
                DB::beginTransaction();
                $cekheder = self::cekheder($validated['nopanjar'],$notrans,$validated['totalpanjar'],$validated['totalbelanja'],$validated['diskon'],$validated['pajak'],$request->jumlahpembayaran);

                if(!$notrans){
                    if($validated['jabatan'] == 'J000004'){
                        DB::select('call spjpanjar(@nomor)');
                        $nomor = DB::table('counter')->select('spjpanjar')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::spjpanjar($nomor->spjpanjar, $flag);
                    }else if($validated['jabatan'] == 'J000005'){
                        DB::select('call spjpanjar(@nomor)');
                        $nomor = DB::table('counter')->select('spjpanjar')->first();
                        $flag = 'TK';
                        $notrans = FormatingHelper::spjpanjar($nomor->spjpanjar, $flag);
                    }else if($validated['jabatan'] == 'J000006'){
                        DB::select('call spjpanjar(@nomor)');
                        $nomor = DB::table('counter')->select('spjpanjar')->first();
                        $flag = 'SD';
                        $notrans = FormatingHelper::spjpanjar($nomor->spjpanjar, $flag);
                    }else{
                        DB::select('call spjpanjar(@nomor)');
                        $nomor = DB::table('counter')->select('spjpanjar')->first();
                        $flag = 'SMP';
                        $notrans = FormatingHelper::spjpanjar($nomor->spjpanjar, $flag);
                    }
                }
                $user = Auth::user();
                // if($validated['diskon'] > $validated['totalmentah']){
                //     return new JsonResponse([
                //         'message' => 'Diskon Tidak Boleh Lebih besar Dari Total Belanja'
                //     ],500);
                // }
                $simpan = spjpanjar_heder::updateOrCreate(
                    [
                        'nospjpanjar' => $notrans
                    ],[
                        'nopanjar' => $validated['nopanjar'],
                        'tglspjpanjar' => $validated['tglspjpanjar'],
                        'tglpanjar' => $validated['tglspj'],
                        'unit' => $validated['kodeunit'],
                        'jabatan' => $validated['jabatan'],
                        'ditujukanke' => $validated['kodeditujukjanke'],
                        'kegiatan' => $validated['kegiatan'],
                        'penyedia' => $validated['penyedia'],
                        'jumlahpanjar' => $validated['totalpanjar'],
                        'totalbelanja' => $validated['totalbelanja'],
                        'diskon' => $validated['diskon'],
                        'pajak' => $validated['pajak'],
                        'jumlahpembayaran' => $request->jumlahpembayaran,
                        'user' => $user->kode,
                    ]
                );
                self::gettotalbelanja($notrans);
            DB::commit();
                $data = self::getnotrans($notrans);
                return new JsonResponse(
                    [
                        'data' => $data,
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

    public function storerinci(Request $request)
    {
        $validated =  $request->validate([
            'notrans' => 'required',
            'akun' => 'required',
            'rincian' => 'required',
            'qty' => 'required|numeric|gt:0',
            'harga' => 'required|numeric|gt:0',
            'satuan' => 'required',
            'jumlah' => 'required|numeric|gt:0',
        ], [
            'notrans.required' => 'Notrans Harus di isi',
            'akun.required' => 'Akun Harus Diisi...!!!',
            'rincian.required' => 'Rincian Tidak Boleh Kosong...!!!',
            'qty.required' => 'Kuantitas Tidak Boleh Kosong...!!!',
            'qty.numeric' => 'Kuantitas harus angka',
            'qty.gt' => 'Kuantitas harus lebih dari 0 ❌',
            'harga.required' => 'Harga Tidak Boleh Kosong...!!!',
            'harga.numeric' => 'Harga harus angka',
            'harga.gt' => 'Harga harus lebih dari 0 ❌',
            'satuan.required' => 'Satuan Tidak Boleh Kosong...!!!',
            'jumlah.required' => 'Jumlah Tidak Boleh Kosong...!!!',
            'jumlah.gt' => 'Jumlah harus lebih dari 0 ❌',
        ]);
        try{
            DB::beginTransaction();
                $nopanjar = spjpanjar_heder::select('nopanjar')->where('nospjpanjar', $validated['notrans'])->first();
                $cek = self::sisapanjar($nopanjar->nopanjar,$validated['jumlah']);
                if($cek->sisa_setelah_transaksi < 0)
                {
                    throw new \Exception('Jumlah Belanja Melebihi Jumlah Panjar...!!');
                }

                $user = Auth::user();

                $simpan = spjpanjar_rinci::create(
                    [
                        'nospjpanjar' => $validated['notrans'],
                        'akun' => $validated['akun'],
                        'rincian' => $validated['rincian'],
                        'qty' => $validated['qty'],
                        'satuan' => $validated['satuan'],
                        'harga' => $validated['harga'],
                        'jumlah' => $validated['jumlah'],
                        'user' => $user->kode,
                    ]
                );
                self::gettotalbelanja($validated['notrans']);
            DB::commit();
                $data = self::getnotrans($validated['notrans']);
                return new JsonResponse(
                    [
                        'data' => $data,
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
    public function getnotrans($notrans)
    {
        $data = spjpanjar_heder::with(
            [
                'rinci',
                'Penyedia',
                'jabatan',
                'unit'
            ]
        )->where('nospjpanjar', $notrans)
        ->get();

        return $data;
    }

    public function gettotalbelanja($notrans)
        {
        // ambil header
        $header = spjpanjar_heder::where('nospjpanjar', $notrans)->first();

        if (!$header) {
            throw new \Exception('Data tagihan tidak ditemukan');
        }

        // hitung total rincian
        $total = spjpanjar_rinci::where('nospjpanjar', $notrans)->sum('jumlah');

        // ambil diskon & pajak
        $diskon = $header->diskon ?? 0;
        $pajak  = $header->pajak ?? 0;

        // 🔥 VALIDASI: diskon tidak boleh lebih besar dari total
        // if ($diskon > $total) {
        //     throw new \Exception('Diskon tidak boleh melebihi total belanja');
        // }

        // hitung jumlah ditagihkan
        $jumlahditagihkan = $total - $diskon + $pajak;

        if ($jumlahditagihkan < 0) {
            throw new \Exception('Total Tidak Boleh Kurang Dari 0');
        }

        // 🔥 GUARD: tidak boleh minus (double safety)
        $jumlahditagihkan = max(0, $jumlahditagihkan);

        // update ke database
        $header->update([
            'totalbelanja' => $total,
            'jumlahpembayaran' => $jumlahditagihkan,
        ]);

        return $header;
    }

    public function sisapanjar($nopanjar, $nominal)
    {
        $sisa = DB::table('panjar as p')
        ->leftJoin('spjpanjar_h as s', 's.nopanjar', '=', 'p.notrans')
        ->where('p.notrans', $nopanjar)
        ->selectRaw('
            p.jumlahpanjar,
            COALESCE(SUM(s.jumlahpembayaran),0) as total_pembayaran,
            (p.jumlahpanjar - COALESCE(SUM(s.jumlahpembayaran),0)) as sisa_panjar
        ')
        ->groupBy('p.notrans', 'p.jumlahpanjar')
        ->first();

        $sisa->sisa_setelah_transaksi = $sisa->sisa_panjar - $nominal;

        return $sisa;
    }

    public static function cekheder($nopanjar,$notrans,$totalpanjar,$totalbelanja,$diskon,$pajak,$jumlahpembayaran)
    {
        $query = DB::table('panjar as p')
            ->leftJoin('spjpanjar_h as s', function ($join) use ($notrans) {
                $join->on('s.nopanjar', '=', 'p.notrans');

                // Saat update, jangan hitung transaksi yang sedang diedit
                if ($notrans) {
                    $join->where('s.nospjpanjar', '!=', $notrans);
                }
            })
            ->where('p.notrans', $nopanjar)
            ->selectRaw('
                p.jumlahpanjar,
                COALESCE(SUM(s.jumlahpembayaran), 0) as total_pembayaran
            ')
            ->selectSub(function ($subQuery) use ($notrans) {
                $subQuery->from('spjpanjar_h')
                    ->select('diskon')
                    ->where('nospjpanjar', $notrans)
                    ->limit(1);
            }, 'diskon')
            ->selectSub(function ($subQuery) use ($notrans) {
                $subQuery->from('spjpanjar_h')
                    ->select('pajak')
                    ->where('nospjpanjar', $notrans)
                    ->limit(1);
            }, 'pajak')
            ->groupBy('p.notrans', 'p.jumlahpanjar')
            ->first();

        if (!$query) {
            throw new \Exception('Data panjar tidak ditemukan.');
        }
        $diskonLama = (float) ($query->diskon ?? 0);
        $pajakLama = (float) ($query->pajak ?? 0);

        $totalPanjar = (float) $query->jumlahpanjar;
        $totalBelanja = (float) $totalbelanja;
        $diskon = (float) ($diskon ?? 0);
        $pajak = (float) ($pajak ?? 0);
        $sudahDibayar = (float) (
            $query->total_pembayaran + $diskonLama - $pajakLama
        );

        if ($totalBelanja < 0) {
            throw new \Exception('Total belanja tidak boleh kurang dari nol.');
        }

        if ($diskon < 0) {
            throw new \Exception('Diskon tidak boleh kurang dari nol.');
        }

        if ($pajak < 0) {
            throw new \Exception('Pajak tidak boleh kurang dari nol.');
        }

        if ($diskon > $totalBelanja) {
            throw new \Exception(
                'Diskon tidak boleh melebihi total belanja.'
            );
        }


        // Rumus pembayaran setelah diskon dan pajak
        $pembayaranSekarang = ($totalBelanja - $diskon) + $pajak;

        $totalPembayaran = $sudahDibayar + $pembayaranSekarang;
        $sisaPanjarSebelum = $totalPanjar - $sudahDibayar;
        $sisaPanjarSesudah = $totalPanjar - $totalPembayaran;

        if ($totalPembayaran > $totalPanjar) {
            throw new \Exception(
                'Jumlah pembayaran melebihi total panjar. ' .
                'Sisa panjar yang tersedia adalah Rp ' .
                number_format(max($sisaPanjarSebelum, 0), 0, ',', '.') .
                ', sedangkan pembayaran sekarang Rp ' .
                number_format($sudahDibayar, 0, ',', '.')
            );
        }

        return [
            'jumlah_panjar' => $totalPanjar,
            'total_belanja' => $totalBelanja,
            'diskon' => $diskon,
            'pajak' => $pajak,
            'sudah_dibayar' => $sudahDibayar,
            'jumlah_pembayaran' => $pembayaranSekarang,
            'total_pembayaran' => $totalPembayaran,
            'sisa_panjar' => $sisaPanjarSesudah,
        ];


    }
}
