<?php

namespace App\Http\Controllers\Api\Pengembaliankas;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\SaldoController;
use App\Models\pengembaliankas\pengembaliankas;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengembaliankasController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $search = request('search');
        $query = pengembaliankas::with(
            [
                'unit',
                'jabatan',
            ]
        )
        ->where('jabatan', $jabatan)
        ->when($search, function ($query) use ($search) {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('notrans', 'like', "%{$search}%")
                    ->orWhere('nopanjar', 'like', "%{$search}%");
            });
        })
        ->orderBy('created_at','desc');
        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }
    public function store(Request $request){

        $notrans = $request->notrans ?? null;
        $validated =  $request->validate([

            'tgl' => 'required',
            'unit' => 'required',
            'jabatan' => 'required',
            'totalpengembaliankas' => 'required',
        ], [

            'tgl.required' => 'Tanggal  Harus di isi',
            'unit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'jabatan.required' => 'Jabatan Tidak Boleh Kosong...!!!',
            'totalpengembaliankas.required' => 'Total Pengembalian Kas Tidak Boleh Kosong...!!!',
        ]);

        try{
                DB::beginTransaction();

                if(!$notrans){
                    if($validated['jabatan'] == 'J000004'){
                        DB::select('call pengembaliankas(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliankas')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::pengembaliankas($nomor->pengembaliankas, $flag);
                    }else if($validated['jabatan'] == 'J000005'){
                        DB::select('call pengembaliankas(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliankas')->first();
                        $flag = 'TK';
                        $notrans = FormatingHelper::pengembaliankas($nomor->pengembaliankas, $flag);
                    }else if($validated['jabatan'] == 'J000006'){
                        DB::select('call pengembaliankas(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliankas')->first();
                        $flag = 'SD';
                        $notrans = FormatingHelper::pengembaliankas($nomor->pengembaliankas, $flag);
                    }else{
                        DB::select('call pengembaliankas(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliankas')->first();
                        $flag = 'SMP';
                        $notrans = FormatingHelper::pengembaliankas($nomor->pengembaliankas, $flag);
                    }
                }
                $user = Auth::user();

                $simpan = pengembaliankas::updateOrCreate(
                    [
                        'nopengembalian' => $notrans
                    ],[
                        'tgl' => $validated['tgl'],
                        'unit' => $validated['unit'],
                        'jabatan' => $validated['jabatan'],
                        'userentry' => $user->kode,
                        'nominal' => $validated['totalpengembaliankas'],
                    ]
                );

                if ($simpan->wasRecentlyCreated) {
                    $nominal = $validated['totalpengembaliankas'];

                    // Uang dikembalikan dari bank bendahara unit ke
                    // bank bendahara pengeluaran.
                    $saldo = SaldoController::saldo($validated['jabatan'], '1', $nominal);
                    $tujuan = $validated['jabatan'] === 'J000004' ? 'J000003' : 'J000004';
                    SaldoController::saldokembali($tujuan, '1', $nominal);
                } else {
                    $saldo = \App\Models\Master\Saldo::where('pemilik', $validated['jabatan'])
                        ->orderBy('jenis')
                        ->get();
                }
            DB::commit();
                $data = self::getnotrans($notrans);
                return new JsonResponse(
                    [
                        'data' => $data,
                        'message' => 'Data berhasil disimpan',
                        'saldo' => $saldo,
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

    public static function getnotrans($notrans)
    {
        $query = pengembaliankas::with(
            [
                'unit',
                'jabatan',
            ]
        )
        ->where('nopengembalian', $notrans)
        ->get();
        return $query;
    }
}
