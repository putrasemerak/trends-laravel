<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportBioburden2025 extends Command
{
    protected $signature = 'bioburden:import-2025
                            {--dry-run : Preview counts without inserting into database}
                            {--file= : Import a single specific file instead of all files}';

    protected $description = 'Import all BIOBURDEN_2025 Excel files into TS_0010 (MSSQL)';

    /**
     * Map Excel sheet names to prodline codes stored in TS_0010.
     * Trim is applied before lookup to handle trailing spaces in sheet names.
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

    public function handle(): int
    {
        $folder = base_path('BIOBURDEN_2025');

        if (!is_dir($folder)) {
            $this->error("Folder not found: {$folder}");
            $this->line("Please make sure BIOBURDEN_2025/ exists in the project root.");
            return 1;
        }

        // Step 1: Make sure the new BBR columns exist in TS_0010
        $this->ensureNewColumns();

        // Step 2: Collect files to process
        if ($this->option('file')) {
            $files = [base_path('BIOBURDEN_2025/' . $this->option('file'))];
        } else {
            $files = glob($folder . '/*.xlsx');
            // Skip Excel temp/lock files (start with ~$)
            $files = array_filter($files, fn($f) => !str_starts_with(basename($f), '~$'));
            sort($files);
        }

        if (empty($files)) {
            $this->warn("No .xlsx files found in {$folder}");
            return 0;
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE — No data will be inserted.');
        }

        $totalInserted = 0;
        $totalSkipped  = 0;
        $totalErrors   = 0;

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                continue;
            }

            $this->info('');
            $this->info('Reading: ' . basename($file));
            $this->line(str_repeat('-', 60));

            try {
                $spreadsheet = IOFactory::load($file);
            } catch (\Exception $e) {
                $this->error("  Cannot open file: " . $e->getMessage());
                continue;
            }

            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                $prodline = $this->getProdline($sheetName);

                if (!$prodline) {
                    $this->line("  Skipping sheet: '{$sheetName}' (not in prodline map)");
                    continue;
                }

                $sheet      = $spreadsheet->getSheetByName($sheetName);
                $highestRow = $sheet->getHighestDataRow();
                $inserted   = 0;
                $skipped    = 0;

                for ($row = 8; $row <= $highestRow; $row++) {
                    // Column B: datetested — skip row if empty
                    $dateCell = $sheet->getCell("B{$row}");
                    $dateRaw  = $dateCell->getValue();

                    if ($dateRaw === null || $dateRaw === '') {
                        continue;
                    }

                    // Silently skip rows where column B is clearly not a date
                    // (e.g. summary tables with batch numbers or repeated headers)
                    if (is_string($dateRaw) && !$this->looksLikeDate($dateRaw)) {
                        continue;
                    }

                    $datetested = $this->parseDate($dateRaw, $dateCell->getFormattedValue());

                    if (!$datetested) {
                        $this->warn("  [{$prodline}] Row {$row}: Cannot parse date '{$dateRaw}', skipping.");
                        $totalErrors++;
                        continue;
                    }

                    $batch    = trim((string)($sheet->getCell("D{$row}")->getValue() ?? ''));
                    $runno    = trim((string)($sheet->getCell("E{$row}")->getValue() ?? 'R1'));
                    $prodname = trim((string)($sheet->getCell("C{$row}")->getValue() ?? ''));

                    // Skip if batch is empty
                    if ($batch === '') continue;

                    // Bioburden Result Raw Data (columns F–I)
                    $tamcr1 = $this->toInt($sheet->getCell("F{$row}")->getValue());
                    $tamcr2 = $this->toInt($sheet->getCell("G{$row}")->getValue());
                    $tymcr1 = $this->toInt($sheet->getCell("H{$row}")->getValue());
                    $tymcr2 = $this->toInt($sheet->getCell("I{$row}")->getValue());

                    // Bioburden Result (columns J–M)
                    $bbr_tamc_r1 = $this->toInt($sheet->getCell("J{$row}")->getValue());
                    $bbr_tamc_r2 = $this->toInt($sheet->getCell("K{$row}")->getValue());
                    $bbr_tymc_r1 = $this->toInt($sheet->getCell("L{$row}")->getValue());
                    $bbr_tymc_r2 = $this->toInt($sheet->getCell("M{$row}")->getValue());

                    $resultavg = (float)($sheet->getCell("N{$row}")->getValue() ?? 0);
                    $limit     = (float)($sheet->getCell("O{$row}")->getValue() ?? 10);
                    if ($limit <= 0) $limit = 10;

                    // Skip duplicate records (same prodline + batch + runno + datetested)
                    $exists = DB::connection('sqlsrv')
                        ->table('TS_0010')
                        ->where('prodline', $prodline)
                        ->where('batch', $batch)
                        ->where('runno', $runno)
                        ->where('datetested', $datetested)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    if (!$this->option('dry-run')) {
                        DB::connection('sqlsrv')->table('TS_0010')->insert([
                            'prodline'     => $prodline,
                            'batch'        => $batch,
                            'prodname'     => $prodname,
                            'datetested'   => $datetested,
                            'runno'        => $runno,
                            'tamcr1'       => $tamcr1,
                            'tamcr2'       => $tamcr2,
                            'tymcr1'       => $tymcr1,
                            'tymcr2'       => $tymcr2,
                            'bbr_tamc_r1'  => $bbr_tamc_r1,
                            'bbr_tamc_r2'  => $bbr_tamc_r2,
                            'bbr_tymc_r1'  => $bbr_tymc_r1,
                            'bbr_tymc_r2'  => $bbr_tymc_r2,
                            'resultavg'    => $resultavg,
                            'limit'        => $limit,
                            'AddDate'      => now()->format('Y-m-d'),
                            'AddTime'      => now()->format('H:i:s'),
                            'AddUser'      => 'IMPORT_2025',
                            'Status'       => 'ACTIVE',
                        ]);
                    }

                    $inserted++;
                }

                $label = $this->option('dry-run') ? 'Would insert' : 'Inserted';
                $this->line(sprintf(
                    "  %-10s %s: %d | Skipped (duplicate): %d",
                    "[{$prodline}]",
                    $label,
                    $inserted,
                    $skipped
                ));

                $totalInserted += $inserted;
                $totalSkipped  += $skipped;
            }
        }

        $this->info('');
        $this->info('========================================');
        $this->info("Total inserted : {$totalInserted}");
        $this->info("Total skipped  : {$totalSkipped} (duplicates)");
        if ($totalErrors > 0) {
            $this->warn("Total errors   : {$totalErrors} (check warnings above)");
        }
        $this->info('========================================');

        return 0;
    }

    /**
     * Add the four new BBR columns to TS_0010 if they don't already exist.
     * Also ensures the unique index on (prodline, batch, runno, datetested).
     */
    private function ensureNewColumns(): void
    {
        $newColumns = ['bbr_tamc_r1', 'bbr_tamc_r2', 'bbr_tymc_r1', 'bbr_tymc_r2'];

        foreach ($newColumns as $col) {
            $result = DB::connection('sqlsrv')->selectOne(
                "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_NAME = 'TS_0010' AND COLUMN_NAME = ?",
                [$col]
            );

            if ($result->cnt == 0) {
                DB::connection('sqlsrv')->statement(
                    "ALTER TABLE TS_0010 ADD [{$col}] INT NULL DEFAULT 0"
                );
                $this->info("Added new column to TS_0010: {$col}");
            }
        }

        // Add unique index to prevent duplicate rows at DB level
        $indexName = 'UQ_TS0010_prodline_batch_runno_date';
        $indexExists = DB::connection('sqlsrv')->selectOne(
            "SELECT COUNT(*) AS cnt FROM sys.indexes
             WHERE name = ? AND object_id = OBJECT_ID('TS_0010')",
            [$indexName]
        );

        if ($indexExists->cnt == 0) {
            try {
                DB::connection('sqlsrv')->statement(
                    "CREATE UNIQUE INDEX [{$indexName}]
                     ON TS_0010 (prodline, batch, runno, datetested)"
                );
                $this->info("Created unique index on TS_0010 (prodline, batch, runno, datetested)");
            } catch (\Exception $e) {
                $this->warn("Could not create unique index (table may have existing duplicates): " . $e->getMessage());
                $this->warn("The import will still proceed using PHP-level duplicate checks.");
            }
        }
    }

    /**
     * Resolve sheet name (trimmed) to a prodline code.
     */
    private function getProdline(string $sheetName): ?string
    {
        return $this->sheetMap[trim($sheetName)] ?? null;
    }

    /**
     * Quick check: does this string look like it could be a date?
     * Rejects batch numbers, header text, etc.
     * Accepts: "01-Jan-25", "2025-01-01", "01/01/2025", Excel serials
     */
    private function looksLikeDate(string $value): bool
    {
        $v = trim($value);

        // Must contain at least one digit
        if (!preg_match('/\d/', $v)) return false;

        // Typical date patterns: contains -, / or is a month abbreviation
        if (preg_match('/\d{1,2}[-\/]\w{2,3}[-\/]\d{2,4}/', $v)) return true;
        if (preg_match('/\d{4}[-\/]\d{2}[-\/]\d{2}/', $v)) return true;
        if (preg_match('/\d{1,2}[-\/]\d{1,2}[-\/]\d{2,4}/', $v)) return true;
        // "01 MAY 25" or "01 MAY 2025" style
        if (preg_match('/^\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4}$/', $v)) return true;

        // Reject if it looks like a batch number (letters + digits, 6+ chars, no separators)
        if (preg_match('/^[A-Z]{1,3}\d{2}[A-Z]\d{3,}/i', $v)) return false;

        return false;
    }

    /**
     * Parse a date value from Excel.
     * Handles both Excel serial numbers and string formats.
     */
    private function parseDate(mixed $raw, string $formatted = ''): ?string
    {
        // Excel date serial number
        if (is_numeric($raw) && $raw > 1000) {
            try {
                $dt = ExcelDate::excelToDateTimeObject((float)$raw);
                return $dt->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        // String date — try formatted value first, then raw
        $candidates = array_filter(array_unique([trim($formatted), trim((string)$raw)]));

        $formats = ['d-M-y', 'd-M-Y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'j-M-y', 'j-M-Y', 'j M y', 'j M Y', 'd M y', 'd M Y'];

        foreach ($candidates as $str) {
            foreach ($formats as $fmt) {
                $dt = \DateTime::createFromFormat($fmt, $str);
                if ($dt && $dt->format($fmt) || $this->looseDateMatch($dt, $str, $fmt)) {
                    return $dt->format('Y-m-d');
                }
            }

            // Carbon fallback
            try {
                return Carbon::parse($str)->format('Y-m-d');
            } catch (\Exception $e) {
                // fall through
            }
        }

        return null;
    }

    private function looseDateMatch(mixed $dt, string $str, string $fmt): bool
    {
        if (!$dt) return false;
        // Allow partial format match (e.g. single-digit day)
        return $dt !== false;
    }

    private function toInt(mixed $value): int
    {
        if ($value === null || $value === '') return 0;
        return (int)round((float)$value);
    }
}
