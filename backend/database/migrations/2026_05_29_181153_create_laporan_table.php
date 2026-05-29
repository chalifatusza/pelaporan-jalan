<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('judul_laporan', 200);
            $table->string('lokasi_jalan', 255);
            $table->string('kecamatan', 50);
            $table->text('deskripsi_kerusakan');
            $table->string('foto_path', 255)->nullable();
            $table->enum('tingkat_kerusakan', ['ringan', 'sedang', 'berat'])->default('ringan');
            $table->enum('status', ['dikirim', 'diproses', 'selesai'])->default('dikirim');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
