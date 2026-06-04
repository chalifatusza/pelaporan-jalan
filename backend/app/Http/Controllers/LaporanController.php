<?php

namespace App\Http\Controllers;

use App\Models\Laporan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LaporanController extends Controller
{
    /**
     * Get report list.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $laporans = Laporan::with('user:id,nama_lengkap,username,email')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $laporans = Laporan::with('user:id,nama_lengkap,username,email')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Map and format for frontend compatibility
        $formattedLaporans = $laporans->map(function ($laporan) {
            $arr = $laporan->toArray();
            $arr['nama_lengkap'] = $laporan->user->nama_lengkap ?? '';
            $arr['username'] = $laporan->user->username ?? '';
            $arr['tanggal_laporan_formatted'] = $laporan->created_at->format('d F Y H:i');
            return $arr;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data laporan berhasil diambil',
            'data' => [
                'laporan' => $formattedLaporans
            ]
        ]);
    }

    /**
     * Get report by ID.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $laporan = Laporan::with('user:id,nama_lengkap,username,email')->find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ]);
        }

        if ($user->role !== 'admin' && $laporan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini'
            ]);
        }

        $arr = $laporan->toArray();
        $arr['nama_lengkap'] = $laporan->user->nama_lengkap ?? '';
        $arr['username'] = $laporan->user->username ?? '';
        $arr['tanggal_laporan_formatted'] = $laporan->created_at->format('d F Y H:i');

        return response()->json([
            'success' => true,
            'message' => 'Data laporan berhasil diambil',
            'data' => [
                'laporan' => $arr
            ]
        ]);
    }

    /**
     * Submit new report.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul_laporan' => 'required|string|max:200',
            'lokasi_jalan' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:50',
            'deskripsi_kerusakan' => 'required|string',
            'tingkat_kerusakan' => 'nullable|in:ringan,sedang,berat',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:5120', // 5MB
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $fotoPath = '';
        if ($request->hasFile('foto')) {
            try {
                $file = $request->file('foto');
                $tempPath = $file->getRealPath();
                
                // Compress image if GD is loaded
                if (extension_loaded('gd')) {
                    $this->compressImage($tempPath, $tempPath, 80);
                }
                
                $mimeType = $file->getMimeType();
                $fileData = file_get_contents($tempPath);
                $base64 = base64_encode($fileData);
                $fotoPath = 'data:' . $mimeType . ';base64,' . $base64;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('File upload failed in legacy controller: ' . $e->getMessage());
            }
        }

        $laporan = Laporan::create([
            'user_id' => $request->user()->id,
            'judul_laporan' => $request->judul_laporan,
            'lokasi_jalan' => $request->lokasi_jalan,
            'kecamatan' => $request->kecamatan,
            'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
            'tingkat_kerusakan' => $request->tingkat_kerusakan ?? 'ringan',
            'foto_path' => $fotoPath,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => 'dikirim',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dikirim' . (empty($fotoPath) ? '' : ' (dengan foto)')
        ]);
    }

    /**
     * Update report.
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ]);
        }

        if ($user->role !== 'admin' && $laporan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'judul_laporan' => 'required|string|max:200',
            'lokasi_jalan' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:50',
            'deskripsi_kerusakan' => 'required|string',
            'tingkat_kerusakan' => 'required|in:ringan,sedang,berat',
            'status' => 'nullable|in:dikirim,diproses,selesai',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ]);
        }

        $data = [
            'judul_laporan' => $request->judul_laporan,
            'lokasi_jalan' => $request->lokasi_jalan,
            'kecamatan' => $request->kecamatan,
            'deskripsi_kerusakan' => $request->deskripsi_kerusakan,
            'tingkat_kerusakan' => $request->tingkat_kerusakan,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ];

        // If admin updates the status
        if ($user->role === 'admin' && $request->has('status')) {
            $data['status'] = $request->status;
        }

        $laporan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diperbarui'
        ]);
    }

    /**
     * Delete report.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $laporan = Laporan::find($id);

        if (!$laporan) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ]);
        }

        if ($user->role !== 'admin' && $laporan->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini'
            ]);
        }

        // Delete associated image file
        if ($laporan->foto_path && strpos($laporan->foto_path, 'data:') !== 0) {
            $fullPath = base_path('../' . $laporan->foto_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $laporan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil dihapus'
        ]);
    }

    /**
     * Helper: compress image.
     */
    private function compressImage($source, $destination, $quality)
    {
        $info = getimagesize($source);
        if (!$info) return false;
        
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($source);
                imagejpeg($image, $destination, $quality);
                break;
            case 'image/png':
                $image = imagecreatefrompng($source);
                imagepng($image, $destination, floor($quality / 10));
                break;
            case 'image/gif':
                $image = imagecreatefromgif($source);
                imagegif($image, $destination);
                break;
            default:
                return false;
        }

        imagedestroy($image);
        return true;
    }
}
