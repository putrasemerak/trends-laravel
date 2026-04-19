@extends('layouts.app')

@section('title', 'Upload Bioburden Data')

@section('content')
<div class="container mt-3 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- ===== STEP 1: FILE SELECTION ===== --}}
            <div class="card mb-4" id="step1Card">
                <div class="card-header">
                    <i class="bi bi-cloud-arrow-up-fill"></i> {{ __('app.upload_title') }}
                </div>
                <div class="card-body">

                    <div class="alert alert-info py-2 mb-3" style="font-size:12px;">
                        <i class="bi bi-info-circle"></i>
                        <strong>{{ __('app.upload_info') }}</strong>
                    </div>

                    <div class="form-group mb-3">
                        <label><strong>{{ __('app.upload_label') }}</strong></label>
                        <div id="dropZone">
                            <input type="file" id="upload_file" accept=".xlsx,.xls" class="d-none">

                            <div class="drop-area text-center" id="dropArea">
                                <i class="bi bi-file-earmark-excel" style="font-size:2.5rem; color:var(--text-muted);"></i>
                                <p class="mb-1 mt-2" style="font-size:13px;">
                                    {{ __('app.upload_drop') }} <a href="#" id="browseLink">{{ __('app.upload_browse') }}</a>
                                </p>
                                <small class="text-muted">{{ __('app.upload_accepted') }}</small>
                            </div>

                            <div class="file-selected text-center d-none" id="fileInfo">
                                <i class="bi bi-file-earmark-check" style="font-size:1.8rem; color:#27ae60;"></i>
                                <p class="mb-0 mt-1" style="font-size:13px; font-weight:600;" id="fileNameLabel"></p>
                                <small class="text-muted" id="fileSizeLabel"></small>
                                <br><a href="#" id="removeFile" style="font-size:11px; color:#e74c3c;">{{ __('app.upload_remove') }}</a>
                            </div>
                        </div>
                    </div>

                    <button id="btnPreview" class="btn btn-preview btn-block" disabled>
                        <i class="bi bi-eye"></i> {{ __('app.upload_preview') }}
                    </button>

                </div>
            </div>

            {{-- ===== STEP 2: PREVIEW + CONFIRM ===== --}}
            <div id="step2Section" class="d-none">

                {{-- preview loading is now the #overlayPreview modal below --}}

                <div id="previewContent" class="d-none">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0"><i class="bi bi-table"></i> Preview — <span id="previewFileName"></span></h6>
                        <span class="badge badge-primary" id="previewTotalBadge"></span>
                    </div>

                    <div id="sheetAccordion"></div>

                    <form method="POST" action="{{ route('bioburden.smart-upload.post') }}"
                          enctype="multipart/form-data" id="confirmForm">
                        @csrf
                        <input type="file" id="confirmFileInput" name="upload_file" class="d-none">


                        <div class="d-flex mt-4 mb-5" style="gap:10px;">
                            <button type="button" id="btnBack" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> {{ __('app.upload_change') }}
                            </button>
                            <button type="submit" id="btnConfirm" class="btn btn-success flex-grow-1">
                                <i class="bi bi-check-circle"></i> {{ __('app.upload_confirm') }}
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            {{-- ===== STEP 3: IMPORT RESULTS (rendered by JS after XHR) ===== --}}
            <div id="importResultsSection" class="d-none">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard-check"></i> {{ __('app.res_import_title') }}</span>
                        <span id="resultsBadges"></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th class="pl-3">{{ __('app.res_sheet') }}</th>
                                    <th>{{ __('app.res_line') }}</th>
                                    <th class="text-center">{{ __('app.res_inserted') }}</th>
                                    <th class="text-center">{{ __('app.res_duplicate') }}</th>
                                    <th class="text-center">{{ __('app.res_incomplete') }}</th>
                                    <th>{{ __('app.res_status') }}</th>
                                </tr>
                            </thead>
                            <tbody id="resultsTableBody"></tbody>
                            <tfoot id="resultsTableFoot"></tfoot>
                        </table>
                    </div>
                    <div class="card-footer text-right" style="background:none;">
                        <button type="button" id="btnNewUpload" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-repeat"></i> {{ __('app.upload_new') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- ===== FALLBACK: session flash results (standard form submit) ===== --}}
            @if(session('upload_results'))
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-clipboard-check"></i> Import Results</span>
                    <span>
                        <span class="badge badge-success mr-1">{{ session('total_inserted') }} inserted</span>
                        @if(session('total_skipped') > 0)
                            <span class="badge badge-secondary">{{ session('total_skipped') }} skipped</span>
                        @endif
                    </span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:13px;">
                        <thead>
                            <tr>
                                <th class="pl-3">Sheet</th>
                                <th>Line</th>
                                <th class="text-center">Inserted</th>
                                <th class="text-center">Duplicate</th>
                                <th class="text-center">Incomplete</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(session('upload_results') as $r)
                            <tr>
                                <td class="pl-3">{{ $r['sheet'] }}</td>
                                <td>
                                    @if($r['prodline'])
                                        <span class="badge badge-secondary">{{ $r['prodline'] }}</span>
                                    @else <span class="text-muted">—</span> @endif
                                </td>
                                <td class="text-center font-weight-bold {{ $r['inserted'] > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ $r['ignored'] ? '—' : $r['inserted'] }}
                                </td>
                                <td class="text-center {{ ($r['skipped_dupe'] ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">
                                    {{ $r['ignored'] ? '—' : (($r['skipped_dupe'] ?? 0) ?: '—') }}
                                </td>
                                <td class="text-center {{ ($r['skipped_incomplete'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                    {{ $r['ignored'] ? '—' : (($r['skipped_incomplete'] ?? 0) ?: '—') }}
                                </td>
                                <td style="font-size:11px;">
                                    @if($r['ignored'])
                                        <span class="text-muted">Sheet not recognised</span>
                                    @elseif(!empty($r['errors']))
                                        <span class="text-danger">{{ implode('; ', array_slice($r['errors'], 0, 2)) }}</span>
                                    @elseif($r['inserted'] === 0 && ($r['skipped_dupe'] ?? 0) > 0 && ($r['skipped_incomplete'] ?? 0) === 0)
                                        <span class="text-muted">All already in DB</span>
                                    @elseif($r['inserted'] === 0 && ($r['skipped_incomplete'] ?? 0) > 0)
                                        <span class="text-danger">No valid rows found</span>
                                    @else
                                        <span class="text-success"><i class="bi bi-check-circle"></i> OK</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="font-weight:600; border-top:2px solid var(--border-color);">
                                <td class="pl-3" colspan="2">Total</td>
                                <td class="text-center text-success">{{ session('total_inserted') }}</td>
                                <td class="text-center" colspan="2">{{ session('total_skipped') ?: '—' }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- ===== OVERLAY: Preview scanning ===== --}}
<div id="overlayPreview" class="upload-overlay d-none" role="status" aria-live="polite">
    <div class="upload-overlay-card">
        <div class="overlay-brand">
            <i class="bi bi-graph-up"></i> Laboratory Trending Analysis
        </div>
        <div class="overlay-icon-wrap mb-3">
            <i class="bi bi-file-earmark-spreadsheet overlay-icon"></i>
        </div>
        <div class="overlay-title mb-1">{{ __('app.ovl_scan_title') }}</div>
        <div class="overlay-sub mb-3" id="previewStepLabel">{{ __('app.ovl_scan_reading') }}</div>
        <div class="progress overlay-progress">
            <div id="previewProgressBar"
                 class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                 role="progressbar" style="width:0%">
            </div>
        </div>
        <div class="overlay-pct mt-1" id="previewPct">0%</div>
    </div>
</div>

{{-- ===== OVERLAY: Saving to database ===== --}}
<div id="overlayImport" class="upload-overlay d-none" role="status" aria-live="polite">
    <div class="upload-overlay-card">
        <div class="overlay-brand">
            <i class="bi bi-graph-up"></i> Laboratory Trending Analysis
        </div>
        <div class="overlay-icon-wrap mb-3">
            <i class="bi bi-database-up overlay-icon" id="importOverlayIcon"></i>
        </div>
        <div class="overlay-title mb-1" id="importOverlayTitle">{{ __('app.ovl_save_title') }}</div>
        <div class="overlay-sub mb-3" id="importStepLabel">{{ __('app.ovl_save_uploading') }}</div>
        <div class="progress overlay-progress">
            <div id="progressBar"
                 class="progress-bar progress-bar-striped progress-bar-animated"
                 role="progressbar" style="width:0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
        <div class="overlay-pct mt-1" id="progressPct">0%</div>
    </div>
</div>
@endsection

@push('styles')
<style>
.drop-area {
    border: 2px dashed var(--border-color);
    border-radius: 8px;
    padding: 28px 20px;
    cursor: pointer;
    transition: border-color .2s, background .2s;
}
.drop-area.dragover { border-color: #5b9bd5; background: rgba(91,155,213,.07); }
.file-selected { border: 1px solid #27ae60; border-radius: 8px; padding: 16px; }
.sheet-preview-table th { font-size: 10px; text-transform: uppercase; background: var(--table-head-bg, #f8f9fa); }
.sheet-preview-table td { font-size: 11px; }
.sheet-badge-ignored { opacity: .5; }
.accordion .card-header { padding: 0; }
.accordion .card-header button {
    width: 100%; text-align: left; padding: 10px 14px;
    font-size: 13px; font-weight: 600; border: none;
    background: none; color: var(--text-body);
}
.accordion .card-header button:focus { outline: none; box-shadow: none; }

/* ---- Preview button ---- */
.btn-preview {
    background: #0e9f8e;
    color: #fff;
    border: none;
    font-weight: 700;
    letter-spacing: .02em;
    font-size: .9rem;
    padding: .6rem 1.25rem;
    transition: background .2s, box-shadow .2s, transform .1s;
}
.btn-preview:hover:not(:disabled) {
    background: #0cc5af;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(14,159,142,.25), 0 4px 14px rgba(14,159,142,.35);
    transform: translateY(-1px);
}
.btn-preview:active:not(:disabled) {
    background: #0a7d6e;
    transform: translateY(0);
    box-shadow: none;
}
.btn-preview:disabled {
    background: #0e9f8e;
    opacity: .38;
    cursor: not-allowed;
}
[data-theme="dark"] .btn-preview {
    background: #0fb39e;
}
[data-theme="dark"] .btn-preview:hover:not(:disabled) {
    background: #13d4bb;
    box-shadow: 0 0 0 4px rgba(15,179,158,.3), 0 4px 18px rgba(15,179,158,.4);
}

/* ---- Confirm / Save button ---- */
#btnConfirm {
    font-weight: 700;
    letter-spacing: .02em;
    font-size: .95rem;
    padding: .65rem 1.5rem;
    transition: background .2s, box-shadow .2s, transform .1s;
}
#btnConfirm:hover:not(:disabled) {
    background-color: #1dbd5e;
    border-color: #1aaa55;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(40,167,69,.25), 0 4px 14px rgba(40,167,69,.35);
    transform: translateY(-1px);
}
#btnConfirm:active:not(:disabled) {
    background-color: #157a38;
    transform: translateY(0);
    box-shadow: none;
}
[data-theme="dark"] #btnConfirm {
    background-color: #1e9e4f;
    border-color: #1a8f46;
    color: #fff;
}
[data-theme="dark"] #btnConfirm:hover:not(:disabled) {
    background-color: #24c060;
    border-color: #20ad57;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(36,192,96,.3), 0 4px 18px rgba(36,192,96,.45);
    transform: translateY(-1px);
}
[data-theme="dark"] #btnConfirm:active:not(:disabled) {
    background-color: #178040;
    transform: translateY(0);
    box-shadow: none;
}

