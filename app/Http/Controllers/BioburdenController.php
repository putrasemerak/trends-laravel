<?php

namespace App\Http\Controllers;

use App\Models\Bioburden;
use App\Models\BioburdenRemark;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class BioburdenController extends Controller
{
    public function index(Request $request)
    {
        $prodline = $request->input('prodline', '');
        $accessLevel = $request->input('access_level', 1);

        if (empty($prodline)) {
            return redirect()->route('home');
        }

        // Date range handling
        $now = Carbon::now();
        $isSameMonth = false;
        $dataExistsTS0011 = false;
        $remark = null;

        if ($request->filled('monthStart')) {
            $monthStart = $request->input('monthStart');
            $yearStart = $request->input('yearStart');
            $monthEnd = $request->input('monthEnd');
            $yearEnd = $request->input('yearEnd');

            $dateStart = Carbon::createFromDate($yearStart, $monthStart, 1)->startOfMonth();
            $dateTo = Carbon::createFromDate($yearEnd, $monthEnd, 1)->endOfMonth();

            // Swap if start > end
            if ($dateStart->gt($dateTo)) {
                [$dateStart, $dateTo] = [$dateTo, $dateStart];
                session()->flash('warning', 'Start month is later than End month. Dates were swapped automatically.');
            }

            $isSameMonth = ($monthStart == $monthEnd && $yearStart == $yearEnd);
        } else {
            $dateStart = $now->copy()->subDays(55);
            $dateTo = $now->copy();
        }

        // Query bioburden data
        $results = Bioburden::active()
            ->forProdline($prodline)
            ->dateRange($dateStart->format('Y-m-d'), $dateTo->format('Y-m-d'))
            ->orderBy('AddDate')
            ->orderBy('AddTime')
            ->get();

        // Chart data
        $chartData = $results->map(function ($row) {
            return [
                'Batch' => $row->batch . ' (' . $row->runno . ')',
                'Average' => floatval($row->resultavg),
                'BatchNo' => $row->batch,
                'RunNo' => $row->runno,
            ];
        })->values();

        // Check for monthly remark if same month selected
        if ($isSameMonth) {
            $remarkRecord = BioburdenRemark::where('monthyear', $dateStart->format('Y-m-d'))
                ->where('prodline', $prodline)
                ->orderByDesc('AddDate')
                ->first();

            $dataExistsTS0011 = !is_null($remarkRecord);
            $remark = $remarkRecord;
        }

        // Get distinct years for filter dropdowns
        $years = DB::connection('sqlsrv')
            ->table('TS_0010')
            ->selectRaw('DISTINCT YEAR(datetested) AS year_val')
            ->orderBy('year_val')
            ->pluck('year_val');

        // Spec limit
        $specLimit = 10;

        // Batch list for Add modal (from PD_0010 + PD_0030)
        $batches = DB::connection('sqlsrv')
            ->table('PD_0010')
            ->leftJoin('PD_0030', 'PD_0010.PCode', '=', 'PD_0030.NCODE')
            ->select('PD_0010.Batch', 'PD_0030.PBrand')
            ->get();

        return view('bioburden.index', compact(
            'prodline',
            'results',
            'chartData',
            'dateStart',
            'dateTo',
            'isSameMonth',
            'dataExistsTS0011',
            'remark',
            'years',
            'specLimit',
            'batches',
            'accessLevel'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'batch' => 'required|string',
            'runno' => 'required|string',
            'datetested' => 'required|date',
            'tamcr1' => 'required|numeric|min:0',
            'tamcr2' => 'required|numeric|min:0',
            'tymcr1' => 'required|numeric|min:0',
            'tymcr2' => 'required|numeric|min:0',
            'resultavg' => 'required|numeric|min:0',
            'limit' => 'required|numeric',
            'prodline' => 'required|string',
        ]);

        // Parse "BATCH - PRODUCT NAME" format
        $parts = explode(' - ', $request->input('batch'));
        if (count($parts) !== 2 || empty(trim($parts[0])) || empty(trim($parts[1]))) {
            return back()->with('error', 'Please select a valid product with both batch number and product name.');
        }

        /** @var \App\Models\Employee $user */
        $user = Auth::user();
        $displayName = $user->details->display_name ?? $user->EmpNo;

        Bioburden::create([
            'prodline' => $request->input('prodline'),
            'batch' => trim($parts[0]),
            'prodname' => trim($parts[1]),
            'datetested' => $request->input('datetested'),
            'runno' => $request->input('runno'),
            'tamcr1' => $request->input('tamcr1'),
            'tamcr2' => $request->input('tamcr2'),
            'tymcr1' => $request->input('tymcr1'),
            'tymcr2' => $request->input('tymcr2'),
            'resultavg' => $request->input('resultavg'),
            'limit' => $request->input('limit'),
            'AddDate' => now()->format('Y-m-d'),
            'AddTime' => now()->format('H:i:s'),
            'AddUser' => $displayName,
            'Status' => 'ACTIVE',
        ]);

        return back()->with('success', 'Entry has been submitted.');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
        ]);

        Bioburden::where('id', $request->input('id'))
            ->update(['Status' => 'INACTIVE']);

        return back()->with('success', 'Entry has been removed.');
    }

    public function storeRemark(Request $request)
    {
        $request->validate([
            'remark' => 'required|string|max:2000',
            'monthyear' => 'required|date',
            'prodline' => 'required|string',
        ]);

        /** @var \App\Models\Employee $user */
        $user = Auth::user();
        $displayName = $user->details->display_name ?? $user->EmpNo;

        BioburdenRemark::create([
            'remark' => $request->input('remark'),
            'monthyear' => $request->input('monthyear'),
            'prodline' => $request->input('prodline'),
            'AddDate' => now()->format('Y-m-d H:i:s'),
            'AddUser' => $displayName,
        ]);

        return back()->with('success', 'Remark has been submitted.');
    }

    public function updateRemark(Request $request)
    {
        $request->validate([
            'remark' => 'required|string|max:2000',
            'monthyear' => 'required|date',
            'prodline' => 'required|string',
        ]);

        /** @var \App\Models\Employee $user */
        $user = Auth::user();
        $displayName = $user->details->display_name ?? $user->EmpNo;

        BioburdenRemark::where('monthyear', $request->input('monthyear'))
            ->where('prodline', $request->input('prodline'))
            ->update([
                'remark' => $request->input('remark'),
                'EditDate' => now()->format('Y-m-d H:i:s'),
                'EditUser' => $displayName,
            ]);

        return back()->with('success', 'Remark has been updated.');
    }

    public function uploadForm()
    {
        // Hardcoded prodlines for now — will change to table lookup later
        $prodlines = collect(['SVP1', 'SVP2', 'SVP3']);

        return view('bioburden.upload', compact('prodlines'));
    }

    public function uploadFile(Request $request)
    {
        $request->validate([
            'upload_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:5120',
            'prodline' => 'required|string',
        ]);

        /** @var \App\Models\Employee $user */
        $user = Auth::user();
        $displayName = $user->details->display_name ?? $user->EmpNo;
        $prodline = $request->input('prodline');
        $file = $request->file('upload_file');
        $ext = strtolower($file->getClientOriginalExtension());

        // Parse rows from file
        $dataRows = [];
        $headerMap = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            // Excel via PhpSpreadsheet
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $allRows = $sheet->toArray(null, true, true, false);

            if (count($allRows) < 2) {
                return back()->with('error', 'File has no data rows.');
            }

            $headerMap = $this->mapHeader($allRows[0]);
            $dataRows = array_slice($allRows, 1);
        } else {
            // CSV
            $lines = array_map('str_getcsv', file($file->getRealPath()));
            if (count($lines) < 2) {
                return back()->with('error', 'File has no data rows.');
            }

            $headerMap = $this->mapHeader($lines[0]);
            $dataRows = array_slice($lines, 1);
        }

        if (empty($headerMap['datetested']) && empty($headerMap['prodname']) && empty($headerMap['batch'])) {
            return back()->with('error', 'Could not recognise column headers. Expected: datetested, prodname, batch, tamcr1, tamcr2, tymcr1, tymcr2, resultavg, limit');
        }

        $inserted = 0;
        $skipped = 0;

        foreach ($dataRows as $row) {
            // Skip empty rows
            $vals = array_filter($row, fn($v) => $v !== null && $v !== '');
            if (empty($vals)) {
                continue;
            }

            $datetested = $this->getCell($row, $headerMap, 'datetested');
            $prodname   = $this->getCell($row, $headerMap, 'prodname');
            $batch      = $this->getCell($row, $headerMap, 'batch');
            $tamcr1     = (float) ($this->getCell($row, $headerMap, 'tamcr1') ?? 0);
            $tamcr2     = (float) ($this->getCell($row, $headerMap, 'tamcr2') ?? 0);
            $tymcr1     = (float) ($this->getCell($row, $headerMap, 'tymcr1') ?? 0);
            $tymcr2     = (float) ($this->getCell($row, $headerMap, 'tymcr2') ?? 0);
            $resultavg  = $this->getCell($row, $headerMap, 'resultavg');
            $limit      = $this->getCell($row, $headerMap, 'limit');

            if (empty($batch) || empty($prodname)) {
                $skipped++;
                continue;
            }

            // Parse date — accept dd/mm/yyyy or yyyy-mm-dd
            $parsedDate = $this->parseDate($datetested);
            if (!$parsedDate) {
                $skipped++;
                continue;
            }

            // Calculate resultavg if not provided
            if ($resultavg === null || $resultavg === '') {
                $resultavg = ceil((($tamcr1 + $tymcr1) + ($tamcr2 + $tymcr2)) / 2);
            }

            // Clean limit value — strip non-numeric like "<10 CFU/100 ml" → 10
            $limitNum = 10;
            if ($limit !== null && $limit !== '') {
                preg_match('/[\d.]+/', (string) $limit, $m);
                $limitNum = !empty($m) ? (float) $m[0] : 10;
            }

            Bioburden::create([
                'prodline'   => $prodline,
                'batch'      => trim($batch),
                'prodname'   => trim($prodname),
                'datetested' => $parsedDate,
                'runno'      => $this->getCell($row, $headerMap, 'runno') ?? '1',
                'tamcr1'     => $tamcr1,
                'tamcr2'     => $tamcr2,
                'tymcr1'     => $tymcr1,
                'tymcr2'     => $tymcr2,
                'resultavg'  => (float) $resultavg,
                'limit'      => $limitNum,
                'AddDate'    => now()->format('Y-m-d'),
                'AddTime'    => now()->format('H:i:s'),
                'AddUser'    => $displayName,
                'Status'     => 'ACTIVE',
            ]);
            $inserted++;
        }

        $msg = "$inserted records uploaded successfully.";
        if ($skipped > 0) {
            $msg .= " ($skipped rows skipped due to missing data.)";
        }

        return back()->with('success', $msg);
    }

    /**
     * Build a header-name → column-index map from the first row.
     */
    private function mapHeader(array $headerRow): array
    {
        $map = [];
        $aliases = [
            'datetested' => ['datetested', 'date_tested', 'date tested', 'date'],
            'prodname'   => ['prodname', 'prod_name', 'product', 'product name', 'productname'],
            'batch'      => ['batch', 'batchno', 'batch_no', 'batch no'],
            'tamcr1'     => ['tamcr1', 'tamc r1', 'tamc_r1'],
            'tamcr2'     => ['tamcr2', 'tamc r2', 'tamc_r2'],
            'tymcr1'     => ['tymcr1', 'tymc r1', 'tymc_r1'],
            'tymcr2'     => ['tymcr2', 'tymc r2', 'tymc_r2'],
            'resultavg'  => ['resultavg', 'result_avg', 'result avg', 'average', 'avg'],
            'limit'      => ['limit', 'spec_limit', 'speclimit', 'spec limit'],
            'runno'      => ['runno', 'run_no', 'run no', 'run'],
        ];

        foreach ($headerRow as $idx => $col) {
            $clean = strtolower(trim((string) $col));
            foreach ($aliases as $field => $names) {
                if (in_array($clean, $names, true)) {
                    $map[$field] = $idx;
                    break;
                }
            }
        }

        return $map;
    }

    private function getCell(array $row, array $map, string $field)
    {
        if (!isset($map[$field])) {
            return null;
        }
        return $row[$map[$field]] ?? null;
    }

    private function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        // dd/mm/yyyy
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', (string) $value, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // yyyy-mm-dd
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', (string) $value)) {
            return (string) $value;
        }
        // Try Carbon parsing as fallback
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
