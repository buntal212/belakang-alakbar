<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_stok_opname', function (Blueprint $table) {
            $table->id();
            $table->decimal('nominal', 12, 2)->default(0);
            $table->string('pemilik');
            $table->string('jenis');
            $table->date('tanggal_opname');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_stok_opname');
    }
};
