@extends('layouts.app')
@section('title', __('app.em_upload_title'))

@php
    $hasPreview = session('em_preview', false);
    $phCnt      = session('em_personnel_cnt', 0);
    $surfCnt    = session('em_surface_cnt', 0);
    $anomPh     = session('em_anomaly_ph', 0);
    $anomSurf   = session('em_anomaly_surf', 0);
    $prevPh     = session('em_preview_ph', []);
    $prevSurf   = session('em_preview_surf', []);
    $months     = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
@endphp

@push('styles')
<style>
.em-upload-wrap    { max-width: 780px; margin: 30px auto; }
.em-card-header    {
    background: linear-gradient(135deg, rgba(91,155,213,.14) 0%, rgba(91,155,213,.05) 100%);
    border-bottom: 1px solid var(--border-color);
    padding: 18px 22px; display:flex; align-items:center; gap:14px;
}
.em-icon-ring      {
    width:50px;height:50px;border-radius:50%;flex-shrink:0;
    background:linear-gradient(135deg,rgba(91,155,213,.28),rgba(91,155,213,.1));
    border:2px solid rgba(91,155,213,.4);
    display:flex;align-items:center;justify-content:center;
    font-size:20px;color:#5b9bd5;
    box-shadow:0 0 14px rgba(91,155,213,.2);
}
.em-brand-sub  { font-size:10px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#5b9bd5;opacity:.8; }
.em-brand-title{ font-size:15px;font-weight:700;color:var(--text-body);margin-top:2px; }
.em-drop-zone  {
    border:2px dashed var(--border-color);border-radius:10px;
    padding:30px 20px;text-align:center;cursor:pointer;
    transition:border-color .2s,background .2s;
}
.em-drop-zone:hover,.em-drop-zone.dragover { border-color:#5b9bd5;background:rgba(91,155,213,.05); }
.em-drop-zone i { font-size:36px;color:#5b9bd5;opacity:.5; }
.em-drop-label { font-size:13px;color:var(--text-muted);margin-top:6px; }
.em-drop-hint  { font-size:11px;color:var(--text-muted);opacity:.65;margin-top:3px; }
.em-file-name  { font-size:11px;color:var(--text-muted);margin-top:5px;min-height:16px; }
.em-stat-box   { border-radius:10px;border:1px solid var(--border-color);padding:14px 18px;text-align:center;flex:1; }
.em-stat-val   { font-size:22px;font-weight:700;color:var(--text-body); }
.em-stat-lbl   { font-size:10px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;margin-top:2px; }
.em-stat-box.danger .em-stat-val { color:#ef4444; }
.em-stat-box.warn   .em-stat-val { color:#f59e0b; }
.em-preview-table th { font-size:10px;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap; }
.em-preview-table td { font-size:12px;white-space:nowrap; }
.em-preview-table tr.anomaly td { background:rgba(239,68,68,.08); }
.em-preview-table td.anomaly-val { color:#ef4444;font-weight:700; }
.tab-section { display:none; }
.tab-section.active { display:block; }
.em-tab-btn { font-size:12px;padding:5px 14px; }

/* ---- Overlay ---- */
.upload-overlay {
    position:fixed;inset:0;z-index:1060;
    background:rgba(0,0,0,.55);backdrop-filter:blur(3px);
    display:flex;align-items:center;justify-content:center;
    animation:overlayFadeIn .2s ease;
}
.upload-overlay.d-none{display:none!important;}
@keyframes overlayFadeIn{from{opacity:0}to{opacity:1}}
.upload-overlay-card {
    background:var(--bg-card);border:1px solid var(--border-color);
    border-radius:14px;padding:36px 40px 28px;
    min-width:320px;max-width:400px;width:90%;
    text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.35);
    animation:cardSlideUp .25s ease;
}
@keyframes cardSlideUp{from{transform:translateY(18px);opacity:0}to{transform:translateY(0);opacity:1}}
.overlay-icon-wrap {
    width:72px;height:72px;border-radius:50%;
    background:linear-gradient(135deg,rgba(91,155,213,.18),rgba(91,155,213,.08));
    border:2px solid rgba(91,155,213,.35);
    display:inline-flex;align-items:center;justify-content:center;
    box-shadow:0 0 18px rgba(91,155,213,.2);
}
.overlay-icon{font-size:2.1rem;color:#5b9bd5;}
.overlay-icon.success{color:#27ae60;}
.overlay-brand{font-size:11px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#5b9bd5;margin-bottom:18px;opacity:.85;}
.overlay-brand i{font-size:13px;margin-right:4px;}
.overlay-title{font-size:15px;font-weight:700;color:var(--text-body);}
.overlay-sub{font-size:12px;color:var(--text-muted);min-height:18px;}
.overlay-progress{height:8px;border-radius:4px;overflow:hidden;}
.overlay-pct{font-size:11px;color:var(--text-muted);font-weight:600;}
</style>
@endpush

@section('content')
<div class="container mt-3 pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
<div class="em-upload-wrap" style="max-width:100%;margin:0;">

    <div class="card" style="border-radius:14px;overflow:hidden;">
        <div class="em-card-header">
            <div class="em-icon-ring"><i class="bi bi-wind"></i></div>
            <div>
                <div class="em-brand-sub"><i class="bi bi-graph-up"></i> Laboratory Trending Analysis</div>
                <div class="em-brand-title">{{ __('app.em_upload_title') }}</div>
            </div>
        </div>

        <div class="card-body p-4">

            @if(!$hasPreview)
            {{-- â•â•â•â• STEP 1: Upload form â•â•â•â• --}}
            <p class="text-muted mb-3" style="font-size:13px;">{{ __('app.em_upload_desc') }}</p>

            <form method="POST" action="{{ route('em.upload.preview', [], false) }}"
                  enctype="multipart/form-data" id="emForm">
                @csrf

                <div class="row g-2 mb-3">
                    <div class="col-12 col-sm-5">
                        <label class="form-label" style="font-size:12px;font-weight:600;">{{ __('app.em_machine') }}</label>
                        <input type="text" name="machine_code" id="emMachineCode" class="form-control form-control-sm"
                               placeholder="e.g. BFS1, SVP1, MEDIBAG"
                               value="{{ old('machine_code') }}" required>
                    </div>
                    <div class="col-6 col-sm-4">
                        <label class="form-label" style="font-size:12px;font-weight:600;">Year</label>
                        <select name="year" class="form-select form-select-sm" required>
                            @for($y = now()->year; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ old('year', now()->year) == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-6 col-sm-3">
                        <label class="form-label" style="font-size:12px;font-weight:600;">Month</label>
                        <select name="month" class="form-select form-select-sm" required>
                            @foreach($months as $mi => $mn)
                                <option value="{{ $mi + 1 }}" {{ old('month', now()->month) == ($mi + 1) ? 'selected' : '' }}>{{ $mn }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="em-drop-zone" id="emDropZone"
                     onclick="document.getElementById('emFileInput').click()">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    <div class="em-drop-label">Click or drag &amp; drop your .xls file here</div>
                    <div class="em-drop-hint">.xls only &bull; max 20 MB</div>
                </div>
                <div class="em-file-name" id="emFileName">&nbsp;</div>

                <input type="file" id="emFileInput" name="excel_file"
                       accept=".xls" class="d-none"
                       onchange="handleEmFile(this)">

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary" id="emPreviewBtn" disabled>
                        <i class="bi bi-eye"></i> Preview Data
                    </button>
                    <a href="{{ route('em.dashboard', [], false) }}" class="btn btn-secondary ms-2">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </form>

            @else
            {{-- â•â•â•â• STEP 2: Preview + Confirm â•â•â•â• --}}
            <div class="d-flex gap-2 flex-wrap mb-4">
                <div class="em-stat-box">
                    <div class="em-stat-val">{{ $phCnt }}</div>
                    <div class="em-stat-lbl">{{ __('app.em_personnel') }}</div>
                </div>
                <div class="em-stat-box">
                    <div class="em-stat-val">{{ $surfCnt }}</div>
                    <div class="em-stat-lbl">{{ __('app.em_surface') }} / {{ __('app.em_air') }}</div>
                </div>
                @if($anomPh > 0)
                <div class="em-stat-box danger">
                    <div class="em-stat-val">{{ $anomPh }}</div>
                    <div class="em-stat-lbl">{{ __('app.em_anomaly_ph') }}</div>
                </div>
                @endif
                @if($anomSurf > 0)
                <div class="em-stat-box warn">
                    <div class="em-stat-val">{{ $anomSurf }}</div>
                    <div class="em-stat-lbl">{{ __('app.em_anomaly_surf') }}</div>
                </div>
                @endif
            </div>

            <div class="mb-3 d-flex gap-1">
                <button class="btn btn-outline-secondary em-tab-btn active" id="tabBtnPh"
                        onclick="switchTab('ph')">
                    <i class="bi bi-person-check"></i> {{ __('app.em_personnel') }}
                    <span class="badge bg-secondary ms-1">{{ $phCnt }}</span>
                </button>
                <button class="btn btn-outline-secondary em-tab-btn" id="tabBtnSurf"
                        onclick="switchTab('surf')">
                    <i class="bi bi-layers"></i> {{ __('app.em_surface') }} / Air
                    <span class="badge bg-secondary ms-1">{{ $surfCnt }}</span>
                </button>
            </div>

            {{-- Personnel preview --}}
            <div id="tabPh" class="tab-section active">
                @if(empty($prevPh))
                    <p class="text-muted" style="font-size:13px;">No personnel hygiene rows found.</p>
                @else
                <div class="table-responsive">
                <table class="table table-sm em-preview-table border">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th><th>{{ __('app.em_emp_no') }}</th><th>Type</th>
                            <th>Result</th><th>{{ __('app.em_std') }}</th>
                            <th>{{ __('app.em_action') }}</th><th>{{ __('app.em_alert') }}</th>
                            <th>!</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($prevPh as $row)
                    <tr class="{{ $row['anomaly'] ? 'anomaly' : '' }}">
                        <td>{{ $row['sample_date'] }}</td>
                        <td>{{ $row['emp_no'] }}</td>
                        <td>{{ strtoupper($row['test_type']) }}</td>
                        <td class="{{ $row['anomaly'] ? 'anomaly-val' : '' }}">
                            {{ $row['is_na'] ? 'NA' : $row['result'] }}</td>
                        <td>{{ $row['std_limit'] ?? '-' }}</td>
                        <td>{{ $row['action_limit'] ?? '-' }}</td>
                        <td>{{ $row['alert_limit'] ?? '-' }}</td>
                        <td>{{ $row['anomaly'] ? '!' : '' }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                @if($phCnt > 20)
                    <p class="text-muted" style="font-size:11px;">Showing first 20 of {{ $phCnt }} rows.</p>
                @endif
                @endif
            </div>

            {{-- Surface/Air preview --}}
            <div id="tabSurf" class="tab-section">
                @if(empty($prevSurf))
                    <p class="text-muted" style="font-size:13px;">No surface / air rows found.</p>
                @else
                <div class="table-responsive">
                <table class="table table-sm em-preview-table border">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th><th>Type</th><th>{{ __('app.em_room') }}</th>
                            <th>{{ __('app.em_loc') }}</th><th>Result</th>
                            <th>{{ __('app.em_std') }}</th><th>{{ __('app.em_action') }}</th>
                            <th>{{ __('app.em_alert') }}</th><th>!</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($prevSurf as $row)
                    <tr class="{{ $row['anomaly'] ? 'anomaly' : '' }}">
                        <td>{{ $row['sample_date'] }}</td>
                        <td>{{ strtoupper($row['test_type']) }}</td>
                        <td>{{ $row['room_label'] ?? '-' }}</td>
                        <td>{{ $row['location_label'] ?? '-' }}</td>
                        <td class="{{ $row['anomaly'] ? 'anomaly-val' : '' }}">
                            {{ $row['is_na'] ? 'NA' : $row['result'] }}</td>
                        <td>{{ $row['std_limit'] ?? '-' }}</td>
                        <td>{{ $row['action_limit'] ?? '-' }}</td>
                        <td>{{ $row['alert_limit'] ?? '-' }}</td>
                        <td>{{ $row['anomaly'] ? '!' : '' }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
                </div>
                @if($surfCnt > 20)
                    <p class="text-muted" style="font-size:11px;">Showing first 20 of {{ $surfCnt }} rows.</p>
                @endif
                @endif
            </div>

            <div class="mt-4 d-flex" style="gap:16px;">
                <button type="button" id="emConfirmBtn" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Confirm &amp; Save
                </button>
                <a href="{{ route('em.upload', [], false) }}" class="btn btn-secondary ms-3">
                    <i class="bi bi-arrow-repeat"></i> Upload Different File
                </a>
            </div>
            @endif

        </div>
    </div>

</div>{{-- /em-upload-wrap --}}

{{-- ===== Import Results (shown inline after XHR save) ===== --}}
<div id="emResultsSection" class="d-none mt-3">
    <div class="card" style="border-radius:14px;overflow:hidden;">
        <div class="card-header d-flex justify-content-between align-items-center"
             style="background:linear-gradient(135deg,rgba(39,174,96,.12),rgba(39,174,96,.04));border-bottom:1px solid var(--border-color);padding:14px 20px;">
            <span style="font-weight:700;font-size:14px;">
                <i class="bi bi-clipboard-check text-success"></i> Import Results
            </span>
            <span id="emResultBadges"></span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:45%;">Data Type</th>
                        <th class="text-center">Inserted</th>
                        <th class="text-center">Replaced</th>
                        <th class="text-center">Anomalies</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="emResultsBody"></tbody>
                <tfoot id="emResultsFoot"></tfoot>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center"
             style="background:none;padding:10px 16px;">
            <small class="text-muted" id="emResultMeta"></small>
            <button type="button" id="emBtnNewUpload" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-arrow-repeat"></i> Upload Another File
            </button>
        </div>
    </div>
</div>{{-- /emResultsSection --}}

        </div>{{-- /col-lg-10 --}}
    </div>{{-- /row --}}
</div>{{-- /container --}}
@endsection

{{-- ===== OVERLAY: Scanning EM file ===== --}}
<div id="emOverlayScan" class="upload-overlay d-none" role="status" aria-live="polite">
    <div class="upload-overlay-card">
        <div class="overlay-brand">
            <i class="bi bi-graph-up"></i> Laboratory Trending Analysis
        </div>
        <div class="overlay-icon-wrap mb-3">
            <i class="bi bi-file-earmark-spreadsheet overlay-icon"></i>
        </div>
        <div class="overlay-title mb-1">{{ __('app.ovl_scan_title') }}</div>
        <div class="overlay-sub mb-3" id="emScanLabel">{{ __('app.ovl_scan_reading') }}</div>
        <div class="progress overlay-progress">
            <div id="emScanBar"
                 class="progress-bar progress-bar-striped progress-bar-animated bg-info"
                 role="progressbar" style="width:0%"></div>
        </div>
        <div class="overlay-pct mt-1" id="emScanPct">0%</div>
    </div>
</div>

{{-- ===== OVERLAY: Saving to database ===== --}}
<div id="emOverlaySave" class="upload-overlay d-none" role="status" aria-live="polite">
    <div class="upload-overlay-card">
        <div class="overlay-brand">
            <i class="bi bi-graph-up"></i> Laboratory Trending Analysis
        </div>
        <div class="overlay-icon-wrap mb-3">
            <i class="bi bi-database-up overlay-icon" id="emSaveIcon"></i>
        </div>
        <div class="overlay-title mb-1" id="emSaveTitle">{{ __('app.ovl_save_title') }}</div>
        <div class="overlay-sub mb-3" id="emSaveLabel">{{ __('app.ovl_save_uploading') }}</div>
        <div class="progress overlay-progress">
            <div id="emSaveBar"
                 class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                 role="progressbar" style="width:0%"></div>
        </div>
        <div class="overlay-pct mt-1" id="emSavePct">0%</div>
    </div>
</div>

@push('scripts')
<script>
function handleEmFile(input) {
    var file      = input.files[0];
    var nameEl    = document.getElementById('emFileName');
    var btn       = document.getElementById('emPreviewBtn');
    var zone      = document.getElementById('emDropZone');
    var machineEl = document.getElementById('emMachineCode');
    if (file) {
        nameEl.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
        btn.disabled = false;
        zone.style.borderColor = '#5b9bd5';
        // Auto-fill machine code from filename (strip extension), only if field is blank
        if (machineEl && machineEl.value.trim() === '') {
            machineEl.value = file.name.replace(/\.[^.]+$/, '').trim().toUpperCase();
        }
    } else {
        nameEl.innerHTML = '&nbsp;';
        btn.disabled = true;
        zone.style.borderColor = '';
    }
}
(function() {
    var zone  = document.getElementById('emDropZone');
    var input = document.getElementById('emFileInput');
    if (!zone) return;
    zone.addEventListener('dragover',  function(e) { e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function()   { zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e) {
        e.preventDefault(); zone.classList.remove('dragover');
        var dt = e.dataTransfer.files;
        if (dt.length) { input.files = dt; handleEmFile(input); }
    });
})();
function switchTab(tab) {
    ['Ph','Surf'].forEach(function(t) {
        document.getElementById('tab'    + t).classList.remove('active');
        document.getElementById('tabBtn' + t).classList.remove('active');
    });
    var key = tab === 'ph' ? 'Ph' : 'Surf';
    document.getElementById('tab'    + key).classList.add('active');
    document.getElementById('tabBtn' + key).classList.add('active');
}

/* ===== Overlay helpers ===== */
(function () {
    var MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    function animateOverlay(barId, labelId, pctId, steps) {
        var bar   = document.getElementById(barId);
        var label = document.getElementById(labelId);
        var pct   = document.getElementById(pctId);
        bar.style.width = '0%';
        pct.textContent = '0%';
        steps.forEach(function (s) {
            setTimeout(function () {
                bar.style.width = s[0] + '%';
                pct.textContent = s[0] + '%';
                if (s[2]) label.textContent = s[2];
            }, s[1]);
        });
    }

    /* -- Preview form (em upload step 1) -- */
    var emForm = document.getElementById('emForm');
    if (emForm) {
        emForm.addEventListener('submit', function () {
            document.getElementById('emOverlayScan').classList.remove('d-none');
            animateOverlay('emScanBar', 'emScanLabel', 'emScanPct', [
                [10,  200,  '{{ __("app.ovl_scan_reading") }}'],
                [30,  700,  '{{ __("app.ovl_scan_header") }}'],
                [52,  1400, '{{ __("app.ovl_scan_layout") }}'],
                [74,  2300, '{{ __("app.ovl_scan_extract") }}'],
                [90,  3400, '{{ __("app.ovl_scan_prepare") }}'],
            ]);
        });
    }

    /* -- Confirm & Save (XHR) -- */
    /* ---- Confirm submit (XHR with progress) — mirrors bioburden exactly ---- */
    var importResultsSection = document.getElementById('emResultsSection');
    var resultsBadges        = document.getElementById('emResultBadges');
    var resultsTableBody     = document.getElementById('emResultsBody');
    var resultsTableFoot     = document.getElementById('emResultsFoot');
    var overlayImport        = document.getElementById('emOverlaySave');
    var importOverlayIcon    = document.getElementById('emSaveIcon');
    var importOverlayTitle   = document.getElementById('emSaveTitle');
    var importStepLabel      = document.getElementById('emSaveLabel');
    var progressBar          = document.getElementById('emSaveBar');
    var progressPct          = document.getElementById('emSavePct');
    var confirmBtn           = document.getElementById('emConfirmBtn');

    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            /* Show import overlay */
            overlayImport.classList.remove('d-none');
            importOverlayIcon.className = 'bi bi-database-up overlay-icon';
            importOverlayTitle.textContent = '{{ __("app.ovl_save_title") }}';
            setProgress(0, '{{ __("app.ovl_save_uploading") }}');

            var formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            var xhr = new XMLHttpRequest();

            /* Phase 1: real upload progress (0 → 70%) */
            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    var pct = Math.round((e.loaded / e.total) * 70);
                    setProgress(pct, pct < 70 ? '{{ __("app.ovl_save_uploading") }}' : '{{ __("app.ovl_save_processing") }}');
                }
            });

            xhr.upload.addEventListener('load', function () {
                /* Upload done — server is processing. Animate 70 → 95% slowly. */
                setProgress(70, '{{ __("app.ovl_save_processing") }}');
                var current = 70;
                var timer = setInterval(function () {
                    current += Math.random() * 3;
                    if (current >= 95) { current = 95; clearInterval(timer); }
                    setProgress(Math.round(current), '{{ __("app.ovl_save_processing") }}');
                }, 300);

                xhr.addEventListener('load', function () {
                    clearInterval(timer);
                    setProgress(100, '{{ __("app.ovl_save_complete") }}');
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    progressBar.classList.add('bg-success');
                    importOverlayTitle.textContent = '{{ __("app.ovl_save_done") }}';
                    importOverlayIcon.className = 'bi bi-check-circle-fill overlay-icon success';

                    try {
                        var data = JSON.parse(xhr.responseText);
                        if (xhr.status === 200 && data.results) {
                            setTimeout(function () {
                                overlayImport.classList.add('d-none');
                                renderResults(data);
                            }, 900);
                        } else {
                            var msg = (data && data.message) ? data.message : '{{ __("app.ovl_err_server") }}';
                            overlayImport.classList.add('d-none');
                            alert(msg);
                            resetConfirmBtn();
                        }
                    } catch (ex) {
                        overlayImport.classList.add('d-none');
                        alert('{{ __("app.ovl_err_response") }}');
                        resetConfirmBtn();
                    }
                });

                xhr.addEventListener('error', function () {
                    clearInterval(timer);
                    overlayImport.classList.add('d-none');
                    alert('{{ __("app.ovl_err_network") }}');
                    resetConfirmBtn();
                });
            });

            xhr.open('POST', '{{ route("em.upload.post", [], false) }}');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);
        });
    }

    function setProgress(pct, label) {
        progressBar.style.width = pct + '%';
        progressBar.setAttribute('aria-valuenow', pct);
        progressPct.textContent = pct + '%';
        if (label) importStepLabel.textContent = label;
    }

    function resetConfirmBtn() {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm &amp; Save';
        overlayImport.classList.add('d-none');
        progressBar.style.width = '0%';
        progressBar.classList.remove('bg-success');
        progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
    }

    function renderResults(data) {
        var results   = data.results;
        var totalIns  = data.total_inserted;
        var totalSkip = data.total_skipped;
        var monthName = MONTHS[(data.month || 1) - 1] || data.month;

        /* Badges */
        var badgesHtml = '<span class="badge badge-success mr-1">' + totalIns + ' inserted</span>';
        if (totalSkip > 0) badgesHtml += '<span class="badge badge-secondary">' + totalSkip + ' skipped</span>';
        if (results.some(function (r) { return r.replaced; })) badgesHtml += '<span class="badge badge-warning ml-1">Replaced</span>';
        resultsBadges.innerHTML = badgesHtml;

        /* Rows */
        var bodyHtml = '';
        results.forEach(function (r) {
            var status = r.inserted > 0
                ? '<span class="text-success"><i class="bi bi-check-circle"></i> OK</span>'
                : '<span class="text-muted">No rows</span>';
            bodyHtml += '<tr>';
            bodyHtml += '<td class="pl-3"><i class="bi bi-' + (r.type === 'personnel' ? 'person-check' : 'layers') + '"></i> ' + escHtml(r.label) + '</td>';
            bodyHtml += '<td class="text-center font-weight-bold ' + (r.inserted > 0 ? 'text-success' : 'text-muted') + '">' + r.inserted + '</td>';
            bodyHtml += '<td class="text-center text-muted">' + (r.replaced ? 'Yes' : '\u2014') + '</td>';
            bodyHtml += '<td class="text-center ' + (r.anomaly > 0 ? 'text-danger font-weight-bold' : 'text-muted') + '">' + (r.anomaly || '\u2014') + '</td>';
            bodyHtml += '<td style="font-size:11px;">' + status + '</td>';
            bodyHtml += '</tr>';
        });
        resultsTableBody.innerHTML = bodyHtml;

        /* Footer */
        var totalAnom = results.reduce(function (s, r) { return s + (r.anomaly || 0); }, 0);
        resultsTableFoot.innerHTML =
            '<tr style="font-weight:600; border-top:2px solid var(--border-color);">' +
            '<td class="pl-3">Total</td>' +
            '<td class="text-center text-success">' + totalIns + '</td>' +
            '<td class="text-center text-muted">' + (results.some(function(r){return r.replaced;}) ? 'Yes' : '\u2014') + '</td>' +
            '<td class="text-center ' + (totalAnom > 0 ? 'text-danger' : 'text-muted') + '">' + (totalAnom || '\u2014') + '</td>' +
            '<td></td></tr>';

        /* Meta */
        document.getElementById('emResultMeta').textContent =
            data.machine_code + ' \u2014 ' + monthName + ' ' + data.year;

        /* Show section, scroll to it */
        importResultsSection.classList.remove('d-none');
        importResultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });

        /* Update confirm button */
        confirmBtn.classList.remove('btn-success');
        confirmBtn.classList.add('btn-secondary');
        confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Import Complete';

        /* Wire New Upload button */
        document.getElementById('emBtnNewUpload').addEventListener('click', function () {
            importResultsSection.classList.add('d-none');
            resultsTableBody.innerHTML = '';
            resultsTableFoot.innerHTML = '';
            /* reset overlay state */
            progressBar.style.width = '0%';
            progressBar.classList.remove('bg-success');
            progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
            confirmBtn.classList.remove('btn-secondary');
            confirmBtn.classList.add('btn-success');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm &amp; Save';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }, { once: true });
    }

    function escHtml(str) {
        var d = document.createElement('div');
        d.textContent = str == null ? '' : String(str);
        return d.innerHTML;
    }

})();
</script>
@endpush
