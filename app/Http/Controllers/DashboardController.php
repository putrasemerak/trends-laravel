<?php

namespace App\Http\Controllers;

use App\Models\Bioburden;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Main dashboard — one chart card per production line.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();
        $specLimit = 10;
        $monthsBack = 6;
        $cutoff = $now->copy()->subMonths($monthsBack)->startOfMonth()->format('Y-m-d');

        // Get all active prodlines with summary stats
        $prodlines = DB::connection('sqlsrv')
            ->table('TS_0010')
            ->selectRaw("
                prodline,
                COUNT(*) AS total_samples,
                AVG(resultavg) AS avg_result,
                MAX(resultavg) AS max_result,
                MAX(datetested) AS latest_test
            ")
            ->where('Status', 'ACTIVE')
            ->groupBy('prodline')
            ->orderBy('prodline')
            ->get();

        // For each prodline, get last N months of monthly averages (for sparkline)
        $sparklines = [];
        foreach ($prodlines as $pl) {
            $sparklines[$pl->prodline] = DB::connection('sqlsrv')
                ->table('TS_0010')
                ->selectRaw("FORMAT(datetested, 'yyyy-MM') AS m, AVG(resultavg) AS avg_val")
                ->where('Status', 'ACTIVE')
                ->where('prodline', $pl->prodline)
                ->where('datetested', '>=', $cutoff)
                ->groupByRaw("FORMAT(datetested, 'yyyy-MM')")
                ->orderByRaw("FORMAT(datetested, 'yyyy-MM')")
                ->get()
                ->map(fn($r) => ['month' => $r->m, 'avg' => round($r->avg_val, 2)])
                ->values();
        }

        return view('dashboard', compact('prodlines', 'sparklines', 'specLimit'));
    }

    /**
     * Detail dashboard for a single production line.
     */
    public function detail(Request $request, string $prodline)
    {
        $now = Carbon::now();
        $specLimit = 10;

        // Monthly trend — last 12 months
        $monthlyTrend = DB::connection('sqlsrv')
            ->table('TS_0010')
            ->selectRaw("FORMAT(datetested, 'yyyy-MM') AS month_label, AVG(resultavg) AS avg_result, COUNT(*) AS sample_count")
            ->where('Status', 'ACTIVE')
            ->where('prodline', $prodline)
            ->where('datetested', '>=', $now->copy()->subMonths(12)->startOfMonth()->format('Y-m-d'))
            ->groupByRaw("FORMAT(datetested, 'yyyy-MM')")
            ->orderByRaw("FORMAT(datetested, 'yyyy-MM')")
            ->get();

        $monthlyChartData = $monthlyTrend->map(fn($r) => [
            'month' => $r->month_label,
            'avg'   => round($r->avg_result, 2),
            'count' => $r->sample_count,
        ])->values();

        // Summary stats for this prodline
        $stats = DB::connection('sqlsrv')
            ->table('TS_0010')
            ->selectRaw("COUNT(*) AS total_samples, AVG(resultavg) AS avg_result, MAX(resultavg) AS max_result, MAX(datetested) AS latest_test")
            ->where('Status', 'ACTIVE')
            ->where('prodline', $prodline)
            ->first();

        // Recent 15 entries
        $recentEntries = Bioburden::active()
            ->forProdline($prodline)
            ->orderByDesc('datetested')
            ->orderByDesc('AddDate')
            ->limit(15)
            ->get();

        // Batch-level chart data (last 60 days)
        $batchData = Bioburden::active()
            ->forProdline($prodline)
            ->dateRange($now->copy()->subDays(60)->format('Y-m-d'), $now->format('Y-m-d'))
            ->orderBy('datetested')
            ->get()
            ->map(fn($r) => [
                'label'   => $r->batch . ' (' . $r->runno . ')',
                'avg'     => (float) $r->resultavg,
                'date'    => Carbon::parse($r->datetested)->format('d-M'),
                'product' => $r->prodname,
            ])->values();

        // All prodlines for tab navigation
        $allProdlines = DB::connection('sqlsrv')
            ->table('TS_0010')
            ->selectRaw('DISTINCT prodline')
            ->where('Status', 'ACTIVE')
            ->orderBy('prodline')
            ->pluck('prodline');

        return view('dashboard-detail', compact(
            'prodline', 'stats', 'monthlyChartData', 'recentEntries',
            'batchData', 'specLimit', 'allProdlines'
        ));
    }
}
