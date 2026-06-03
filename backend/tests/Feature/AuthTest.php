<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JWTService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user registration.
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'testuser@example.com',
            'nama_lengkap' => 'Test User Registration',
            'alamat' => 'Jl. Test No. 123',
            'no_telepon' => '081234567',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Registrasi berhasil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id', 'username', 'nama_lengkap', 'email', 'role', 'api_key'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'email' => 'testuser@example.com',
        ]);
    }

    /**
     * Test user login.
     */
    public function test_user_can_login()
    {
        $user = User::create([
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'nama_lengkap' => 'John Doe',
            'api_key' => 'test_api_key_123',
        ]);

        $response = $this->postJson('/api/login', [
            'username' => 'john_doe',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login berhasil',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id', 'username', 'nama_lengkap', 'email', 'role', 'api_key'
                    ]
                ]
            ]);
    }

    /**
     * Test check session with JWT Bearer token.
     */
    public function test_user_can_check_session_with_jwt()
    {
        $user = User::create([
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'nama_lengkap' => 'John Doe',
        ]);

        $token = JWTService::generateToken($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Session aktif',
            ])
            ->assertJsonPath('data.user.username', 'john_doe');
    }

    /**
     * Test check session with Basic Auth.
     */
    public function test_user_can_authenticate_with_basic_auth()
    {
        $user = User::create([
            'username' => 'john_doe',
            'password' => 'secret123', // auto-hashed
            'email' => 'john@example.com',
            'nama_lengkap' => 'John Doe',
        ]);

        $basicAuthHeader = 'Basic ' . base64_encode('john_doe:secret123');

        $response = $this->withHeaders([
            'Authorization' => $basicAuthHeader,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.username', 'john_doe');
    }

    /**
     * Test check session with API Key.
     */
    public function test_user_can_authenticate_with_api_key()
    {
        $user = User::create([
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'nama_lengkap' => 'John Doe',
            'api_key' => 'my_secret_api_key_999',
        ]);

        $response = $this->withHeaders([
            'X-API-Key' => 'my_secret_api_key_999',
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonPath('data.user.username', 'john_doe');
    }

    public function test_oauth_redirect_mock_endpoints()
    {
        $responseGoogle = $this->get('/api/auth/google/redirect');
        $this->assertTrue(in_array($responseGoogle->getStatusCode(), [200, 302]));

        $responseGithub = $this->get('/api/auth/github/redirect');
        $this->assertTrue(in_array($responseGithub->getStatusCode(), [200, 302]));
    }
}
