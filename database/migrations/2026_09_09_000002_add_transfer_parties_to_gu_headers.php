<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gu_h', function (Blueprint $table) {
            $table->string('dari')->nullable()->after('jabatan');
        });
    }

    public function down(): void
    {
        Schema::table('gu_h', function (Blueprint $table) {
            $table->dropColumn('dari');
        });
    }
};
