<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Preserve sen values for Tagihan LS and its payments.
     */
    public function up(): void
    {
        Schema::table('tagihan_ls_h', function (Blueprint $table) {
            $table->decimal('jumlahbelanja', 12, 2)->default(0)->change();
            $table->decimal('diskon', 12, 2)->default(0)->change();
            $table->decimal('pajak', 12, 2)->default(0)->change();
            $table->decimal('jumlahditagihkan', 12, 2)->default(0)->change();
        });

        Schema::table('tagihan_ls_r', function (Blueprint $table) {
            $table->decimal('harga', 12, 2)->nullable()->change();
            $table->decimal('jumlah', 12, 2)->nullable()->change();
        });

        Schema::table('pembayaran_ls', function (Blueprint $table) {
            $table->decimal('sisapembayaran', 12, 2)->default(0)->change();
            $table->decimal('nominal', 12, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('tagihan_ls_h', function (Blueprint $table) {
            $table->decimal('jumlahbelanja', 12, 0)->default(0)->change();
            $table->decimal('diskon', 12, 0)->default(0)->change();
            $table->decimal('pajak', 12, 0)->default(0)->change();
            $table->decimal('jumlahditagihkan', 12, 0)->default(0)->change();
        });

        Schema::table('tagihan_ls_r', function (Blueprint $table) {
            $table->decimal('harga', 12, 0)->nullable()->change();
            $table->decimal('jumlah', 12, 0)->nullable()->change();
        });

        Schema::table('pembayaran_ls', function (Blueprint $table) {
            $table->decimal('sisapembayaran', 12, 0)->default(0)->change();
            $table->decimal('nominal', 12, 0)->default(0)->change();
        });
    }
};
