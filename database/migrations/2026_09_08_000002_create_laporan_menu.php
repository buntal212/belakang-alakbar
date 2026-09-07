<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void {
        $menuId = DB::table('menus')->where('label', 'Laporan')->value('id');
        if (!$menuId) { $menuId = DB::table('menus')->insertGetId(['label' => 'Laporan', 'icon' => 'summarize', 'route' => '/laporan', 'type' => 'default', 'urut' => 99, 'created_at' => now(), 'updated_at' => now()]); }
        DB::table('submenus')->where('route', '/laporan/laporanpengeluaran')->update(['id_menus' => $menuId, 'updated_at' => now()]);
        $submenuId = DB::table('submenus')->where('route', '/laporan/laporanpengeluaran')->value('id');
        foreach (DB::table('user_submenu_accesses')->where('submenu_id', $submenuId)->pluck('user_id') as $userId) {
            DB::table('user_menu_accesses')->updateOrInsert(['user_id' => $userId, 'menu_id' => $menuId], ['created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void { }
};
