<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Master\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UnitController extends Controller
{
    public function index()
    {
        $query = Unit::orderBy('kode');

        if (request('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%$search%")
                ->orWhere('nama_unit', 'like', "%$search%");
            });
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $kode = $request->kode ?? null;
        $validated = $request->validate([
            'nama_unit' => 'required',
        ], [

            'nama_unit.required' => 'Nama harus di isi',
        ]);

        try {
            DB::beginTransaction();
                if (!$kode) {
                    DB::select('call masterunit(@nomor)');
                    $nomor = DB::table('counter')->select('masterunit')->first();
                    $kode = FormatingHelper::genKodeMaster($nomor->masterunit, 'U');
                }
                $data = Unit::updateOrCreate(
                    [
                        'kode' => $kode
                    ],
                    $validated
                );
            DB::commit();
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
