<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saldo_stok_opname', function (Blueprint $table) {
            $table->decimal('nominal', 18, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('saldo_stok_opname', function (Blueprint $table) {
            $table->decimal('nominal', 12, 2)->default(0)->change();
        });
    }
};
