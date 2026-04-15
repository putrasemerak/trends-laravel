<?php

namespace App\Http\Controllers;

use App\Models\BioburdenUpload;
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
        $sparkMonths = 6; // how many most-recent months to show on sparkline

        // Get all active prodlines with summary stats
        $prodlines = DB::connection('devdb')
            ->table('TS_0020')
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
        // Uses actual data range — not calendar cutoff — so historical uploads always show
        $sparklines = [];
        foreach ($prodlines as $pl) {
            $sparklines[$pl->prodline] = DB::connection('devdb')
                ->table('TS_0020')
                ->selectRaw("FORMAT(datetested, 'yyyy-MM') AS m, AVG(resultavg) AS avg_val")
                ->where('Status', 'ACTIVE')
                ->where('prodline', $pl->prodline)
                ->groupByRaw("FORMAT(datetested, 'yyyy-MM')")
                ->orderByRaw("FORMAT(datetested, 'yyyy-MM') DESC")
                ->limit($sparkMonths)
                ->get()
                ->sortBy('m')           // re-sort ascending for chart display
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
        $monthlyTrend = DB::connection('devdb')
            ->table('TS_0020')
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
        $stats = DB::connection('devdb')
            ->table('TS_0020')
            ->selectRaw("COUNT(*) AS total_samples, AVG(resultavg) AS avg_result, MAX(resultavg) AS max_result, MAX(datetested) AS latest_test")
            ->where('Status', 'ACTIVE')
            ->where('prodline', $prodline)
            ->first();

        // Recent 15 entries
        $recentEntries = BioburdenUpload::active()
            ->forProdline($prodline)
            ->orderByDesc('datetested')
            ->orderByDesc('AddDate')
            ->limit(15)
            ->get();

        // Yearly trend — all years on record
        $yearlyTrend = DB::connection('devdb')
            ->table('TS_0020')
            ->selectRaw("YEAR(datetested) AS yr, AVG(resultavg) AS avg_result, MAX(resultavg) AS max_result, COUNT(*) AS sample_count")
            ->where('Status', 'ACTIVE')
            ->where('prodline', $prodline)
            ->groupByRaw("YEAR(datetested)")
            ->orderByRaw("YEAR(datetested)")
            ->get()
            ->map(fn($r) => [
                'year'  => (string)$r->yr,
                'avg'   => round($r->avg_result, 2),
                'max'   => (float)$r->max_result,
                'count' => (int)$r->sample_count,
            ])->values();

        // Batch-level chart data (last 60 days)
        $batchData = BioburdenUpload::active()
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

        // CFU/mL vs Batch line plot — last 12 months, every individual record
        $cfuBatchPlot = BioburdenUpload::active()
            ->forProdline($prodline)
            ->dateRange($now->copy()->subMonths(12)->format('Y-m-d'), $now->format('Y-m-d'))
            ->orderBy('datetested')
            ->orderBy('batch')
            ->get()
            ->map(function($r) {
                // Average only non-zero readings — avoids halving when second run wasn't done
                $tamcVals = collect([$r->tamcr1, $r->tamcr2])->filter(fn($v) => $v > 0);
                $tymcVals = collect([$r->tymcr1, $r->tymcr2])->filter(fn($v) => $v > 0);
                return [
                    'batch'   => $r->batch,
                    'cfu'     => (float) $r->resultavg,
                    'date'    => Carbon::parse($r->datetested)->format('d-M-y'),
                    'product' => $r->prodname,
                    'run'     => $r->runno,
                    'tamc'    => $tamcVals->isNotEmpty() ? round($tamcVals->avg(), 1) : null,
                    'tymc'    => $tymcVals->isNotEmpty() ? round($tymcVals->avg(), 1) : -1,
                ];
            })->values();

        // All prodlines for tab navigation
        $allProdlines = DB::connection('devdb')
            ->table('TS_0020')
            ->selectRaw('DISTINCT prodline')
            ->where('Status', 'ACTIVE')
            ->orderBy('prodline')
            ->pluck('prodline');

        // CFU/mL vs Batch by month — dropdown months + selected month data
        $availableMonths = DB::connection('devdb')
            ->table('TS_0020')
            ->selectRaw("FORMAT(datetested, 'yyyy-MM') AS ym")
            ->where('Status', 'ACTIVE')
            ->where('prodline', $prodline)
            ->groupByRaw("FORMAT(datetested, 'yyyy-MM')")
            ->orderByRaw("FORMAT(datetested, 'yyyy-MM') DESC")
            ->pluck('ym');

        $selectedMonth = $request->get('month', $availableMonths->first() ?? $now->format('Y-m'));

        // Clamp to valid format
        if (!preg_match('/^\d{4}-\d{2}$/', $selectedMonth)) {
            $selectedMonth = $now->format('Y-m');
        }

        [$selYear, $selMon] = explode('-', $selectedMonth);
        $monthStart = Carbon::createFromDate((int)$selYear, (int)$selMon, 1)->startOfMonth()->format('Y-m-d');
        $monthEnd   = Carbon::createFromDate((int)$selYear, (int)$selMon, 1)->endOfMonth()->format('Y-m-d');

        $cfuMonthData = BioburdenUpload::active()
            ->forProdline($prodline)
            ->dateRange($monthStart, $monthEnd)
            ->orderBy('datetested')
            ->orderBy('batch')
            ->get()
            ->map(fn($r) => [
                'label'   => $r->batch . ' ' . $r->runno . ($r->filing !== '-' ? ' ' . $r->filing : ''),
                'batch'   => $r->batch,
                'cfu'     => (float) $r->resultavg,
                'tamc'    => (float) $r->tamcr1 > 0 || (float) $r->tamcr2 > 0
                                ? round(collect([$r->tamcr1, $r->tamcr2])->filter(fn($v) => $v > 0)->avg(), 1)
                                : 0,
                'date'    => Carbon::parse($r->datetested)->format('d-M'),
                'product' => $r->prodname,
                'run'     => $r->runno,
                'filing'  => $r->filing,
            ])->values();

        return view('dashboard-detail', compact(
            'prodline', 'stats', 'monthlyChartData', 'recentEntries',
            'batchData', 'yearlyTrend', 'cfuBatchPlot', 'specLimit', 'allProdlines',
            'availableMonths', 'selectedMonth', 'cfuMonthData'
        ));
    }
}
