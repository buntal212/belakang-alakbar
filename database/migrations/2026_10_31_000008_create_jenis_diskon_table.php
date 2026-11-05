<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jenis_diskon', function (Blueprint $table) {
            $table->id();
            $table->string('jenisdiskon', 100);
            $table->decimal('persentase_diskon', 5, 2)->default(0);
            $table->char('flag', 1)->default('0');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_diskon');
    }
};
