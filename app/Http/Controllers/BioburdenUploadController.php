<?php

namespace App\Http\Controllers;

use App\Models\BioburdenUpload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BioburdenUploadController extends Controller
{
    /**
     * Sheet name → prodline code mapping.
     * Trim is applied before lookup to handle trailing spaces.
     */
    protected array $sheetMap = [
        'PP BOTTLE' => 'PPBOTTLE',
        'SYRINGE'   => 'SYRINGE',
        'SVP 4'     => 'SVP4',
        'SVP 3'     => 'SVP3',
        'SVP 2'     => 'SVP2',
        'SVP 1'     => 'SVP1',
        'Medibag'   => 'MEDIBAG',
        'BFS 5'     => 'BFS5',
        'BFS4'      => 'BFS4',
        'BFS3'      => 'BFS3',
        'BFS2'      => 'BFS2',
        'BFS 1'     => 'BFS1',
    ];

    public function showForm()
    {
        return view('bioburden.smart-upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'upload_file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        /** @var \App\Models\Employee $user */
        $user        = Auth::user();
        $displayName = $user->details->display_name ?? $user->EmpNo;
        $file        = $request->file('upload_file');

        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (\Exception $e) {
            return back()->with('error', 'Cannot read file: ' . $e->getMessage());
        }

        $results  = [];   // per-sheet summary to show user
        $totalIn  = 0;
        $totalSkip = 0;

        foreach ($spreadsheet->getSheetNames() as $sheetName) {
            $prodline = $this->sheetMap[trim($sheetName)] ?? null;

            if (!$prodline) {
                // Try to guess prodline from sheet name (e.g. "SVP1", "BFS 2")
                $prodline = $this->guessProdline($sheetName);
            }

            if (!$prodline) {
                $results[] = [
                    'sheet'    => $sheetName,
                    'prodline' => null,
                    'inserted' => 0,
                    'skipped'  => 0,
                    'errors'   => [],
                    'ignored'  => true,
                ];
                continue;
            }

            $sheet  = $spreadsheet->getSheetByName($sheetName);
            $layout = $this->detectLayout($sheet);

            [$inserted, $skipped, $errors] = $this->importSheet(
                $sheet, $prodline, $layout, $displayName
            );

            $results[]  = [
                'sheet'    => $sheetName,
                'prodline' => $prodline,
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'errors'   => $errors,
                'ignored'  => false,
            ];

            $totalIn   += $inserted;
            $totalSkip += $skipped;
        }

        return back()
            ->with('upload_results', $results)
            ->with('total_inserted', $totalIn)
            ->with('total_skipped', $totalSkip);
    }

    // -------------------------------------------------------------------------
    // Layout detection
    // -------------------------------------------------------------------------

    /**
     * Auto-detect where data starts and which columns hold what.
     * Returns a layout array with keys: dataStartRow, col_date, col_prodname,
     * col_batch, col_runno, col_tamcr1, col_tamcr2, col_tymcr1, col_tymcr2,
     * col_bbr_tamc_r1, col_bbr_tamc_r2, col_bbr_tymc_r1, col_bbr_tymc_r2,
     * col_resultavg, col_limit.
     */
    private function detectLayout($sheet): array
    {
        $highestRow = min($sheet->getHighestDataRow(), 20);

        // Step 1 — scan first 20 rows for header keywords
        $headerRow  = null;
        $headerData = [];

        for ($r = 1; $r <= $highestRow; $r++) {
            $rowText = '';
            for ($c = 1; $c <= 20; $c++) {
                $rowText .= strtolower(trim((string)$sheet->getCell([$c, $r])->getValue())) . ' ';
            }

            // Look for rows that contain date/batch/tamc keywords
            if (
                (str_contains($rowText, 'date') || str_contains($rowText, 'tamc'))
                && (str_contains($rowText, 'batch') || str_contains($rowText, 'r1'))
            ) {
                $headerRow = $r;
                for ($c = 1; $c <= 20; $c++) {
                    $headerData[$c] = strtolower(trim((string)$sheet->getCell([$c, $r])->getValue()));
                }
                break;
            }
        }

        // Step 2 — if no header found, try the known fixed layout (our 2025 format)
        // Row 5 = section headers, Row 6 = sub-headers (TAMC/TYMC), Row 7 = R1/R2
        // Data starts row 8
        if (!$headerRow) {
            return $this->knownLayout();
        }

        // Step 3 — find the sub-header rows below (R1/R2 labels)
        $r1Row = null;
        for ($r = $headerRow + 1; $r <= $headerRow + 3; $r++) {
            $rowText = '';
            for ($c = 1; $c <= 20; $c++) {
                $rowText .= strtolower(trim((string)$sheet->getCell([$c, $r])->getValue())) . ' ';
            }
            if (str_contains($rowText, 'r1') && str_contains($rowText, 'r2')) {
                $r1Row = $r;
                break;
            }
        }

        $dataStartRow = ($r1Row ?? $headerRow) + 1;

        // Step 4 — map columns from header
        $layout = ['dataStartRow' => $dataStartRow];

        // Scan merged/multi-row headers for TAMC and TYMC block positions
        $tamcCols = [];
        $tymcCols = [];

        for ($c = 1; $c <= 20; $c++) {
            $h = strtolower(trim((string)$sheet->getCell([$c, $headerRow])->getValue()));
            if (str_contains($h, 'tamc')) $tamcCols[] = $c;
            if (str_contains($h, 'tymc')) $tymcCols[] = $c;
        }

        // If R1 row found, use it to refine column positions
        if ($r1Row) {
            $r1Cols = [];
            for ($c = 1; $c <= 20; $c++) {
                $v = strtolower(trim((string)$sheet->getCell([$c, $r1Row])->getValue()));
                if ($v === 'r1' || $v === 'r2') $r1Cols[$c] = $v;
            }

            // Group R1/R2 pairs
            $pairs = [];
            $prev  = null;
            foreach ($r1Cols as $col => $val) {
                if ($val === 'r1') $prev = $col;
                if ($val === 'r2' && $prev) {
                    $pairs[] = [$prev, $col];
                    $prev = null;
                }
            }

            // First pair = BBRaw TAMC, Second = BBRaw TYMC, Third = BBR TAMC, Fourth = BBR TYMC
            $layout['col_tamcr1']      = $pairs[0][0] ?? null;
            $layout['col_tamcr2']      = $pairs[0][1] ?? null;
            $layout['col_tymcr1']      = $pairs[1][0] ?? null;
            $layout['col_tymcr2']      = $pairs[1][1] ?? null;
            $layout['col_bbr_tamc_r1'] = $pairs[2][0] ?? null;
            $layout['col_bbr_tamc_r2'] = $pairs[2][1] ?? null;
            $layout['col_bbr_tymc_r1'] = $pairs[3][0] ?? null;
            $layout['col_bbr_tymc_r2'] = $pairs[3][1] ?? null;
        }

        // Map simple columns from header row
        foreach ($headerData as $c => $h) {
            if (str_contains($h, 'date'))    $layout['col_date']    = $c;
            if (str_contains($h, 'product')) $layout['col_prodname'] = $c;
            if (str_contains($h, 'batch'))   $layout['col_batch']   = $c;
            if ($h === 'run' || str_contains($h, 'run no')) $layout['col_runno'] = $c;
            if (str_contains($h, 'average') || $h === 'avg') $layout['col_resultavg'] = $c;
            if ($h === 'limit')              $layout['col_limit']   = $c;
        }

        // Fallback for AVERAGE and LIMIT — scan beyond TAMC/TYMC columns
        if (empty($layout['col_resultavg']) || empty($layout['col_limit'])) {
            $fallback = $this->knownLayout();
            $layout['col_resultavg'] = $layout['col_resultavg'] ?? $fallback['col_resultavg'];
            $layout['col_limit']     = $layout['col_limit']     ?? $fallback['col_limit'];
        }

        // Fill any missing columns with known layout defaults
        $known = $this->knownLayout();
        foreach ($known as $key => $val) {
            if (!isset($layout[$key])) {
                $layout[$key] = $val;
            }
        }

        return $layout;
    }

    /**
     * The fixed known layout used in Bioburden 2025 monitoring files.
     * Col B=date, C=prodname, D=batch, E=runno
     * F=tamcr1, G=tamcr2, H=tymcr1, I=tymcr2
     * J=bbr_tamc_r1, K=bbr_tamc_r2, L=bbr_tymc_r1, M=bbr_tymc_r2
     * N=resultavg, O=limit
     */
    private function knownLayout(): array
    {
        return [
            'dataStartRow'   => 8,
            'col_date'       => 2,   // B
            'col_prodname'   => 3,   // C
            'col_batch'      => 4,   // D
            'col_runno'      => 5,   // E
            'col_tamcr1'     => 6,   // F
            'col_tamcr2'     => 7,   // G
            'col_tymcr1'     => 8,   // H
            'col_tymcr2'     => 9,   // I
            'col_bbr_tamc_r1'=> 10,  // J
            'col_bbr_tamc_r2'=> 11,  // K
            'col_bbr_tymc_r1'=> 12,  // L
            'col_bbr_tymc_r2'=> 13,  // M
            'col_resultavg'  => 14,  // N
            'col_limit'      => 15,  // O
        ];
    }

    // -------------------------------------------------------------------------
    // Import rows from one sheet
    // -------------------------------------------------------------------------

    private function importSheet($sheet, string $prodline, array $layout, string $addUser): array
    {
        $inserted   = 0;
        $skipped    = 0;
        $errors     = [];
        $highestRow = $sheet->getHighestDataRow();
        $start      = $layout['dataStartRow'];

        for ($row = $start; $row <= $highestRow; $row++) {
            $dateRaw = $sheet->getCell([$layout['col_date'], $row])->getValue();

            if ($dateRaw === null || $dateRaw === '') continue;

            // Skip rows where col_date is clearly not a date
            $formatted = $sheet->getCell([$layout['col_date'], $row])->getFormattedValue();
            if (is_string($dateRaw) && !$this->looksLikeDate($dateRaw)) continue;

            $datetested = $this->parseDate($dateRaw, $formatted);
            if (!$datetested) continue;

            $batch    = trim((string)($sheet->getCell([$layout['col_batch'],   $row])->getValue() ?? ''));
            $prodname = trim((string)($sheet->getCell([$layout['col_prodname'], $row])->getValue() ?? ''));
            $runno    = trim((string)($sheet->getCell([$layout['col_runno'],   $row])->getValue() ?? 'R1'));

            if ($batch === '') { $skipped++; continue; }

            $tamcr1      = $this->toInt($sheet->getCell([$layout['col_tamcr1'],      $row])->getValue());
            $tamcr2      = $this->toInt($sheet->getCell([$layout['col_tamcr2'],      $row])->getValue());
            $tymcr1      = $this->toInt($sheet->getCell([$layout['col_tymcr1'],      $row])->getValue());
            $tymcr2      = $this->toInt($sheet->getCell([$layout['col_tymcr2'],      $row])->getValue());
            $bbrTamcR1   = $this->toInt($sheet->getCell([$layout['col_bbr_tamc_r1'], $row])->getValue());
            $bbrTamcR2   = $this->toInt($sheet->getCell([$layout['col_bbr_tamc_r2'], $row])->getValue());
            $bbrTymcR1   = $this->toInt($sheet->getCell([$layout['col_bbr_tymc_r1'], $row])->getValue());
            $bbrTymcR2   = $this->toInt($sheet->getCell([$layout['col_bbr_tymc_r2'], $row])->getValue());

            $resultavg = (float)($sheet->getCell([$layout['col_resultavg'], $row])->getValue() ?? 0);
            $limit     = (float)($sheet->getCell([$layout['col_limit'],     $row])->getValue() ?? 10);
            if ($limit <= 0) $limit = 10;

            // Check duplicate
            $exists = BioburdenUpload::where('prodline',   $prodline)
                ->where('batch',      $batch)
                ->where('runno',      $runno)
                ->where('datetested', $datetested)
                ->exists();

            if ($exists) { $skipped++; continue; }

            try {
                BioburdenUpload::create([
                    'prodline'     => $prodline,
                    'batch'        => $batch,
                    'prodname'     => $prodname,
                    'datetested'   => $datetested,
                    'runno'        => $runno,
                    'tamcr1'       => $tamcr1,
                    'tamcr2'       => $tamcr2,
                    'tymcr1'       => $tymcr1,
                    'tymcr2'       => $tymcr2,
                    'bbr_tamc_r1'  => $bbrTamcR1,
                    'bbr_tamc_r2'  => $bbrTamcR2,
                    'bbr_tymc_r1'  => $bbrTymcR1,
                    'bbr_tymc_r2'  => $bbrTymcR2,
                    'resultavg'    => $resultavg,
                    'limit'        => $limit,
                    'AddDate'      => now()->format('Y-m-d'),
                    'AddTime'      => now()->format('H:i:s'),
                    'AddUser'      => $addUser,
                    'Status'       => 'ACTIVE',
                ]);
                $inserted++;
            } catch (\Exception $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
                $skipped++;
            }
        }

        return [$inserted, $skipped, $errors];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function guessProdline(string $name): ?string
    {
        $n = strtoupper(trim(preg_replace('/\s+/', '', $name)));
        $known = ['SVP1','SVP2','SVP3','SVP4','BFS1','BFS2','BFS3','BFS4','BFS5',
                  'PPBOTTLE','SYRINGE','MEDIBAG'];
        return in_array($n, $known) ? $n : null;
    }

    private function looksLikeDate(string $value): bool
    {
        $v = trim($value);
        if (!preg_match('/\d/', $v)) return false;
        if (preg_match('/\d{1,2}[-\/]\w{2,3}[-\/]\d{2,4}/', $v)) return true;
        if (preg_match('/\d{4}[-\/]\d{2}[-\/]\d{2}/', $v)) return true;
        if (preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', $v)) return true;
        if (preg_match('/^\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4}$/', $v)) return true;
        if (preg_match('/^[A-Z]{1,3}\d{2}[A-Z]\d{3,}/i', $v)) return false;
        return false;
    }

    private function parseDate(mixed $raw, string $formatted = ''): ?string
    {
        if (is_numeric($raw) && $raw > 1000) {
            try {
                return ExcelDate::excelToDateTimeObject((float)$raw)->format('Y-m-d');
            } catch (\Exception $e) {}
        }

        $candidates = array_filter(array_unique([trim($formatted), trim((string)$raw)]));
        $formats    = ['d-M-y','d-M-Y','d/m/Y','d-m-Y','Y-m-d','j-M-y','j-M-Y','j M y','j M Y','d M y','d M Y'];

        foreach ($candidates as $str) {
            foreach ($formats as $fmt) {
                $dt = \DateTime::createFromFormat($fmt, $str);
                if ($dt) return $dt->format('Y-m-d');
            }
            try { return Carbon::parse($str)->format('Y-m-d'); } catch (\Exception $e) {}
        }

        return null;
    }

    private function toInt(mixed $value): int
    {
        if ($value === null || $value === '') return 0;
        return (int)round((float)$value);
    }
}
