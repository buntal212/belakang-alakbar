<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saldo_stok_opname', function (Blueprint $table) {
            $table->unique(['pemilik', 'jenis', 'tanggal_opname'], 'saldo_stok_opname_unique_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('saldo_stok_opname', function (Blueprint $table) {
            $table->dropUnique('saldo_stok_opname_unique_snapshot');
        });
    }
};
