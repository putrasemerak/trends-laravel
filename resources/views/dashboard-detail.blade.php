@extends('layouts.app')

@section('title', $prodline . ' — Detail Dashboard')

@push('styles')
<style>
.tab-pills .nav-link {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 6px;
    margin-right: 4px;
    margin-bottom: 4px;
    color: var(--text-body);
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    transition: color .2s ease, border-color .2s ease, box-shadow .2s ease;
}
.tab-pills .nav-link:hover {
    color: #60a5fa;
    border-color: #60a5fa;
    box-shadow: 0 0 8px rgba(96,165,250,.55), 0 0 2px rgba(96,165,250,.8);
    text-decoration: none;
}
.tab-pills .nav-link.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
    box-shadow: 0 0 10px rgba(59,130,246,.6);
}
#cfuMonthChart { width: 100%; height: 320px; }

/* Chart spinner overlay — visible on page load, dismissed when chart is ready */
#chartSpinner {
    position: absolute; inset: 0; z-index: 20;
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 14px;
    background: rgba(30,41,59,.92); border-radius: 0 0 6px 6px;
    transition: opacity 0.5s ease;
}
#chartSpinner .cs-ring {
    width: 48px; height: 48px;
    border: 5px solid rgba(96,165,250,.25);
    border-top-color: #60a5fa;
    border-radius: 50%;
    animation: csSpin 0.85s linear infinite;
}
@keyframes csSpin { to { transform: rotate(360deg); } }
#chartSpinner .cs-label { color: #e2e8f0; font-size: 13px; font-weight: 500; letter-spacing: .5px; }
#chartSpinner .cs-done  { color: #34d399; font-size: 13px; font-weight: 700; letter-spacing: .5px; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    {{-- Back + Title --}}
    <div class="d-flex align-items-center mb-2" style="gap:10px;">
        <a href="{{ route('dashboard', [], false) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ __('app.detail_all') }}
        </a>
        <h5 class="mb-0"><i class="bi bi-activity"></i> {{ $prodline }}</h5>
    </div>

    {{-- Product line tabs --}}
    <nav class="tab-pills mb-3">
        <ul class="nav flex-wrap">
            @foreach($allProdlines as $pl)
                <li class="nav-item">
                    <a class="nav-link {{ $pl === $prodline ? 'active' : '' }}"
                       href="{{ route('dashboard.detail', $pl, false) }}">{{ $pl }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Stat Cards --}}
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-primary">{{ number_format($stats->total_samples) }}</div>
                <div class="stat-label">{{ __('app.detail_total') }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-success">{{ round($stats->avg_result, 2) }}</div>
                <div class="stat-label">{{ __('app.detail_avg') }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number {{ $stats->max_result >= $specLimit ? 'text-danger' : '' }}">{{ $stats->max_result }}</div>
                <div class="stat-label">{{ __('app.detail_max') }}</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-info" style="font-size:1.1rem;">
                    {{ $stats->latest_test ? \Carbon\Carbon::parse($stats->latest_test)->format('d-M-Y') : '-' }}
                </div>
                <div class="stat-label">{{ __('app.detail_latest') }}</div>
            </div>
        </div>
    </div>

    {{-- CFU/mL vs Batch by Month chart --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap" style="gap:8px;">
                    <strong><i class="bi bi-graph-up-arrow"></i> CFU/mL vs Batch Number</strong>
                    <form method="GET" action="{{ route('dashboard.detail', $prodline, false) }}" class="d-flex align-items-center" style="gap:6px;">
                        <label for="monthSelect" class="mb-0" style="font-size:12px;font-weight:600;">Month:</label>
                        <select id="monthSelect" name="month" class="form-control form-control-sm" style="width:auto;">
                            <option value="ALL" {{ 'ALL' === $selectedMonth ? 'selected' : '' }}>— All Records —</option>
                            @foreach($availableMonths as $m)
                                <option value="{{ $m }}" {{ $m === $selectedMonth ? 'selected' : '' }}>{{ $m }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body" style="position:relative;">
                    @if($cfuMonthData->isEmpty())
                        <div class="text-muted text-center py-4" style="font-size:13px;">No data for {{ $selectedMonth === 'ALL' ? 'this prodline' : $selectedMonth }}</div>
                    @else
                        {{-- Spinner overlay: shown on load, dismissed by amCharts ready event --}}
                        <div id="chartSpinner">
                            <div class="cs-ring"></div>
                            <div class="cs-label" id="csLabel">Menjana Carta...</div>
                        </div>
                        <div id="cfuMonthChart"></div>
                        <p class="text-muted mt-1 mb-0" style="font-size:10px;">
                            <span class="text-danger">&#9679;</span> At/above spec limit &nbsp;
                            <span class="text-success">&#9679;</span> Within limit &nbsp;|&nbsp;
                            Dashed red line = Spec Limit ({{ $specLimit }} CFU/mL) &nbsp;|&nbsp;
                            Hover for details
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Entries Table --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><strong><i class="bi bi-clock-history"></i> {{ __('app.detail_recent') }}</strong></div>
                <div class="card-body">
                    <table class="table table-sm table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('app.col_date') }}</th>
                                <th>{{ __('app.col_product') }}</th>
                                <th>{{ __('app.col_batch') }}</th>
                                <th class="text-center">{{ __('app.col_run') }}</th>
                                <th class="text-center">TAMC R1</th>
                                <th class="text-center">TAMC R2</th>
                                <th class="text-center">TYMC R1</th>
                                <th class="text-center">TYMC R2</th>
                                <th class="text-center">{{ __('app.col_avg') }}</th>
                                <th>{{ __('app.col_added_by') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentEntries as $entry)
                                <tr>
                                    <td>{{ $entry->datetested->format('d-M-Y') }}</td>
                                    <td>{{ $entry->prodname }}</td>
                                    <td>{{ $entry->batch }}</td>
                                    <td class="text-center">{{ $entry->runno }}</td>
                                    <td class="text-center">{{ $entry->tamcr1 }}</td>
                                    <td class="text-center">{{ $entry->tamcr2 }}</td>
                                    <td class="text-center">{{ $entry->tymcr1 }}</td>
                                    <td class="text-center">{{ $entry->tymcr2 }}</td>
                                    <td class="text-center">
                                        <span class="{{ $entry->resultavg >= $specLimit ? 'text-danger font-weight-bold' : 'text-success' }}">
                                            {{ $entry->resultavg }}
                                        </span>
                                    </td>
                                    <td>{{ $entry->AddUser }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- All Records accordion — lazy-loaded via AJAX on first open --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="accordion" id="allRecordsAccordion">
                <div class="accordion-item" style="border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden;">
                    <h2 class="accordion-header" id="allRecordsHeading">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#allRecordsBody"
                                aria-expanded="false" aria-controls="allRecordsBody"
                                style="font-size:13px; font-weight:600; background: var(--bg-card); color: var(--text-heading);">
                            <i class="bi bi-table me-2"></i>
                            All Records — {{ $prodline }}
                            <span class="badge bg-secondary ms-2" style="font-size:10px;">{{ $stats->total_samples ?? 0 }}</span>
                            <span class="ms-2 text-muted" style="font-size:11px; font-weight:400;">Click to expand &amp; search</span>
                        </button>
                    </h2>
                    <div id="allRecordsBody" class="accordion-collapse collapse" aria-labelledby="allRecordsHeading">
                        <div class="accordion-body p-2">
                            <div id="allRecordsLoading" class="text-center py-4 text-muted" style="font-size:13px;">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                {{ __('app.lbl_loading_records') }}
                            </div>
                            <div id="allRecordsTableWrap" style="display:none;">
                                <table id="allRecordsTable" class="table table-sm table-striped table-hover w-100" style="font-size:12px;">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Product</th>
                                            <th>Batch</th>
                                            <th class="text-center">Run</th>
                                            <th class="text-center">Filing</th>
                                            <th class="text-center">TAMC R1</th>
                                            <th class="text-center">TAMC R2</th>
                                            <th class="text-center">TYMC R1</th>
                                            <th class="text-center">TYMC R2</th>
                                            <th class="text-center">Avg CFU</th>
                                            <th>Added By</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="{{ asset('assets/datatables/jquery.dataTables.min.css') }}">
<script src="{{ asset('assets/datatables/jquery-3.5.1.js') }}"></script>
<script src="{{ asset('assets/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/datatables/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('assets/datatables/buttons.html5.min.js') }}"></script>
<script src="{{ asset('assets/datatables/jszip.min.js') }}"></script>

<script type="application/json" id="__cfuMonthData">@json($cfuMonthData)</script>
<script type="application/json" id="__specLimit">{{ $specLimit }}</script>
<script type="application/json" id="__i18n">{!! json_encode([
    'lbl_search'         => __('app.lbl_search'),
    'lbl_show'           => __('app.lbl_show'),
    'lbl_info'           => __('app.lbl_info'),
    'lbl_loading_records'=> __('app.lbl_loading_records'),
    'lbl_failed_load'    => __('app.lbl_failed_load'),
]) !!}</script>
<script>var __recordsUrl = "{{ route('dashboard.records', $prodline, false) }}";</script>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var __i = JSON.parse(document.getElementById('__i18n').textContent);

    // All Records DataTable — lazy load on first accordion open
    var recordsLoaded = false;
    var accordionEl = document.getElementById('allRecordsBody');
    if (accordionEl) {
        accordionEl.addEventListener('show.bs.collapse', function() {
            if (recordsLoaded) return;
            recordsLoaded = true;
            fetch(__recordsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(json) {
                    var specLimit = JSON.parse(document.getElementById('__specLimit').textContent);
                    var wrap = document.getElementById('allRecordsTableWrap');
                    var loading = document.getElementById('allRecordsLoading');
                    if (loading) loading.style.display = 'none';
                    if (wrap) wrap.style.display = '';

                    $('#allRecordsTable').DataTable({
                        data: json.data,
                        pageLength: 25,
                        order: [[0, 'desc']],
                        dom: '<"d-flex justify-content-between align-items-center mb-2"lfB>rtip',
                        buttons: [{ extend: 'csvHtml5', text: '<i class="bi bi-download"></i> CSV', className: 'btn btn-sm btn-outline-secondary' }],
                        columns: [
                            { data: 'date' },
                            { data: 'prodname' },
                            { data: 'batch' },
                            { data: 'runno',   className: 'text-center' },
                            { data: 'filing',  className: 'text-center' },
                            { data: 'tamcr1',  className: 'text-center' },
                            { data: 'tamcr2',  className: 'text-center' },
                            { data: 'tymcr1',  className: 'text-center' },
                            { data: 'tymcr2',  className: 'text-center' },
                            {
                                data: 'resultavg',
                                className: 'text-center fw-bold',
                                render: function(v, type, row) {
                                    if (type === 'display') {
                                        var cls = row.anomaly ? 'text-danger' : 'text-success';
                                        return '<span class="' + cls + '">' + v + '</span>';
                                    }
                                    return v;
                                }
                            },
                            { data: 'adduser' },
                        ],
                        createdRow: function(row, data) {
                            if (data.anomaly) $(row).addClass('table-danger');
                        },
                        language: {
                            search: __i.lbl_search,
                            lengthMenu: __i.lbl_show,
                            info: __i.lbl_info,
                            paginate: { previous: '&lsaquo;', next: '&rsaquo;' },
                        },
                    });
                })
                .catch(function() {
                    var loading = document.getElementById('allRecordsLoading');
                    if (loading) loading.innerHTML = '<span class="text-danger">' + __i.lbl_failed_load + '</span>';
                });
        });
    }
    // Month select — submit form (spinner shows naturally on new page load)
    var monthSel = document.getElementById('monthSelect');
    if (monthSel) {
        monthSel.addEventListener('change', function() { this.form.submit(); });
    }

    var el = document.getElementById('cfuMonthChart');
    if (!el) return;

    // Fixed dark-panel chart colours — high contrast on both themes
    var bgChart   = '#2d3748';   // slate dark: blends in dark mode, intentional panel in light
    var textColor = '#f1f5f9';   // near-white — readable on dark bg
    var gridColor = '#4a5568';   // medium slate grid
    var specLimit = JSON.parse(document.getElementById('__specLimit').textContent);
    var data      = JSON.parse(document.getElementById('__cfuMonthData').textContent);

    var chart = am4core.create('cfuMonthChart', am4charts.XYChart);
    chart.data = data;
    chart.background.fill = am4core.color(bgChart);
    chart.background.fillOpacity = 1;
    chart.plotContainer.background.fillOpacity = 0;

    // X axis — Batch / Run / Filing label (unique per record)
    var catAxis = chart.xAxes.push(new am4charts.CategoryAxis());
    catAxis.dataFields.category = 'label';
    catAxis.renderer.grid.template.location = 0;
    catAxis.renderer.labels.template.rotation = -55;
    catAxis.renderer.labels.template.horizontalCenter = 'right';
    catAxis.renderer.labels.template.verticalCenter = 'middle';
    catAxis.renderer.labels.template.fontSize = 9;
    catAxis.renderer.labels.template.fill = am4core.color(textColor);
    catAxis.renderer.grid.template.stroke = am4core.color(gridColor);
    catAxis.renderer.grid.template.strokeOpacity = 0.5;
    catAxis.renderer.line.stroke = am4core.color(gridColor);
    catAxis.renderer.ticks.template.stroke = am4core.color(gridColor);
    catAxis.renderer.minGridDistance = 20;
    catAxis.title.text = 'Batch / Run / Filing';
    catAxis.title.fill = am4core.color(textColor);
    catAxis.title.fontSize = 11;
    catAxis.title.marginTop = 6;

    // Y axis — CFU/mL
    var valAxis = chart.yAxes.push(new am4charts.ValueAxis());
    valAxis.min = 0;
    valAxis.strictMinMax = true;
    valAxis.extraMax = 0.2;
    valAxis.title.text = 'CFU/mL';
    valAxis.title.fill = am4core.color(textColor);
    valAxis.renderer.labels.template.fill = am4core.color(textColor);
    valAxis.renderer.grid.template.stroke = am4core.color(gridColor);
    valAxis.renderer.grid.template.strokeOpacity = 0.5;
    valAxis.renderer.line.stroke = am4core.color(gridColor);
    valAxis.renderer.ticks.template.stroke = am4core.color(gridColor);

    // Line series
    var series = chart.series.push(new am4charts.LineSeries());
    series.dataFields.valueY = 'cfu';
    series.dataFields.categoryX = 'label';
    series.strokeWidth = 2;
    series.stroke = am4core.color('#60a5fa');   // bright sky-blue — pops on dark bg
    series.fillOpacity = 0;
    // Use mild smoothing — high enough to prevent bezier loop artefacts on spikes
    series.tensionX = data.length > 60 ? 0.95 : 0.88;
    series.tooltipText = '{product}\nBatch: {batch} | {run} | Filing: {filing}\n{date}\nCFU/mL: {cfu}';

    // Dots — green or red
    var bullet = series.bullets.push(new am4charts.CircleBullet());
    bullet.circle.radius = 2;
    bullet.circle.strokeWidth = 0;
    bullet.circle.fill = am4core.color('#34d399');
    bullet.circle.adapter.add('fill', function(fill, target) {
        if (target.dataItem && target.dataItem.valueY >= specLimit)
            return am4core.color('#f87171');   // bright rose-red
        return fill;
    });

    // Spec limit dashed line
    var range = valAxis.axisRanges.create();
    range.value = specLimit;
    range.grid.stroke = am4core.color('#f87171');
    range.grid.strokeWidth = 1.5;
    range.grid.strokeDasharray = '5,3';
    range.label.text = 'Spec: ' + specLimit;
    range.label.fill = am4core.color('#f87171');
    range.label.inside = true;
    range.label.verticalCenter = 'bottom';
    range.label.fontSize = 10;

    chart.cursor = new am4charts.XYCursor();
    chart.cursor.lineY.disabled = true;
    if (data.length > 20) {
        var scrollbar = new am4core.Scrollbar();
        chart.scrollbarX = scrollbar;
        chart.scrollbarX.parent = chart.bottomAxesContainer;

        // Style scrollbar to be obviously interactive — amber thumb/grips
        scrollbar.thumb.background.fill    = am4core.color('#6ee7b7');  // mint green track
        scrollbar.thumb.background.opacity = 0.8;
        scrollbar.startGrip.background.fill = am4core.color('#f59e0b'); // amber handles
        scrollbar.endGrip.background.fill   = am4core.color('#f59e0b'); // amber handles
        scrollbar.background.fill           = am4core.color('#4a5568');
        scrollbar.background.opacity        = 0.4;
    }

    // When chart finishes — show Selesai, then fade out and remove spinner
    chart.events.on('ready', function() {
        var spinner = document.getElementById('chartSpinner');
        var label   = document.getElementById('csLabel');
        if (!spinner) return;
        var ring = spinner.querySelector('.cs-ring');
        if (ring) { ring.style.animation = 'none'; ring.style.borderColor = '#34d399'; }
        if (label) { label.textContent = 'Selesai ✓'; label.className = 'cs-done'; }
        setTimeout(function() {
            spinner.style.opacity = '0';
            setTimeout(function() { spinner.style.display = 'none'; }, 500);
        }, 1000);
    });
});
</script>
@endpush
