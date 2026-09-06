<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('counter', 'tagihanlspengeluaranyayasan')) {
            Schema::table('counter', function (Blueprint $table) {
                $table->unsignedInteger('tagihanlspengeluaranyayasan')
                    ->default(0)
                    ->after('tagihanpengeluaranyayasan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('counter', 'tagihanlspengeluaranyayasan')) {
            Schema::table('counter', function (Blueprint $table) {
                $table->dropColumn('tagihanlspengeluaranyayasan');
            });
        }
    }
};
