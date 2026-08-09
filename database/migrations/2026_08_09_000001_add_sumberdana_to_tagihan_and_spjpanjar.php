<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tagihan_h', 'spjpanjar_h'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'sumberdana')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('sumberdana')->nullable()->after('penyedia');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['tagihan_h', 'spjpanjar_h'] as $tableName) {
            if (Schema::hasColumn($tableName, 'sumberdana')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropColumn('sumberdana');
                });
            }
        }
    }
};
