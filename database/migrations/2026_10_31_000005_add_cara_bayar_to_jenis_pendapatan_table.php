<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jenis_pendapatan', function (Blueprint $table) {
            $table->enum('cara_bayar', ['bulanan', '1x bayar', 'cicilan'])
                ->default('bulanan')
                ->after('jenis_pendapatan');
        });
    }

    public function down(): void
    {
        Schema::table('jenis_pendapatan', function (Blueprint $table) {
            $table->dropColumn('cara_bayar');
        });
    }
};
