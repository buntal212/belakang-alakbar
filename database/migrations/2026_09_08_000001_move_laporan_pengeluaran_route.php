<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        DB::table('submenus')
            ->where('route', '/pengeluaran_yayasan/laporan_pengeluaran')
            ->update(['route' => '/laporan/laporanpengeluaran', 'updated_at' => now()]);
    }

    public function down(): void {
        DB::table('submenus')
            ->where('route', '/laporan/laporanpengeluaran')
            ->update(['route' => '/pengeluaran_yayasan/laporan_pengeluaran', 'updated_at' => now()]);
    }
};
