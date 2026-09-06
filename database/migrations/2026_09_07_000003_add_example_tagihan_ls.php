<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $nomor = '000001/TGLS-PK/1/' . date('Y');

        if (DB::table('tagihan_ls_h')->where('notagihan', $nomor)->exists()) {
            return;
        }

        $penyedia = DB::table('m_penyedia')->value('kode');
        $akun = DB::table('m_kodebelanja')->value('kode');
        $satuan = DB::table('m_satuan')->value('satuan') ?? 'Unit';

        DB::table('tagihan_ls_h')->insert([
            'notagihan' => $nomor,
            'tgl' => now()->toDateString(),
            'kegiatan' => 'Contoh transaksi Belanja Langsung',
            'penyedia' => $penyedia,
            'unit' => 'U001',
            'jabatan' => 'J000004',
            'jumlahbelanja' => 100000,
            'diskon' => 0,
            'pajak' => 0,
            'jumlahditagihkan' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tagihan_ls_r')->insert([
            'notagihan' => $nomor,
            'akun' => $akun,
            'rincian' => 'Contoh belanja LS',
            'qty' => 1,
            'satuan' => $satuan,
            'harga' => 100000,
            'jumlah' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('counter')->update([
            'tagihanlspengeluaranyayasan' => DB::raw('GREATEST(tagihanlspengeluaranyayasan, 1)'),
        ]);
    }

    public function down(): void
    {
        DB::table('tagihan_ls_r')->where('notagihan', 'like', '%/TGLS-PK/1/' . date('Y'))->delete();
        DB::table('tagihan_ls_h')->where('notagihan', 'like', '%/TGLS-PK/1/' . date('Y'))->delete();
    }
};
