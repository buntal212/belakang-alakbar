<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('m_penyedia', 'alamat')) {
            Schema::table('m_penyedia', function (Blueprint $table) {
                $table->text('alamat')->nullable()->after('nama');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('m_penyedia', 'alamat')) {
            Schema::table('m_penyedia', function (Blueprint $table) {
                $table->dropColumn('alamat');
            });
        }
    }
};
