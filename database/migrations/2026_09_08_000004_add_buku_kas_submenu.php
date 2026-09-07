<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $menu = DB::table('menus')->where('label', 'Laporan')->first();
        if (!$menu) return;
        DB::table('submenus')->updateOrInsert(
            ['id_menus' => $menu->id, 'route' => '/laporan/bukukas'],
            ['label' => 'Buku Kas Pengeluaran', 'icon' => 'menu_book', 'urut' => 3, 'created_at' => now(), 'updated_at' => now()]
        );
        $submenuId = DB::table('submenus')->where('route', '/laporan/bukukas')->value('id');
        foreach (DB::table('user_menu_accesses')->where('menu_id', $menu->id)->pluck('user_id') as $userId) {
            DB::table('user_submenu_accesses')->updateOrInsert(['user_id' => $userId, 'submenu_id' => $submenuId], ['created_at' => now(), 'updated_at' => now()]);
        }
    }
    public function down(): void {}
};
