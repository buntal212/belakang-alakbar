<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Master\Menus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. VALIDASI INPUT
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // 2. CARI USER
        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // 3. CEK PASSWORD (WAJIB BCRYPT)
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Password salah'
            ], 401);
        }

        // 4. CEK KODE USER & AMBIL MENU
        if ($user->kode === 'X00X') {

            // ADMIN / SUPER USER
            $menus = Menus::with([
                'submenus' => function($q){
                    $q->orderBy('urut');
                }
            ])->orderBy('urut')->get();

        } else {

            // USER BIASA
            $menus = Menus::where('type', 'default')
                ->with('submenus')
                ->get();
        }

        // 5. GENERATE TOKEN SANCTUM
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. RESPONSE
        return response()->json([
            'user' => $user,
            'menus' => $menus,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }
}
