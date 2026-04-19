<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Xls as XlsReader;

/**
 * Parses an Environmental Monitoring .xls file.
 *
 * Returns two arrays:
 *   ->personnel  — rows for em_personnel table
 *   ->surface    — rows for em_surface table
 *
 * Each row already has all DB-ready fields except em_file_id.
 */
class EmXlsParser
{
    // Column header keywords that identify a "Date" block header row
    private const PERSONNEL_PARAMS = ['f/dab', 'fdab', 'gar', 'garment'];
    private const SURFACE_PARAMS   = ['wall', 'floor', 'machine', 'nozzle', 'f/nozzle',
                                      'tank', 'loc 1', 'loc 2', 'loc 3', 'loc 4', 'loc 5'];
    private const LIMIT_KEYWORDS   = ['standard', 'std', 'action', 'alert', 'limit'];

    public array $personnel = [];
    public array $surface   = [];
    public array $errors    = [];

    /**
     * Parse the given XLS file path.
     * Returns $this for fluent usage.
     */
    public function parse(string $filePath): static
    {
        $reader = new XlsReader();
        $reader->setReadDataOnly(true);
        $xls = $reader->load($filePath);

        // Sheet1 — personnel hygiene + surface tests
        $sheet1 = $xls->getSheetByName('Sheet1');
        if ($sheet1) {
            $this->parseDataSheet($sheet1, 'sheet1');
        }

        // SP & AS lain2 — settle plate + active sampling
        $spas = $xls->getSheetByName('SP & AS lain2');
        if ($spas) {
            $this->parseDataSheet($spas, 'sp_as');
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    // Main sheet parser
    // -------------------------------------------------------------------------

    private function parseDataSheet($sheet, string $sheetSource): void
    {
        $maxRow = (int) $sheet->getHighestDataRow();
        $maxCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        // Build a 2D cell array [row][col] — cols are 1-indexed
        $cells = [];
        for ($r = 1; $r <= $maxRow; $r++) {
            for ($c = 1; $c <= $maxCol; $c++) {
                $cells[$r][$c] = trim($sheet->getCell(
                    Coordinate::stringFromColumnIndex($c) . $r
                )->getFormattedValue());
            }
        }

        // Discover all blocks in this sheet
        $blocks = $this->discoverBlocks($cells, $maxRow, $maxCol);

        foreach ($blocks as $block) {
            if ($block['is_personnel']) {
                $this->readPersonnelBlock($cells, $block, $sheetSource);
            } else {
                $this->readSurfaceBlock($cells, $block, $sheetSource);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Block discovery
    // -------------------------------------------------------------------------

    /**
     * Scan every row looking for a "Date" header cell.
     * A block header row must contain "date" AND at least one
     * data-column keyword nearby (personnel or surface param name).
     *
     * Returns array of block descriptors.
     */
    private function discoverBlocks(array $cells, int $maxRow, int $maxCol): array
    {
        $blocks = [];

        for ($r = 1; $r <= $maxRow; $r++) {
            $row = $cells[$r];

            // Find all "Date" cells in this row
            foreach ($row as $col => $val) {
                if (strtolower(trim($val)) !== 'date' && strtolower(trim($val)) !== 'date ') {
                    continue;
                }

                // Scan right from this col to find param headers
                $block = $this->buildBlock($cells, $r, $col, $maxRow, $maxCol);
                if ($block) {
                    $blocks[] = $block;
                }
            }
        }

        return $blocks;
    }

    /**
     * Given the column of a "Date" cell at row $headerRow,
     * read the adjacent headers and build a block descriptor.
     */
    private function buildBlock(array $cells, int $headerRow, int $dateCol,
                                int $maxRow, int $maxCol): ?array
    {
        $subHeaderRow = null;  // e.g. row with "Loc 1 | Loc 2 | …"
        $dataStartRow = null;

        // Look ahead up to 3 rows for a data row (first row with a date value)
        for ($r = $headerRow + 1; $r <= min($headerRow + 3, $maxRow); $r++) {
            $v = $cells[$r][$dateCol] ?? '';
            if ($this->looksLikeDate($v)) {
                $dataStartRow = $r;
                break;
            }
            // Treat as sub-header (e.g. SP & AS "Loc 1 | Loc 2 | …" row)
            if ($subHeaderRow === null && $this->rowHasContent($cells[$r] ?? [])) {
                $subHeaderRow = $r;
            }
        }

        if (!$dataStartRow) {
            return null;
        }

        // Collect column definitions scanning right from $dateCol+1
        $hasPic    = false;
        $valuesCols = [];       // [colIndex => label]
        $limitCols  = [];       // [colIndex => 'std'|'action'|'alert']

        // For SP/AS sheets the value cols appear on the sub-header row
        $scanRow = ($subHeaderRow !== null) ? $subHeaderRow : $headerRow;

        for ($c = $dateCol + 1; $c <= min($dateCol + 25, $maxCol); $c++) {
            $header = strtolower(trim($cells[$scanRow][$c] ?? ''));

            if ($header === '') continue;

            if ($header === 'pic' || $header === 'p/ic') {
                $hasPic = true;
                continue;
            }

            if ($this->isLimitKeyword($header)) {
                $limitCols[$c] = $this->classifyLimit($header);
                continue;
            }

            // If we hit "date" again it's a new block — stop
            if ($header === 'date' || $header === 'date ') {
                break;
            }

            // Otherwise treat as a value column
            $valuesCols[$c] = trim($cells[$scanRow][$c] ?? '');
        }

        if (empty($valuesCols)) {
            return null;
        }

        // Determine data end row (next blank date col, or next "Date" header row)
        $dataEndRow = $this->findBlockEnd($cells, $dataStartRow, $dateCol, $maxRow);

        // Is this a personnel block?
        $isPersonnel = $hasPic || $this->valueColsArePersonnel($valuesCols);

        // Determine param label for personnel blocks (first value col header)
        $paramLabel = $isPersonnel
            ? ($cells[$headerRow][$dateCol + ($hasPic ? 2 : 1)] ?? reset($valuesCols))
            : null;

        // Room label: scan far-right of the last data row
        $roomLabel = $this->findRoomLabel($cells, $dataEndRow, $dateCol, $maxCol);

        return [
            'header_row'   => $headerRow,
            'sub_hdr_row'  => $subHeaderRow,
            'data_start'   => $dataStartRow,
            'data_end'     => $dataEndRow,
            'date_col'     => $dateCol,
            'pic_col'      => $hasPic ? ($dateCol + 1) : null,
            'values_cols'  => $valuesCols,  // [colIdx => label]
            'limit_cols'   => $limitCols,   // [colIdx => type]
            'is_personnel' => $isPersonnel,
            'param_label'  => $paramLabel,
            'room_label'   => $roomLabel,
        ];
    }

    // -------------------------------------------------------------------------
    // Block readers
    // -------------------------------------------------------------------------

    private function readPersonnelBlock(array $cells, array $block, string $src): void
    {
        [$stdLimit, $actionLimit, $alertLimit] = $this->extractLimitsFromBlock(
            $cells, $block['data_start'], $block['limit_cols']
        );

        // For personnel blocks there's typically ONE value col (the reading itself)
        $valueCol = array_key_first($block['values_cols']);

        // Determine test_type from param label or value col header
        $label     = strtolower(trim($block['param_label'] ?? reset($block['values_cols']) ?? ''));
        $testType  = $this->classifyTestType($label, true);

        $lastDate = null;

        for ($r = $block['data_start']; $r <= $block['data_end']; $r++) {
            $dateVal = $cells[$r][$block['date_col']] ?? '';
            $picVal  = $block['pic_col'] ? ($cells[$r][$block['pic_col']] ?? '') : '';
            $readVal = $cells[$r][$valueCol] ?? '';

            // Limit row override (first and last rows usually have limits)
            if (!empty($block['limit_cols'])) {
                [$ro, $ao, $alo] = $this->rowLimits($cells[$r], $block['limit_cols']);
                if ($ro !== null) { $stdLimit = $ro; $actionLimit = $ao; $alertLimit = $alo; }
            }

            // Date carry-forward
            if ($this->looksLikeDate($dateVal)) {
                $lastDate = $this->parseDate($dateVal);
            }
            if (!$lastDate) continue;
            if (trim($picVal) === '' && trim($readVal) === '') continue;

            $isNa  = strtoupper(trim($readVal)) === 'NA';
            $value = $isNa ? null : (is_numeric(str_replace(',', '.', $readVal))
                           ? (float) str_replace(',', '.', $readVal) : null);

            $anomaly = false;
            if (!$isNa && $value !== null) {
                $thresh = $alertLimit ?? $actionLimit ?? $stdLimit;
                if ($thresh !== null) $anomaly = $value > $thresh;
            }

            $this->personnel[] = [
                'test_type'    => $testType,
                'sample_date'  => $lastDate,
                'emp_no'       => $picVal,
                'result'       => $value,
                'is_na'        => $isNa,
                'std_limit'    => $stdLimit,
                'action_limit' => $actionLimit,
                'alert_limit'  => $alertLimit,
                'anomaly'      => $anomaly,
            ];
        }
    }

    private function readSurfaceBlock(array $cells, array $block, string $src): void
    {
        [$stdLimit, $actionLimit, $alertLimit] = $this->extractLimitsFromBlock(
            $cells, $block['data_start'], $block['limit_cols']
        );

        // Determine base test_type from first value col label
        $firstLabel = strtolower(trim(reset($block['values_cols']) ?: ''));
        $baseTestType = $this->classifyTestType($firstLabel, false);

        $roomLabel = $block['room_label'];

        for ($r = $block['data_start']; $r <= $block['data_end']; $r++) {
            $dateVal = $cells[$r][$block['date_col']] ?? '';
            if (!$this->looksLikeDate($dateVal)) continue;

            $sampleDate = $this->parseDate($dateVal);
            if (!$sampleDate) continue;

            // Limit row override
            if (!empty($block['limit_cols'])) {
                [$ro, $ao, $alo] = $this->rowLimits($cells[$r], $block['limit_cols']);
                if ($ro !== null) { $stdLimit = $ro; $actionLimit = $ao; $alertLimit = $alo; }
            }

            // One surface row per value column
            foreach ($block['values_cols'] as $col => $colLabel) {
                $readVal = $cells[$r][$col] ?? '';

                $isNa  = strtoupper(trim($readVal)) === 'NA';
                $value = $isNa ? null : (is_numeric(str_replace(',', '.', $readVal))
                               ? (float) str_replace(',', '.', $readVal) : null);

                if (!$isNa && $value === null) continue; // empty cell, skip

                // Derive test_type per column independently (Wall vs Floor in same block)
                $colTestType = $this->classifyTestType(strtolower(trim($colLabel)), false);
                if ($colTestType === 'surface') {
                    $colTestType = $baseTestType; // fall back to block-level type
                }

                $anomaly = false;
                if (!$isNa && $value !== null) {
                    $thresh = $alertLimit ?? $actionLimit ?? $stdLimit;
                    if ($thresh !== null) $anomaly = $value > $thresh;
                }

                $this->surface[] = [
                    'sheet_source'   => $src,
                    'test_type'      => $colTestType,
                    'location_label' => trim($colLabel),
                    'room_label'     => $roomLabel,
                    'sample_date'    => $sampleDate,
                    'result'         => $value,
                    'is_na'          => $isNa,
                    'std_limit'      => $stdLimit,
                    'action_limit'   => $actionLimit,
                    'alert_limit'    => $alertLimit,
                    'anomaly'        => $anomaly,
                ];
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function findBlockEnd(array $cells, int $dataStart, int $dateCol, int $maxRow): int
    {
        $end = $dataStart;
        for ($r = $dataStart; $r <= $maxRow; $r++) {
            $v = trim($cells[$r][$dateCol] ?? '');
            // Stop at blank date col followed by more blank rows, or at a new "Date" header
            if ($v === '') {
                // Peek next row — if also blank stop here
                $next = trim($cells[$r + 1][$dateCol] ?? '');
                if ($next === '' || strtolower($next) === 'date' || strtolower($next) === 'date ') {
                    break;
                }
                // Non-blank date in next row — might be carry-forward, keep going
                continue;
            }
            if (strtolower($v) === 'date' || strtolower($v) === 'date ') {
                break;
            }
            $end = $r;
        }
        return $end;
    }

    /** Read limits from the first data row of a block */
    private function extractLimitsFromBlock(array $cells, int $firstRow, array $limitCols): array
    {
        return $this->rowLimits($cells[$firstRow] ?? [], $limitCols);
    }

    /** Extract std/action/alert from a single row using pre-mapped limit cols */
    private function rowLimits(array $row, array $limitCols): array
    {
        $std = $action = $alert = null;
        foreach ($limitCols as $col => $type) {
            $v = trim($row[$col] ?? '');
            if (!is_numeric($v)) continue;
            $v = (float) $v;
            if ($type === 'std')    $std    = $v;
            if ($type === 'action') $action = $v;
            if ($type === 'alert')  $alert  = $v;
        }
        return [$std, $action, $alert];
    }

    /** Check far-right cells on the last data row for a room label text */
    private function findRoomLabel(array $cells, int $lastRow, int $dateCol, int $maxCol): ?string
    {
        $row = $cells[$lastRow] ?? [];
        // Also check 1-2 rows after for stray label cells
        foreach ([$lastRow, $lastRow + 1, $lastRow + 2] as $r) {
            $checkRow = $cells[$r] ?? [];
            foreach ($checkRow as $col => $val) {
                if ($col <= $dateCol + 20) continue; // skip data area
                $v = trim($val);
                if ($v !== '' && strlen($v) > 5 && !is_numeric($v) && !$this->looksLikeDate($v)) {
                    // Looks like a text label — clean it up
                    $clean = preg_replace('/\s+/', ' ', $v);
                    if (stripos($clean, 'page') === false) {
                        return $clean;
                    }
                }
            }
        }
        return null;
    }

    private function rowHasContent(array $row): bool
    {
        foreach ($row as $v) {
            if (trim($v) !== '') return true;
        }
        return false;
    }

    private function isLimitKeyword(string $header): bool
    {
        foreach (self::LIMIT_KEYWORDS as $kw) {
            if (str_contains($header, $kw)) return true;
        }
        return false;
    }

    private function classifyLimit(string $header): string
    {
        if (str_contains($header, 'action')) return 'action';
        if (str_contains($header, 'alert'))  return 'alert';
        return 'std';
    }

    private function valueColsArePersonnel(array $valuesCols): bool
    {
        foreach ($valuesCols as $label) {
            $l = strtolower(trim($label));
            foreach (self::PERSONNEL_PARAMS as $kw) {
                if (str_contains($l, $kw)) return true;
            }
        }
        return false;
    }

    private function classifyTestType(string $label, bool $isPersonnel): string
    {
        if ($isPersonnel) {
            if (str_contains($label, 'dab'))     return 'fdab';
            if (str_contains($label, 'gar'))     return 'garment';
            return 'personnel';
        }
        if (str_contains($label, 'wall'))        return 'wall';
        if (str_contains($label, 'floor'))       return 'floor';
        if (str_contains($label, 'machine'))     return 'machine';
        if (str_contains($label, 'nozzle'))      return 'nozzle';
        if (str_contains($label, 'tank'))        return 'tank';
        if (str_contains($label, 'settle'))      return 'settle_plate';
        if (str_contains($label, 'active'))      return 'active_sampling';
        if (str_starts_with($label, 'loc'))      return 'air_loc';
        return 'surface';
    }

    private function looksLikeDate(string $v): bool
    {
        if ($v === '') return false;
        // DD.MM.YYYY or DD/MM/YYYY or YYYY-MM-DD
        return (bool) preg_match('/^\d{1,2}[.\/-]\d{1,2}[.\/-]\d{2,4}$/', $v)
            || (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v);
    }

    private function parseDate(string $v): ?string
    {
        if ($v === '') return null;
        // DD.MM.YYYY → YYYY-MM-DD
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }
        return null;
    }
}
