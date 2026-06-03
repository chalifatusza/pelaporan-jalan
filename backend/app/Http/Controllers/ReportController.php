<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    /**
     * Get report list.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Report::with(['user:id,nama_lengkap,username,email', 'kategori:id,nama_kategori']);

        // Filters
        if ($request->has('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('tingkat_kerusakan')) {
            $query->where('tingkat_kerusakan', $request->tingkat_kerusakan);
        }
        if ($request->has('kecamatan')) {
            $query->where('kecamatan', $request->kecamatan);
        }

        if ($user->role === 'admin') {
            $reports = $query->orderBy('created_at', 'desc')->get();
        } else {
            $reports = $query->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        }

        // Map and format for frontend compatibility
        $formattedReports = $reports->map(function ($report) {
            $arr = $report->toArray();
            $arr['nama_lengkap'] = $report->user->nama_lengkap ?? '';
            $arr['username'] = $report->user->username ?? '';
            $arr['nama_kategori'] = $report->kategori->nama_kategori ?? '';
            $arr['tanggal_laporan_formatted'] = $report->created_at->format('d F Y H:i');
            return $arr;
        });

        return response()->json([
            'success' => true,
            'message' => 'Data laporan berhasil diambil',
            'data' => [
                'laporan' => $formattedReports
            ]
        ]);
    }

    /**
     * Get report by ID.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $report = Report::with(['user:id,nama_lengkap,username,email', 'kategori:id,nama_kategori'])->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ]);
        }

        if ($user->role !== 'admin' && $report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini'
            ]);
        }

        $arr = $report->toArray();
        $arr['nama_lengkap'] = $report->user->nama_lengkap ?? '';
        $arr['username'] = $report->user->username ?? '';
        $arr['nama_kategori'] = $report->kategori->nama_kategori ?? '';
        $arr['tanggal_laporan_formatted'] = $report->created_at->format('d F Y H:i');

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
            'kategori_id' => 'nullable|exists:kategori,id',
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

        // Determine kategori_id (with fallback if not supplied by frontend)
        $kategoriId = $request->kategori_id;
        if (!$kategoriId) {
            $defaultKategori = Kategori::firstOrCreate(
                ['nama_kategori' => 'Jalan Rusak'],
                ['user_id' => $request->user()->id, 'deskripsi' => 'Kategori default']
            );
            $kategoriId = $defaultKategori->id;
        }

        $fotoPath = '';
        if ($request->hasFile('foto')) {
            try {
                $file = $request->file('foto');
                
                $rootUploadsDir = base_path('../uploads');
                $isWritable = true;
                
                if (!is_dir($rootUploadsDir)) {
                    if (!@mkdir($rootUploadsDir, 0777, true)) {
                        $isWritable = false;
                    }
                } elseif (!is_writable($rootUploadsDir)) {
                    $isWritable = false;
                }

                if (!$isWritable || isset($_SERVER['VERCEL']) || env('VERCEL') || isset($_ENV['VERCEL'])) {
                    // Fallback to /tmp on Vercel or read-only environments
                    $rootUploadsDir = '/tmp';
                }

                $fileExt = $file->getClientOriginalExtension();
                $fileName = time() . '_' . uniqid() . '.' . $fileExt;
                $targetPath = $rootUploadsDir . '/' . $fileName;

                if ($file->move($rootUploadsDir, $fileName)) {
                    $fotoPath = 'uploads/' . $fileName;

                    if (extension_loaded('gd')) {
                        @$this->compressImage($targetPath, $targetPath, 80);
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('File upload failed: ' . $e->getMessage());
            }
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'kategori_id' => $kategoriId,
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
        $report = Report::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ]);
        }

        if ($user->role !== 'admin' && $report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'kategori_id' => 'nullable|exists:kategori,id',
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

        if ($request->has('kategori_id')) {
            $data['kategori_id'] = $request->kategori_id;
        }

        if ($user->role === 'admin' && $request->has('status')) {
            $data['status'] = $request->status;
        }

        $report->update($data);

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
        $report = Report::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Laporan tidak ditemukan'
            ]);
        }

        if ($user->role !== 'admin' && $report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke laporan ini'
            ]);
        }

        if ($report->foto_path) {
            $fullPath = base_path('../' . $report->foto_path);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $report->delete();

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
