<?php

namespace App\Http\Controllers\Api\Master;

use App\Helpers\Formating\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\JsonResponse;

class UsersController extends Controller
{
    public function index()
    {
        $query = User::where(function ($q) {
            $q->where('flaging', '<>', '1')
            ->orWhereNull('flaging');
        })->where('kode','<>','X00X')->orderBy('kode');

        if (request('search')) {
            $search = request('search');

            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%$search%")
                ->orWhere('name', 'like', "%$search%");
            });
        }

        $data = $query->simplePaginate(request('per_page', 10));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $kode = $request->kode ?? null;

        $validated = $request->validate([
            'username' => [
            'required',
                Rule::unique('users', 'username')->ignore($kode, 'kode') // 🔥 penting untuk edit
            ],
            'name'     => 'required',
            'email'    => 'required|email',
            'pass'     => 'required',
            'jabatan'  => 'required',
            'unit'     => 'required',
        ], [
            'username.required' => 'Username harus diisi',
            'username.unique'   => 'Username sudah digunakan',
            'name.required'     => 'Nama harus diisi',
            'email.required'    => 'Email harus diisi',
            'email.email'       => 'Format email tidak valid',
            'pass.required'     => 'Password harus diisi',
            'jabatan.required'  => 'Jabatan harus dipilih',
            'unit.required'     => 'Unit harus dipilih',
        ]);

        try {
            DB::beginTransaction();

            if (!$kode) {
                DB::select('call masteruser(@nomor)'); // 🔥 ganti sesuai kebutuhan
                $nomor = DB::table('counter')->select('masteruser')->first();
                $kode = FormatingHelper::genKodeMaster($nomor->masteruser, 'U');
            }

            $data = User::updateOrCreate(
                [
                    'kode' => $kode
                ],
                [
                    'username' => $validated['username'],
                    'name'     => $validated['name'],
                    'email'    => $validated['email'],
                    'password' => bcrypt($validated['pass']), // 🔥 penting!
                    'pass'     => $validated['pass'],
                    'jabatan'  => $validated['jabatan'],
                    'unit'     => $validated['unit'],
                ]
            );

            DB::commit();

            return new JsonResponse([
                'data' => $data,
                'message' => 'Data berhasil disimpan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return new JsonResponse([
                'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        $id = $request->id ?? null;
        $validated = $request->validate([
            'id' => 'required',
        ], [

            'id.required' => 'Data Tidak Bisa Dihapus,karena Tidak mempunyai ID!!!',
        ]);

        try {
            DB::beginTransaction();
                $update = User::find($id);

                if ($update) {
                    $update->flaging = '1';
                    $update->save();
                }
            DB::commit();
                return new JsonResponse([
                    'data' => $update,
                    'status' => 'OK',
                    'message' => 'Data berhasil dihapus'
                ]);

        }catch (\Exception $e) {
            DB::rollBack();
                return new JsonResponse([
                    'message' => 'Gagal menyimpan data: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),

                ], 410);
        }
    }
}
