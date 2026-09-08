<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tagihan_ls_h', 'sumberdana')) {
            Schema::table('tagihan_ls_h', function (Blueprint $table) {
                $table->string('sumberdana')->nullable()->after('penyedia');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tagihan_ls_h', 'sumberdana')) {
            Schema::table('tagihan_ls_h', function (Blueprint $table) {
                $table->dropColumn('sumberdana');
            });
        }
    }
};
