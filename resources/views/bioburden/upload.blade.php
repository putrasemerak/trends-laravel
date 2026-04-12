@extends('layouts.app')

@section('title', 'Upload Result Data')

@section('content')
<div class="container mt-3">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">

            <div class="card">
                <div class="card-header">
                    <i class="bi bi-cloud-arrow-up-fill"></i> Upload Result Data
                </div>
                <div class="card-body">

                    <form method="POST" action="{{ route('upload.file') }}" enctype="multipart/form-data" id="uploadForm">
                        @csrf

                        <!-- Production Line -->
                        <div class="form-group mb-3">
                            <label for="prodline"><strong>Production Line</strong></label>
                            <select name="prodline" id="prodline" class="form-control" required>
                                <option value="">-- Select Production Line --</option>
                                @foreach($prodlines as $pl)
                                    <option value="{{ $pl }}" {{ old('prodline') == $pl ? 'selected' : '' }}>{{ $pl }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- File Upload -->
                        <div class="form-group mb-3">
                            <label for="upload_file"><strong>Select File</strong></label>
                            <div class="custom-file-upload" id="dropZone">
                                <input type="file" name="upload_file" id="upload_file"
                                       accept=".csv,.xlsx,.xls" required class="d-none">
                                <div class="drop-area text-center" id="dropArea">
                                    <i class="bi bi-file-earmark-spreadsheet" style="font-size:2rem; color:var(--text-muted);"></i>
                                    <p class="mb-1" style="font-size:12px; color:var(--text-body);">
                                        Drag & drop file here, or <a href="#" id="browseLink">browse</a>
                                    </p>
                                    <small class="text-muted">Accepted: .csv, .xlsx, .xls (max 5MB)</small>
                                </div>
                                <div class="file-selected text-center d-none" id="fileInfo">
                                    <i class="bi bi-file-earmark-check" style="font-size:1.5rem; color:#27ae60;"></i>
                                    <p class="mb-0" style="font-size:12px;" id="fileName"></p>
                                    <small class="text-muted" id="fileSize"></small>
                                    <br><a href="#" id="removeFile" style="font-size:11px; color:#e74c3c;">Remove</a>
                                </div>
                            </div>
                        </div>

                        <!-- Expected Format -->
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i>
                                Expected columns: <code>datetested, prodname, batch, tamcr1, tamcr2, tymcr1, tymcr2, resultavg, limit</code><br>
                                Date format: <code>dd/mm/yyyy</code> or <code>yyyy-mm-dd</code>.
                                Optional columns: <code>runno</code>.
                            </small>
                        </div>

                        <!-- Preview Table -->
                        <div id="previewSection" class="d-none mb-3">
                            <label><strong>Preview</strong> <small class="text-muted">(first 10 rows)</small></label>
                            <div class="table-responsive" style="max-height:250px; overflow-y:auto;">
                                <table class="table table-sm table-bordered" id="previewTable">
                                    <thead id="previewHead"></thead>
                                    <tbody id="previewBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Submit -->
                        <button type="submit" class="btn btn-primary btn-block" id="btnUpload" disabled>
                            <i class="bi bi-upload"></i> Upload & Import
                        </button>
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .drop-area {
        border: 2px dashed var(--border-color);
        border-radius: 8px;
        padding: 20px;
        cursor: pointer;
        transition: border-color .2s, background .2s;
    }
    .drop-area.dragover {
        border-color: #5b9bd5;
        background: rgba(91,155,213,.06);
    }
    .file-selected {
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 12px;
    }
    #previewTable th { font-size: 10px; text-transform: uppercase; }
    #previewTable td { font-size: 11px; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var fileInput = document.getElementById('upload_file');
    var dropArea = document.getElementById('dropArea');
    var dropZone = document.getElementById('dropZone');
    var fileInfo = document.getElementById('fileInfo');
    var fileName = document.getElementById('fileName');
    var fileSize = document.getElementById('fileSize');
    var browseLink = document.getElementById('browseLink');
    var removeFile = document.getElementById('removeFile');
    var btnUpload = document.getElementById('btnUpload');
    var previewSection = document.getElementById('previewSection');

    // Browse link
    browseLink.addEventListener('click', function(e) {
        e.preventDefault();
        fileInput.click();
    });

    // Click drop area to browse
    dropArea.addEventListener('click', function() {
        fileInput.click();
    });

    // Drag & drop
    ['dragenter', 'dragover'].forEach(function(evt) {
        dropZone.addEventListener(evt, function(e) {
            e.preventDefault();
            dropArea.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach(function(evt) {
        dropZone.addEventListener(evt, function(e) {
            e.preventDefault();
            dropArea.classList.remove('dragover');
        });
    });
    dropZone.addEventListener('drop', function(e) {
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleFile(fileInput.files[0]);
        }
    });

    // File selected via input
    fileInput.addEventListener('change', function() {
        if (this.files.length) {
            handleFile(this.files[0]);
        }
    });

    // Remove file
    removeFile.addEventListener('click', function(e) {
        e.preventDefault();
        fileInput.value = '';
        dropArea.classList.remove('d-none');
        fileInfo.classList.add('d-none');
        previewSection.classList.add('d-none');
        btnUpload.disabled = true;
    });

    function handleFile(file) {
        var ext = file.name.split('.').pop().toLowerCase();
        if (!['csv', 'xlsx', 'xls'].includes(ext)) {
            alert('Only CSV, XLSX, and XLS files are accepted.');
            fileInput.value = '';
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('File exceeds 5MB limit.');
            fileInput.value = '';
            return;
        }

        fileName.textContent = file.name;
        fileSize.textContent = formatSize(file.size);
        dropArea.classList.add('d-none');
        fileInfo.classList.remove('d-none');
        btnUpload.disabled = false;

        // Preview CSV only (XLSX needs server-side parsing)
        if (ext === 'csv') {
            previewCSV(file);
        } else {
            previewSection.classList.add('d-none');
        }
    }

    function previewCSV(file) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var lines = e.target.result.split('\n').filter(function(l) { return l.trim(); });
            if (lines.length < 2) return;

            var header = parseCSVLine(lines[0]);
            var headHtml = '<tr>';
            header.forEach(function(h) { headHtml += '<th>' + escHtml(h) + '</th>'; });
            headHtml += '</tr>';
            document.getElementById('previewHead').innerHTML = headHtml;

            var bodyHtml = '';
            var limit = Math.min(lines.length, 11); // header + 10 data rows
            for (var i = 1; i < limit; i++) {
                var cols = parseCSVLine(lines[i]);
                bodyHtml += '<tr>';
                cols.forEach(function(c) { bodyHtml += '<td>' + escHtml(c) + '</td>'; });
                bodyHtml += '</tr>';
            }
            document.getElementById('previewBody').innerHTML = bodyHtml;
            previewSection.classList.remove('d-none');
        };
        reader.readAsText(file);
    }

    function parseCSVLine(line) {
        // Simple CSV parse (handles quoted fields)
        var result = [];
        var current = '';
        var inQuotes = false;
        for (var i = 0; i < line.length; i++) {
            var ch = line[i];
            if (ch === '"') { inQuotes = !inQuotes; }
            else if (ch === ',' && !inQuotes) { result.push(current.trim()); current = ''; }
            else { current += ch; }
        }
        result.push(current.trim());
        return result;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }
});
</script>
@endpush
