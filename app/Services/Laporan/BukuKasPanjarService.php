<?php

namespace App\Services\Laporan;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BukuKasPanjarService
{
    public function generate(string $jabatan, string $mulai, string $selesai): array
    {
        $mutasi = $this->mutasi($jabatan);
        $sebelumPeriode = $mutasi->filter(fn (array $item) => $item['tanggal'] < $mulai);
        $saldoAwal = (float) $sebelumPeriode->sum('penerimaan') - (float) $sebelumPeriode->sum('pengeluaran');
        $periode = $mutasi
            ->filter(fn (array $item) => $item['tanggal'] >= $mulai && $item['tanggal'] <= $selesai)
            ->sortBy([['tanggal', 'asc'], ['urutan', 'asc'], ['id_urutan', 'asc']])
            ->values();

        $saldo = $saldoAwal;
        $transaksi = $periode->map(function (array $item) use (&$saldo) {
            $saldo += $item['penerimaan'] - $item['pengeluaran'];
            $item['saldo'] = $saldo;

            return $item;
        });

        return [
            'periode' => ['tanggal_mulai' => $mulai, 'tanggal_selesai' => $selesai],
            'saldo_awal' => $saldoAwal,
            'transaksi' => $transaksi,
            'total_penerimaan' => (float) $periode->sum('penerimaan'),
            'total_pengeluaran' => (float) $periode->sum('pengeluaran'),
            'saldo_bulan' => (float) $periode->sum('penerimaan') - (float) $periode->sum('pengeluaran'),
            'saldo_akhir' => $saldo,
        ];
    }

    private function mutasi(string $jabatan): Collection
    {
        $rows = collect();
        $pengguna = DB::table('users')->pluck('name', 'kode');

        foreach (DB::table('panjar')->where('jabatan', $jabatan)->get() as $panjar) {
            $penerima = $pengguna->get($panjar->ditujukanke);
            $rows->push($this->row(
                $panjar->tgl,
                $panjar->notrans,
                $penerima ? 'Panjar - ' . $penerima : 'Penerimaan Panjar',
                $panjar->jumlahpanjar,
                0,
                0,
                $panjar->id
            ));
        }

        $spjPanjar = DB::table('spjpanjar_h')->where('jabatan', $jabatan)->get();
        $rincianSpj = DB::table('spjpanjar_r')->whereIn('nospjpanjar', $spjPanjar->pluck('nospjpanjar'))->orderBy('id')->get()->groupBy('nospjpanjar');
        foreach ($spjPanjar as $spj) {
            $rows->push($this->row(
                $spj->tglspjpanjar,
                $spj->nospjpanjar,
                $spj->kegiatan ?: 'SPJ Panjar',
                0,
                0,
                1,
                $spj->id,
                true
            ));

            $rincian = $rincianSpj->get($spj->nospjpanjar, collect());
            if ($rincian->isEmpty()) {
                $rows->push($this->row($spj->tglspjpanjar, null, 'Belanja Panjar', 0, $spj->jumlahpembayaran, 2, $spj->id));
                continue;
            }

            foreach ($rincian as $detail) {
                $rows->push($this->row(
                    $spj->tglspjpanjar,
                    null,
                    $detail->rincian ?: 'Belanja Panjar',
                    0,
                    $detail->jumlah,
                    2,
                    $detail->id,
                    false,
                    true
                ));
            }
        }

        foreach (DB::table('pengembaliansisapanjar')->where('jabatan', $jabatan)->get() as $pengembalian) {
            $penerima = DB::table('panjar')->where('notrans', $pengembalian->nopanjar)->value('ditujukanke');
            $nama = $pengguna->get($penerima);
            $rows->push($this->row(
                $pengembalian->tgl,
                $pengembalian->notrans,
                $nama ? 'Pengembalian Sisa Panjar - ' . $nama : 'Pengembalian Sisa Panjar',
                0,
                $pengembalian->sisapanjar,
                3,
                $pengembalian->id
            ));
        }

        return $rows;
    }

    private function row($tanggal, $nomor, $keterangan, $penerimaan, $pengeluaran, int $urutan, int $id, bool $headerSpj = false, bool $rincianSpj = false): array
    {
        return [
            'tanggal' => $tanggal,
            'nomor_bukti' => $nomor,
            'keterangan' => $keterangan,
            'penerimaan' => (float) $penerimaan,
            'pengeluaran' => (float) $pengeluaran,
            'urutan' => $urutan,
            'id_urutan' => $id,
            'is_header_spj' => $headerSpj,
            'is_rincian_spj' => $rincianSpj,
        ];
    }
}
