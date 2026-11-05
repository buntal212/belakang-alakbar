<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisPendapatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisPendapatanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JenisPendapatan::where('flag', '<>', '1')
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('jenis_pendapatan', 'like', '%'.$request->input('search').'%');
            })
            ->orderBy('jenis_pendapatan');

        return new JsonResponse($query->simplePaginate($request->integer('per_page', 10)));
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:jenis_pendapatan,id'],
            'jenis_pendapatan' => [
                'required',
                'string',
                'max:100',
                Rule::unique('jenis_pendapatan', 'jenis_pendapatan')->ignore($id),
            ],
            'cara_bayar' => ['required', Rule::in(['bulanan', '1x bayar', 'cicilan'])],
        ], [
            'jenis_pendapatan.required' => 'Jenis pendapatan wajib diisi.',
            'jenis_pendapatan.unique' => 'Jenis pendapatan sudah tersedia.',
            'cara_bayar.required' => 'Cara bayar wajib dipilih.',
            'cara_bayar.in' => 'Cara bayar tidak valid.',
        ]);

        $jenisPendapatan = JenisPendapatan::updateOrCreate(
            ['id' => $id],
            [
                'jenis_pendapatan' => $validated['jenis_pendapatan'],
                'cara_bayar' => $validated['cara_bayar'],
            ],
        );

        return new JsonResponse([
            'data' => $jenisPendapatan,
            'message' => $id ? 'Jenis pendapatan berhasil diperbarui.' : 'Jenis pendapatan berhasil ditambahkan.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:jenis_pendapatan,id'],
        ]);

        JenisPendapatan::findOrFail($validated['id'])->update(['flag' => '1']);

        return new JsonResponse([
            'status' => 'OK',
            'message' => 'Jenis pendapatan berhasil dihapus.',
        ]);
    }
}
