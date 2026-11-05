<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('menus')->where('label', 'Master')->first();
        if (!$menu) return;
        DB::table('submenus')->updateOrInsert(
            ['id_menus' => $menu->id, 'route' => '/master/jenisdiskon'],
            ['label' => 'Jenis Diskon', 'icon' => 'percent', 'urut' => 6, 'created_at' => now(), 'updated_at' => now()],
        );
        $submenuId = DB::table('submenus')->where('id_menus', $menu->id)->where('route', '/master/jenisdiskon')->value('id');
        foreach (DB::table('user_menu_accesses')->where('menu_id', $menu->id)->pluck('user_id') as $userId) {
            DB::table('user_submenu_accesses')->updateOrInsert(
                ['user_id' => $userId, 'submenu_id' => $submenuId],
                ['created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('submenus')->where('route', '/master/jenisdiskon')->pluck('id');
        DB::table('user_submenu_accesses')->whereIn('submenu_id', $ids)->delete();
        DB::table('submenus')->whereIn('id', $ids)->delete();
    }
};
