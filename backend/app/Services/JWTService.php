<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Exception;

class JWTService
{
    /**
     * Get JWT secret key.
     */
    private static function getSecretKey(): string
    {
        return env('JWT_SECRET') ?: (env('APP_KEY') ?: 'secret_key_12345678901234567890');
    }

    /**
     * Generate JWT for a user.
     */
    public static function generateToken(User $user): string
    {
        $payload = [
            'iss' => env('APP_URL', 'http://localhost:8000'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + (60 * 60 * 24 * 7), // 7 days expiration
        ];

        return JWT::encode($payload, self::getSecretKey(), 'HS256');
    }

    /**
     * Decode JWT token.
     */
    public static function decodeToken(string $token): ?array
    {
        try {
            // Remove Bearer prefix if present
            if (preg_match('/Bearer\s(\S+)/', $token, $matches)) {
                $token = $matches[1];
            }

            $decoded = JWT::decode($token, new Key(self::getSecretKey(), 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
}
