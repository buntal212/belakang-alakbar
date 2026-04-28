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
            'diskon' => 'required',
            'pajak' => 'required',
            'total' => 'required'
        ], [
            'tgl.required' => 'Tanggal Harus di isi',
            'jabatan.required' => 'Jabatan Harus Diisi...!!!',
            'unit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'kegiatan.required' => 'Kegiatan Tidak Boleh Kosong...!!!',
            'penyedia.required' => 'Penyedia Tidak Boleh Kosong...!!!',
            'totalmentah.required' => 'Total sebelum Diskon Dan Pajak Tidak Boleh Kosong...!!!',
            'diskon.required' => 'Diskon Tidak Boleh Kosong...!!!',
            'pajak.required' => 'Pajak Tidak Boleh Kosong...!!!',
            'total.required' => 'Total Tidak Boleh Kosong...!!!',
        ]);

        try{
            DB::beginTransaction();
                if(!$notrans){
                    if($validated['jabatan'] == 'J000004'){
                        DB::select('call tagihanpengeluaranyayasan(@nomor)');
                        $nomor = DB::table('counter')->select('tagihanpengeluaranyayasan')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::pergeserankas($nomor->tagihanpengeluaranyayasan, $flag);
                    }else if($validated['jabatan'] == 'J000005'){
                        DB::select('call pergeserankaspengeluarantk(@nomor)');
                        $nomor = DB::table('counter')->select('pergeserankaspengeluarantk')->first();
                        $flag = 'TK';
                        $notrans = FormatingHelper::pergeserankas($nomor->pergeserankaspengeluarantk, $flag);
                    }else if($validated['jabatan'] == 'J000006'){
                        DB::select('call pergeserankaspengeluaransd(@nomor)');
                        $nomor = DB::table('counter')->select('pergeserankaspengeluaransd')->first();
                        $flag = 'SD';
                        $notrans = FormatingHelper::pergeserankas($nomor->pergeserankaspengeluaransd, $flag);
                    }else{
                        DB::select('call pergeserankaspengeluaransmp(@nomor)');
                        $nomor = DB::table('counter')->select('pergeserankaspengeluaransmp')->first();
                        $flag = 'SMP';
                        $notrans = FormatingHelper::pergeserankas($nomor->pergeserankaspengeluaransmp, $flag);
                    }
                }
                $user = Auth::user();
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
            'qty' => 'required',
            'harga' => 'required',
            'satuan' => 'required',
            'jumlah' => 'required',
        ], [
            'notrans.required' => 'Notrans Harus di isi',
            'akun.required' => 'Akun Harus Diisi...!!!',
            'rincian.required' => 'Rincian Tidak Boleh Kosong...!!!',
            'qty.required' => 'Kuantitas Tidak Boleh Kosong...!!!',
            'harga.required' => 'Harga Tidak Boleh Kosong...!!!',
            'satuan.required' => 'Satuan Tidak Boleh Kosong...!!!',
            'jumlah.required' => 'Jumlah Tidak Boleh Kosong...!!!',
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

            return response()->json([
                'message' => 'Gagal hapus data',
                'error' => $e->getMessage()
            ], 500);
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

    public function gettotalbelanja($notrans)
    {
        $header = Tagihanbelanjaheder::where('notagihan', $notrans)->first();

        $total = TagihanbelanjaRinci::where('notagihan', $notrans)->sum('jumlah');

        $header->update([
            'jumlahbelanja' => $total,
            'jumlahditagihkan' => $total - ($header->diskon ?? 0) + ($header->pajak ?? 0)
        ]);

        return $header;
    }
}
