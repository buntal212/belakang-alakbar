<?php

namespace App\Http\Controllers\Api\Pengembaliansisapanjar;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Api\SaldoController;
use App\Http\Controllers\Controller;
use App\Models\Pengembaliansisapanjar\pengembaliansisapanjar;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengembaliansisapanjarController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $query = pengembaliansisapanjar::with(
            [
                'unit',
                'jabatan',
                'user',
                'panjar' => function ($panjar) {
                    $panjar->with([
                        'user'
                    ]);
                }
            ]
        )
        ->where('jabatan', $jabatan)
        ->orderBy('created_at','desc');
        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $notrans = $request->notrans ?? null;
        $validated =  $request->validate([

            'tgl' => 'required',
            'nopanjar' => 'required',
            'kodeunit' => 'required',
            'kodejabatan' => 'required',
            'totalpanjar' => 'required',
            'totaltagihan' => 'required',
            'sisapanjar' => 'required',

        ], [

            'tgl.required' => 'Tanggal  Harus di isi',
            'nopanjar.required' => 'No. Panjar Harus Diisi...!!!',
            'kodeunit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'kodejabatan.required' => 'Jabatan Tidak Boleh Kosong...!!!',
            'totaltagihan.required' => 'Total Tagihan Tidak Boleh Kosong...!!!',
            'totalpanjar.required' => 'Total Panjar Tidak Boleh Kosong...!!!',
            'sisapanjar.required' => 'Sisa panjar ke Tidak Boleh Kosong...!!!',
        ]);

        try{
                DB::beginTransaction();

                if(!$notrans){
                    if($validated['kodejabatan'] == 'J000004'){
                        DB::select('call pengembaliansisapanjar(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliansisapanjar')->first();
                        $flag = 'PK';
                        $notrans = FormatingHelper::pengembaliansisapanjar($nomor->pengembaliansisapanjar, $flag);
                    }else if($validated['kodejabatan'] == 'J000005'){
                        DB::select('call pengembaliansisapanjar(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliansisapanjar')->first();
                        $flag = 'TK';
                        $notrans = FormatingHelper::pengembaliansisapanjar($nomor->pengembaliansisapanjar, $flag);
                    }else if($validated['kodejabatan'] == 'J000006'){
                        DB::select('call pengembaliansisapanjar(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliansisapanjar')->first();
                        $flag = 'SD';
                        $notrans = FormatingHelper::pengembaliansisapanjar($nomor->pengembaliansisapanjar, $flag);
                    }else{
                        DB::select('call pengembaliansisapanjar(@nomor)');
                        $nomor = DB::table('counter')->select('pengembaliansisapanjar')->first();
                        $flag = 'SMP';
                        $notrans = FormatingHelper::pengembaliansisapanjar($nomor->pengembaliansisapanjar, $flag);
                    }
                }
                $user = Auth::user();

                $simpan = pengembaliansisapanjar::updateOrCreate(
                    [
                        'notrans' => $notrans
                    ],[
                        'tgl' => $validated['tgl'],
                        'nopanjar' => $validated['nopanjar'],
                        'unit' => $validated['kodeunit'],
                        'jabatan' => $validated['kodejabatan'],
                        'userentry' => $user->kode,
                        'totalpanjar' => $validated['totalpanjar'],
                        'totalpembayaran' => $validated['totaltagihan'],
                        'sisapanjar' => $validated['sisapanjar'],
                    ]
                );
                SaldoController::saldokembali($validated['kodejabatan'],'2',$validated['sisapanjar']);
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

    public function getnotrans($notrans)
    {
        $query = pengembaliansisapanjar::with(
            [
                'unit',
                'jabatan',
                'user',
                'panjar' => function ($panjar) {
                    $panjar->with([
                        'user'
                    ]);
                }
            ]
        )
        ->where('notrans', $notrans)
        ->get();
        return $query;
    }
}
