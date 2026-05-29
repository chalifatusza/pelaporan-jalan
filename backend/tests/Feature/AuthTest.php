<?php

namespace Tests\Feature;

use App\Models\User;
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
                        'id', 'username', 'nama_lengkap', 'email', 'role'
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
                        'id', 'username', 'nama_lengkap', 'email', 'role'
                    ]
                ]
            ]);
    }

    /**
     * Test check session.
     */
    public function test_user_can_check_session()
    {
        $user = User::create([
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'nama_lengkap' => 'John Doe',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

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
     * Test profile update.
     */
    public function test_user_can_update_profile()
    {
        $user = User::create([
            'username' => 'john_doe',
            'password' => 'secret123',
            'email' => 'john@example.com',
            'nama_lengkap' => 'John Doe',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/user', [
            'nama_lengkap' => 'John Doe Updated',
            'email' => 'john.updated@example.com',
            'alamat' => 'New Address 456',
            'no_telepon' => '089999999',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'nama_lengkap' => 'John Doe Updated',
            'email' => 'john.updated@example.com',
        ]);
    }
}
