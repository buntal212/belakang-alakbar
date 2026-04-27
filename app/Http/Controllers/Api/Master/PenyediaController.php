<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Master\Bank;
use App\Models\Master\Penyedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class PenyediaController extends Controller
{
    public function index()
    {
        $query = Penyedia::with(
            [
                'rekening' => function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('flaging', '<>', '1')
                        ->orWhereNull('flaging');
                    });
                }
            ]
        )
        ->where(function ($q) {
            $q->where('flaging', '<>', '1')
            ->orWhereNull('flaging');
        })->orderBy('kode');

        $search = request('search');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', '%' . $search . '%')
                ->orWhere('nama', 'like', '%' . $search . '%');
            });
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $kode = $request->kode ?? null;
        $validated = $request->validate([
            'nama' => 'required',
            'telepon' => 'required',
            'npwp' => 'required',
            'bentukusaha' => 'required',
            'bidangusaha' => 'required',
            'pimpinan' => 'required',
            'nomor_rekening' => 'required',
            'bank' => 'required',
            'atas_nama' => 'required'
        ], [

            'nama.required' => 'Nama harus di isi',
            'telepon.required' => 'Telepon harus di isi',
            'npwp.required' => 'NPWP harus di isi',
            'bentukusaha.required' => 'Bentuk Usaha harus di isi',
            'pimpinan.required' => 'Pimpinan harus di isi',
            'nomor_rekening.required' => 'No. Rekening harus di isi',
            'bank.required' => 'Bank harus di isi',
            'atas_nama.required' => 'Atas Nama harus di isi',
        ]);

        try {
            DB::beginTransaction();
                if (!$kode) {
                    DB::select('call mpenyedia(@nomor)');
                    $nomor = DB::table('counter')->select('mpenyedia')->first();
                    $kode = FormatingHelper::genKodeMaster($nomor->mpenyedia, 'P');
                }
                $query = Penyedia::updateOrCreate(
                    [
                        'kode' => $kode
                    ],
                    [
                        'nama' => $validated['nama'],
                        'telepon' => $validated['telepon'],
                        'npwp' => $validated['npwp'],
                        'bentukusaha' => $validated['bentukusaha'],
                        'bidangusaha' => $validated['nama'],
                        'pimpinan' => $validated['pimpinan']
                    ]
                );

                $queryx = Bank::updateOrCreate(
                    [
                        'kode_penyedia' => $kode,
                        'nama_bank' => $validated['bank'],
                        'norek' => $validated['nomor_rekening'],
                        'atasnama' => $validated['atas_nama']
                    ]
                );
            DB::commit();
                $data = Penyedia::with(
                        [
                            'rekening' => function($q){
                                $q->orderby('id', 'desc');
                            }
                        ]
                    )->where('kode', $kode)
                    ->get();

                return new JsonResponse([
                    'data' => $data,
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
}
