<?php

namespace App\Services\Laporan;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BukuKasPengeluaranService
{
    public function generate(string $jabatan, string $mulai, string $selesai): array
    {
        $mutasi = $this->mutasi($jabatan);
        $sebelum = $mutasi->filter(fn ($x) => $x['tanggal'] < $mulai);
        $saldoAwal = $sebelum->sum('penerimaan') - $sebelum->sum('pengeluaran');
        $periode = $mutasi->filter(fn ($x) => $x['tanggal'] >= $mulai && $x['tanggal'] <= $selesai)->sortBy([['tanggal', 'asc'], ['urutan', 'asc']])->values();
        $saldo = $saldoAwal;
        $transaksi = $periode->map(function ($x) use (&$saldo) { $saldo += $x['penerimaan'] - $x['pengeluaran']; $x['saldo'] = $saldo; return $x; });
        $saldoJenis = DB::table('saldo_sekarang')->where('pemilik', $jabatan)->pluck('nominal', 'jenis');
        return ['periode'=>['tanggal_mulai'=>$mulai,'tanggal_selesai'=>$selesai], 'saldo_awal'=>$saldoAwal, 'transaksi'=>$transaksi, 'total_penerimaan'=>$periode->sum('penerimaan'), 'total_pengeluaran'=>$periode->sum('pengeluaran'), 'saldo_bulan'=>$periode->sum('penerimaan')-$periode->sum('pengeluaran'), 'saldo_akhir'=>$saldo, 'saldo_tunai'=>(float)($saldoJenis['Tunai']??0), 'saldo_bank'=>(float)($saldoJenis['Bank']??0), 'saldo_panjar'=>(float)($saldoJenis['Panjar']??0)];
    }

    private function mutasi(string $jabatan): Collection
    {
        $rows = collect();
        foreach (DB::table('pembayaran')->where('jabatan',$jabatan)->where('flag','2')->get() as $x) $rows->push($this->row($x->tgl,$x->nopembayaran,'Pembayaran tagihan',0,$x->nominal,$x->id));
        foreach (DB::table('pembayaran_ls')->where('jabatan',$jabatan)->where('flag','2')->get() as $x) $rows->push($this->row($x->tgl,$x->nopembayaran,'Pembayaran LS',0,$x->nominal,$x->id));
        foreach (DB::table('pengajuan_up')->where('jabatan',$jabatan)->whereNotNull('tgl_terima')->get() as $x) $rows->push($this->row($x->tgl_terima,$x->no_pengajuan,'Penerimaan UP',$x->nilai_pengajuan,0,$x->id));
        foreach (DB::table('panjar')->where('jabatan',$jabatan)->get() as $x) $rows->push($this->row($x->tgl,$x->notrans,'Panjar - '.$x->kegiatan,$x->jumlahpanjar,$x->jumlahpanjar,$x->id));
        foreach (DB::table('pergeserankas')->where('jabatan',$jabatan)->get() as $x) $rows->push($this->row($x->tgl,$x->no_pergeseran,$x->jenis==='1'?'Pergeseran Bank ke Tunai':'Pergeseran Tunai ke Bank',$x->nominal,$x->nominal,$x->id));
        foreach (DB::table('pengembaliansisapanjar')->where('jabatan',$jabatan)->get() as $x) $rows->push($this->row($x->tgl,$x->notrans,'Pengembalian Sisa Panjar',$x->sisapanjar,$x->sisapanjar,$x->id));
        return $rows;
    }
    private function row($tanggal,$nomor,$keterangan,$penerimaan,$pengeluaran,$urutan): array { return ['tanggal'=>$tanggal,'nomor_bukti'=>$nomor,'keterangan'=>$keterangan,'penerimaan'=>(float)$penerimaan,'pengeluaran'=>(float)$pengeluaran,'urutan'=>$urutan]; }
}
