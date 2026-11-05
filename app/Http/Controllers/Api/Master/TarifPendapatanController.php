<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\TarifPendapatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TarifPendapatanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $query = $this->queryTarifPendapatan($search);

        return new JsonResponse($query->simplePaginate($request->integer('per_page', 10)));
    }

    public function kelas(): JsonResponse
    {
        $kelas = \App\Models\Master\Kelas::with('unitData:kode,nama_unit')
                ->where('flag', '<>', '1')
                ->orderBy('kelas')
                ->get(['kode', 'kelas', 'unit'])
                ->unique(fn ($item) => mb_strtolower($item->kelas).'|'.$item->unit)
                ->values();

        return new JsonResponse($kelas);
    }

    public function jenisPendapatan(): JsonResponse
    {
        return new JsonResponse(
            \App\Models\Master\JenisPendapatan::where('flag', '<>', '1')
                ->orderBy('jenis_pendapatan')
                ->get(['id', 'jenis_pendapatan', 'cara_bayar']),
        );
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:tarif_pendapatan,id'],
            'kelas' => ['required', 'string', 'exists:m_kelas,kode'],
            'jenispendapatan' => [
                'required',
                'integer',
                'exists:jenis_pendapatan,id',
                Rule::unique('tarif_pendapatan', 'jenispendapatan')
                    ->where(fn ($query) => $query
                        ->where('kelas', $request->input('kelas'))
                        ->where('flag', '<>', '1'))
                    ->ignore($id),
            ],
            'nilai' => ['required', 'numeric', 'min:0'],
        ], [
            'kelas.required' => 'Kelas wajib dipilih.',
            'kelas.exists' => 'Kelas yang dipilih tidak valid.',
            'jenispendapatan.required' => 'Jenis pendapatan wajib dipilih.',
            'jenispendapatan.exists' => 'Jenis pendapatan yang dipilih tidak valid.',
            'jenispendapatan.unique' => 'Tarif untuk kelas dan jenis pendapatan ini sudah tersedia.',
            'nilai.required' => 'Nilai tarif wajib diisi.',
            'nilai.numeric' => 'Nilai tarif harus berupa angka.',
        ]);

        $tarif = DB::transaction(function () use ($id, $validated) {
            if ($id) {
                $tarif = TarifPendapatan::findOrFail($id);
                $tarif->update($validated);

                return $tarif;
            }

            return TarifPendapatan::create([
                ...$validated,
                'kode_tarif' => $this->buatKodeTarif(),
            ]);
        });

        return new JsonResponse([
            'data' => $tarif->load([
                'kelasData:kode,kelas,unit',
                'kelasData.unitData:kode,nama_unit',
                'jenisPendapatanData:id,jenis_pendapatan,cara_bayar',
            ]),
            'message' => $id ? 'Tarif pendapatan berhasil diperbarui.' : 'Tarif pendapatan berhasil ditambahkan.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:tarif_pendapatan,id'],
        ]);

        TarifPendapatan::findOrFail($validated['id'])->update(['flag' => '1']);

        return new JsonResponse([
            'status' => 'OK',
            'message' => 'Tarif pendapatan berhasil dihapus.',
        ]);
    }

    private function queryTarifPendapatan(string $search = '')
    {
        return TarifPendapatan::with([
            'kelasData:kode,kelas,unit',
            'kelasData.unitData:kode,nama_unit',
            'jenisPendapatanData:id,jenis_pendapatan,cara_bayar',
        ])
            ->where('flag', '<>', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($filter) use ($search) {
                    $filter->where('kode_tarif', 'like', "%{$search}%")
                        ->orWhereHas('kelasData', fn ($kelas) => $kelas->where('kelas', 'like', "%{$search}%"))
                        ->orWhereHas('jenisPendapatanData', fn ($jenis) => $jenis->where('jenis_pendapatan', 'like', "%{$search}%"));
                });
            })
            ->orderBy('kode_tarif');
    }

    private function buatKodeTarif(): string
    {
        $kodeTerakhir = TarifPendapatan::query()
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('kode_tarif');
        $nomorTerakhir = 0;

        if (preg_match('/^TRF-(\d+)$/', (string) $kodeTerakhir, $cocok)) {
            $nomorTerakhir = (int) $cocok[1];
        }

        return 'TRF-'.str_pad((string) ($nomorTerakhir + 1), 4, '0', STR_PAD_LEFT);
    }
}
