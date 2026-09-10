<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('counter', 'pembayaranlspengeluaranyayasan')) {
            return;
        }

        Schema::table('counter', function (Blueprint $table) {
            $table->unsignedInteger('pembayaranlspengeluaranyayasan')
                ->default(0)
                ->after('pembayaranpengeluaranyayasan');
        });

        // Ambil urutan tertinggi dari nomor PBLS lama dan counter Yayasan.
        // Dengan begitu, jika counter Yayasan saat ini 82, pembayaran LS
        // pertama setelah pemisahan akan menjadi 000083/PBLS-PK/1/YYYY.
        $nomorPbLsTerakhir = DB::table('pembayaran_ls')
            ->where('nopembayaran', 'like', '%/PBLS-PK/%')
            ->max(DB::raw('CAST(SUBSTRING(nopembayaran, 1, 6) AS UNSIGNED)')) ?? 0;

        $nomorYayasanTerakhir = DB::table('counter')
            ->max('pembayaranpengeluaranyayasan') ?? 0;

        $nomorTerakhir = max($nomorPbLsTerakhir, $nomorYayasanTerakhir);

        DB::table('counter')->update([
            'pembayaranlspengeluaranyayasan' => $nomorTerakhir,
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('counter', 'pembayaranlspengeluaranyayasan')) {
            Schema::table('counter', function (Blueprint $table) {
                $table->dropColumn('pembayaranlspengeluaranyayasan');
            });
        }
    }
};