/* ---- Centered overlay ---- */
.upload-overlay {
    position: fixed;
    inset: 0;
    z-index: 1060;
    background: rgba(0,0,0,.55);
    backdrop-filter: blur(3px);
    display: flex;
    align-items: center;
    justify-content: center;
    animation: overlayFadeIn .2s ease;
}
.upload-overlay.d-none { display: none !important; }
@keyframes overlayFadeIn { from { opacity:0; } to { opacity:1; } }
.upload-overlay-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 36px 40px 28px;
    min-width: 320px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
    animation: cardSlideUp .25s ease;
}
@keyframes cardSlideUp { from { transform: translateY(18px); opacity:0; } to { transform: translateY(0); opacity:1; } }
.overlay-icon-wrap {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(91,155,213,.18) 0%, rgba(91,155,213,.08) 100%);
    border: 2px solid rgba(91,155,213,.35);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 18px rgba(91,155,213,.2);
}
.overlay-icon { font-size: 2.1rem; color: #5b9bd5; }
.overlay-icon.success { color: #27ae60; }
.overlay-brand {
    font-size: 11px; font-weight: 700; letter-spacing: .06em;
    text-transform: uppercase; color: #5b9bd5;
    margin-bottom: 18px; opacity: .85;
}
.overlay-brand i { font-size: 13px; margin-right: 4px; }
.overlay-title { font-size: 15px; font-weight: 700; color: var(--text-body); }
.overlay-sub { font-size: 12px; color: var(--text-muted); min-height: 18px; }
.overlay-progress { height: 8px; border-radius: 4px; overflow: hidden; }
.overlay-pct { font-size: 11px; color: var(--text-muted); font-weight: 600; }
</style>
@endpush

@push('scripts')
<link rel="stylesheet" href="{{ asset('assets/datatables/jquery.dataTables.min.css') }}">
<script src="{{ asset('assets/datatables/jquery-3.5.1.js') }}"></script>
<script src="{{ asset('assets/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('assets/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('assets/datatables/jszip.min.js') }}"></script>
<script type="application/json" id="blade-i18n">{!! json_encode([
    'col_date'           => __('app.col_date'),
    'col_product'        => __('app.col_product'),
    'col_batch'          => __('app.col_batch'),
    'col_run'            => __('app.col_run'),
    'col_avg'            => __('app.col_avg'),
    'res_sheet'          => __('app.res_sheet'),
    'res_line'           => __('app.res_line'),
    'res_inserted'       => __('app.res_inserted'),
    'res_duplicate'      => __('app.res_duplicate'),
    'res_incomplete'     => __('app.res_incomplete'),
    'res_status'         => __('app.res_status'),
    'res_total'          => __('app.res_total'),
    'res_dupe_label'     => __('app.res_dupe_label'),
    'lbl_saving'         => __('app.lbl_saving'),
    'ovl_scan_title'     => __('app.ovl_scan_title'),
    'ovl_scan_reading'   => __('app.ovl_scan_reading'),
    'ovl_scan_header'    => __('app.ovl_scan_header'),
    'ovl_scan_layout'    => __('app.ovl_scan_layout'),
    'ovl_scan_extract'   => __('app.ovl_scan_extract'),
    'ovl_scan_prepare'   => __('app.ovl_scan_prepare'),
    'ovl_scan_done'      => __('app.ovl_scan_done'),
    'ovl_save_title'     => __('app.ovl_save_title'),
    'ovl_save_uploading' => __('app.ovl_save_uploading'),
    'ovl_save_processing'=> __('app.ovl_save_processing'),
    'ovl_save_complete'  => __('app.ovl_save_complete'),
    'ovl_save_done'      => __('app.ovl_save_done'),
    'ovl_err_read'       => __('app.ovl_err_read'),
    'ovl_err_server'     => __('app.ovl_err_server'),
    'ovl_err_response'   => __('app.ovl_err_response'),
    'ovl_err_network'    => __('app.ovl_err_network'),
    'lbl_rows_across'    => __('app.lbl_rows_across'),
    'lbl_sheets'         => __('app.lbl_sheets'),
    'lbl_anomaly'        => __('app.lbl_anomaly'),
    'lbl_loading_records'=> __('app.lbl_loading_records'),
    'lbl_failed_load'    => __('app.lbl_failed_load'),
    'lbl_search'         => __('app.lbl_search'),
    'lbl_show'           => __('app.lbl_show'),
    'lbl_info'           => __('app.lbl_info'),
]) !!}</script>
<script>
var __t = JSON.parse(document.getElementById('blade-i18n').textContent);
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    var fileInput       = document.getElementById('upload_file');
    var dropArea        = document.getElementById('dropArea');
    var dropZone        = document.getElementById('dropZone');
    var fileInfo        = document.getElementById('fileInfo');
    var fileNameLabel   = document.getElementById('fileNameLabel');
    var fileSizeLabel   = document.getElementById('fileSizeLabel');
    var browseLink      = document.getElementById('browseLink');
    var removeFile      = document.getElementById('removeFile');
    var btnPreview      = document.getElementById('btnPreview');
    var step2Section    = document.getElementById('step2Section');
    var previewLoading  = document.getElementById('previewLoading');
    var previewContent  = document.getElementById('previewContent');
    var sheetAccordion  = document.getElementById('sheetAccordion');
    var previewFileName = document.getElementById('previewFileName');
    var previewTotalBadge = document.getElementById('previewTotalBadge');
    var btnBack         = document.getElementById('btnBack');
    var btnConfirm      = document.getElementById('btnConfirm');
    var confirmForm     = document.getElementById('confirmForm');
    var confirmFileInput = document.getElementById('confirmFileInput');

    /* ---- Overlay elements ---- */
    var overlayPreview    = document.getElementById('overlayPreview');
    var previewProgressBar = document.getElementById('previewProgressBar');
    var previewStepLabel  = document.getElementById('previewStepLabel');
    var previewPctEl      = document.getElementById('previewPct');

    var overlayImport     = document.getElementById('overlayImport');
    var importOverlayIcon  = document.getElementById('importOverlayIcon');
    var importOverlayTitle = document.getElementById('importOverlayTitle');
    var importStepLabel   = document.getElementById('importStepLabel');
    var progressBar       = document.getElementById('progressBar');
    var progressPct       = document.getElementById('progressPct');

    var selectedFile = null;

    /* ---- File selection ---- */
    browseLink.addEventListener('click', function (e) { e.preventDefault(); fileInput.click(); });
    dropArea.addEventListener('click', function () { fileInput.click(); });

    ['dragenter','dragover'].forEach(function (e) {
        dropZone.addEventListener(e, function (ev) { ev.preventDefault(); dropArea.classList.add('dragover'); });
    });
    ['dragleave','drop'].forEach(function (e) {
        dropZone.addEventListener(e, function (ev) { ev.preventDefault(); dropArea.classList.remove('dragover'); });
    });
    dropZone.addEventListener('drop', function (e) {
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener('change', function () {
        if (this.files.length) setFile(this.files[0]);
    });
    removeFile.addEventListener('click', function (e) { e.preventDefault(); clearFile(); });
    btnBack.addEventListener('click', function () { step2Section.classList.add('d-none'); clearFile(); });

    function setFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (!['xlsx','xls'].includes(ext)) { alert('Only .xlsx and .xls files are accepted.'); return; }
        if (file.size > 10 * 1024 * 1024)  { alert('File exceeds 10MB limit.'); return; }
        selectedFile = file;
        fileNameLabel.textContent = file.name;
        fileSizeLabel.textContent = formatSize(file.size);
        dropArea.classList.add('d-none');
        fileInfo.classList.remove('d-none');
        btnPreview.disabled = false;
    }

    function clearFile() {
        selectedFile = null;
        fileInput.value = '';
        dropArea.classList.remove('d-none');
        fileInfo.classList.add('d-none');
        btnPreview.disabled = true;
        btnPreview.innerHTML = '<i class="bi bi-eye"></i> Preview Data';
        step2Section.classList.add('d-none');
    }

    /* ---- Preview ---- */
    btnPreview.addEventListener('click', function () {
        if (!selectedFile) return;
        step2Section.classList.remove('d-none');
        previewContent.classList.add('d-none');
        btnPreview.disabled = true;
        btnPreview.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Reading...';

        /* Show preview overlay with animated progress */
        showOverlayPreview();

        var formData = new FormData();
        formData.append('upload_file', selectedFile);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch('{{ route("bioburden.smart-upload.preview") }}', {
            method: 'POST',
            body: formData
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.error) {
                hideOverlayPreview();
                alert(data.error);
                resetPreviewBtn();
                return;
            }
            /* Finish bar at 100% then reveal content */
            setPreviewProgress(100, __t.ovl_scan_done);
            setTimeout(function () {
                hideOverlayPreview();
                renderPreview(data.sheets);
            }, 500);
        })
        .catch(function () {
            hideOverlayPreview();
        alert(__t.ovl_err_read);
            resetPreviewBtn();
        });
    });

    function showOverlayPreview() {
        overlayPreview.classList.remove('d-none');
        setPreviewProgress(0, __t.ovl_scan_reading);
        var steps = [
            [15, 400,  __t.ovl_scan_header],
            [40, 900,  __t.ovl_scan_layout],
            [65, 1600, __t.ovl_scan_extract],
            [85, 2400, __t.ovl_scan_prepare],
        ];
        steps.forEach(function (s) {
            setTimeout(function () {
                if (!overlayPreview.classList.contains('d-none')) {
                    setPreviewProgress(s[0], s[2]);
                }
            }, s[1]);
        });
    }

    function hideOverlayPreview() {
        overlayPreview.classList.add('d-none');
    }

    function setPreviewProgress(pct, label) {
        previewProgressBar.style.width = pct + '%';
        if (label) previewStepLabel.textContent = label;
        previewPctEl.textContent = pct + '%';
    }

    function resetPreviewBtn() {
        btnPreview.disabled = false;
        btnPreview.innerHTML = '<i class="bi bi-eye"></i> Preview Data';
    }

    /* ---- Render preview ---- */
    var previewDtInstances = {}; // track DataTable instances per sheet
    function renderPreview(sheets) {
        previewContent.classList.remove('d-none');
        btnPreview.innerHTML = '<i class="bi bi-eye"></i> Preview Data';
        btnPreview.disabled = false;

        var totalRows = 0;
        var activeSheets = sheets.filter(function (s) { return !s.ignored && s.total > 0; });
        sheets.forEach(function (s) { if (!s.ignored) totalRows += s.total; });

        previewFileName.textContent = selectedFile.name;
        previewTotalBadge.textContent = totalRows + ' ' + __t.lbl_rows_across + ' ' + activeSheets.length + ' ' + __t.lbl_sheets;

        var html = '<div class="accordion" id="acc">';
        sheets.forEach(function (s, idx) {
            var isIgnored = s.ignored || s.total === 0;
            var headClass = isIgnored ? 'bg-light text-muted' : '';
            var collapseShow = (!isIgnored && idx === 0) ? 'show' : ''; // only first open

            html += '<div class="card mb-1 ' + (isIgnored ? 'sheet-badge-ignored' : '') + '">';
            html += '<div class="card-header ' + headClass + '">';
            html += '<button type="button" data-toggle="collapse" data-target="#sheet' + idx + '">';
            html +=   '<i class="bi bi-table mr-1"></i> ' + escHtml(s.sheet);
            if (s.prodline) html += ' <span class="badge badge-secondary ml-1">' + escHtml(s.prodline) + '</span>';
            if (!isIgnored) {
                var anomalyCount = s.rows.filter(function(r){ return parseFloat(r.resultavg) >= 10; }).length;
                html += ' <span class="badge badge-info ml-1">' + s.total + ' rows</span>';
                if (anomalyCount > 0) html += ' <span class="badge badge-danger ml-1"><i class="bi bi-exclamation-triangle"></i> ' + anomalyCount + ' ' + __t.lbl_anomaly + '</span>';
            } else {
                html += ' <span class="badge badge-light ml-1 text-muted">Skipped</span>';
            }
            html += '</button></div>';
            html += '<div id="sheet' + idx + '" class="collapse ' + collapseShow + '">';
            html += '<div class="card-body p-2">';

            if (isIgnored) {
                html += '<p class="text-muted p-3 mb-0" style="font-size:12px;">Sheet not recognised as a production line \u2014 will be skipped.</p>';
            } else if (s.rows.length === 0) {
                html += '<p class="text-muted p-3 mb-0" style="font-size:12px;">No valid data rows detected.</p>';
            } else {
                var hasRemark = s.has_remark;
                html += '<table id="previewDt_' + idx + '" class="table table-sm table-bordered sheet-preview-table w-100" style="font-size:11px;">';
                html += '<thead><tr>';
                [__t.col_date, 'Filing', __t.col_product, __t.col_batch, __t.col_run,
                 'TAMC R1','TAMC R2','TYMC R1','TYMC R2', __t.col_avg].forEach(function(h){
                    html += '<th>' + h + '</th>';
                });
                if (hasRemark) html += '<th>Remark</th>';
                html += '</tr></thead><tbody></tbody></table>';
            }

            html += '</div></div></div>';
        });
        html += '</div>';
        sheetAccordion.innerHTML = html;

        // Init DataTables for each non-ignored sheet
        sheets.forEach(function (s, idx) {
            if (s.ignored || s.rows.length === 0) return;
            var specLimit = 10;
            var tableId = '#previewDt_' + idx;
            var cols = [
                { data: 'datetested' },
                { data: 'filing', className: 'text-center' },
                { data: 'prodname' },
                { data: 'batch' },
                { data: 'runno', className: 'text-center' },
                { data: 'tamcr1', className: 'text-center' },
                { data: 'tamcr2', className: 'text-center' },
                { data: 'tymcr1', className: 'text-center' },
                { data: 'tymcr2', className: 'text-center' },
                {
                    data: 'resultavg', className: 'text-center fw-bold',
                    render: function(v, type) {
                        if (type === 'display') {
                            var cls = parseFloat(v) >= specLimit ? 'text-danger' : 'text-success';
                            return '<span class="' + cls + '">' + escHtml(v) + '</span>';
                        }
                        return v;
                    }
                },
            ];
            if (s.has_remark) cols.push({ data: 'remark', className: 'text-muted' });

            var dt = $(tableId).DataTable({
                data: s.rows,
                columns: cols,
                pageLength: 25,
                order: [[0, 'asc']],
                dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>rtip',
                buttons: [{ extend: 'csvHtml5', text: '<i class="bi bi-download"></i> CSV', className: 'btn btn-sm btn-outline-secondary' }],
                createdRow: function(row, data) {
                    if (parseFloat(data.resultavg) >= specLimit) $(row).addClass('table-warning');
                },
                language: {
                    search: __t.lbl_search,
                    lengthMenu: __t.lbl_show,
                    info: __t.lbl_info,
                    paginate: { previous: '&lsaquo;', next: '&rsaquo;' },
                },
            });
            previewDtInstances[idx] = dt;

            // Re-draw when accordion opens (DataTables needs visible container to render columns)
            document.getElementById('sheet' + idx).addEventListener('shown.bs.collapse', function() {
                dt.columns.adjust().draw(false);
            });
        });

        // Attach file to confirm form
        try {
            var dt2 = new DataTransfer();
            dt2.items.add(selectedFile);
            confirmFileInput.files = dt2.files;
        } catch (e) { /* browser may not support DataTransfer constructor */ }
    }

    /* ---- Confirm submit (XHR with progress) ---- */
    var importResultsSection = document.getElementById('importResultsSection');
    var resultsBadges   = document.getElementById('resultsBadges');
    var resultsTableBody = document.getElementById('resultsTableBody');
    var resultsTableFoot = document.getElementById('resultsTableFoot');

    confirmForm.addEventListener('submit', function (e) {
        e.preventDefault();

        btnConfirm.disabled = true;
        btnBack.disabled = true;
        btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> ' + __t.lbl_saving;

        /* Show import overlay */
        overlayImport.classList.remove('d-none');
        importOverlayIcon.className = 'bi bi-database-up overlay-icon';
        importOverlayTitle.textContent = __t.ovl_save_title;
        setProgress(0, __t.ovl_save_uploading);

        var formData = new FormData(confirmForm);

        var xhr = new XMLHttpRequest();

        /* ---- Phase 1: real upload progress (0 → 70%) ---- */
        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 70);
                setProgress(pct, pct < 70 ? __t.ovl_save_uploading : __t.ovl_save_processing);
            }
        });

        xhr.upload.addEventListener('load', function () {
            /* Upload done — now server is processing. Animate 70 → 95% slowly. */
            setProgress(70, __t.ovl_save_processing);
            var current = 70;
            var timer = setInterval(function () {
                current += Math.random() * 3;
                if (current >= 95) { current = 95; clearInterval(timer); }
                setProgress(Math.round(current), __t.ovl_save_processing);
            }, 300);

            xhr.addEventListener('load', function () {
                clearInterval(timer);
                setProgress(100, __t.ovl_save_complete);
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                progressBar.classList.add('bg-success');
                importOverlayTitle.textContent = __t.ovl_save_done;
                importOverlayIcon.className = 'bi bi-check-circle-fill overlay-icon success';

                try {
                    var data = JSON.parse(xhr.responseText);
                    if (xhr.status === 200 && data.results) {
                        /* Auto-close overlay after brief success pause */
                        setTimeout(function () {
                            overlayImport.classList.add('d-none');
                            renderResults(data);
                        }, 900);
                    } else {
                        var msg = (data && data.message) ? data.message : __t.ovl_err_server;
                        overlayImport.classList.add('d-none');
                        alert(msg);
                        resetConfirmBtn();
                    }
                } catch (ex) {
                    overlayImport.classList.add('d-none');
                    alert(__t.ovl_err_response);
                    resetConfirmBtn();
                }
            });

            xhr.addEventListener('error', function () {
                clearInterval(timer);
                overlayImport.classList.add('d-none');
                alert(__t.ovl_err_network);
                resetConfirmBtn();
            });
        });

        xhr.open('POST', confirmForm.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.send(formData);
    });

    function setProgress(pct, label) {
        progressBar.style.width = pct + '%';
        progressBar.setAttribute('aria-valuenow', pct);
        progressPct.textContent = pct + '%';
        if (label) importStepLabel.textContent = label;
    }

    function resetConfirmBtn() {
        btnConfirm.disabled = false;
        btnBack.disabled = false;
        btnConfirm.innerHTML = '<i class="bi bi-check-circle"></i> Confirm & Save to Database';
        overlayImport.classList.add('d-none');
    }

    function renderResults(data) {
        var results     = data.results;
        var totalIns    = data.total_inserted;
        var totalSkip   = data.total_skipped;

        /* Badges */
        var badgesHtml = '<span class="badge badge-success mr-1">' + totalIns + ' inserted</span>';
        if (totalSkip > 0) badgesHtml += '<span class="badge badge-secondary">' + totalSkip + ' skipped</span>';
        resultsBadges.innerHTML = badgesHtml;

        /* Rows */
        var bodyHtml = '';
        results.forEach(function (r) {
            var dupe       = r.skipped_dupe       || 0;
            var incomplete = r.skipped_incomplete || 0;
            var status = '';
            if (r.ignored) {
                status = '<span class="text-muted">Sheet not recognised</span>';
            } else if (r.errors && r.errors.length) {
                status = '<span class="text-danger">' + escHtml(r.errors.slice(0,2).join('; ')) + '</span>';
            } else if (r.inserted === 0 && dupe > 0 && incomplete === 0) {
                status = '<span class="text-muted">All already in DB</span>';
            } else if (r.inserted === 0 && incomplete > 0) {
                status = '<span class="text-danger">No valid rows found</span>';
            } else {
                status = '<span class="text-success"><i class="bi bi-check-circle"></i> OK</span>';
            }

            var dupeId = 'dupeList_' + r.sheet.replace(/\s+/g, '_');
            var dupeCell = '\u2014';
            if (!r.ignored && dupe > 0) {
                dupeCell = '<a href="#' + dupeId + '" data-toggle="collapse" style="color:inherit;text-decoration:none;" title="Click to see duplicates">'
                    + '<span class="text-warning font-weight-bold">' + dupe + ' <i class="bi bi-chevron-down" style="font-size:10px;"></i></span></a>';
            }

            bodyHtml += '<tr>';
            bodyHtml += '<td class="pl-3">' + escHtml(r.sheet) + '</td>';
            bodyHtml += '<td>' + (r.prodline ? '<span class="badge badge-secondary">' + escHtml(r.prodline) + '</span>' : '<span class="text-muted">\u2014</span>') + '</td>';
            bodyHtml += '<td class="text-center font-weight-bold ' + (r.inserted > 0 ? 'text-success' : 'text-muted') + '">' + (r.ignored ? '\u2014' : r.inserted) + '</td>';
            bodyHtml += '<td class="text-center">' + (r.ignored ? '\u2014' : dupeCell) + '</td>';
            bodyHtml += '<td class="text-center ' + (incomplete > 0 ? 'text-danger' : 'text-muted') + '">' + (r.ignored ? '\u2014' : (incomplete || '\u2014')) + '</td>';
            bodyHtml += '<td style="font-size:11px;">' + status + '</td>';
            bodyHtml += '</tr>';

            // Collapsible dupe detail row
            if (!r.ignored && dupe > 0 && r.dupe_list && r.dupe_list.length) {
                bodyHtml += '<tr class="collapse" id="' + dupeId + '">';
                bodyHtml += '<td colspan="6" class="p-0">';
                bodyHtml += '<div style="background:var(--bg-card);border-top:1px solid var(--border-color);padding:8px 16px;">';
                bodyHtml += '<small class="text-warning font-weight-bold">' + __t.res_dupe_label + ':</small>';
                bodyHtml += '<table class="table table-sm mb-0 mt-1" style="font-size:11px;">';
                bodyHtml += '<thead><tr><th>Date Tested</th><th>Product Name</th><th>Batch</th><th>Filing</th><th>Run</th></tr></thead><tbody>';
                r.dupe_list.forEach(function(d) {
                    bodyHtml += '<tr><td>' + escHtml(d.datetested) + '</td><td>' + escHtml(d.prodname || '—') + '</td><td>' + escHtml(d.batch) + '</td>'
                        + '<td>' + escHtml(d.filing) + '</td><td>' + escHtml(d.runno) + '</td></tr>';
                });
                bodyHtml += '</tbody></table></div></td></tr>';
            }
        });
        resultsTableBody.innerHTML = bodyHtml;

        /* Footer */
        resultsTableFoot.innerHTML =
            '<tr style="font-weight:600; border-top:2px solid var(--border-color);">' +
            '<td class="pl-3" colspan="2">Total</td>' +
            '<td class="text-center text-success">' + totalIns + '</td>' +
            '<td class="text-center" colspan="2">' + (totalSkip || '\u2014') + '</td>' +
            '<td></td></tr>';

        /* Show section, scroll to it */
        importResultsSection.classList.remove('d-none');
        importResultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        /* Reset confirm button area */
        btnConfirm.classList.remove('btn-success');
        btnConfirm.classList.add('btn-secondary');
        btnConfirm.innerHTML = '<i class="bi bi-check-circle"></i> Import Complete';
        btnBack.disabled = false;

        /* Activate New Upload button */
        var btnNewUpload = document.getElementById('btnNewUpload');
        if (btnNewUpload) {
            btnNewUpload.addEventListener('click', function () {
                importResultsSection.classList.add('d-none');
                resultsTableBody.innerHTML = '';
                resultsTableFoot.innerHTML = '';
                step2Section.classList.add('d-none');
                previewContent.classList.add('d-none');
                sheetAccordion.innerHTML = '';
                /* Reset confirm button */
                btnConfirm.classList.remove('btn-secondary');
                btnConfirm.classList.add('btn-success');
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="bi bi-check-circle"></i> Confirm & Save to Database';
                progressBar.style.width = '0%';
                progressBar.classList.remove('bg-success');
                progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
                clearFile();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }, { once: true });
        }
    }

    /* ---- Helpers ---- */
    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }
    function formatSize(bytes) {
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
});
</script>
@endpush
