<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Cek kredensial
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email atau Password salah.'
                ], 401);
            }

            // Ambil data user yang berhasil login
            $user = User::where('email', $request->email)->firstOrFail();

            // Generate Token Sanctum
            // 'admin-token' adalah nama token (bisa apa saja)
            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil.',
                'data' => [
                    'user' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                    'token' => $token // Token ini yang akan disimpan oleh React
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Login gagal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat login.'
            ], 500);
        }
    }

    /**
     * Endpoint untuk Logout (Menghapus token saat ini).
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        try {
            // Hapus token yang sedang digunakan untuk request ini
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Logout berhasil. Token telah dihapus.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Logout gagal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat logout.'
            ], 500);
        }
    }

    /**
     * Endpoint untuk mengecek profil user yang sedang login (Cek Token Valid/Tidak).
     * GET /api/me
     */
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

}
