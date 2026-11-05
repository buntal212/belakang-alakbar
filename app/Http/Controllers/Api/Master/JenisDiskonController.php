<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisDiskon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JenisDiskonController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JenisDiskon::where('flag', '<>', '1')
            ->when($request->filled('search'), fn ($query) => $query->where('jenisdiskon', 'like', '%'.$request->input('search').'%'))
            ->orderBy('jenisdiskon');
        return new JsonResponse($query->simplePaginate($request->integer('per_page', 10)));
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:jenis_diskon,id'],
            'jenisdiskon' => ['required', 'string', 'max:100', Rule::unique('jenis_diskon', 'jenisdiskon')->ignore($id)],
            'persentase_diskon' => ['required', 'numeric', 'min:0', 'max:100'],
        ], [
            'jenisdiskon.required' => 'Jenis diskon wajib diisi.',
            'jenisdiskon.unique' => 'Jenis diskon sudah tersedia.',
            'persentase_diskon.required' => 'Persentase diskon wajib diisi.',
            'persentase_diskon.max' => 'Persentase diskon maksimal 100%.',
        ]);
        $data = JenisDiskon::updateOrCreate(['id' => $id], $validated);
        return new JsonResponse(['data' => $data, 'message' => $id ? 'Jenis diskon berhasil diperbarui.' : 'Jenis diskon berhasil ditambahkan.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->validate(['id' => ['required', 'integer', 'exists:jenis_diskon,id']])['id'];
        JenisDiskon::findOrFail($id)->update(['flag' => '1']);
        return new JsonResponse(['status' => 'OK', 'message' => 'Jenis diskon berhasil dihapus.']);
    }
}
