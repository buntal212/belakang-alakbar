<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('menus')->where('label', 'Pengeluaran Yayasan')->first();

        if (!$menu) {
            return;
        }

        foreach ([
            ['label' => 'Tagihan LS', 'icon' => 'receipt_long', 'route' => '/pengeluaran_yayasan/tagihan_ls', 'urut' => 50],
            ['label' => 'Pembayaran LS', 'icon' => 'payments', 'route' => '/pengeluaran_yayasan/pembayaran_ls', 'urut' => 51],
        ] as $submenu) {
            DB::table('submenus')->updateOrInsert(
                ['id_menus' => $menu->id, 'route' => $submenu['route']],
                [...$submenu, 'updated_at' => now(), 'created_at' => now()],
            );
        }

        $submenuIds = DB::table('submenus')
            ->where('id_menus', $menu->id)
            ->whereIn('route', ['/pengeluaran_yayasan/tagihan_ls', '/pengeluaran_yayasan/pembayaran_ls'])
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
        $ids = DB::table('submenus')->whereIn('route', [
            '/pengeluaran_yayasan/tagihan_ls',
            '/pengeluaran_yayasan/pembayaran_ls',
        ])->pluck('id');

        DB::table('user_submenu_accesses')->whereIn('submenu_id', $ids)->delete();
        DB::table('submenus')->whereIn('id', $ids)->delete();
    }
};
