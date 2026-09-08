<?php

use App\Services\SaldoStokOpnameSnapshotService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('saldo:stok-opname {--bulan=}', function () {
    $hasil = app(SaldoStokOpnameSnapshotService::class)->snapshot($this->option('bulan'));

    $this->info(sprintf('Snapshot saldo %s berhasil disimpan (%d data).', $hasil['tanggal_opname'], $hasil['jumlah']));
})->purpose('Simpan snapshot saldo Tunai, Bank, dan Panjar untuk bulan yang telah selesai');
