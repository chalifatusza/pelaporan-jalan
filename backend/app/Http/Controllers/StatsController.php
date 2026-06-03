<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    /**
     * Get general system stats (Admin dashboard / General stats).
     */
    public function getGeneralStats()
    {
        $totalLaporan = Report::count();
        
        $statusCounts = Report::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $laporanSelesai = $statusCounts['selesai'] ?? 0;
        $totalUsers = User::where('role', 'user')->count();

        return response()->json([
            'success' => true,
            'message' => 'Statistik berhasil diambil',
            'data' => [
                'stats' => [
                    'total_laporan' => $totalLaporan,
                    'status_stats' => [
                        'dikirim' => $statusCounts['dikirim'] ?? 0,
                        'diproses' => $statusCounts['diproses'] ?? 0,
                        'selesai' => $statusCounts['selesai'] ?? 0,
                    ],
                    'laporan_selesai' => $laporanSelesai,
                    'total_users' => $totalUsers
                ]
            ]
        ]);
    }

    /**
     * Get authenticated user stats.
     */
    public function getUserStats(Request $request)
    {
        $userId = $request->user()->id;

        $totalLaporan = Report::where('user_id', $userId)->count();

        $statusCounts = Report::where('user_id', $userId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $laporanBulanIni = Report::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Statistik user berhasil diambil',
            'data' => [
                'stats' => [
                    'total_laporan' => $totalLaporan,
                    'status_stats' => [
                        'dikirim' => $statusCounts['dikirim'] ?? 0,
                        'diproses' => $statusCounts['diproses'] ?? 0,
                        'selesai' => $statusCounts['selesai'] ?? 0,
                    ],
                    'laporan_bulan_ini' => $laporanBulanIni
                ]
            ]
        ]);
    }

    /**
     * Get status stats for charts (with range filter).
     */
    public function getStatusStats(Request $request)
    {
        $range = $request->get('range', 'all');
        $query = Report::select('status', DB::raw('count(*) as total'));

        $this->applyRangeFilter($query, $range);

        $stats = $query->groupBy('status')->get();

        $order = ['dikirim' => 1, 'diproses' => 2, 'selesai' => 3];
        $sortedStats = $stats->sortBy(function ($item) use ($order) {
            return $order[strtolower($item->status)] ?? 99;
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Statistik status berhasil diambil',
            'data' => $sortedStats, // direct for filtered calls
            'stats' => $sortedStats // compatibility
        ]);
    }

    /**
     * Get damage level stats for charts (with range filter).
     */
    public function getKerusakanStats(Request $request)
    {
        $range = $request->get('range', 'all');
        $query = Report::select('tingkat_kerusakan', DB::raw('count(*) as total'));

        $this->applyRangeFilter($query, $range);

        $stats = $query->groupBy('tingkat_kerusakan')->get();

        $order = ['ringan' => 1, 'sedang' => 2, 'berat' => 3];
        $sortedStats = $stats->sortBy(function ($item) use ($order) {
            return $order[strtolower($item->tingkat_kerusakan)] ?? 99;
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Statistik kerusakan berhasil diambil',
            'data' => $sortedStats, // direct for filtered calls
            'stats' => $sortedStats // compatibility
        ]);
    }

    /**
     * Get kecamatan stats for charts (with range filter).
     */
    public function getKecamatanStats(Request $request)
    {
        $range = $request->get('range', 'all');
        $query = Report::select(
            'kecamatan',
            DB::raw('count(*) as total'),
            DB::raw("sum(case when status = 'selesai' then 1 else 0 end) as selesai"),
            DB::raw("sum(case when status = 'diproses' then 1 else 0 end) as diproses"),
            DB::raw("sum(case when status = 'dikirim' then 1 else 0 end) as dikirim")
        );

        $this->applyRangeFilter($query, $range);

        $stats = $query->groupBy('kecamatan')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Statistik kecamatan berhasil diambil',
            'data' => $stats, // direct for filtered calls
            'stats' => $stats // compatibility
        ]);
    }

    /**
     * Helper: apply time range filter to query.
     */
    private function applyRangeFilter($query, $range)
    {
        if ($range === '7d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(7));
        } elseif ($range === '30d') {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        } elseif ($range === '3m') {
            $query->where('created_at', '>=', Carbon::now()->subMonths(3));
        }
    }
}
