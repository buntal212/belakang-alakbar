<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('gu_h', 'tujuan')) {
            Schema::table('gu_h', function (Blueprint $table) {
                $table->dropColumn('tujuan');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('gu_h', 'tujuan')) {
            Schema::table('gu_h', function (Blueprint $table) {
                $table->string('tujuan')->nullable()->after('dari');
            });
        }
    }
};
