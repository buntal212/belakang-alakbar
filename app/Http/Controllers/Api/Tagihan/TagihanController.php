<?php

namespace App\Http\Controllers\Api\Tagihan;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran\Pembayaran;
use App\Models\Tagihan\Tagihanbelanjaheder;
use App\Models\Tagihan\TagihanbelanjaRinci;
use App\Models\Tagihan\TagihanLsHeder;
use App\Models\Tagihan\TagihanLsRinci;
use App\Models\Pembayaran\PembayaranLs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TagihanController extends Controller
{
    public function indexLsRoute(Request $request) { return $this->indexLs($request->input('jabatan'), $request->input('search'), $request->input('status')); }
    public function indexallLsRoute(Request $request) { return $this->indexallLs($request->input('jabatan')); }
    public function storeHederLsRoute(Request $request) { return $this->storeHederLs($request); }
    public function storeRinciLsRoute(Request $request) { $request->merge(['ls' => true]); return $this->storerinci($request); }
    public function hapusRinciLsRoute(Request $request) { $request->merge(['ls' => true]); return $this->hapusrinci($request); }
    public function hapusHederLsRoute(Request $request) { $request->merge(['ls' => true]); return $this->hapusheder($request); }

    public function index()
    {
        $jabatan = request('jabatan');
        $search = request('search');
        $status = request('status');
        $sumberdana = request('sumberdana');

        if ($sumberdana === 'LS') {
            return $this->indexLs($jabatan, $search, $status);
        }

        $pembayaran = Pembayaran::query()
            ->selectRaw(
                'notagihan,
                MAX(nopembayaran) as nopembayaran,
                COALESCE(SUM(CASE WHEN flag = ? THEN nominal ELSE 0 END), 0) as sudah_dibayar',
                ['2']
            )
            ->groupBy('notagihan');

        $query = Tagihanbelanjaheder::query()
            ->select(
                'tagihan_h.*',
                'rekap_pembayaran.nopembayaran',
                DB::raw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) as sudah_dibayar')
            )
            ->leftJoinSub($pembayaran, 'rekap_pembayaran', function ($join) {
                $join->on('rekap_pembayaran.notagihan', '=', 'tagihan_h.notagihan');
            })
            ->with([
                'rinci' => function ($q) {
                    $q->with(['akun']);
                },
                'penyedia',
                'unit',
                'jabatan'
            ])
            ->where('tagihan_h.jabatan', $jabatan)
            ->when($sumberdana, fn ($q) => $q->where('tagihan_h.sumberdana', $sumberdana))
            ->when($status === 'lunas', fn ($q) => $q->whereRaw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) >= tagihan_h.jumlahditagihkan'))
            ->when($status === 'proses', fn ($q) => $q->whereRaw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) > 0 AND COALESCE(rekap_pembayaran.sudah_dibayar, 0) < tagihan_h.jumlahditagihkan'))
            ->when($status === 'belum', fn ($q) => $q->whereRaw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) = 0'))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($searchQuery) use ($search) {
                    // Cari pada bagian nomor/kode tagihan saja. Bagian tahun
                    // (contoh /2026) tidak ikut agar pencarian "20" tidak
                    // mencocokkan seluruh tagihan pada tahun yang sama.
                    $searchQuery->whereRaw(
                        "SUBSTRING_INDEX(tagihan_h.notagihan, '/', 3) LIKE ?",
                        ["%{$search}%"]
                    )
                        ->orWhere('tagihan_h.kegiatan', 'like', "%{$search}%")
                        ->orWhereHas('penyedia', function ($penyediaQuery) use ($search) {
                            $penyediaQuery->where('nama', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('tagihan_h.created_at', 'desc');

        $data = $query->simplePaginate(request('per_page', 10));

        return new JsonResponse($data);
    }

    private function indexLs($jabatan, $search, $status): JsonResponse
    {
        $pembayaran = PembayaranLs::query()
            ->selectRaw(
                'notagihan,
                MAX(nopembayaran) as nopembayaran,
                COALESCE(SUM(CASE WHEN flag = ? THEN nominal ELSE 0 END), 0) as sudah_dibayar',
                ['2']
            )
            ->groupBy('notagihan');

        $query = TagihanLsHeder::query()
            ->select(
                'tagihan_ls_h.*',
                'rekap_pembayaran.nopembayaran',
                DB::raw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) as sudah_dibayar')
            )
            ->leftJoinSub($pembayaran, 'rekap_pembayaran', fn ($join) => $join->on('rekap_pembayaran.notagihan', '=', 'tagihan_ls_h.notagihan'))
            ->with(['rinci.akun', 'penyedia', 'unit', 'jabatan'])
            ->where('tagihan_ls_h.jabatan', $jabatan)
            ->when($status === 'lunas', fn ($q) => $q->whereRaw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) >= tagihan_ls_h.jumlahditagihkan'))
            ->when($status === 'proses', fn ($q) => $q->whereRaw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) > 0 AND COALESCE(rekap_pembayaran.sudah_dibayar, 0) < tagihan_ls_h.jumlahditagihkan'))
            ->when($status === 'belum', fn ($q) => $q->whereRaw('COALESCE(rekap_pembayaran.sudah_dibayar, 0) = 0'))
            ->when($search, fn ($q) => $q->where(fn ($s) => $s->where('tagihan_ls_h.notagihan', 'like', "%{$search}%")->orWhere('tagihan_ls_h.kegiatan', 'like', "%{$search}%")))
            ->orderByDesc('tagihan_ls_h.created_at');

        return new JsonResponse($query->simplePaginate(request('per_page', 10)));
    }

    public function storeheder(Request $request)
    {
        if ($request->input('sumberdana') === 'LS') {
            return $this->storeHederLs($request);
        }
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
            'total' => 'required',
            'sumberdana' => 'required'
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
                $cek = Pembayaran::where('notagihan', $notrans)->count();

                if ($cek > 0) {
                    throw new \Exception('Tagihan Ini sudah dibayar ');
                }
                if(!$notrans){
                    if ($validated['jabatan'] === 'J000004' && $validated['sumberdana'] === 'LS') {
                        $counter = DB::table('counter')->lockForUpdate()->first();

                        if (!$counter) {
                            throw new \Exception('Counter Tagihan LS belum tersedia');
                        }

                        DB::table('counter')->where('id', $counter->id)->increment('tagihanlspengeluaranyayasan');
                        $nomor = DB::table('counter')->where('id', $counter->id)->value('tagihanlspengeluaranyayasan');
                        $notrans = FormatingHelper::tagihanLs($nomor);
                    } else if($validated['jabatan'] == 'J000004'){
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
                        'sumberdana' => $validated['sumberdana'],
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
        if ($request->boolean('ls')) {
            return $this->storeRinciLs($request);
        }
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
                $cek = Pembayaran::where('notagihan', $validated['notrans'])->count();

                if ($cek > 0) {
                    throw new \Exception('Tagihan Ini sudah dibayar ');
                }
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
        if ($request->boolean('ls')) {
            return $this->hapusRinciLs($request);
        }
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'id' => 'required',
                'notagihan' => 'required'
            ], [
                'id.required' => 'Data ini Tidak Bisa Dihapus,Karena tidak mempunyai ID',
                'notagihan.required' => 'No. Tagihan Harus di isi',
            ]);

            $cek = Pembayaran::where('notagihan', $validated['notagihan'])->count();

            if ($cek > 0) {
                throw new \Exception('Tagihan Ini sudah dibayar ');
            }

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
                    'error' => $e->getMessage(),
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
        $sumberdana = request('sumberdana');

        if ($sumberdana === 'LS') {
            return $this->indexallLs($jabatan);
        }

        $data = Tagihanbelanjaheder::query()
            ->with([
                'rinci' => function ($q) {
                    $q->with('akun:kode,belanja');
                },
                'penyedia:kode,nama',
                'unit:kode,nama_unit',
                'jabatan:kode,jabatan'
            ])
            ->withSum([
                'pembayaran as total_terbayar' => function ($q) {
                    $q->where('flag', 2);
                }
            ], 'nominal')
            ->where('jabatan', $jabatan)
            ->when($sumberdana, fn ($q) => $q->where('sumberdana', $sumberdana))
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

    public function hapusheder(Request $request)
    {
        if ($request->boolean('ls')) {
            return $this->hapusHederLs($request);
        }
        DB::beginTransaction();

        try {

            $validated = $request->validate([
                'notagihan' => 'required'
            ], [
                'notagihan.required' => 'No. Tagihan harus diisi',
            ]);

            // Cari header
            $header = Tagihanbelanjaheder::where(
                'notagihan',
                $validated['notagihan']
            )->first();

            if (!$header) {
                throw new \Exception('Header tagihan tidak ditemukan');
            }

            $cek = Pembayaran::where('notagihan', $validated['notagihan'])->count();

            if ($cek > 0) {
               throw new \Exception('Tagihan Ini sudah dibayar ');
            }
            // Hapus semua rincian
            TagihanbelanjaRinci::where(
                'notagihan',
                $validated['notagihan']
            )->delete();

            // Hapus header
            $header->delete();

            DB::commit();
            $data = self::getnotrans($validated['notagihan']);
            return response()->json([
                'data' => $data,
                'success' => true,
                'message' => 'Header dan rincian berhasil dihapus'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data:'. $e->getMessage(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    private function storeHederLs(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tgl' => 'required', 'jabatan' => 'required', 'unit' => 'required',
            'kegiatan' => 'required', 'penyedia' => 'required', 'totalmentah' => 'required',
            'diskon' => 'nullable', 'pajak' => 'nullable', 'total' => 'required', 'sumberdana' => 'required',
        ]);
        try {
            DB::transaction(function () use (&$notagihan, $request, $data) {
                $notagihan = $request->notrans;
                if ($notagihan && PembayaranLs::where('notagihan', $notagihan)->exists()) {
                    throw new \Exception('Tagihan ini sudah diajukan untuk pembayaran');
                }
                if (!$notagihan) {
                    $counter = DB::table('counter')->lockForUpdate()->first();
                    if (!$counter) throw new \Exception('Counter Tagihan LS belum tersedia');
                    DB::table('counter')->where('id', $counter->id)->increment('tagihanlspengeluaranyayasan');
                    $notagihan = FormatingHelper::tagihanLs(DB::table('counter')->where('id', $counter->id)->value('tagihanlspengeluaranyayasan'));
                }
                TagihanLsHeder::updateOrCreate(['notagihan' => $notagihan], [
                    'tgl' => $data['tgl'], 'jabatan' => $data['jabatan'], 'unit' => $data['unit'],
                    'kegiatan' => $data['kegiatan'], 'penyedia' => $data['penyedia'],
                    'sumberdana' => $data['sumberdana'],
                    'jumlahbelanja' => $data['totalmentah'], 'diskon' => $data['diskon'] ?? 0,
                    'pajak' => $data['pajak'] ?? 0, 'jumlahditagihkan' => $data['total'],
                    'user' => Auth::user()->kode,
                ]);
                $this->totalLs($notagihan);
            });
            return response()->json(['data' => $this->getLs($notagihan), 'message' => 'Tagihan LS berhasil disimpan']);
        } catch (\Throwable $e) { return response()->json(['message' => 'Gagal menyimpan data: '.$e->getMessage()], 422); }
    }

    private function storeRinciLs(Request $request): JsonResponse
    {
        $data = $request->validate(['notrans'=>'required','akun'=>'required','rincian'=>'required','qty'=>'required|numeric|gt:0','satuan'=>'required','harga'=>'required|numeric|gt:0','jumlah'=>'required|numeric|gt:0']);
        try {
            DB::transaction(function () use ($data) {
                if (PembayaranLs::where('notagihan', $data['notrans'])->exists()) throw new \Exception('Tagihan ini sudah dibayar');
                TagihanLsRinci::create(['notagihan'=>$data['notrans'],'akun'=>$data['akun'],'rincian'=>$data['rincian'],'qty'=>$data['qty'],'satuan'=>$data['satuan'],'harga'=>$data['harga'],'jumlah'=>$data['jumlah'],'user'=>Auth::user()->kode]);
                $this->totalLs($data['notrans']);
            });
            return response()->json(['data'=>$this->getLs($data['notrans']),'message'=>'Rincian LS berhasil disimpan']);
        } catch (\Throwable $e) { return response()->json(['message'=>'Gagal menyimpan data: '.$e->getMessage()],422); }
    }

    private function hapusRinciLs(Request $request): JsonResponse
    {
        $data = $request->validate(['id'=>'required','notagihan'=>'required']);
        try {
            DB::transaction(function () use ($data) {
                if (PembayaranLs::where('notagihan',$data['notagihan'])->exists()) throw new \Exception('Tagihan ini sudah dibayar');
                TagihanLsRinci::where('id',$data['id'])->where('notagihan',$data['notagihan'])->delete(); $this->totalLs($data['notagihan']);
            });
            return response()->json(['data'=>$this->getLs($data['notagihan']),'message'=>'Rincian LS berhasil dihapus']);
        } catch (\Throwable $e) { return response()->json(['message'=>'Gagal menghapus data: '.$e->getMessage()],422); }
    }

    private function hapusHederLs(Request $request): JsonResponse
    {
        $data=$request->validate(['notagihan'=>'required']);
        try {
            DB::transaction(function () use ($data) {
                if (PembayaranLs::where('notagihan',$data['notagihan'])->exists()) throw new \Exception('Tagihan ini sudah dibayar');
                TagihanLsRinci::where('notagihan',$data['notagihan'])->delete(); TagihanLsHeder::where('notagihan',$data['notagihan'])->delete();
            });
            return response()->json(['data'=>[],'message'=>'Tagihan LS berhasil dihapus']);
        } catch (\Throwable $e) { return response()->json(['message'=>'Gagal menghapus data: '.$e->getMessage()],422); }
    }

    private function totalLs(string $notagihan): void
    {
        $header=TagihanLsHeder::where('notagihan',$notagihan)->firstOrFail(); $total=TagihanLsRinci::where('notagihan',$notagihan)->sum('jumlah');
        $header->update(['jumlahbelanja'=>$total,'jumlahditagihkan'=>max(0,$total-($header->diskon ?? 0)+($header->pajak ?? 0))]);
    }
    private function getLs(string $notagihan) { return TagihanLsHeder::with(['rinci.akun','penyedia','unit','jabatan'])->where('notagihan',$notagihan)->get(); }
    private function indexallLs(string $jabatan): JsonResponse
    {
        $paid=PembayaranLs::where('flag','2')->selectRaw('notagihan,SUM(nominal) nominal')->groupBy('notagihan');
        $items=TagihanLsHeder::with(['rinci.akun','penyedia','unit','jabatan'])->leftJoinSub($paid,'paid',fn($j)=>$j->on('paid.notagihan','=','tagihan_ls_h.notagihan'))->where('tagihan_ls_h.jabatan',$jabatan)->select('tagihan_ls_h.*',DB::raw('COALESCE(paid.nominal,0) total_terbayar'))->get()->map(function($item){$item->sisa_bayar=$item->jumlahditagihkan-$item->total_terbayar;return $item;})->filter(fn($item)=>$item->sisa_bayar>0)->values();
        return response()->json($items);
    }
}
