<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * User registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:50|unique:users',
            'password' => 'required|string|min:6',
            'email' => 'required|string|email|max:100|unique:users',
            'nama_lengkap' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $user = User::create([
            'username' => $request->username,
            'password' => $request->password, // automatically hashed via cast
            'email' => $request->email,
            'nama_lengkap' => $request->nama_lengkap,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'role' => 'user',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'nama' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'alamat' => $user->alamat,
                    'no_telepon' => $user->no_telepon,
                ]
            ]
        ]);
    }

    /**
     * User login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Username dan password harus diisi'
            ]);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => !$user ? 'Username tidak ditemukan' : 'Password salah'
            ]);
        }

        $user->update([
            'last_login' => now()
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'nama' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'alamat' => $user->alamat,
                    'no_telepon' => $user->no_telepon,
                ]
            ]
        ]);
    }

    /**
     * User logout.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }

    /**
     * Check active session / get authenticated user.
     */
    public function session(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Session aktif',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'nama' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'alamat' => $user->alamat,
                    'no_telepon' => $user->no_telepon,
                ]
            ]
        ]);
    }

    /**
     * Get user profile.
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Data profil berhasil diambil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'nama_lengkap' => $user->nama_lengkap,
                    'nama' => $user->nama_lengkap,
                    'email' => $user->email,
                    'role' => $user->role,
                    'alamat' => $user->alamat,
                    'no_telepon' => $user->no_telepon,
                ]
            ]
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nama_lengkap' => 'required|string|max:100',
            'email' => [
                'required',
                'string',
                'email',
                'max:100',
                Rule::unique('users')->ignore($user->id),
            ],
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:15',
            'password' => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $data = [
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui'
        ]);
    }
}
