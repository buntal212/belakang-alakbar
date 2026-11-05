<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('m_kelas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('kelas', 100);
            $table->string('unit', 20);
            $table->char('flag', 1)->default('0');
            $table->timestamps();

            $table->index('unit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('m_kelas');
    }
};
