<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Services\JWTService;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Check Bearer Token (JWT)
        $authHeader = $request->header('Authorization')
            ?: $request->header('X-Authorization')
            ?: ($request->header('X-Auth-Token') ? 'Bearer ' . $request->header('X-Auth-Token') : null)
            ?: ($request->query('token') ? 'Bearer ' . $request->query('token') : null);

        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $jwt = $matches[1];
            $decoded = JWTService::decodeToken($jwt);
            if ($decoded && isset($decoded['sub'])) {
                $user = User::find($decoded['sub']);
                if ($user) {
                    Auth::login($user);
                    return $next($request);
                }
            }
        }

        // 2. Check Basic Auth
        if ($authHeader && preg_match('/Basic\s(\S+)/', $authHeader, $matches)) {
            $credentials = base64_decode($matches[1]);
            $parts = explode(':', $credentials, 2);
            if (count($parts) === 2) {
                $username = $parts[0];
                $password = $parts[1];

                // Attempt to find user by email or username
                $user = User::where('username', $username)
                    ->orWhere('email', $username)
                    ->first();

                if ($user && Hash::check($password, $user->password)) {
                    Auth::login($user);
                    return $next($request);
                }
            }
        }

        // 3. Check API Key
        $apiKey = $request->header('X-API-Key') ?: $request->query('api_key');
        if ($apiKey) {
            $user = User::where('api_key', $apiKey)->first();
            if ($user) {
                Auth::login($user);
                return $next($request);
            }
        }

        // If none authenticated, return 401
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized'
        ], 401);
    }
}
