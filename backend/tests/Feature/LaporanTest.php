<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Laporan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $admin;
    private $userToken;
    private $adminToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Create user
        $this->user = User::create([
            'username' => 'testuser',
            'password' => 'password123',
            'email' => 'user@example.com',
            'nama_lengkap' => 'Regular User',
            'role' => 'user',
        ]);
        $this->userToken = $this->user->createToken('user_token')->plainTextToken;

        // Create admin
        $this->admin = User::create([
            'username' => 'testadmin',
            'password' => 'password123',
            'email' => 'admin@example.com',
            'nama_lengkap' => 'Admin User',
            'role' => 'admin',
        ]);
        $this->adminToken = $this->admin->createToken('admin_token')->plainTextToken;
    }

    /**
     * Test report submission.
     */
    public function test_user_can_submit_report()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->postJson('/api/laporan', [
            'judul_laporan' => 'Jalan Rusak Parah',
            'lokasi_jalan' => 'Jl. Mulyorejo No. 1',
            'kecamatan' => 'Mulyorejo',
            'deskripsi_kerusakan' => 'Ada lubang jalan berukuran besar dan dalam.',
            'tingkat_kerusakan' => 'berat',
            'latitude' => -7.2650,
            'longitude' => 112.7800,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Laporan berhasil dikirim',
            ]);

        $this->assertDatabaseHas('laporan', [
            'judul_laporan' => 'Jalan Rusak Parah',
            'kecamatan' => 'Mulyorejo',
            'latitude' => -7.2650,
        ]);
    }

    /**
     * Test report index filters for regular user.
     */
    public function test_laporan_index_regular_user()
    {
        // Report by user
        Laporan::create([
            'user_id' => $this->user->id,
            'judul_laporan' => 'User Laporan',
            'lokasi_jalan' => 'Jl. User',
            'kecamatan' => 'Sukolilo',
            'deskripsi_kerusakan' => 'Deskripsi',
        ]);

        // Report by another user
        $anotherUser = User::create([
            'username' => 'other',
            'password' => 'password123',
            'email' => 'other@example.com',
            'nama_lengkap' => 'Other User',
        ]);
        Laporan::create([
            'user_id' => $anotherUser->id,
            'judul_laporan' => 'Other Laporan',
            'lokasi_jalan' => 'Jl. Other',
            'kecamatan' => 'Mulyorejo',
            'deskripsi_kerusakan' => 'Deskripsi',
        ]);

        // Get index as regular user -> should see only 1 report
        $responseUser = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/laporan');

        $responseUser->assertStatus(200);
        $this->assertCount(1, $responseUser->json('data.laporan'));
        $this->assertEquals('User Laporan', $responseUser->json('data.laporan.0.judul_laporan'));
    }

    /**
     * Test report index filters for admin.
     */
    public function test_laporan_index_admin()
    {
        // Report by user
        Laporan::create([
            'user_id' => $this->user->id,
            'judul_laporan' => 'User Laporan',
            'lokasi_jalan' => 'Jl. User',
            'kecamatan' => 'Sukolilo',
            'deskripsi_kerusakan' => 'Deskripsi',
        ]);

        // Report by another user
        $anotherUser = User::create([
            'username' => 'other',
            'password' => 'password123',
            'email' => 'other@example.com',
            'nama_lengkap' => 'Other User',
        ]);
        Laporan::create([
            'user_id' => $anotherUser->id,
            'judul_laporan' => 'Other Laporan',
            'lokasi_jalan' => 'Jl. Other',
            'kecamatan' => 'Mulyorejo',
            'deskripsi_kerusakan' => 'Deskripsi',
        ]);

        // Get index as admin -> should see both reports
        $responseAdmin = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/laporan');

        $responseAdmin->assertStatus(200);
        $this->assertCount(2, $responseAdmin->json('data.laporan'));
    }

    /**
     * Test general stats and charts stats.
     */
    public function test_stats_endpoints()
    {
        Laporan::create([
            'user_id' => $this->user->id,
            'judul_laporan' => 'Laporan 1',
            'lokasi_jalan' => 'Jl. Satu',
            'kecamatan' => 'Sukolilo',
            'deskripsi_kerusakan' => 'Deskripsi',
            'status' => 'selesai',
            'tingkat_kerusakan' => 'ringan',
        ]);

        // General stats
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJsonPath('data.stats.total_laporan', 1)
            ->assertJsonPath('data.stats.laporan_selesai', 1);

        // Filtered status stats
        $responseStatus = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/stats/status');

        $responseStatus->assertStatus(200);
    }

    /**
     * Test map coordinates feed.
     */
    public function test_map_coordinates_feed()
    {
        Laporan::create([
            'user_id' => $this->user->id,
            'judul_laporan' => 'Laporan Dengan Map',
            'lokasi_jalan' => 'Jl. Map',
            'kecamatan' => 'Sukolilo',
            'deskripsi_kerusakan' => 'Deskripsi',
            'latitude' => -7.2700,
            'longitude' => 112.7900,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->userToken,
        ])->getJson('/api/laporan-map');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.laporan')
            ->assertJsonPath('data.laporan.0.judul_laporan', 'Laporan Dengan Map');
    }
}
