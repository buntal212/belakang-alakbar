<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menu = DB::table('menus')->where('label', 'Pengaturan')->first();

        if (!$menu) {
            return;
        }

        DB::table('submenus')->updateOrInsert(
            [
                'id_menus' => $menu->id,
                'route' => '/setting/konek-wa',
            ],
            [
                'label' => 'Konek WA',
                'icon' => 'chat',
                'urut' => 2,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $menu = DB::table('menus')->where('label', 'Pengaturan')->first();

        if ($menu) {
            DB::table('submenus')
                ->where('id_menus', $menu->id)
                ->where('route', '/setting/konek-wa')
                ->delete();
        }
    }
};
