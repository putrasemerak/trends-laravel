@extends('layouts.app')

@section('title', 'Smart Upload — Bioburden')

@section('content')
<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- Upload Form --}}
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Smart Upload — Bioburden Data
                </div>
                <div class="card-body">

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('bioburden.smart-upload.post') }}"
                          enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        {{-- Drop Zone --}}
                        <div class="form-group mb-3">
                            <label><strong>Select Bioburden Excel File</strong></label>
                            <div id="dropZone">
                                <input type="file" name="upload_file" id="upload_file"
                                       accept=".xlsx,.xls" required class="d-none">

                                <div class="drop-area text-center" id="dropArea">
                                    <i class="bi bi-file-earmark-excel" style="font-size:2.5rem; color:var(--text-muted);"></i>
                                    <p class="mb-1 mt-2" style="font-size:13px; color:var(--text-body);">
                                        Drag & drop your file here, or <a href="#" id="browseLink">browse</a>
                                    </p>
                                    <small class="text-muted">Accepted: .xlsx, .xls &nbsp;|&nbsp; Max 10MB</small>
                                </div>

                                <div class="file-selected text-center d-none" id="fileInfo">
                                    <i class="bi bi-file-earmark-check" style="font-size:1.8rem; color:#27ae60;"></i>
                                    <p class="mb-0 mt-1" style="font-size:13px; font-weight:600;" id="fileName"></p>
                                    <small class="text-muted" id="fileSize"></small>
                                    <br><a href="#" id="removeFile" style="font-size:11px; color:#e74c3c;">Remove</a>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info py-2" style="font-size:12px;">
                            <i class="bi bi-info-circle"></i>
                            <strong>No reformatting needed.</strong>
                            Upload your original monitoring Excel file as-is.
                            The system will auto-detect each sheet's layout and import all production lines automatically.
                        </div>

                        <button type="submit" class="btn btn-primary btn-block" id="btnUpload" disabled>
                            <i class="bi bi-upload"></i> Upload & Import All Sheets
                        </button>
                    </form>

                </div>
            </div>

            {{-- Results Summary --}}
            @if(session('upload_results'))
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-clipboard-data"></i> Import Results</span>
                        <span class="badge badge-success" style="font-size:13px;">
                            {{ session('total_inserted') }} inserted
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th class="pl-3">Sheet</th>
                                    <th>Prodline</th>
                                    <th class="text-center">Inserted</th>
                                    <th class="text-center">Skipped</th>
                                    <th>Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(session('upload_results') as $r)
                                    <tr class="{{ $r['ignored'] ? 'text-muted' : ($r['inserted'] > 0 ? '' : 'text-muted') }}">
                                        <td class="pl-3">{{ $r['sheet'] }}</td>
                                        <td>
                                            @if($r['prodline'])
                                                <span class="badge badge-secondary">{{ $r['prodline'] }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$r['ignored'])
                                                <span class="{{ $r['inserted'] > 0 ? 'text-success font-weight-bold' : '' }}">
                                                    {{ $r['inserted'] }}
                                                </span>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if(!$r['ignored'])
                                                {{ $r['skipped'] > 0 ? $r['skipped'] : '—' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td style="font-size:11px;">
                                            @if($r['ignored'])
                                                <span class="text-muted">Sheet not recognised — skipped</span>
                                            @elseif(!empty($r['errors']))
                                                <span class="text-danger">
                                                    {{ count($r['errors']) }} error(s):
                                                    {{ implode('; ', array_slice($r['errors'], 0, 2)) }}
                                                </span>
                                            @elseif($r['skipped'] > 0 && $r['inserted'] === 0)
                                                <span class="text-muted">All rows already exist (duplicates)</span>
                                            @elseif($r['inserted'] > 0)
                                                <span class="text-success">OK</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:600; border-top: 2px solid var(--border-color);">
                                    <td class="pl-3" colspan="2">Total</td>
                                    <td class="text-center text-success">{{ session('total_inserted') }}</td>
                                    <td class="text-center">{{ session('total_skipped') }}</td>
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
    .drop-area.dragover {
        border-color: #5b9bd5;
        background: rgba(91,155,213,.07);
    }
    .file-selected {
        border: 1px solid #27ae60;
        border-radius: 8px;
        padding: 16px;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var fileInput   = document.getElementById('upload_file');
    var dropArea    = document.getElementById('dropArea');
    var dropZone    = document.getElementById('dropZone');
    var fileInfo    = document.getElementById('fileInfo');
    var fileNameEl  = document.getElementById('fileName');
    var fileSizeEl  = document.getElementById('fileSize');
    var browseLink  = document.getElementById('browseLink');
    var removeFile  = document.getElementById('removeFile');
    var btnUpload   = document.getElementById('btnUpload');

    function showFile(file) {
        fileNameEl.textContent = file.name;
        fileSizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
        dropArea.classList.add('d-none');
        fileInfo.classList.remove('d-none');
        btnUpload.disabled = false;
    }

    function clearFile() {
        fileInput.value = '';
        fileInfo.classList.add('d-none');
        dropArea.classList.remove('d-none');
        btnUpload.disabled = true;
    }

    browseLink.addEventListener('click', function (e) { e.preventDefault(); fileInput.click(); });
    dropArea.addEventListener('click', function () { fileInput.click(); });
    removeFile.addEventListener('click', function (e) { e.preventDefault(); clearFile(); });

    fileInput.addEventListener('change', function () {
        if (this.files.length) showFile(this.files[0]);
    });

    ['dragenter','dragover'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault(); dropArea.classList.add('dragover');
        });
    });
    ['dragleave','drop'].forEach(function (evt) {
        dropZone.addEventListener(evt, function (e) {
            e.preventDefault(); dropArea.classList.remove('dragover');
        });
    });
    dropZone.addEventListener('drop', function (e) {
        var files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            showFile(files[0]);
        }
    });

    // Show spinner on submit
    document.getElementById('uploadForm').addEventListener('submit', function () {
        btnUpload.disabled = true;
        btnUpload.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> Processing...';
    });
});
</script>
@endpush
