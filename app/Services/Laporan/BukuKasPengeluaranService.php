<?php

namespace App\Services\Laporan;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BukuKasPengeluaranService
{
    public function generate(string $jabatan, string $mulai, string $selesai): array
    {
        $mutasi = $this->mutasi($jabatan);
        $bulanSebelumnya = CarbonImmutable::parse($mulai)->startOfMonth()->subMonth();
        $tanggalOpname = DB::table('saldo_stok_opname')
            ->where('pemilik', $jabatan)
            ->whereBetween('tanggal_opname', [$bulanSebelumnya->startOfMonth()->toDateString(), $bulanSebelumnya->endOfMonth()->toDateString()])
            ->max('tanggal_opname');
        $saldoAwalRincian = $tanggalOpname
            ? DB::table('saldo_stok_opname')->where('pemilik', $jabatan)->whereDate('tanggal_opname', $tanggalOpname)->orderBy('jenis')->get(['jenis', 'nominal'])
            : collect();
        $saldoAwal = (float) $saldoAwalRincian->sum('nominal');
        $saldoAwalPerJenis = $saldoAwalRincian->pluck('nominal', 'jenis');
        $bulanBerjalan = CarbonImmutable::parse($selesai)->startOfMonth();
        $tanggalOpnameAkhir = DB::table('saldo_stok_opname')
            ->where('pemilik', $jabatan)
            ->whereBetween('tanggal_opname', [$bulanBerjalan->startOfMonth()->toDateString(), $bulanBerjalan->endOfMonth()->toDateString()])
            ->max('tanggal_opname');
        $saldoAkhirPerJenis = $tanggalOpnameAkhir
            ? DB::table('saldo_stok_opname')->where('pemilik', $jabatan)->whereDate('tanggal_opname', $tanggalOpnameAkhir)->pluck('nominal', 'jenis')
            : collect();
        $periode = $mutasi->filter(fn ($x) => $x['tanggal'] >= $mulai && $x['tanggal'] <= $selesai)->sortBy([['tanggal', 'asc'], ['prioritas', 'asc'], ['urutan', 'asc']])->values();
        $saldo = $saldoAwal;
        $transaksi = $periode->map(function ($x) use (&$saldo) { $saldo += $x['penerimaan'] - $x['pengeluaran']; $x['saldo'] = $saldo; return $x; });
        return ['periode'=>['tanggal_mulai'=>$mulai,'tanggal_selesai'=>$selesai], 'tanggal_saldo_awal'=>$tanggalOpname, 'tanggal_saldo_akhir'=>$tanggalOpnameAkhir, 'saldo_awal_rincian'=>$saldoAwalRincian->map(fn ($item) => ['jenis' => $item->jenis, 'nominal' => (float) $item->nominal])->values(), 'saldo_awal'=>$saldoAwal, 'transaksi'=>$transaksi, 'total_penerimaan'=>$periode->sum('penerimaan'), 'total_pengeluaran'=>$periode->sum('pengeluaran'), 'saldo_bulan'=>$periode->sum('penerimaan')-$periode->sum('pengeluaran'), 'saldo_akhir'=>$saldo, 'saldo_awal_tunai'=>(float)($saldoAwalPerJenis['Tunai']??0), 'saldo_awal_bank'=>(float)($saldoAwalPerJenis['Bank']??0), 'saldo_awal_panjar'=>(float)($saldoAwalPerJenis['Panjar']??0), 'saldo_akhir_tunai'=>(float)($saldoAkhirPerJenis['Tunai']??0), 'saldo_akhir_bank'=>(float)($saldoAkhirPerJenis['Bank']??0), 'saldo_akhir_panjar'=>(float)($saldoAkhirPerJenis['Panjar']??0)];
    }

    private function mutasi(string $jabatan): Collection
    {
        $rows = collect();
        $penyedia = DB::table('m_penyedia')->pluck('nama', 'kode');
        $penerimaPanjar = DB::table('panjar')->pluck('ditujukanke', 'notrans');
        $pengguna = DB::table('users')->pluck('name', 'kode');
        foreach (DB::table('pembayaran')->where('jabatan',$jabatan)->where('flag','2')->get() as $x) {
            $namaPenyedia = $penyedia->get($x->penyedia);
            $keterangan = $namaPenyedia ? 'Pembayaran tagihan ke '.$namaPenyedia : 'Pembayaran tagihan';
            $rows->push($this->row($x->tgl,$x->nopembayaran,$keterangan,0,$x->nominal,$x->id));
        }
        foreach (DB::table('pembayaran_ls')->where('jabatan',$jabatan)->where('flag','2')->get() as $x) $rows->push($this->row($x->tgl,$x->nopembayaran,'Pembayaran LS',0,$x->nominal,$x->id));
        foreach (DB::table('pengajuan_up')->where('jabatan',$jabatan)->whereNotNull('tgl_terima')->get() as $x) $rows->push($this->row($x->tgl_terima,$x->no_pengajuan,'Penerimaan UP',$x->nilai_pengajuan,0,$x->id));
        foreach (DB::table('panjar')->where('jabatan',$jabatan)->get() as $x) $rows->push($this->row($x->tgl,$x->notrans,'Pergeseran Tunai ke Panjar',$x->jumlahpanjar,$x->jumlahpanjar,$x->id));
        foreach (DB::table('pergeserankas')->where('jabatan',$jabatan)->get() as $x) $rows->push($this->row($x->tgl,$x->no_pergeseran,$x->jenis==='1'?'Pergeseran Bank ke Tunai':'Pergeseran Tunai ke Bank',$x->nominal,$x->nominal,$x->id));
        foreach (DB::table('pengembaliansisapanjar')->where('jabatan',$jabatan)->get() as $x) {
            $namaPihakKetiga = $pengguna->get($penerimaPanjar->get($x->nopanjar));
            $keterangan = $namaPihakKetiga
                ? 'Pengembalian Sisa Panjar dari '.$namaPihakKetiga
                : 'Pengembalian Sisa Panjar';
            $rows->push($this->row($x->tgl,$x->notrans,$keterangan,$x->sisapanjar,$x->sisapanjar,$x->id));
        }
        return $rows;
    }
    private function row($tanggal,$nomor,$keterangan,$penerimaan,$pengeluaran,$urutan): array
    {
        $penerimaan = (float) $penerimaan;

        return [
            'tanggal' => $tanggal,
            'nomor_bukti' => $nomor,
            'keterangan' => $keterangan,
            'penerimaan' => $penerimaan,
            'pengeluaran' => (float) $pengeluaran,
            // Pada tanggal yang sama: Penerimaan UP, Pergeseran Kas, penerimaan lain, lalu pengeluaran.
            'prioritas' => $keterangan === 'Penerimaan UP'
                ? 0
                : (str_starts_with($keterangan, 'Pergeseran') ? 1 : ($penerimaan > 0 ? 2 : 3)),
            'urutan' => $urutan,
        ];
    }
}
