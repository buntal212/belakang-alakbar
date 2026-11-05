<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menuId = DB::table('menus')->where('label', 'Master')->value('id');
        if (!$menuId) return;
        DB::table('submenus')->where('id_menus', $menuId)->where('label', 'Siswa')->update(['route' => '/master/siswa', 'updated_at' => now()]);
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('label', 'Master')->value('id');
        if (!$menuId) return;
        DB::table('submenus')->where('id_menus', $menuId)->where('label', 'Siswa')->update(['route' => null, 'updated_at' => now()]);
    }
};
