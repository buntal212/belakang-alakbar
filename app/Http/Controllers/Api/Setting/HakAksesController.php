<?php

namespace App\Http\Controllers\Api\Setting;

use App\Http\Controllers\Controller;
use App\Models\Master\Menus;
use App\Models\Master\UserMenuAccess;
use App\Models\Master\UserSubmenuAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HakAksesController extends Controller
{
    public function users(Request $request)
    {
        $this->ensureSuperAdmin($request);
        $query = User::query()
            ->with(['jabatanData:id,kode,jabatan', 'unitData:id,kode,nama_unit'])
            ->where(fn ($q) => $q->where('flaging', '<>', '1')->orWhereNull('flaging'))
            ->where('kode', '<>', 'X00X')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('kode', 'like', "%{$search}%"));
        }

        return response()->json($query->get());
    }

    public function navigation(Request $request)
    {
        $this->ensureSuperAdmin($request);
        return response()->json(Menus::with(['submenus' => fn ($q) => $q->orderBy('urut')])
            ->orderBy('urut')->get());
    }

    public function show(Request $request, User $user)
    {
        $this->ensureSuperAdmin($request);
        return response()->json([
            'menu_ids' => UserMenuAccess::where('user_id', $user->id)->pluck('menu_id'),
            'submenu_ids' => UserSubmenuAccess::where('user_id', $user->id)->pluck('submenu_id'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureSuperAdmin($request);
        if ($user->kode === 'X00X') {
            return response()->json(['message' => 'Hak akses super admin tidak dapat diubah.'], 422);
        }

        $data = $request->validate([
            'menu_ids' => ['present', 'array'],
            'menu_ids.*' => ['integer', 'exists:menus,id'],
            'submenu_ids' => ['present', 'array'],
            'submenu_ids.*' => ['integer', 'exists:submenus,id'],
        ]);

        $submenuMenuIds = DB::table('submenus')->whereIn('id', $data['submenu_ids'])
            ->pluck('id_menus')->map(fn ($id) => (int) $id)->all();
        $menuIds = array_values(array_unique(array_merge($data['menu_ids'], $submenuMenuIds)));

        DB::transaction(function () use ($user, $menuIds, $data) {
            UserMenuAccess::where('user_id', $user->id)->delete();
            UserSubmenuAccess::where('user_id', $user->id)->delete();

            UserMenuAccess::insert(array_map(fn ($menuId) => [
                'user_id' => $user->id, 'menu_id' => $menuId,
                'created_at' => now(), 'updated_at' => now(),
            ], $menuIds));
            UserSubmenuAccess::insert(array_map(fn ($submenuId) => [
                'user_id' => $user->id, 'submenu_id' => $submenuId,
                'created_at' => now(), 'updated_at' => now(),
            ], $data['submenu_ids']));
        });

        return response()->json(['message' => 'Hak akses berhasil disimpan.']);
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->kode === 'X00X', 403, 'Hanya super admin yang dapat mengatur hak akses.');
    }
}
