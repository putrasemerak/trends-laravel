<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmFile;
use App\Models\EmPersonnel;
use App\Models\EmSurface;

class EmController extends Controller
{
    public function index()
    {
        // Summary: distinct machine codes that have been uploaded
        $machines = EmFile::select('machine_code')
            ->selectRaw('MAX(year) as latest_year, MAX(month) as latest_month, COUNT(*) as file_count')
            ->groupBy('machine_code')
            ->orderBy('machine_code')
            ->get();

        return view('em.dashboard', compact('machines'));
    }

    public function detail(Request $request, string $machine)
    {
        $machine = strtoupper($machine);

        // All known machine codes (for shortcut nav)
        $allMachines = EmFile::select('machine_code')
            ->distinct()
            ->orderBy('machine_code')
            ->pluck('machine_code');

        if (!$allMachines->contains($machine)) {
            abort(404, 'No data for machine: ' . $machine);
        }

        // Available years for this machine
        $years = EmFile::where('machine_code', $machine)
            ->orderByDesc('year')
            ->distinct()
            ->pluck('year');

        if ($years->isEmpty()) {
            abort(404, 'No data for machine: ' . $machine);
        }

        $selectedYear = $request->integer('year', $years->first());

        // File IDs for this machine + year
        $fileIds = EmFile::where('machine_code', $machine)
            ->where('year', $selectedYear)
            ->pluck('id');

        // ── Chart 1: Personnel (cfu/glove) ──────────────────────────────────
        $personnelRows = EmPersonnel::whereIn('em_file_id', $fileIds)
            ->where('is_na', false)
            ->whereNotNull('result')
            ->orderBy('sample_date')
            ->orderBy('emp_no')
            ->get(['test_type', 'sample_date', 'emp_no', 'result',
                   'std_limit', 'action_limit', 'alert_limit', 'anomaly']);

        // Build per-worker series grouped by test_type
        $workerSeries = [];
        foreach ($personnelRows as $row) {
            $series = $row->test_type;
            $workerSeries[$series][] = [
                'worker'  => $row->emp_no ?: '—',
                'date'    => $row->sample_date->format('d M Y'),
                'value'   => round((float) $row->result, 2),
                'anomaly' => (bool) $row->anomaly,
                'limit'   => $row->alert_limit ?? $row->action_limit ?? $row->std_limit,
            ];
        }

        // ── Chart 2: Surface sheet1 — location lines ─────────────────────
        $surfaceRows = EmSurface::whereIn('em_file_id', $fileIds)
            ->where('sheet_source', 'sheet1')
            ->where('is_na', false)
            ->whereNotNull('result')
            ->orderBy('sample_date')
            ->get(['test_type', 'location_label', 'room_label',
                   'sample_date', 'result', 'std_limit', 'action_limit', 'alert_limit', 'anomaly']);

        // Group: location_label → sorted series of {date, value, anomaly}
        $locationSeries = [];
        $surfaceLimit   = null;
        foreach ($surfaceRows as $row) {
            $loc = $row->location_label ?: $row->test_type;
            $locationSeries[$loc][] = [
                'date'    => $row->sample_date->format('Y-m-d'),
                'label'   => $row->sample_date->format('d M'),
                'value'   => round((float) $row->result, 2),
                'anomaly' => (bool) $row->anomaly,
            ];
            if ($surfaceLimit === null) {
                $surfaceLimit = $row->alert_limit ?? $row->action_limit ?? $row->std_limit;
            }
        }

        // ── Chart 3: SP & AS lain2 ──────────────────────────────────────
        $spasRows = EmSurface::whereIn('em_file_id', $fileIds)
            ->where('sheet_source', 'sp_as')
            ->where('is_na', false)
            ->whereNotNull('result')
            ->orderBy('sample_date')
            ->get(['test_type', 'location_label',
                   'sample_date', 'result', 'std_limit', 'action_limit', 'alert_limit', 'anomaly']);

        $spasSeries = [];
        $spasLimit  = null;
        foreach ($spasRows as $row) {
            $loc = $row->location_label ?: $row->test_type;
            $spasSeries[$loc][] = [
                'date'    => $row->sample_date->format('Y-m-d'),
                'label'   => $row->sample_date->format('d M'),
                'value'   => round((float) $row->result, 2),
                'anomaly' => (bool) $row->anomaly,
            ];
            if ($spasLimit === null) {
                $spasLimit = $row->alert_limit ?? $row->action_limit ?? $row->std_limit;
            }
        }

        return view('em.detail', compact(
            'machine', 'allMachines', 'years', 'selectedYear',
            'workerSeries', 'locationSeries', 'surfaceLimit',
            'spasSeries', 'spasLimit'
        ));
    }
}

