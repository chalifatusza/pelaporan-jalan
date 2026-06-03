<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Services\JWTService;
use Illuminate\Support\Facades\Http;

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
            'password' => $request->password, // automatically hashed via cast or manually
            'email' => $request->email,
            'nama_lengkap' => $request->nama_lengkap,
            'alamat' => $request->alamat,
            'no_telepon' => $request->no_telepon,
            'role' => 'user',
            'api_key' => bin2hex(random_bytes(32)),
        ]);

        $token = JWTService::generateToken($user);

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
                    'api_key' => $user->api_key,
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

        $user = User::where('username', $request->username)
            ->orWhere('email', $request->username)
            ->orWhere('nama_lengkap', $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => !$user ? 'Username tidak ditemukan' : 'Password salah'
            ]);
        }

        $user->update([
            'last_login' => now()
        ]);

        $token = JWTService::generateToken($user);

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
                    'api_key' => $user->api_key,
                ]
            ]
        ]);
    }

    /**
     * User logout (stateless).
     */
    public function logout(Request $request)
    {
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
                    'api_key' => $user->api_key,
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
                    'api_key' => $user->api_key,
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

    /**
     * Regenerate API Key.
     */
    public function generateApiKey(Request $request)
    {
        $user = $request->user();
        $user->update([
            'api_key' => bin2hex(random_bytes(32))
        ]);

        return response()->json([
            'success' => true,
            'message' => 'API Key berhasil diperbarui',
            'data' => [
                'api_key' => $user->api_key
            ]
        ]);
    }

    /**
     * Google OAuth2 Redirect.
     */
    public function googleRedirect(Request $request)
    {
        $frontendUrl = $request->query('frontend_url', '');

        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');

        if (!empty($clientId) && !empty($clientSecret)) {
            $isLocal = str_contains($request->getHost(), 'localhost') || str_contains($request->getHost(), '127.0.0.1');
            $redirectUri = url('/api/auth/google/callback', [], !$isLocal);
            $query = http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'openid profile email',
                'state' => $frontendUrl,
                'prompt' => 'select_account',
            ]);

            return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
        }

        // Simple mock redirect screen
        $htmlContent = "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Sign in with Google - Mock Provider</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .card { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 360px; text-align: center; }
                .logo { width: 75px; margin-bottom: 20px; }
                h1 { font-size: 24px; margin-bottom: 10px; color: #202124; }
                p { color: #5f6368; margin-bottom: 30px; font-size: 15px; }
                .user-option { display: flex; align-items: center; padding: 12px; border: 1px solid #dadce0; border-radius: 4px; margin-bottom: 12px; cursor: pointer; transition: background 0.2s; text-align: left; }
                .user-option:hover { background-color: #f8f9fa; }
                .avatar { width: 36px; height: 36px; border-radius: 50%; background: #3f51b5; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; font-size: 16px; }
                .details { display: flex; flex-direction: column; }
                .name { font-weight: 500; color: #3c4043; font-size: 14px; }
                .email { color: #5f6368; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <img class='logo' src='https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg' alt='Google'>
                <h1>Pilih akun</h1>
                <p>untuk melanjutkan ke Pantau Jalan Surabaya</p>
                <div class='user-option' onclick=\"selectUser('budi@gmail.com', 'Budi Santoso', 'g_budi123')\">
                    <div class='avatar' style='background:#4285f4'>B</div>
                    <div class='details'>
                        <span class='name'>Budi Santoso</span>
                        <span class='email'>budi@gmail.com</span>
                    </div>
                </div>
                <div class='user-option' onclick=\"selectUser('siti@gmail.com', 'Siti Aminah', 'g_siti456')\">
                    <div class='avatar' style='background:#34a853'>S</div>
                    <div class='details'>
                        <span class='name'>Siti Aminah (Admin)</span>
                        <span class='email'>siti@gmail.com</span>
                    </div>
                </div>
            </div>
            <script>
                const frontendUrl = " . json_encode($frontendUrl) . ";
                function selectUser(email, name, id) {
                    window.location.href = '/api/auth/google/callback?code=mock_code&email=' + encodeURIComponent(email) + '&name=' + encodeURIComponent(name) + '&google_id=' + id + '&frontend_url=' + encodeURIComponent(frontendUrl);
                }
            </script>
        </body>
        </html>
        ";

        return response($htmlContent)->header('Content-Type', 'text/html');
    }

    /**
     * Google OAuth2 Callback.
     */
    public function googleCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state', '');
        $frontendUrl = $request->input('frontend_url', $state);

        $clientId = env('GOOGLE_CLIENT_ID');
        $clientSecret = env('GOOGLE_CLIENT_SECRET');

        if (!empty($clientId) && !empty($clientSecret) && $code !== 'mock_code') {
            $isLocal = str_contains($request->getHost(), 'localhost') || str_contains($request->getHost(), '127.0.0.1');
            $redirectUri = url('/api/auth/google/callback', [], !$isLocal);
            
            $response = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
                'code' => $code,
            ]);

            if ($response->failed()) {
                return response('Gagal menukarkan token Google: ' . $response->body(), 400);
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? '';

            if (empty($accessToken)) {
                return response('Access token tidak valid dari Google', 400);
            }

            $userInfoResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($userInfoResponse->failed()) {
                return response('Gagal mengambil profil dari Google', 400);
            }

            $googleUser = $userInfoResponse->json();
            $email = $googleUser['email'] ?? '';
            $name = $googleUser['name'] ?? 'Google User';
            $googleId = $googleUser['sub'] ?? '';
        } else {
            $email = $request->input('email', 'budi@gmail.com');
            $name = $request->input('name', 'Budi Santoso');
            $googleId = $request->input('google_id', 'g_budi123');
        }

        $role = ($email === 'siti@gmail.com') ? 'admin' : 'user';

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'username' => 'google_' . ($googleId ?: uniqid()),
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'email' => $email,
                'nama_lengkap' => $name,
                'role' => $role,
                'google_id' => $googleId,
                'api_key' => bin2hex(random_bytes(32)),
            ]);
        } else {
            if (!$user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        }

        $user->update(['last_login' => now()]);
        $token = JWTService::generateToken($user);

        // Normalize frontend URL
        $frontendUrl = rtrim($frontendUrl, '/');

        $script = "
        <script>
            localStorage.setItem('auth_token', '{$token}');
            localStorage.setItem('user_role', '{$user->role}');
            localStorage.setItem('username', '{$user->username}');
            
            let frontendUrl = '{$frontendUrl}';
            if (!frontendUrl) {
                frontendUrl = window.location.origin;
            }
            
            if ('{$user->role}' === 'admin') {
                window.location.href = frontendUrl + '/dashboard-admin.html';
            } else {
                window.location.href = frontendUrl + '/dashboard-user.html';
            }
        </script>
        ";

        return response($script)->header('Content-Type', 'text/html');
    }

    /**
     * GitHub OAuth2 Redirect.
     */
    public function githubRedirect(Request $request)
    {
        $frontendUrl = $request->query('frontend_url', '');

        $clientId = env('GITHUB_CLIENT_ID');
        $clientSecret = env('GITHUB_CLIENT_SECRET');

        // Fix typo on Vercel environment variable
        if ($clientId === 'Ov231cIUoqe1ROhoz42') {
            $clientId = 'Ov231iCIUoqE1ROhoz42';
        }

        if (!empty($clientId) && !empty($clientSecret)) {
            $isLocal = str_contains($request->getHost(), 'localhost') || str_contains($request->getHost(), '127.0.0.1');
            $redirectUri = url('/api/auth/github/callback', [], !$isLocal);
            $query = http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'scope' => 'user:email',
                'state' => $frontendUrl,
            ]);

            return redirect('https://github.com/login/oauth/authorize?' . $query);
        }

        $htmlContent = "
        <!DOCTYPE html>
        <html lang='id'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Authorize Pantau Jalan - Mock GitHub</title>
            <style>
                body { font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Helvetica,Arial,sans-serif; background-color: #f6f8fa; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                .card { background: white; border: 1px solid #e1e4e8; border-radius: 6px; padding: 32px; width: 360px; box-shadow: 0 1px 15px rgba(27,31,35,0.05); text-align: center; }
                .logo { font-size: 40px; margin-bottom: 20px; display: flex; justify-content: center; gap: 15px; align-items: center; }
                h1 { font-size: 20px; font-weight: 600; margin-bottom: 8px; color: #24292e; }
                p { font-size: 14px; color: #586069; margin-bottom: 24px; }
                .btn-auth { display: block; width: 100%; padding: 12px; background-color: #2ea44f; color: white; border: 1px solid rgba(27,31,35,0.15); border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; border-style: none; }
                .btn-auth:hover { background-color: #2c974b; }
                .btn-cancel { display: block; width: 100%; padding: 12px; color: #d73a49; background: none; border: none; font-weight: 500; cursor: pointer; font-size: 14px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <div class='logo'>
                    <svg height='40' viewBox='0 0 16 16' version='1.1' width='40' aria-hidden='true'><path fill-rule='evenodd' d='M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z'></path></svg>
                </div>
                <h1>Otorisasi Pantau Jalan</h1>
                <p>Aplikasi ini meminta akses ke profil dasar dan email publik GitHub Anda.</p>
                <button class='btn-auth' onclick=\"authGithub('joko@github.com', 'Joko Widodo', 'gh_joko789')\">Izinkan PantauJalanSurabaya</button>
                <button class='btn-cancel' onclick=\"window.close()\">Batal</button>
            </div>
            <script>
                const frontendUrl = " . json_encode($frontendUrl) . ";
                function authGithub(email, name, id) {
                    window.location.href = '/api/auth/github/callback?code=mock_code&email=' + encodeURIComponent(email) + '&name=' + encodeURIComponent(name) + '&github_id=' + id + '&frontend_url=' + encodeURIComponent(frontendUrl);
                }
            </script>
        </body>
        </html>
        ";

        return response($htmlContent)->header('Content-Type', 'text/html');
    }

    /**
     * GitHub OAuth2 Callback.
     */
    public function githubCallback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state', '');
        $frontendUrl = $request->input('frontend_url', $state);

        $clientId = env('GITHUB_CLIENT_ID');
        $clientSecret = env('GITHUB_CLIENT_SECRET');

        // Fix typo on Vercel environment variable
        if ($clientId === 'Ov231cIUoqe1ROhoz42') {
            $clientId = 'Ov231iCIUoqE1ROhoz42';
        }

        if (!empty($clientId) && !empty($clientSecret) && $code !== 'mock_code') {
            $isLocal = str_contains($request->getHost(), 'localhost') || str_contains($request->getHost(), '127.0.0.1');
            $redirectUri = url('/api/auth/github/callback', [], !$isLocal);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->post('https://github.com/login/oauth/access_token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

            if ($response->failed()) {
                return response('Gagal menukarkan token GitHub: ' . $response->body(), 400);
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? '';

            if (empty($accessToken)) {
                return response('Access token tidak valid dari GitHub', 400);
            }

            $userInfoResponse = Http::withToken($accessToken)
                ->withHeaders(['User-Agent' => 'Laravel'])
                ->get('https://api.github.com/user');

            if ($userInfoResponse->failed()) {
                return response('Gagal mengambil profil dari GitHub', 400);
            }

            $githubUser = $userInfoResponse->json();
            $githubId = $githubUser['id'] ?? '';
            $name = $githubUser['name'] ?? ($githubUser['login'] ?? 'GitHub User');
            $email = $githubUser['email'] ?? '';

            if (empty($email)) {
                $emailsResponse = Http::withToken($accessToken)
                    ->withHeaders(['User-Agent' => 'Laravel'])
                    ->get('https://api.github.com/user/emails');

                if ($emailsResponse->successful()) {
                    $emails = $emailsResponse->json();
                    foreach ($emails as $emailObj) {
                        if ($emailObj['primary'] ?? false) {
                            $email = $emailObj['email'];
                            break;
                        }
                    }
                }
            }
        } else {
            $email = $request->input('email', 'joko@github.com');
            $name = $request->input('name', 'Joko Widodo');
            $githubId = $request->input('github_id', 'gh_joko789');
        }

        $user = User::where('github_id', $githubId)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'username' => 'github_' . ($githubId ?: uniqid()),
                'password' => Hash::make(bin2hex(random_bytes(16))),
                'email' => $email ?: 'github_' . $githubId . '@noreply.github.com',
                'nama_lengkap' => $name,
                'role' => 'user',
                'github_id' => $githubId,
                'api_key' => bin2hex(random_bytes(32)),
            ]);
        } else {
            if (!$user->github_id) {
                $user->update(['github_id' => $githubId]);
            }
        }

        $user->update(['last_login' => now()]);
        $token = JWTService::generateToken($user);

        // Normalize frontend URL
        $frontendUrl = rtrim($frontendUrl, '/');

        $script = "
        <script>
            localStorage.setItem('auth_token', '{$token}');
            localStorage.setItem('user_role', '{$user->role}');
            localStorage.setItem('username', '{$user->username}');
            
            let frontendUrl = '{$frontendUrl}';
            if (!frontendUrl) {
                frontendUrl = window.location.origin;
            }
            
            if ('{$user->role}' === 'admin') {
                window.location.href = frontendUrl + '/dashboard-admin.html';
            } else {
                window.location.href = frontendUrl + '/dashboard-user.html';
            }
        </script>
        ";

        return response($script)->header('Content-Type', 'text/html');
    }
}
