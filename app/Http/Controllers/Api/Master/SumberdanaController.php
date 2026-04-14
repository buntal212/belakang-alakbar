<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Master\Sumberdana;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class SumberdanaController extends Controller
{
    public function index()
    {
        $query = Sumberdana::where(function ($q) {
            $q->where('flaging', '<>', '1')
            ->orWhereNull('flaging');
        })->orderBy('kode');

        if (request('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%$search%")
                ->orWhere('kegiatan', 'like', "%$search%");
            });
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $kode = $request->kode ?? null;
        $validated = $request->validate([
            'kegiatan' => 'required',
        ], [

            'kegiatan.required' => 'Kegiatan harus di isi',
        ]);

        try {
            DB::beginTransaction();
                if (!$kode) {
                    DB::select('call msumberdana(@nomor)');
                    $nomor = DB::table('counter')->select('msumberdana')->first();
                    $kode = FormatingHelper::genKodeMaster($nomor->msumberdana, 'SD');
                }
                $data = Sumberdana::updateOrCreate(
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

    public function destroy(Request $request)
    {
        $id = $request->id ?? null;
        $validated = $request->validate([
            'id' => 'required',
        ], [

            'id.required' => 'Data Tidak Bisa Dihapus,karena Tidak mempunyai ID!!!',
        ]);

        try {
            DB::beginTransaction();
                $update = Sumberdana::find($id);

                if ($update) {
                    $update->flaging = '1';
                    $update->save();
                }
            DB::commit();
                return new JsonResponse([
                    'data' => $update,
                    'status' => 'OK',
                    'message' => 'Data berhasil dihapus'
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
