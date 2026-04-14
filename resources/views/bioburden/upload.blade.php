@extends('layouts.app')

@section('title', 'Upload Bioburden Data')

@section('content')
<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            {{-- ===== STEP 1: FILE SELECTION ===== --}}
            <div class="card mb-4" id="step1Card">
                <div class="card-header">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Upload Bioburden Data
                </div>
                <div class="card-body">

                    <div class="alert alert-info py-2 mb-3" style="font-size:12px;">
                        <i class="bi bi-info-circle"></i>
                        <strong>No reformatting needed.</strong>
                        Upload your original monthly monitoring Excel file.
                        The system reads <strong>all sheets</strong> automatically — each sheet is one production line.
                    </div>

                    <div class="form-group mb-3">
                        <label><strong>Select Monitoring Excel File</strong></label>
                        <div id="dropZone">
                            <input type="file" id="upload_file" accept=".xlsx,.xls" class="d-none">

                            <div class="drop-area text-center" id="dropArea">
                                <i class="bi bi-file-earmark-excel" style="font-size:2.5rem; color:var(--text-muted);"></i>
                                <p class="mb-1 mt-2" style="font-size:13px;">
                                    Drag & drop your file here, or <a href="#" id="browseLink">browse</a>
                                </p>
                                <small class="text-muted">Accepted: .xlsx, .xls &nbsp;|&nbsp; Max 10MB</small>
                            </div>

                            <div class="file-selected text-center d-none" id="fileInfo">
                                <i class="bi bi-file-earmark-check" style="font-size:1.8rem; color:#27ae60;"></i>
                                <p class="mb-0 mt-1" style="font-size:13px; font-weight:600;" id="fileNameLabel"></p>
                                <small class="text-muted" id="fileSizeLabel"></small>
                                <br><a href="#" id="removeFile" style="font-size:11px; color:#e74c3c;">Remove</a>
                            </div>
                        </div>
                    </div>

                    <button id="btnPreview" class="btn btn-secondary btn-block" disabled>
                        <i class="bi bi-eye"></i> Preview Data
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


                        <div class="d-flex mt-3" style="gap:10px;">
                            <button type="button" id="btnBack" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Change File
                            </button>
                            <button type="submit" id="btnConfirm" class="btn btn-success flex-grow-1">
                                <i class="bi bi-check-circle"></i> Confirm & Save to Database
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            {{-- ===== STEP 3: IMPORT RESULTS (rendered by JS after XHR) ===== --}}
            <div id="importResultsSection" class="d-none">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard-check"></i> Import Results</span>
                        <span id="resultsBadges"></span>
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
                            <tbody id="resultsTableBody"></tbody>
                            <tfoot id="resultsTableFoot"></tfoot>
                        </table>
                    </div>
                    <div class="card-footer text-right" style="background:none;">
                        <button type="button" id="btnNewUpload" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-arrow-repeat"></i> New Upload
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
        <div class="overlay-icon-wrap mb-3">
            <i class="bi bi-file-earmark-spreadsheet overlay-icon"></i>
        </div>
        <div class="overlay-title mb-1">Scanning File</div>
        <div class="overlay-sub mb-3" id="previewStepLabel">Reading sheets...</div>
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
        <div class="overlay-icon-wrap mb-3">
            <i class="bi bi-database-up overlay-icon" id="importOverlayIcon"></i>
        </div>
        <div class="overlay-title mb-1" id="importOverlayTitle">Saving to Database</div>
        <div class="overlay-sub mb-3" id="importStepLabel">Uploading file...</div>
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
    background: var(--card-bg, #fff);
    border-radius: 14px;
    padding: 36px 40px 28px;
    min-width: 320px;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    animation: cardSlideUp .25s ease;
}
@keyframes cardSlideUp { from { transform: translateY(18px); opacity:0; } to { transform: translateY(0); opacity:1; } }
.overlay-icon-wrap {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: rgba(91,155,213,.12);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.overlay-icon { font-size: 1.9rem; color: #5b9bd5; }
.overlay-icon.success { color: #27ae60; }
.overlay-title { font-size: 15px; font-weight: 700; }
.overlay-sub { font-size: 12px; color: var(--text-muted, #888); min-height: 18px; }
.overlay-progress { height: 8px; border-radius: 4px; overflow: hidden; }
.overlay-pct { font-size: 11px; color: var(--text-muted, #888); }
</style>
@endpush

@push('scripts')
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
            setPreviewProgress(100, 'Done!');
            setTimeout(function () {
                hideOverlayPreview();
                renderPreview(data.sheets);
            }, 500);
        })
        .catch(function () {
            hideOverlayPreview();
            alert('Failed to read file. Please try again.');
            resetPreviewBtn();
        });
    });

    function showOverlayPreview() {
        overlayPreview.classList.remove('d-none');
        setPreviewProgress(0, 'Reading sheets...');
        /* Animate 0 → 85% while server works */
        var cur = 0;
        var steps = [
            [15, 400,  'Parsing header rows...'],
            [40, 900,  'Detecting column layout...'],
            [65, 1600, 'Extracting data rows...'],
            [85, 2400, 'Preparing preview...'],
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
    function renderPreview(sheets) {
        previewContent.classList.remove('d-none');
        btnPreview.innerHTML = '<i class="bi bi-eye"></i> Preview Data';
        btnPreview.disabled = false;

        var totalRows = 0;
        var activeSheets = sheets.filter(function (s) { return !s.ignored && s.total > 0; });
        sheets.forEach(function (s) { if (!s.ignored) totalRows += s.total; });

        previewFileName.textContent = selectedFile.name;
        previewTotalBadge.textContent = totalRows + ' rows across ' + activeSheets.length + ' sheet(s)';

        var html = '<div class="accordion" id="acc">';
        sheets.forEach(function (s, idx) {
            var isIgnored = s.ignored || s.total === 0;
            var headClass = isIgnored ? 'bg-light text-muted' : '';
            var collapseShow = (!isIgnored && idx < 3) ? 'show' : '';

            html += '<div class="card mb-1 ' + (isIgnored ? 'sheet-badge-ignored' : '') + '">';
            html += '<div class="card-header ' + headClass + '">';
            html += '<button type="button" data-toggle="collapse" data-target="#sheet' + idx + '">';
            html +=   '<i class="bi bi-table mr-1"></i> ' + escHtml(s.sheet);
            if (s.prodline) html += ' <span class="badge badge-secondary ml-1">' + escHtml(s.prodline) + '</span>';
            if (!isIgnored) {
                html += ' <span class="badge badge-info ml-1">' + s.total + ' rows</span>';
            } else {
                html += ' <span class="badge badge-light ml-1 text-muted">Skipped</span>';
            }
            html += '</button></div>';
            html += '<div id="sheet' + idx + '" class="collapse ' + collapseShow + '">';
            html += '<div class="card-body p-0">';

            if (isIgnored) {
                html += '<p class="text-muted p-3 mb-0" style="font-size:12px;">Sheet not recognised as a production line \u2014 will be skipped.</p>';
            } else if (s.rows.length === 0) {
                html += '<p class="text-muted p-3 mb-0" style="font-size:12px;">No valid data rows detected.</p>';
            } else {
                html += '<div class="table-responsive">';
                html += '<table class="table table-sm table-bordered sheet-preview-table mb-0">';
                if (s.has_remark) {
                    html += '<caption style="caption-side:top; font-size:10px; color:var(--text-muted); padding:4px 8px;">' +
                            '<i class="bi bi-info-circle"></i> Values like <code>&lt;1</code> will be saved as <strong>0</strong>.' +
                            '</caption>';
                }
                html += '<thead><tr>';
                var cols = ['Date Tested','Product','Batch','Run','TAMC R1','TAMC R2','TYMC R1','TYMC R2','Avg'];
                if (s.has_remark) cols.push('Remark');
                cols.forEach(function (h) { html += '<th>' + h + '</th>'; });
                html += '</tr></thead><tbody>';
                s.rows.forEach(function (row) {
                    html += '<tr>';
                    html += '<td>' + escHtml(row.datetested) + '</td>';
                    html += '<td>' + escHtml(row.prodname)   + '</td>';
                    html += '<td>' + escHtml(row.batch)      + '</td>';
                    html += '<td>' + escHtml(row.runno)      + '</td>';
                    html += '<td>' + escHtml(row.tamcr1)     + '</td>';
                    html += '<td>' + escHtml(row.tamcr2)     + '</td>';
                    html += '<td>' + escHtml(row.tymcr1)     + '</td>';
                    html += '<td>' + escHtml(row.tymcr2)     + '</td>';
                    html += '<td>' + escHtml(row.resultavg)  + '</td>';
                    if (s.has_remark) html += '<td class="text-muted">' + escHtml(row.remark) + '</td>';
                    html += '</tr>';
                });
                if (s.total > s.rows.length) {
                    var span = s.has_remark ? 10 : 9;
                    html += '<tr><td colspan="' + span + '" class="text-center text-muted" style="font-size:11px;">... and ' + (s.total - s.rows.length) + ' more rows</td></tr>';
                }
                html += '</tbody></table></div>';
            }

            html += '</div></div></div>';
        });
        html += '</div>';
        sheetAccordion.innerHTML = html;

        // Attach file to confirm form
        try {
            var dt = new DataTransfer();
            dt.items.add(selectedFile);
            confirmFileInput.files = dt.files;
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
        btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Saving...';

        /* Show import overlay */
        overlayImport.classList.remove('d-none');
        importOverlayIcon.className = 'bi bi-database-up overlay-icon';
        importOverlayTitle.textContent = 'Saving to Database';
        setProgress(0, 'Uploading file...');

        var formData = new FormData(confirmForm);

        var xhr = new XMLHttpRequest();

        /* ---- Phase 1: real upload progress (0 → 70%) ---- */
        xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
                var pct = Math.round((e.loaded / e.total) * 70);
                setProgress(pct, pct < 70 ? 'Uploading file...' : 'Processing data...');
            }
        });

        xhr.upload.addEventListener('load', function () {
            /* Upload done — now server is processing. Animate 70 → 95% slowly. */
            setProgress(70, 'Processing data...');
            var current = 70;
            var timer = setInterval(function () {
                current += Math.random() * 3;
                if (current >= 95) { current = 95; clearInterval(timer); }
                setProgress(Math.round(current), 'Processing data...');
            }, 300);

            xhr.addEventListener('load', function () {
                clearInterval(timer);
                setProgress(100, 'Import complete!');
                progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                progressBar.classList.add('bg-success');
                importOverlayTitle.textContent = 'All Done!';
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
                        var msg = (data && data.message) ? data.message : 'Server returned an error. Please try again.';
                        overlayImport.classList.add('d-none');
                        alert(msg);
                        resetConfirmBtn();
                    }
                } catch (ex) {
                    overlayImport.classList.add('d-none');
                    alert('Unexpected response from server. Please try again.');
                    resetConfirmBtn();
                }
            });

            xhr.addEventListener('error', function () {
                clearInterval(timer);
                overlayImport.classList.add('d-none');
                alert('Network error during import. Please try again.');
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
                bodyHtml += '<small class="text-warning font-weight-bold">Duplicate rows (already in DB):</small>';
                bodyHtml += '<table class="table table-sm mb-0 mt-1" style="font-size:11px;">';
                bodyHtml += '<thead><tr><th>Date Tested</th><th>Batch</th><th>Filing</th><th>Run</th></tr></thead><tbody>';
                r.dupe_list.forEach(function(d) {
                    bodyHtml += '<tr><td>' + escHtml(d.datetested) + '</td><td>' + escHtml(d.batch) + '</td>'
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
