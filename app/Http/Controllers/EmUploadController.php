<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\EmXlsParser;
use App\Models\EmFile;
use App\Models\EmPersonnel;
use App\Models\EmSurface;
use Carbon\Carbon;

class EmUploadController extends Controller
{
    // ─── Show upload form ────────────────────────────────────────────────────
    public function showForm()
    {
        return view('em.upload');
    }

    // ─── Preview (parse only, don't save) ───────────────────────────────────
    public function preview(Request $request)
    {
        $request->validate([
            'excel_file'   => 'required|file|max:20480',
            'machine_code' => 'required|string|max:20',
            'year'         => 'required|integer|min:2000|max:2100',
            'month'        => 'required|integer|min:1|max:12',
        ]);

        $path = $request->file('excel_file')->store('em_temp', 'local');
        $full = Storage::disk('local')->path($path);
        $full = str_replace('/', DIRECTORY_SEPARATOR, $full);

        try {
            $parser = (new EmXlsParser())->parse($full);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return back()->with('error', 'Parse error: ' . $e->getMessage())->withInput();
        }

        // Store temp path in session so the save step can reuse it
        session([
            'em_temp_file'     => $path,
            'em_machine_code'  => strtoupper(trim($request->machine_code)),
            'em_year'          => (int) $request->year,
            'em_month'         => (int) $request->month,
            'em_personnel_cnt' => count($parser->personnel),
            'em_surface_cnt'   => count($parser->surface),
            'em_anomaly_ph'    => count(array_filter($parser->personnel, fn($r) => $r['anomaly'])),
            'em_anomaly_surf'  => count(array_filter($parser->surface,   fn($r) => $r['anomaly'])),
            'em_preview_ph'    => array_slice($parser->personnel, 0, 20),
            'em_preview_surf'  => array_slice($parser->surface,   0, 20),
        ]);

        return redirect()->route('em.upload')->with('em_preview', true);
    }

    // ─── Save to database ────────────────────────────────────────────────────
    public function upload(Request $request)
    {
        $tempPath    = session('em_temp_file');
        $machineCode = session('em_machine_code');
        $year        = session('em_year');
        $month       = session('em_month');

        if (!$tempPath || !$machineCode) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Session expired. Please re-upload the file.'], 422);
            }
            return redirect()->route('em.upload')
                ->with('error', 'Session expired. Please re-upload the file.');
        }

        $full = Storage::disk('local')->path($tempPath);
        $full = str_replace('/', DIRECTORY_SEPARATOR, $full);
        if (!file_exists($full)) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Temporary file not found. Please re-upload.'], 422);
            }
            return redirect()->route('em.upload')
                ->with('error', 'Temporary file not found. Please re-upload.');
        }

        try {
            $parser = (new EmXlsParser())->parse($full);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['ok' => false, 'message' => 'Parse error: ' . $e->getMessage()], 500);
            }
            return redirect()->route('em.upload')
                ->with('error', 'Parse error: ' . $e->getMessage());
        }

        // Check if this machine+month already had data (replace scenario)
        $wasReplace = EmFile::where('machine_code', $machineCode)
            ->where('year', $year)
            ->where('month', $month)
            ->exists();

        DB::transaction(function () use ($parser, $machineCode, $year, $month, $request) {
            // Upsert em_files row (one per machine+month)
            $emFile = EmFile::updateOrCreate(
                ['machine_code' => $machineCode, 'year' => $year, 'month' => $month],
                [
                    'source_filename' => session('em_source_filename'),
                    'imported_by'     => Auth::user()?->EmpNo ?? null,
                ]
            );

            $fileId = $emFile->id;

            // Delete previous data for this file before re-importing
            EmPersonnel::where('em_file_id', $fileId)->delete();
            EmSurface::where('em_file_id', $fileId)->delete();

            // Bulk insert personnel rows
            if (!empty($parser->personnel)) {
                $rows = array_map(fn($r) => array_merge($r, [
                    'em_file_id' => $fileId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]), $parser->personnel);

                foreach (array_chunk($rows, 200) as $chunk) {
                    EmPersonnel::insert($chunk);
                }
            }

            // Bulk insert surface rows
            if (!empty($parser->surface)) {
                $rows = array_map(fn($r) => array_merge($r, [
                    'em_file_id' => $fileId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]), $parser->surface);

                foreach (array_chunk($rows, 200) as $chunk) {
                    EmSurface::insert($chunk);
                }
            }
        });

        // Clean up temp file
        Storage::disk('local')->delete($tempPath);
        session()->forget(['em_temp_file','em_machine_code','em_year','em_month',
                           'em_personnel_cnt','em_surface_cnt','em_anomaly_ph',
                           'em_anomaly_surf','em_preview_ph','em_preview_surf',
                           'em_source_filename']);

        $phCount   = count($parser->personnel);
        $surfCount = count($parser->surface);
        $anomPh    = count(array_filter($parser->personnel, fn($r) => $r['anomaly']));
        $anomSurf  = count(array_filter($parser->surface,   fn($r) => $r['anomaly']));

        if ($request->wantsJson()) {
            return response()->json([
                'results' => [
                    [
                        'type'     => 'personnel',
                        'label'    => 'Personnel Hygiene',
                        'inserted' => $phCount,
                        'anomaly'  => $anomPh,
                        'replaced' => $wasReplace,
                    ],
                    [
                        'type'     => 'surface',
                        'label'    => 'Surface / Air Sampling',
                        'inserted' => $surfCount,
                        'anomaly'  => $anomSurf,
                        'replaced' => $wasReplace,
                    ],
                ],
                'total_inserted' => $phCount + $surfCount,
                'total_skipped'  => 0,
                'machine_code'   => $machineCode,
                'year'           => $year,
                'month'          => $month,
            ]);
        }

        return redirect()->route('em.upload')
            ->with('success', __('app.em_import_ok') .
                " Personnel: $phCount rows. Surface/Air: $surfCount rows.");
    }
}

