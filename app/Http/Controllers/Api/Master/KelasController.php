<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Kelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KelasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = $this->queryKelas($request->string('search')->toString());

        return new JsonResponse($query->simplePaginate($request->integer('per_page', 10)));
    }

    public function all(Request $request): JsonResponse
    {
        return new JsonResponse($this->queryKelas($request->string('search')->toString())->get());
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:m_kelas,id'],
            'kelas' => ['required', 'string', 'max:100'],
            'unit' => ['required', 'string', 'max:20', 'exists:unit,kode'],
        ], [
            'kelas.required' => 'Nama kelas wajib diisi.',
            'unit.required' => 'Unit wajib dipilih.',
            'unit.exists' => 'Unit yang dipilih tidak valid.',
        ]);

        $kelas = DB::transaction(function () use ($id, $validated) {
            if ($id) {
                $kelas = Kelas::findOrFail($id);
                $kelas->update($validated);

                return $kelas;
            }

            return Kelas::create([
                ...$validated,
                'kode' => $this->buatKodeKelas(),
            ]);
        });

        return new JsonResponse([
            'data' => $kelas->load('unitData:id,kode,nama_unit'),
            'message' => $id ? 'Data kelas berhasil diperbarui.' : 'Data kelas berhasil ditambahkan.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:m_kelas,id'],
        ]);

        $kelas = Kelas::findOrFail($validated['id']);
        $kelas->update(['flag' => '1']);

        return new JsonResponse([
            'status' => 'OK',
            'message' => 'Data kelas berhasil dihapus.',
        ]);
    }

    private function queryKelas(string $search = '')
    {
        return Kelas::with('unitData:id,kode,nama_unit')
            ->where('flag', '<>', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('kode', 'like', "%{$search}%")
                        ->orWhere('kelas', 'like', "%{$search}%")
                        ->orWhere('unit', 'like', "%{$search}%");
                });
            })
            ->orderBy('kelas');
    }

    private function buatKodeKelas(): string
    {
        $kodeTerakhir = Kelas::query()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('kode');
        $nomorTerakhir = 0;

        if (preg_match('/^KLS(\d+)$/', (string) $kodeTerakhir, $cocok)) {
            $nomorTerakhir = (int) $cocok[1];
        }

        return 'KLS'.str_pad((string) ($nomorTerakhir + 1), 4, '0', STR_PAD_LEFT);
    }
}
