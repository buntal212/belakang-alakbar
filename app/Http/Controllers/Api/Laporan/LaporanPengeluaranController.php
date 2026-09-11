<?php

namespace App\Http\Controllers\Api\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran\Pembayaran;
use App\Models\Pembayaran\PembayaranLs;
use Illuminate\Http\Request;

class LaporanPengeluaranController extends Controller
{
    public function bukuKas(Request $request)
    {
        $data=$request->validate(['tanggal_mulai'=>'required|date','tanggal_selesai'=>'required|date|after_or_equal:tanggal_mulai','jabatan'=>'required']);
        return response()->json(app(\App\Services\Laporan\BukuKasPengeluaranService::class)->generate($data['jabatan'],$data['tanggal_mulai'],$data['tanggal_selesai']));
    }
    public function utang(Request $request)
    {
        $data = $request->validate(['tanggal_mulai' => 'required|date', 'tanggal_selesai' => 'required|date', 'jabatan' => 'required']);
        $regular = \DB::table('tagihan_h as t')->leftJoin('m_penyedia as p', function ($join) {
            $join->on(\DB::raw('p.kode COLLATE utf8mb4_unicode_ci'), '=', \DB::raw('t.penyedia COLLATE utf8mb4_unicode_ci'));
        })
            ->where('t.jabatan', $data['jabatan'])->whereBetween('t.tgl', [$data['tanggal_mulai'], $data['tanggal_selesai']])->orderBy('t.tgl')->orderBy('t.created_at')->get()->map(function ($tagihan) {
                $bayar = \DB::table('pembayaran')->where('notagihan', $tagihan->notagihan)->where('flag', '2')->orderBy('tgl')->get(['tgl', 'nopembayaran', 'nominal']);
                $terbayar = $bayar->sum('nominal'); $tagihan->penyedia_nama = $tagihan->nama ?? '-'; $tagihan->rincian_belanja = \DB::table('tagihan_r')->where('notagihan', $tagihan->notagihan)->orderBy('id')->pluck('rincian'); $tagihan->pembayaran = $bayar; $tagihan->sisa_utang = max(0, $tagihan->jumlahditagihkan - $terbayar); return $tagihan;
            })->filter(fn ($tagihan) => $tagihan->sisa_utang > 0);
        $ls = \DB::table('tagihan_ls_h as t')->leftJoin('m_penyedia as p', function ($join) {
            $join->on(\DB::raw('p.kode COLLATE utf8mb4_unicode_ci'), '=', \DB::raw('t.penyedia COLLATE utf8mb4_unicode_ci'));
        })
            ->where('t.jabatan', $data['jabatan'])->whereBetween('t.tgl', [$data['tanggal_mulai'], $data['tanggal_selesai']])->orderBy('t.tgl')->orderBy('t.created_at')->get()->map(function ($tagihan) {
                $bayar = \DB::table('pembayaran_ls')->where('notagihan', $tagihan->notagihan)->where('flag', '2')->orderBy('tgl')->get(['tgl', 'nopembayaran', 'nominal']);
                $terbayar = $bayar->sum('nominal'); $tagihan->penyedia_nama = $tagihan->nama ?? '-'; $tagihan->rincian_belanja = \DB::table('tagihan_ls_r')->where('notagihan', $tagihan->notagihan)->orderBy('id')->pluck('rincian'); $tagihan->pembayaran = $bayar; $tagihan->sisa_utang = max(0, $tagihan->jumlahditagihkan - $terbayar); return $tagihan;
            })->filter(fn ($tagihan) => $tagihan->sisa_utang > 0);
        $items = $regular->concat($ls)
            ->sortBy([['tgl', 'asc'], ['created_at', 'asc']])
            ->groupBy('penyedia_nama')
            ->map(fn ($tagihan, $penyedia) => ['penyedia' => $penyedia, 'tagihan' => $tagihan->values(), 'total' => $tagihan->sum('sisa_utang')])
            ->values();
        return response()->json(['data' => $items, 'meta' => ['total_utang' => $items->sum('total')]]);
    }
    public function pengeluaran(Request $request)
    {
        $data = $request->validate([
            'tanggal_mulai' => 'required|date', 'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'jabatan' => 'required', 'penyedia' => 'nullable', 'sumberdana' => 'nullable', 'kodebelanja' => 'nullable|string', 'jenis_pembayaran' => 'nullable',
        ]);

        $regular = Pembayaran::query()->with(['rinci.akun', 'penyedia', 'unit', 'jabatan'])
            ->leftJoin('tagihan_h as tagihan', 'tagihan.notagihan', '=', 'pembayaran.notagihan')
            ->leftJoin('m_sumberdana as sumber_dana', function ($join) {
                $join->on(
                    \DB::raw('sumber_dana.kode COLLATE utf8mb4_unicode_ci'),
                    '=',
                    \DB::raw('tagihan.sumberdana COLLATE utf8mb4_unicode_ci')
                );
            })
            ->select('pembayaran.*', 'tagihan.kegiatan as kegiatan', 'tagihan.sumberdana', 'sumber_dana.kegiatan as nama_sumberdana')
            ->where('pembayaran.flag', '2')->where('pembayaran.jabatan', $data['jabatan'])
            ->whereBetween('pembayaran.tgl', [$data['tanggal_mulai'], $data['tanggal_selesai']])
            ->when($data['penyedia'] ?? null, fn ($q, $v) => $q->where('pembayaran.penyedia', $v))
            ->when($data['sumberdana'] ?? null, fn ($q, $v) => $q->where('tagihan.sumberdana', $v))
            ->when($data['kodebelanja'] ?? null, fn ($q, $v) => $q->whereHas('rinci', fn ($r) => $r->where('akun', $v)))
            ->when($data['jenis_pembayaran'] ?? null, fn ($q, $v) => $q->where('pembayaran.jenispembayaran', $v))->get();

        $ls = PembayaranLs::query()->with(['rinci.akun', 'penyedia', 'unit', 'jabatan'])
            ->leftJoin('tagihan_ls_h as tagihan', function ($join) {
                $join->on(
                    \DB::raw('tagihan.notagihan COLLATE utf8mb4_unicode_ci'),
                    '=',
                    \DB::raw('pembayaran_ls.notagihan COLLATE utf8mb4_unicode_ci')
                );
            })
            ->leftJoin('m_sumberdana as sumber_dana', function ($join) {
                $join->on(
                    \DB::raw('sumber_dana.kode COLLATE utf8mb4_unicode_ci'),
                    '=',
                    \DB::raw('tagihan.sumberdana COLLATE utf8mb4_unicode_ci')
                );
            })
            ->select('pembayaran_ls.*', 'tagihan.kegiatan as kegiatan', 'tagihan.sumberdana', 'sumber_dana.kegiatan as nama_sumberdana', \DB::raw("'1' as jenispembayaran"))
            ->where('pembayaran_ls.flag', '2')->where('pembayaran_ls.jabatan', $data['jabatan'])
            ->whereBetween('pembayaran_ls.tgl', [$data['tanggal_mulai'], $data['tanggal_selesai']])
            ->when($data['penyedia'] ?? null, fn ($q, $v) => $q->where('pembayaran_ls.penyedia', $v))
            ->when($data['sumberdana'] ?? null, fn ($q, $v) => $q->where('tagihan.sumberdana', $v))
            ->when($data['kodebelanja'] ?? null, fn ($q, $v) => $q->whereHas('rinci', fn ($r) => $r->where('akun', $v)))
            // Pembayaran LS selalu non-tunai dan tidak memiliki kolom jenispembayaran.
            ->when(($data['jenis_pembayaran'] ?? null) === '2', fn ($q) => $q->whereRaw('1 = 0'))->get();

        $items = $regular->toBase()->map(fn ($item) => $this->format($item, 'Reguler'))
            ->merge($ls->toBase()->map(fn ($item) => $this->format($item, 'LS')))->sortByDesc('tgl')->values();

        return response()->json(['data' => $items, 'meta' => ['jumlah_transaksi' => $items->count(), 'total_pengeluaran' => $items->sum('nominal')]]);
    }

    private function format($item, string $asal): array
    {
        $penyedia = $item->getRelation('penyedia');
        $unit = $item->getRelation('unit');
        $jabatan = $item->getRelation('jabatan');

        return ['id' => $asal.'-'.$item->id, 'asal' => $asal, 'tgl' => $item->tgl, 'no_spj' => $item->nopembayaran,
            'no_tagihan' => $item->notagihan, 'sumberdana' => $item->nama_sumberdana ?: '-', 'kegiatan' => $item->kegiatan,
            'penyedia' => $penyedia?->nama ?: '-', 'jenis_pembayaran' => $item->jenispembayaran === '1' ? 'Non Tunai' : 'Tunai',
            'nominal' => (float) $item->nominal, 'unit' => $unit?->nama_unit, 'bendahara' => $jabatan?->jabatan,
            'rincian' => $item->rinci->map(fn ($r) => ['akun' => $r->getRelation('akun')?->belanja ?: '-', 'uraian' => $r->rincian, 'qty' => $r->qty, 'satuan' => $r->satuan, 'harga' => (float) $r->harga, 'jumlah' => (float) $r->jumlah])->values()];
    }
}
