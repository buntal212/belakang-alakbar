<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_menu_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Tabel navigasi lama memakai INT signed, bukan BIGINT unsigned.
            $table->integer('menu_id');
            $table->timestamps();
            $table->unique(['user_id', 'menu_id']);
            $table->foreign('menu_id')->references('id')->on('menus')->cascadeOnDelete();
        });

        Schema::create('user_submenu_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Tabel navigasi lama memakai INT signed, bukan BIGINT unsigned.
            $table->integer('submenu_id');
            $table->timestamps();
            $table->unique(['user_id', 'submenu_id']);
            $table->foreign('submenu_id')->references('id')->on('submenus')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_submenu_accesses');
        Schema::dropIfExists('user_menu_accesses');
    }
};
