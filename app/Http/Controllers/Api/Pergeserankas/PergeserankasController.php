<?php

namespace App\Http\Controllers\Api\Pergeserankas;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Api\SaldoController;
use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\Pergeserankas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PergeserankasController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $query = Pergeserankas::with(
            [
                'unit',
                'jabatan'
            ]
        )->where('jabatan', $jabatan)
        ->orderBy('created_at','desc');

        if (request('search')) {
            $search = request('search');

            $query->where('no_pergeseran', 'like', "%{$search}%");
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $notrans = $request->no_pergeseran ?? null;
        $validated =  $request->validate([
            'jenis' => 'required',
            'jabatan' => 'required',
            'unit' => 'required',
            'nilai_pengajuan' => 'required'
        ], [
            'jenis.required' => 'Jenis Harus di isi',
            'jabatan.required' => 'Jabatan Harus Diisi...!!!',
            'unit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'nilai_pengajuan.required' => 'Nilai Pengajuan Tidak Boleh Kosong...!!!',
        ]);

        try{
            DB::beginTransaction();
                if(!$notrans){
                    if($validated['jabatan'] == 'J000004'){
                        DB::select('call pergeserankaspengeluaranyayasan(@nomor)');
                        $nomor = DB::table('counter')->select('pergeserankaspengeluaranyayasan')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::pergeserankas($nomor->pergeserankaspengeluaranyayasan, $flag);
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
                $simpan = Pergeserankas::updateOrCreate(
                    [
                        'no_pergeseran' => $notrans
                    ],[
                        'tgl' => date('Y-m-d'),
                        'unit' => $validated['unit'],
                        'jabatan' => $validated['jabatan'],
                        'jenis' => $validated['jenis'],
                        'nominal' => $validated['nilai_pengajuan'],
                        'user' => $user->kode,
                    ]
                );
                $saldo = self::saldo($validated['jabatan'],$validated['jenis'],$validated['nilai_pengajuan']);
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

    public static function saldo($jabatan,$jenis, $nominal)
    {
        if($jenis === '1'){
            $keluar = Saldo::where('jenis', 'Bank')->where('pemilik', $jabatan)->first();
            $masuk = Saldo::where('jenis', 'Tunai')->where('pemilik', $jabatan)->first();

        }else{
            $keluar = Saldo::where('jenis', 'Tunai')->where('pemilik', $jabatan)->first();
            $masuk = Saldo::where('jenis', 'Bank')->where('pemilik', $jabatan)->first();

        }
       if (!$keluar || !$masuk) {
            throw new \Exception('Data saldo tidak ditemukan');
        }

        $keluar->decrement('nominal', $nominal);
        $masuk->increment('nominal', $nominal);
        $data = Saldo::where('pemilik', $jabatan)->get();
        return $data;
    }

    public static function getnotrans($notrans)
    {
        $data = Pergeserankas::with(
            [
                'unit',
                'jabatan'
            ]
        )
        ->where('no_pergeseran', $notrans)->get();
        return $data;
    }
}
