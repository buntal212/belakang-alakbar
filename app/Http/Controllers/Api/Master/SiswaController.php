<?php

namespace App\Http\Controllers\Api\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\JenisDiskon;
use App\Models\Master\Kelas;
use App\Models\Master\PendapatanSiswa;
use App\Models\Master\Siswa;
use App\Models\Master\TarifPendapatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SiswaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->toString();
        $query = Siswa::with(['kelasData:kode,kelas,unit', 'kelasData.unitData:kode,nama_unit', 'diskonData:id,jenisdiskon,persentase_diskon', 'pendapatanSiswa.jenisPendapatanData:id,jenis_pendapatan'])
            ->where('flag', '<>', '1')
            ->when($request->filled('angkatan'), fn ($query) => $query->where('angkatan', $request->input('angkatan')))
            ->when($request->filled('kelas'), fn ($query) => $query->where('kelas', $request->input('kelas')))
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->where('nis', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%")->orWhere('angkatan', 'like', "%{$search}%")))
            ->orderBy('nama');
        $data = $query->simplePaginate($request->integer('per_page', 10));
        $this->tambahkanNilaiTarif($data->getCollection());

        return new JsonResponse($data);
    }

    public function kelas(): JsonResponse
    {
        return new JsonResponse(Kelas::with('unitData:kode,nama_unit')->where('flag', '<>', '1')->orderBy('kelas')->get(['kode', 'kelas', 'unit'])->unique(fn ($item) => mb_strtolower($item->kelas).'|'.$item->unit)->values());
    }

    public function diskon(): JsonResponse
    {
        return new JsonResponse(JenisDiskon::where('flag', '<>', '1')->orderBy('jenisdiskon')->get(['id', 'jenisdiskon', 'persentase_diskon']));
    }

    public function angkatan(): JsonResponse
    {
        return new JsonResponse(
            Siswa::where('flag', '<>', '1')->whereNotNull('angkatan')->distinct()->orderBy('angkatan')->pluck('angkatan'),
        );
    }

    public function pendapatan(Request $request): JsonResponse
    {
        $kelas = $request->validate(['kelas' => ['required', 'string', 'exists:m_kelas,kode']])['kelas'];
        $items = TarifPendapatan::with('jenisPendapatanData:id,jenis_pendapatan,cara_bayar')
            ->where('flag', '<>', '1')->where('kelas', $kelas)->orderBy('kode_tarif')->get()
            ->unique('jenispendapatan')->values()->map(fn ($tarif) => $tarif->jenisPendapatanData);
        return new JsonResponse($items);
    }

    public function store(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:msiswa,id'],
            'nis' => ['required', 'string', 'max:30', Rule::unique('msiswa', 'nis')->ignore($id)],
            'nama' => ['required', 'string', 'max:150'],
            'angkatan' => ['required', 'string', 'max:20'],
            'kelas' => ['required', 'string', 'exists:m_kelas,kode'],
            'diskon' => ['nullable', 'integer', 'exists:jenis_diskon,id'],
            'pendapatan_ids' => ['required', 'array', 'min:1'],
            'pendapatan_ids.*' => ['integer', 'exists:jenis_pendapatan,id'],
        ], ['nis.unique' => 'NIS sudah digunakan.', 'pendapatan_ids.min' => 'Pilih minimal satu jenis pendapatan.']);
        $siswa = DB::transaction(function () use ($id, $validated) {
            $data = collect($validated)->only(['nis', 'nama', 'angkatan', 'kelas', 'diskon'])->all();
            $siswa = Siswa::updateOrCreate(['id' => $id], $data);
            PendapatanSiswa::where('nis', $siswa->nis)->delete();
            PendapatanSiswa::insert(collect($validated['pendapatan_ids'])->unique()->map(fn ($jenis) => ['nis' => $siswa->nis, 'jenispendapatan' => $jenis, 'created_at' => now(), 'updated_at' => now()])->all());
            return $siswa;
        });
        return new JsonResponse(['data' => $this->detail($siswa->id), 'message' => $id ? 'Data siswa berhasil diperbarui.' : 'Data siswa berhasil ditambahkan.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $id = $request->validate(['id' => ['required', 'integer', 'exists:msiswa,id']])['id'];
        Siswa::findOrFail($id)->update(['flag' => '1']);
        return new JsonResponse(['status' => 'OK', 'message' => 'Data siswa berhasil dihapus.']);
    }

    private function detail(int $id): Siswa
    {
        $siswa = Siswa::with(['kelasData:kode,kelas,unit', 'kelasData.unitData:kode,nama_unit', 'diskonData:id,jenisdiskon,persentase_diskon', 'pendapatanSiswa.jenisPendapatanData:id,jenis_pendapatan'])->findOrFail($id);
        $this->tambahkanNilaiTarif(collect([$siswa]));

        return $siswa;
    }

    private function tambahkanNilaiTarif($siswas): void
    {
        $kelas = $siswas->pluck('kelas')->unique()->values();
        $jenisPendapatan = $siswas->flatMap(fn ($siswa) => $siswa->pendapatanSiswa->pluck('jenispendapatan'))->unique()->values();
        $tarif = TarifPendapatan::where('flag', '<>', '1')
            ->whereIn('kelas', $kelas)
            ->whereIn('jenispendapatan', $jenisPendapatan)
            ->get(['kelas', 'jenispendapatan', 'nilai'])
            ->keyBy(fn ($item) => $item->kelas.'|'.$item->jenispendapatan);

        $siswas->each(function ($siswa) use ($tarif) {
            $siswa->pendapatanSiswa->each(function ($pendapatan) use ($siswa, $tarif) {
                $pendapatan->setAttribute('nilai_tarif', $tarif->get($siswa->kelas.'|'.$pendapatan->jenispendapatan)?->nilai ?? 0);
            });
        });
    }
}
