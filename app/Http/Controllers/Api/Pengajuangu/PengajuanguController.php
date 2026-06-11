<?php

namespace App\Http\Controllers\Api\Pengajuangu;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pembayaran\Pembayaran;
use App\Models\Pengajuangu\PengajuanguHeder;
use App\Models\Pengajuangu\PengajuanguRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PengajuanguController extends Controller
{
    public function index()
    {
        $jabatan = request('jabatan');
        $search = request('search');

        $query = PengajuanguHeder::query()
            ->with([
                'rinci.pembayaran',
                'rinci.penyedia',
                'unit',
                'jabatan'
            ])
            ->when($jabatan, function ($q) use ($jabatan) {
                $q->where('jabatan', $jabatan);
            })
            ->when($search, function ($q) use ($search) {
                $q->where('nogu', 'like', "%$search%");
            })
            ->orderBy('created_at', 'desc');

        $data = $query->simplePaginate(request('per_page', 10));

        return new JsonResponse($data);
    }

    public function indexall()
    {
        $jabatan = request('jabatan');

        $query = PengajuanguHeder::query()
            ->with([
                'rinci.akun',
                'unit',
                'jabatan'
            ])
            ->when($jabatan, function ($q) use ($jabatan) {
                $q->where('jabatan', $jabatan);
            })
            ->where('flag', '1')
            ->orderBy('created_at', 'desc');

        $data = $query->simplePaginate(request('per_page', 10));

        return new JsonResponse($data);
    }

    public function simpanheder(Request $request)
    {
        $notrans = $request->notrans ?? null;

        $validated = $request->validate([
            'jabatan' => 'required',
            'unit' => 'required',
            'tgl' => 'required|date',
            'nominal' => 'nullable|numeric',
            // 'nominal' => 'required|numeric|not_in:0',

            // 🔥 rincian lengkap (WAJIB biar gak hilang field)
            // 'rincian' => 'required|array|min:1',
            // 'rincian.*.nominal' => 'required|numeric|min:1',
            // 'rincian.*.nopembayaran' => 'nullable|string',
            // 'rincian.*.notagihan' => 'nullable|string',
            // 'rincian.*.tgl_pembayaran' => 'nullable|date',
            // 'rincian.*.kegiatan' => 'nullable|string',
            // 'rincian.*.penyedia' => 'nullable|string',
        ], [
            'jabatan.required' => 'Jabatan Tidak Boleh Kosong...!!!',
            'unit.required' => 'Unit Tidak Boleh Kosong...!!!',
            'tgl.required' => 'Tanggal Tidak Boleh Kosong...!!!',
            // 'nominal.required' => 'Nominal Tidak Boleh Kosong...!!!',
            // 'nominal.numeric' => 'Nominal Harus Berupa Angka...!!!',
            // 'nominal.not_in' => 'Nominal Tidak Boleh 0...!!!',
            // 'rincian.required' => 'Rincian wajib diisi',
            // 'rincian.*.nominal.required' => 'Nominal rincian wajib diisi',
        ]);

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // 🔥 generate nomor kalau belum ada
            if (!$notrans) {
                if ($validated['jabatan'] == 'J000004') {
                    DB::select('call nogupengeluaranyayasan(@nomor)');
                    $nomor = DB::table('counter')->select('nogupengeluaranyayasan')->first();
                    $notrans = FormatingHelper::nogu($nomor->nogupengeluaranyayasan, 'PK');
                } elseif ($validated['jabatan'] == 'J000005') {
                    DB::select('call nogupengeluarantk(@nomor)');
                    $nomor = DB::table('counter')->select('nogupengeluarantk')->first();
                    $notrans = FormatingHelper::nogu($nomor->nogupengeluarantk, 'TK');
                } elseif ($validated['jabatan'] == 'J000006') {
                    DB::select('call nogupengeluaransd(@nomor)');
                    $nomor = DB::table('counter')->select('nogupengeluaransd')->first();
                    $notrans = FormatingHelper::nogu($nomor->nogupengeluaransd, 'SD');
                } else {
                    DB::select('call nogupengeluaransmp(@nomor)');
                    $nomor = DB::table('counter')->select('nogupengeluaransmp')->first();
                    $notrans = FormatingHelper::nogu($nomor->nogupengeluaransmp, 'SMP');
                }
            }

            // 🔥 simpan header
            PengajuanguHeder::updateOrCreate(
                ['nogu' => $notrans],
                [
                    'tgl' => $validated['tgl'],
                    'unit' => $validated['unit'],
                    'jabatan' => $validated['jabatan'],
                    'user' => $user->kode,
                    'flag' => '1',
                    'nominal' => $validated['nominal'],
                ]
            );

            // 🔥 validasi total rincian
            // $totalRinci = collect($validated['rincian'])->sum(function ($item) {
            //     return (int) $item['nominal'];
            // });

            // if ($totalRinci != $validated['nominal']) {
            //     throw new \Exception('Total rincian tidak sama dengan nominal header');
            // }

            // // 🔥 hapus rincian lama
            // PengajuanguRinci::where('nogu', $notrans)->delete();

            // // 🔥 insert rincian
            // foreach ($validated['rincian'] as $item) {
            //     PengajuanguRinci::create([
            //         'nogu' => $notrans,
            //         'nospj' => $item['nopembayaran'] ?? null,
            //         'notagihan' => $item['notagihan'] ?? null,
            //         'tgl_pembayaran' => $item['tgl_pembayaran'] ?? null,
            //         'kegiatan' => $item['kegiatan'] ?? null,
            //         'penyedia' => $item['penyedia'] ?? null,
            //         'nominal' => isset($item['nominal']) ? (int)$item['nominal'] : 0,
            //     ]);
            // }

            DB::commit();

            $data = self::getnotrans($notrans);

            return response()->json([
                'data' => $data,
                'message' => 'Data berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function simpanrinci(Request $request)
    {
        $validated = $request->validate([
            '*.notrans' => 'required',
            '*.nopembayaran' => 'required',
            '*.notagihan' => 'required',
            '*.kegiatan' => 'required',
            '*.penyedia' => 'required',
            '*.tgl_pembayaran' => 'required',
            '*.nominal' => 'required|numeric',
        ], [
            '*.notrans.required' => 'No. Pembayaran Tidak Boleh Kosong...!!!',
            '*.nopembayaran.required' => 'No. Pembayaran Tidak Boleh Kosong...!!!',
            '*.notagihan.required' => 'Tagihan Tidak Boleh Kosong...!!!',
            '*.kegiatan.required' => 'Kegiatan Tidak Boleh Kosong...!!!',
            '*.penyedia.required' => 'Penyedia Tidak Boleh Kosong...!!!',
            '*.tgl_pembayaran.required' => 'TGL Pembayaran Tidak Boleh Kosong...!!!',
            '*nominal.required' => 'Nominal Tidak Boleh Kosong...!!!',
            '*nominal.numeric' => 'Nominal Harus Berupa Angka...!!!',
        ]);

        try {

            DB::beginTransaction();
            foreach ($validated as $item) {

                PengajuanguRinci::create([
                    'nogu' => $item['notrans'],
                    'nospj' => $item['nopembayaran'] ?? null,
                    'notagihan' => $item['notagihan'] ?? null,
                    'tgl_pembayaran' => $item['tgl_pembayaran'] ?? null,
                    'kegiatan' => $item['kegiatan'] ?? null,
                    'penyedia' => $item['penyedia'] ?? null,
                    'nominalpembayaran' => $item['nominal'],
                ]);
            }

            $totalNominal = PengajuanguRinci::where(
                'nogu',
                $validated[0]['notrans']
            )->sum('nominalpembayaran');

            // 🔥 update nominal header
            PengajuanguHeder::where(
                'nogu',
                $validated[0]['notrans']
            )->update([
                'nominal' => $totalNominal
            ]);
            $update = Pembayaran::where('nopembayaran',$validated[0]['nopembayaran'])->update([
                'flag' => '2'
            ]);
            DB::commit();

            $data = self::getnotrans($validated[0]['notrans']);

            return response()->json([
                'data' => $data,
                'message' => 'Data berhasil disimpan'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function hapus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ]);

        try {
            DB::beginTransaction();
            $data = PengajuanguHeder::find($request->id);
            $datarinci = PengajuanguRinci::where('nogu', $request->nogu);
            if (!$data) {
                return response()->json(['message' => 'Data tidak ditemukan'], 404);
            }

            $datarinci->delete();
            $data->delete();
            DB::commit();

            return response()->json([
                'data' => $request->id,
                'message' => 'Data berhasil dihapus',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function hapusrinci(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
            'nogu' => 'required'
        ], [
            'id.required' => 'ID tidak boleh kosong...!!!',
            'nogu.required' => 'No GU tidak boleh kosong...!!!',
        ]);

        try {

            DB::beginTransaction();

            $datarinci = PengajuanguRinci::where(
                'id',
                $validated['id']
            )->first();

            if (!$datarinci) {

                return response()->json([
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            // 🔥 hapus rincian
            $datarinci->delete();

            // 🔥 hitung ulang total rincian
            $totalNominal = PengajuanguRinci::where(
                'nogu',
                $validated['nogu']
            )->sum('nominalpembayaran');

            // 🔥 update nominal header
            PengajuanguHeder::where(
                'nogu',
                $validated['nogu']
            )->update([
                'nominal' => $totalNominal
            ]);

            DB::commit();

            $data = self::getnotrans(
                $validated['nogu']
            );

            return response()->json([
                'data' => $data,
                'message' => 'Data berhasil dihapus',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Gagal menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getnotrans($notrans)
    {
        $data = PengajuanguHeder::query()
            ->with([
                'rinci.pembayaran',
                'unit',
                'jabatan'
            ])
            // ->withSum('rinci', 'nominal')
            ->where('nogu', $notrans)
            ->get();

        return $data;
    }
}
