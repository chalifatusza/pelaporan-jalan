<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use App\Mail\ReportStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    /**
     * Get list of users (Admin only).
     */
    public function getUsers(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak']);
        }

        $users = User::withCount('reports')
            ->orderBy('created_at', 'desc')
            ->get();

        $formattedUsers = $users->map(function ($u) {
            return [
                'id' => $u->id,
                'username' => $u->username,
                'nama_lengkap' => $u->nama_lengkap,
                'email' => $u->email,
                'no_telepon' => $u->no_telepon,
                'role' => $u->role,
                'tanggal_daftar' => $u->created_at->toDateTimeString(),
                'total_laporan' => $u->reports_count,
            ];
        });

        // Double format compatibility
        return response()->json([
            'success' => true,
            'message' => 'Data pengguna berhasil diambil',
            'data' => $formattedUsers,
            'users' => $formattedUsers
        ]);
    }

    /**
     * Update user role (Admin only).
     */
    public function updateUserRole(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak']);
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required|in:user,admin',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan']);
        }

        if ($user->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat mengubah role akun sendiri']);
        }

        $user->update([
            'role' => $request->role
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role pengguna berhasil diubah'
        ]);
    }

    /**
     * Delete user (Admin only).
     */
    public function deleteUser(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak']);
        }

        if ($id == $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun sendiri']);
        }

        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Pengguna tidak ditemukan']);
        }

        if ($user->role === 'admin') {
            return response()->json(['success' => false, 'message' => 'Tidak dapat menghapus akun admin']);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus'
        ]);
    }

    /**
     * Get filterable report list for admin (Admin only).
     */
    public function getLaporanAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak']);
        }

        $page = intval($request->get('page', 1));
        $limit = 10;
        $status = $request->get('status', '');
        $rusak = $request->get('tingkat_kerusakan', '');
        $kec = $request->get('kecamatan', '');
        $range = $request->get('range', '');
        $search = $request->get('search', '');

        $query = Report::with('user:id,nama_lengkap,email');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($rusak)) {
            $query->where('tingkat_kerusakan', $rusak);
        }

        if (!empty($kec)) {
            $query->where('kecamatan', $kec);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('lokasi_jalan', 'like', "%{$search}%")
                  ->orWhere('deskripsi_kerusakan', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('nama_lengkap', 'like', "%{$search}%");
                  });
            });
        }

        if ($range === '7d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        } elseif ($range === '30d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        } elseif ($range === '3m') {
            $query->where('created_at', '>=', Carbon::now()->subMonths(3));
        }

        $total = $query->count();
        $laporans = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        $formattedData = $laporans->map(function ($laporan) {
            return [
                'id' => $laporan->id,
                'user_id' => $laporan->user_id,
                'judul_laporan' => $laporan->judul_laporan,
                'lokasi_jalan' => $laporan->lokasi_jalan,
                'kecamatan' => $laporan->kecamatan,
                'deskripsi_kerusakan' => $laporan->deskripsi_kerusakan,
                'foto_path' => $laporan->foto_path,
                'tingkat_kerusakan' => $laporan->tingkat_kerusakan,
                'status' => $laporan->status,
                'latitude' => $laporan->latitude,
                'longitude' => $laporan->longitude,
                'created_at' => $laporan->created_at->toDateTimeString(),
                'updated_at' => $laporan->updated_at->toDateTimeString(),
                'nama_lengkap' => $laporan->user->nama_lengkap ?? '',
                'email' => $laporan->user->email ?? '',
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedData,
            'total' => $total,
            'total_page' => ceil($total / $limit),
            'page' => $page
        ]);
    }

    /**
     * Update report status + send email notification (Admin only).
     */
    public function updateStatus(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Akses ditolak']);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:dikirim,diproses,selesai',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()]);
        }

        $laporan = Report::with('user')->find($id);

        if (!$laporan) {
            return response()->json(['success' => false, 'message' => 'Laporan tidak ditemukan']);
        }

        $oldStatus = $laporan->status;
        $newStatus = $request->status;

        $laporan->update([
            'status' => $newStatus
        ]);

        // Send email if status changed and user has email
        if ($oldStatus !== $newStatus && $laporan->user && $laporan->user->email) {
            try {
                Mail::to($laporan->user->email)->send(new ReportStatusUpdated(
                    $laporan->user->nama_lengkap,
                    $laporan->id,
                    $laporan->lokasi_jalan,
                    ucfirst($newStatus)
                ));
            } catch (\Exception $e) {
                // Log warning but don't fail the request
                logger()->warning("Gagal mengirim email: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true
        ]);
    }
}
