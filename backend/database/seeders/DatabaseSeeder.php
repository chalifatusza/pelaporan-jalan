<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Kategori;
use App\Models\Report;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed Admin User
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@jalanrusak.surabaya.id',
                'nama_lengkap' => 'Administrator',
                'role' => 'admin',
                'password' => 'admin123', // auto-hashed
                'api_key' => 'api_key_admin_test_12345',
            ]
        );

        // Seed Regular User
        $user = User::updateOrCreate(
            ['username' => 'user'],
            [
                'email' => 'user@example.com',
                'nama_lengkap' => 'User Demo',
                'alamat' => 'Jl. Contoh No. 123, Surabaya',
                'no_telepon' => '081234567890',
                'role' => 'user',
                'password' => 'user123', // auto-hashed
                'api_key' => 'api_key_user_test_54321',
            ]
        );

        // Seed Default Categories
        $katBerlubang = Kategori::updateOrCreate(
            ['nama_kategori' => 'Jalan Berlubang'],
            [
                'user_id' => $admin->id,
                'deskripsi' => 'Kerusakan aspal jalan berupa lubang dengan diameter bervariasi.'
            ]
        );

        $katAmblas = Kategori::updateOrCreate(
            ['nama_kategori' => 'Jalan Amblas'],
            [
                'user_id' => $admin->id,
                'deskripsi' => 'Struktur tanah/aspal jalan menurun secara signifikan.'
            ]
        );

        $katRetak = Kategori::updateOrCreate(
            ['nama_kategori' => 'Jalan Retak'],
            [
                'user_id' => $admin->id,
                'deskripsi' => 'Retakan memanjang pada permukaan aspal jalan.'
            ]
        );

        // Clear existing reports to avoid duplicate entries when re-seeding
        Report::query()->delete();

        // Seed Mock Reports
        Report::create([
            'user_id' => $user->id,
            'kategori_id' => $katBerlubang->id,
            'judul_laporan' => 'Jalan Berlubang Besar',
            'lokasi_jalan' => 'Jl. Dharmahusada Indah Timur No. 35',
            'kecamatan' => 'Mulyorejo',
            'deskripsi_kerusakan' => 'Terdapat lubang jalan berdiameter sekitar 50cm dengan kedalaman 15cm. Sangat membahayakan bagi pengendara sepeda motor terutama saat hujan atau malam hari.',
            'tingkat_kerusakan' => 'berat',
            'status' => 'dikirim',
            'latitude' => -7.2687,
            'longitude' => 112.7825,
            'foto_path' => null
        ]);

        Report::create([
            'user_id' => $user->id,
            'kategori_id' => $katAmblas->id,
            'judul_laporan' => 'Paving Amblas',
            'lokasi_jalan' => 'Jl. Raya Kertajaya Indah',
            'kecamatan' => 'Sukolilo',
            'deskripsi_kerusakan' => 'Paving block di bahu jalan amblas sedalam 10cm menyebabkan genangan air dan mengganggu pejalan kaki serta kendaraan yang parkir.',
            'tingkat_kerusakan' => 'sedang',
            'status' => 'diproses',
            'latitude' => -7.2745,
            'longitude' => 112.7798,
            'foto_path' => null
        ]);

        Report::create([
            'user_id' => $user->id,
            'kategori_id' => $katRetak->id,
            'judul_laporan' => 'Jalan Retak Seribu',
            'lokasi_jalan' => 'Jl. Manyar Kertoarjo',
            'kecamatan' => 'Mulyorejo',
            'deskripsi_kerusakan' => 'Retakan memanjang di aspal sepanjang kurang lebih 5 meter. Sudah mulai melebar dan berpotensi menjadi lubang jika tidak segera ditambal.',
            'tingkat_kerusakan' => 'ringan',
            'status' => 'selesai',
            'latitude' => -7.2798,
            'longitude' => 112.7687,
            'foto_path' => null
        ]);
    }
}
