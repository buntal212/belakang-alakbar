<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class SaldoStokOpnameSnapshotService
{
    public function snapshot(?string $bulanOption = null): array
    {
        $bulan = $bulanOption
            ? CarbonImmutable::createFromFormat('!Y-m', $bulanOption)
            : CarbonImmutable::now()->startOfMonth()->subMonth();
        $mulai = $bulan->startOfMonth()->toDateString();
        $selesai = $bulan->endOfMonth()->toDateString();
        $tanggalOpname = $bulan->endOfMonth()->toDateString();
        $waktu = now();

        $saldo = $this->saldoAwal($bulan);
        foreach (DB::table('saldo_sekarang')->whereNotNull('pemilik')->distinct()->pluck('pemilik') as $pemilik) {
            $saldo[$pemilik] ??= ['Tunai' => 0, 'Bank' => 0, 'Panjar' => 0];
        }
        $tambahSaldo = function (?string $pemilik, string $jenis, float $nominal) use (&$saldo): void {
            if (! $pemilik) return;
            $saldo[$pemilik] ??= ['Tunai' => 0, 'Bank' => 0, 'Panjar' => 0];
            $saldo[$pemilik][$jenis] += $nominal;
        };

        // Seluruh mutasi bulan target dihitung dari saldo opname bulan sebelumnya.
        foreach (DB::table('pengajuan_up')->whereNotNull('tgl_terima')->whereBetween('tgl_terima', [$mulai, $selesai])->get(['jabatan', 'nilai_pengajuan']) as $item) {
            $tambahSaldo($item->jabatan, 'Bank', (float) $item->nilai_pengajuan);
        }
        foreach (DB::table('gu_h')->where('flag', '3')->whereBetween('tgl_verif_ben_penerimaan', [$mulai, $selesai])->get(['jabatan', 'nominal']) as $item) {
            $tambahSaldo($item->jabatan, 'Bank', (float) $item->nominal);
        }
        foreach (DB::table('pembayaran')->where('flag', '2')->whereBetween('tgl', [$mulai, $selesai])->get(['jabatan', 'jenispembayaran', 'nominal']) as $item) {
            $jenis = $item->jenispembayaran === '1' ? 'Bank' : 'Tunai';
            $tambahSaldo($item->jabatan, $jenis, -(float) $item->nominal);
        }
        foreach (DB::table('pembayaran_ls')->where('flag', '2')->whereBetween('tgl', [$mulai, $selesai])->get(['jabatan', 'nominal']) as $item) {
            $tambahSaldo($item->jabatan, 'Bank', -(float) $item->nominal);
        }
        foreach (DB::table('panjar')->whereBetween('tgl', [$mulai, $selesai])->get(['jabatan', 'jumlahpanjar']) as $item) {
            $tambahSaldo($item->jabatan, 'Tunai', -(float) $item->jumlahpanjar);
            $tambahSaldo($item->jabatan, 'Panjar', (float) $item->jumlahpanjar);
        }
        foreach (DB::table('pergeserankas')->whereBetween('tgl', [$mulai, $selesai])->get(['jabatan', 'jenis', 'nominal']) as $item) {
            $asal = $item->jenis === '1' ? 'Bank' : 'Tunai';
            $tujuan = $item->jenis === '1' ? 'Tunai' : 'Bank';
            $tambahSaldo($item->jabatan, $asal, -(float) $item->nominal);
            $tambahSaldo($item->jabatan, $tujuan, (float) $item->nominal);
        }
        foreach (DB::table('pengembaliansisapanjar')->whereBetween('tgl', [$mulai, $selesai])->get(['jabatan', 'sisapanjar']) as $item) {
            $tambahSaldo($item->jabatan, 'Tunai', (float) $item->sisapanjar);
            $tambahSaldo($item->jabatan, 'Panjar', -(float) $item->sisapanjar);
        }

        $snapshots = collect($saldo)->flatMap(fn (array $perJenis, string $pemilik) => collect(['Tunai', 'Bank', 'Panjar'])->map(fn (string $jenis) => [
            'pemilik' => $pemilik,
            'jenis' => $jenis,
            'tanggal_opname' => $tanggalOpname,
            'nominal' => $perJenis[$jenis],
            'created_at' => $waktu,
            'updated_at' => $waktu,
        ]))->all();

        if ($snapshots !== []) {
            DB::table('saldo_stok_opname')->upsert(
                $snapshots,
                ['pemilik', 'jenis', 'tanggal_opname'],
                ['nominal', 'updated_at'],
            );
        }

        return ['tanggal_opname' => $tanggalOpname, 'jumlah' => count($snapshots)];
    }

    private function saldoAwal(CarbonImmutable $bulan): array
    {
        $bulanSebelumnya = $bulan->subMonth();
        $opname = DB::table('saldo_stok_opname')
            ->whereBetween('tanggal_opname', [$bulanSebelumnya->startOfMonth()->toDateString(), $bulanSebelumnya->endOfMonth()->toDateString()])
            ->get(['pemilik', 'jenis', 'nominal', 'tanggal_opname'])
            ->groupBy('pemilik');
        $saldo = [];

        foreach ($opname as $pemilik => $items) {
            $tanggalTerakhir = $items->max('tanggal_opname');
            $saldo[$pemilik] = ['Tunai' => 0, 'Bank' => 0, 'Panjar' => 0];
            foreach ($items->where('tanggal_opname', $tanggalTerakhir) as $item) {
                $saldo[$pemilik][$item->jenis] = (float) $item->nominal;
            }
        }

        return $saldo;
    }
}
