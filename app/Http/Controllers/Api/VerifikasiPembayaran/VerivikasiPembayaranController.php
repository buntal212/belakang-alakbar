<?php

namespace App\Http\Controllers\Api\VerifikasiPembayaran;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Api\SaldoController;
use App\Http\Controllers\Controller;
use App\Models\Master\Saldo;
use App\Models\Pembayaran\Pembayaran;
use App\Models\Pembayaran\PembayaranLs;
use Auth;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerivikasiPembayaranController extends Controller
{
    public function index()
    {
        if (request('sumberdana') === 'LS') return $this->indexLs();
        $jabatan = request('jabatan');
        $search = request('search');
        $statusverif = request('statusverif');

        $query = Pembayaran::query()
            ->leftJoin('tagihan_h as t', 't.notagihan', '=', 'pembayaran.notagihan')
            ->with([
                'rinci.akun',
                'penyedia',
                'unit',
                'jabatan'
            ])
            ->select(
                'pembayaran.*',
                't.id as tagihan_id',
                't.tgl as tgl_tagihan',
                't.kegiatan as kegiatan_tagihan',
                't.penyedia as kode_penyedia',
                't.unit as kode_unit',
                't.jabatan as kode_jabatan',
                't.jumlahbelanja as total_belanja',
                't.diskon as total_diskon',
                't.pajak as total_pajak',
                't.jumlahditagihkan as total_tagihan',
            )
            ->when($jabatan, function ($q) use ($jabatan) {
                $q->where('pembayaran.jabatan', $jabatan);
            })

            ->when($statusverif !== null && $statusverif !== '', function ($q) use ($statusverif) {
                $q->where('pembayaran.flag', $statusverif);
            })

            // 🔥 SEARCH
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('pembayaran.nopembayaran', 'like', "%$search%")
                        ->orWhere('pembayaran.notagihan', 'like', "%$search%")
                        ->orWhereHas('penyedia', function ($q3) use ($search) {
                            $q3->where('nama', 'like', "%$search%");
                        });
                });
            })

            ->orderBy('pembayaran.created_at', 'desc');

        $data = $query->simplePaginate(request('per_page', 10));

        return new JsonResponse($data);
    }

    public function tolak(Request $request)
    {
        $user = Auth::user();
        $id = $request->id;
        $jenispembayaran = $request->jenispembayaran;
        $jabatan = $request->jabatan_flag;
        $alasan = $request->alasan;

        $data = $request->boolean('ls') ? PembayaranLs::find($id) : Pembayaran::find($id);
        if (!$data) return response()->json(['message' => 'Data pembayaran tidak ditemukan'], 404);
        $data->tgl_verif= date('Y-m-d H:i:s');
        $data->user_verif = $user->kode;
        $data->flag = '3';
        $data->alasan = $request->alasan;
        $data->save();
        $result = $data->fresh(['rinci.akun', 'penyedia', 'unit', 'jabatan']);
        if ($request->boolean('ls')) $result->setAttribute('asal', 'LS');
        return new JsonResponse(
            [
                'status' => 'OK',
                'data' => $result,
            ]);
    }

    public static function nopembayaranBuid($id)
    {
        $jenispembayaran = Pembayaran::query()
            ->leftJoin('tagihan_h as t', 't.notagihan', '=', 'pembayaran.notagihan')
            ->with([
                'rinci.akun',
                'penyedia',
                'unit',
                'jabatan'
            ])
            ->select(
                'pembayaran.*',
                't.id as tagihan_id',
                't.tgl as tgl_tagihan',
                't.kegiatan as kegiatan_tagihan',
                't.penyedia as kode_penyedia',
                't.unit as kode_unit',
                't.jabatan as kode_jabatan',
                't.jumlahbelanja as total_belanja',
                't.diskon as total_diskon',
                't.pajak as total_pajak',
                't.jumlahditagihkan as total_tagihan',
            )->find($id);
        return $jenispembayaran;
    }

    public function terima(Request $request)
    {
        DB::beginTransaction();

        try {

            $user = Auth::user();

            $id = $request->id;
            $nopembayaran = $request->nopembayaran;
            $notagihan = $request->notagihan;
            $jenispembayaran = $request->jenispembayaran == '1' ? 'Bank' : 'Tunai';
            $nominal = $request->nominal;
            $pemilik = $request->pemilik;
            $unit = $request->unit;

            $terimaLs = $request->boolean('ls') ? PembayaranLs::find($id) : null;
            $pembayaranLs = $terimaLs !== null || Pembayaran::query()
                ->join('tagihan_h as t', 't.notagihan', '=', 'pembayaran.notagihan')
                ->where('pembayaran.id', $id)
                ->where('t.sumberdana', 'LS')
                ->exists();

            // LS dibayar langsung dari rekening Bendahara Penerimaan Yayasan.
            // Saldo Bendahara Pengeluaran hanya tercatat pada laporan transaksi.
            if ($pembayaranLs) {
                $pemilik = 'J000003';
                $jenispembayaran = 'Bank';
                $request->merge(['jenispembayaran' => '1']);
                $unit = 'Bendahara Penerimaan Yayasan';
            }

            $ceksaldo = Saldo::where('pemilik', $pemilik)
                ->where('jenis', $jenispembayaran)
                ->value('nominal');

            if ($nominal > ($ceksaldo ?? 0)) {

                DB::rollBack();

                return new JsonResponse([
                    'status' => 'error',
                    'message' => 'Maaf saldo untuk Unit ' . $unit .
                        ' tidak mencukupi, saldo yang dimiliki adalah ' .
                        FormatingHelper::rupiah($ceksaldo ?? 0)
                ], 500);
            }

            $terima = $terimaLs ?: Pembayaran::findOrFail($id);
            $terima->flag = '2';
            $terima->tgl_verif = now();
            $terima->user_verif = $user->kode;
            $terima->save();

            // kurangi saldo
            // Saldo::where('pemilik', $pemilik)
            //     ->where('jenis', $jenispembayaran)
            //     ->decrement('nominal', $nominal);
            SaldoController::saldo($pemilik, $request->jenispembayaran, $nominal);
            DB::commit();
            // Kembalikan relasi lengkap agar kartu di frontend langsung memiliki
            // nama unit dan jabatan tanpa harus menunggu refresh halaman.
            $result = $terima->fresh(['rinci.akun', 'penyedia', 'unit', 'jabatan']);
            if ($pembayaranLs) $result->setAttribute('asal', 'LS');
            return new JsonResponse([
                'message' => 'Data Tersimpan...!!',
                'data' => $result,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function indexLs(): JsonResponse
    {
        $jabatan=request('jabatan'); $search=request('search'); $status=request('statusverif');
        $query=PembayaranLs::query()->leftJoin('tagihan_ls_h as t','t.notagihan','=','pembayaran_ls.notagihan')->with(['rinci.akun','penyedia','unit','jabatan'])->select('pembayaran_ls.*','t.tgl as tgl_tagihan','t.kegiatan as kegiatan_tagihan','t.jumlahbelanja as total_belanja','t.diskon as total_diskon','t.pajak as total_pajak','t.jumlahditagihkan as total_tagihan', DB::raw("'LS' as asal"))->when($jabatan,fn($q)=>$q->where('pembayaran_ls.jabatan',$jabatan))->when($status!==null && $status!=='',fn($q)=>$q->where('pembayaran_ls.flag',$status))->when($search,fn($q)=>$q->where(fn($s)=>$s->where('pembayaran_ls.nopembayaran','like',"%{$search}%")->orWhere('pembayaran_ls.notagihan','like',"%{$search}%")))->orderByDesc('pembayaran_ls.created_at');
        return response()->json($query->simplePaginate(request('per_page',10)));
    }
}
