<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('pengajuan_up', 'alasan')) {
            Schema::table('pengajuan_up', function (Blueprint $table) {
                $table->text('alasan')->nullable()->after('nilai_pengajuan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pengajuan_up', 'alasan')) {
            Schema::table('pengajuan_up', function (Blueprint $table) {
                $table->dropColumn('alasan');
            });
        }
    }
};
