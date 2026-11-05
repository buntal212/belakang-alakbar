<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_pendapatan', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_pendapatan', 100);
            $table->char('flag', 1)->default('0');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_pendapatan');
    }
};
