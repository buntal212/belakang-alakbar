<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('menus')->where('label', 'Master')->first();

        if (!$menu) {
            return;
        }

        foreach ([
            ['label' => 'Kelas', 'icon' => 'meeting_room', 'urut' => 2],
            ['label' => 'Siswa', 'icon' => 'groups', 'urut' => 3],
        ] as $submenu) {
            DB::table('submenus')->updateOrInsert(
                ['id_menus' => $menu->id, 'label' => $submenu['label']],
                [...$submenu, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        $submenuIds = DB::table('submenus')
            ->where('id_menus', $menu->id)
            ->whereIn('label', ['Kelas', 'Siswa'])
            ->pluck('id');
        $userIds = DB::table('user_menu_accesses')->where('menu_id', $menu->id)->pluck('user_id');

        foreach ($userIds as $userId) {
            foreach ($submenuIds as $submenuId) {
                DB::table('user_submenu_accesses')->updateOrInsert(
                    ['user_id' => $userId, 'submenu_id' => $submenuId],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('label', 'Master')->value('id');

        if (!$menuId) {
            return;
        }

        $submenuIds = DB::table('submenus')
            ->where('id_menus', $menuId)
            ->whereIn('label', ['Kelas', 'Siswa'])
            ->pluck('id');

        DB::table('user_submenu_accesses')->whereIn('submenu_id', $submenuIds)->delete();
        DB::table('submenus')->whereIn('id', $submenuIds)->delete();
    }
};
