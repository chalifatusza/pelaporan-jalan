<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\Request;

class MapController extends Controller
{
    /**
     * Get all reports with valid coordinates for map visualization.
     */
    public function getMapLaporan()
    {
        $laporans = Laporan::with('user:id,nama_lengkap')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedLaporans = $laporans->map(function ($laporan) {
            return [
                'id' => $laporan->id,
                'judul_laporan' => $laporan->judul_laporan,
                'lokasi_jalan' => $laporan->lokasi_jalan,
                'kecamatan' => $laporan->kecamatan,
                'tingkat_kerusakan' => $laporan->tingkat_kerusakan,
                'status' => $laporan->status,
                'latitude' => $laporan->latitude,
                'longitude' => $laporan->longitude,
                'foto_path' => $laporan->foto_path,
                'tanggal' => $laporan->created_at->format('d F Y'),
                'nama_lengkap' => $laporan->user->nama_lengkap ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Data laporan peta berhasil diambil',
            'data' => [
                'laporan' => $formattedLaporans
            ]
        ]);
    }
}
