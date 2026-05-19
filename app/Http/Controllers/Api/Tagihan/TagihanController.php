<?php

namespace App\Http\Controllers\Api\Tagihan;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Tagihan\Tagihanbelanjaheder;
use App\Models\Tagihan\TagihanbelanjaRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagihanController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $query = Tagihanbelanjaheder::with(
            [
                'rinci'=> function ($q) {
                     $q->with(['akun']);
                },
                'penyedia',
                'unit',
                'jabatan'
            ]
        )
        ->where('jabatan', $jabatan)
        ->orderBy('created_at','desc');
        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function storeheder(Request $request)
    {
        $notrans = $request->notrans ?? null;
        $validated =  $request->validate([
            'tgl' => 'required',
            'jabatan' => 'required',
            'unit' => 'required',
            'kegiatan' => 'required',
            'penyedia' => 'required',
            'totalmentah' => 'required',
            'diskon' => 'nullable',
            'pajak' => 'nullable',
            'total' => 'required'
        ], [
            'tgl.required' => 'Tanggal Harus di isi',
            'jabatan.required' => 'Jabatan Harus Diisi...!!!',
            'unit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'kegiatan.required' => 'Kegiatan Tidak Boleh Kosong...!!!',
            'penyedia.required' => 'Penyedia Tidak Boleh Kosong...!!!',
            'totalmentah.required' => 'Total sebelum Diskon Dan Pajak Tidak Boleh Kosong...!!!',
            // 'diskon.required' => 'Diskon Tidak Boleh Kosong...!!!',
            // 'pajak.required' => 'Pajak Tidak Boleh Kosong...!!!',
            'total.required' => 'Total Tidak Boleh Kosong...!!!',
        ]);

        try{
            DB::beginTransaction();
                if(!$notrans){
                    if($validated['jabatan'] == 'J000004'){
                        DB::select('call tagihanpengeluaranyayasan(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluaranyayasan')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::tagihan($nomor->tagihanpengeluaranyayasan, $flag);
                    }else if($validated['jabatan'] == 'J000005'){
                        DB::select('call tagihanpengeluarantk(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluarantk')->first();
                        $flag = 'TK';
                        $notrans = FormatingHelper::tagihan($nomor->tagihanpengeluarantk, $flag);
                    }else if($validated['jabatan'] == 'J000006'){
                        DB::select('call tagihanpengeluaransd(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluaransd')->first();
                        $flag = 'SD';
                        $notrans = FormatingHelper::tagihan($nomor->tagihanpengeluaransd, $flag);
                    }else{
                        DB::select('call tagihanpengeluaransmp(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluaransmp')->first();
                        $flag = 'SMP';
                        $notrans = FormatingHelper::tagihan($nomor->tagihanpengeluaransmp, $flag);
                    }
                }
                $user = Auth::user();
                // if($validated['diskon'] > $validated['totalmentah']){
                //     return new JsonResponse([
                //         'message' => 'Diskon Tidak Boleh Lebih besar Dari Total Belanja'
                //     ],500);
                // }
                $simpan = Tagihanbelanjaheder::updateOrCreate(
                    [
                        'notagihan' => $notrans
                    ],[
                        'tgl' => $validated['tgl'],
                        'unit' => $validated['unit'],
                        'jabatan' => $validated['jabatan'],
                        'kegiatan' => $validated['kegiatan'],
                        'penyedia' => $validated['penyedia'],
                        'jumlahbelanja' => $validated['totalmentah'],
                        'diskon' => $validated['diskon'],
                        'pajak' => $validated['pajak'],
                        'jumlahditagihkan' => $validated['total'],
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
                $user = Auth::user();

                $simpan = TagihanbelanjaRinci::create(
                    [
                        'notagihan' => $validated['notrans'],
                        'akun' => $validated['akun'],
                        'rincian' => $validated['rincian'],
                        'qty' => $validated['qty'],
                        'satuan' => $validated['satuan'],
                        'harga' => $validated['harga'],
                        'jumlah' => $validated['jumlah'],
                        'user' => $user->kode,
                    ]
                );

                // $header = Tagihanbelanjaheder::where('notagihan', $validated['notrans'])->first();

                // $total = TagihanbelanjaRinci::where('notagihan', $validated['notrans'])->sum('jumlah');

                // $header->update([
                //     'jumlahbelanja' => $total,
                //     'jumlahditagihkan' => $total - ($header->diskon ?? 0) + ($header->pajak ?? 0)
                // ]);
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

    public function hapusrinci(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'id' => 'required',
                'notagihan' => 'required'
            ], [
                'id.required' => 'Data ini Tidak Bisa Dihapus,Karena tidak mempunyai ID',
                'notagihan.required' => 'No. Tagihan Harus di isi',
            ]);

            $rincian = TagihanbelanjaRinci::find($request->id);

            if (!$rincian) {
                throw new \Exception('Data tidak ditemukan');
            }

            $rincian->delete();

            // $header = Tagihanbelanjaheder::where('notagihan', $validated['notagihan'])->first();

            // $total = TagihanbelanjaRinci::where('notagihan', $validated['notagihan'])->sum('jumlah');

            // $header->update([
            //     'jumlahbelanja' => $total,
            //     'jumlahditagihkan' => $total - ($header->diskon ?? 0) + ($header->pajak ?? 0)
            // ]);
            self::gettotalbelanja($validated['notagihan']);
            DB::commit();
                $data = self::getnotrans($validated['notagihan']);
                return response()->json([
                    'data' => $data,
                    'message' => 'Data berhasil dihapus'
                ]);
        } catch (\Throwable $e) {
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
        $data = Tagihanbelanjaheder::with(
            [
                'rinci'=> function ($q) {
                     $q->with(['akun']);
                },
                'penyedia',
                'unit',
                'jabatan'
            ]
        )
        ->where('notagihan', $notrans)->get();
        return $data;
    }

    // public function gettotalbelanja($notrans)
    // {
    //     $header = Tagihanbelanjaheder::where('notagihan', $notrans)->first();

    //     $total = TagihanbelanjaRinci::where('notagihan', $notrans)->sum('jumlah');

    //     $header->update([
    //         'jumlahbelanja' => $total,
    //         'jumlahditagihkan' => $total - ($header->diskon ?? 0) + ($header->pajak ?? 0)
    //     ]);

    //     return $header;
    // }

   public function gettotalbelanja($notrans)
    {
        // ambil header
        $header = Tagihanbelanjaheder::where('notagihan', $notrans)->first();

        if (!$header) {
            throw new \Exception('Data tagihan tidak ditemukan');
        }

        // hitung total rincian
        $total = TagihanbelanjaRinci::where('notagihan', $notrans)->sum('jumlah');

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
            'jumlahbelanja' => $total,
            'jumlahditagihkan' => $jumlahditagihkan
        ]);

        return $header;
    }

    public function indexall()
    {
        $jabatan = request('jabatan');

        $data = Tagihanbelanjaheder::query()
            ->with([
                'rinci' => function ($q) {
                    $q->with('akun:kode,belanja');
                },
                'penyedia:kode,nama',
                'unit:kode,nama_unit',
                'jabatan:kode,jabatan'
            ])
            ->withSum('pembayaran as total_terbayar', 'nominal')
            ->where('jabatan', $jabatan)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($item) {
                $total = $item->total_terbayar ?? 0;
                $item->sisa_bayar = $item->jumlahditagihkan - $total;
                return $item;
            })
            ->filter(function ($item) {
                return $item->sisa_bayar > 0;
            })
            ->values();

        return new JsonResponse($data);
    }
}
