<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('msiswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 30)->unique();
            $table->string('nama', 150);
            $table->string('angkatan', 20);
            $table->string('kelas', 20);
            $table->unsignedBigInteger('diskon')->nullable();
            $table->char('flag', 1)->default('0');
            $table->timestamps();
            $table->index('kelas');
            $table->index('diskon');
        });

        Schema::create('mpendapatansiswa', function (Blueprint $table) {
            $table->id();
            $table->string('nis', 30);
            $table->unsignedBigInteger('jenispendapatan');
            $table->timestamps();
            $table->unique(['nis', 'jenispendapatan']);
            $table->index('nis');
            $table->index('jenispendapatan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpendapatansiswa');
        Schema::dropIfExists('msiswa');
    }
};
