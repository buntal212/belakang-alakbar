<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Kodebelanja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class KodebelanjaController extends Controller
{
    public function index()
    {
        $query = Kodebelanja::where(function ($q) {
            $q->where('flaging', '<>', '1')
            ->orWhereNull('flaging');
        })->orderBy('kode');

        if (request('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%$search%")
                ->orWhere('belanja', 'like', "%$search%");
            });
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode' => 'required',
            'belanja' => 'required',
        ], [

            'kode.required' => 'Kode Belanja harus di isi',
            'belanja.required' => 'Belanja harus di isi',
        ]);

        try {
            DB::beginTransaction();
                $data = Kodebelanja::updateOrCreate(
                    [
                        'kode' => $validated['kode']
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
