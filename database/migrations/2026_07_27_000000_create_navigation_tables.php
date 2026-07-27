<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('menus')) {
            Schema::create('menus', function (Blueprint $table) {
                $table->id();
                $table->string('label');
                $table->string('icon')->nullable();
                $table->string('route')->nullable();
                $table->string('type')->default('default')->index();
                $table->unsignedInteger('urut')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('submenus')) {
            Schema::create('submenus', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_menus')->constrained('menus')->cascadeOnDelete();
                $table->string('label');
                $table->string('icon')->nullable();
                $table->string('route')->nullable();
                $table->unsignedInteger('urut')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus tabel karena migrasi ini juga aman dijalankan
        // terhadap database lama yang tabel navigasinya sudah tersedia.
    }
};
