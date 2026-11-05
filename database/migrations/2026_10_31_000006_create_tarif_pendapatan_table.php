<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_pendapatan', function (Blueprint $table) {
            $table->id();
            $table->string('kode_tarif', 20)->unique();
            $table->string('kelas', 20);
            $table->unsignedBigInteger('jenispendapatan');
            $table->decimal('nilai', 15, 2)->default(0);
            $table->char('flag', 1)->default('0');
            $table->timestamps();

            $table->index('kelas');
            $table->index('jenispendapatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_pendapatan');
    }
};
