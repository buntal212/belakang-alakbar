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

    public function indexall()
    {
        $query = Kodebelanja::where(function ($q) {
            $q->where('flaging', '<>', '1')
            ->orWhereNull('flaging');
        })->orderBy('kode')->get();

        return new JsonResponse($query);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'kode' => 'required',
            'belanja' => 'required',
        ], [

            'kode.required' => 'Kode Belanja harus di isi',
            'belanja.required' => 'Belanja harus di isi',
        ]);

        try {
            DB::beginTransaction();
                if (!empty($validated['id'])) {
                    $data = Kodebelanja::findOrFail($validated['id']);
                    if ($data->kode !== $validated['kode']) {
                        throw new \Exception('Kode belanja tidak dapat diubah setelah dibuat');
                    }
                    $data->update(['belanja' => $validated['belanja']]);
                } else {
                    if (Kodebelanja::where('kode', $validated['kode'])->exists()) {
                        throw new \Exception('Kode belanja sudah digunakan');
                    }
                    $data = Kodebelanja::create([
                        'kode' => $validated['kode'],
                        'belanja' => $validated['belanja'],
                    ]);
                }
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

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        try {
            $data = Kodebelanja::findOrFail($validated['id']);
            $data->update(['flaging' => '1']);

            return new JsonResponse([
                'status' => 'OK',
                'data' => $data,
                'message' => 'Kode belanja berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'message' => 'Gagal menghapus kode belanja: ' . $e->getMessage(),
            ], 422);
        }
    }
}
