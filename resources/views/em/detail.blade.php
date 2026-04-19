@extends('layouts.app')
@section('title', $machine . ' — EM Detail')

@push('styles')
<style>
/* ── Chart cards ───────────────────────────────────────────── */
.em-chart-card {
    border-left: 4px solid #5b9bd5;
    margin-bottom: 1.25rem;
}
.em-chart-card .card-header {
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
}
.em-chart-wrap {
    position: relative;
    width: 100%;
    height: 320px;
}
.em-chart-wrap.tall { height: 360px; }

/* ── Spinner overlay ────────────────────────────────────────── */
.em-spinner-overlay {
    position: absolute; inset: 0; z-index: 20;
    display: flex; align-items: center; justify-content: center;
    background: rgba(30,41,59,.88); border-radius: 0 0 6px 6px;
    transition: opacity .45s ease;
}
.em-spinner-overlay .eso-ring {
    width: 40px; height: 40px;
    border: 4px solid rgba(96,165,250,.25);
    border-top-color: #60a5fa;
    border-radius: 50%;
    animation: esoSpin .85s linear infinite;
}
@keyframes esoSpin { to { transform: rotate(360deg); } }

/* ── Machine tab pills (matches bioburden prodline tabs) ─────── */
.em-machine-pills .nav-link {
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
.em-machine-pills .nav-link:hover {
    color: #60a5fa;
    border-color: #60a5fa;
    box-shadow: 0 0 8px rgba(96,165,250,.55), 0 0 2px rgba(96,165,250,.8);
    text-decoration: none;
}
.em-machine-pills .nav-link.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
    box-shadow: 0 0 10px rgba(59,130,246,.6);
}

/* ── Year pill filter ───────────────────────────────────────── */
.em-year-pills .btn-year {
    font-size: 11px;
    padding: 3px 12px;
    border-radius: 20px;
    border: 1px solid var(--border-color);
    background: var(--bg-card);
    color: var(--text-body);
    text-decoration: none;
    transition: border-color .2s, box-shadow .2s;
}
.em-year-pills .btn-year:hover {
    border-color: #5b9bd5;
    box-shadow: 0 0 8px rgba(91,155,213,.45);
}
.em-year-pills .btn-year.active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}

/* ── No-data placeholder ────────────────────────────────────── */
.em-no-data {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    flex-direction: column;
    color: var(--text-muted);
    font-size: 13px;
    gap: 8px;
}
.em-no-data i { font-size: 2rem; opacity: .35; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4 mt-3 pb-5">

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap align-items-center mb-2" style="gap:10px;">
        <a href="{{ route('em.dashboard', [], false) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> All Machines
        </a>
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-wind" style="color:#5b9bd5;"></i>
            {{ $machine }}
        </h5>

        {{-- Year filter pills --}}
        <div class="em-year-pills d-flex flex-wrap ms-auto" style="gap:6px;">
            @foreach($years as $yr)
                <a href="{{ route('em.detail', $machine, false) }}?year={{ $yr }}"
                   class="btn-year {{ $yr == $selectedYear ? 'active' : '' }}">{{ $yr }}</a>
            @endforeach
        </div>
    </div>

    {{-- ── Machine shortcut nav (all uploaded machines) ───────────── --}}
    <nav class="em-machine-pills mb-3">
        <ul class="nav flex-wrap">
            @foreach($allMachines as $mc)
                <li class="nav-item">
                    <a class="nav-link {{ $mc === $machine ? 'active' : '' }}"
                       href="{{ route('em.detail', $mc, false) }}?year={{ $selectedYear }}">{{ $mc }}</a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Charts 1-N — Personnel Hygiene (one card per test_type)     --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    @if(empty($workerSeries))
    <div class="card em-chart-card">
        <div class="card-header">
            <i class="bi bi-person-badge" style="color:#5b9bd5;"></i>
            Personnel Hygiene
        </div>
        <div class="card-body p-2">
            <div class="em-chart-wrap" style="height:120px;">
                <div class="em-no-data">
                    <i class="bi bi-person-slash"></i>
                    No personnel data for {{ $selectedYear }}
                </div>
            </div>
        </div>
    </div>
    @else
        @php
            $testTypeLabels = [
                'fdab'      => 'F/DAB — Worker vs CFU/Glove',
                'garment'   => 'Garment — Worker vs CFU/Glove',
                'personnel' => 'Personnel Hygiene — Worker vs CFU/Glove',
            ];
        @endphp
        @foreach($workerSeries as $testType => $rows)
        <div class="card em-chart-card">
            <div class="card-header">
                <i class="bi bi-person-badge" style="color:#5b9bd5;"></i>
                {{ $testTypeLabels[$testType] ?? (strtoupper($testType) . ' — Worker vs CFU/Glove') }}
            </div>
            <div class="card-body p-2">
                <div class="em-chart-wrap tall">
                    <div class="em-spinner-overlay" id="workerSpinner_{{ $testType }}">
                        <div class="eso-ring"></div>
                    </div>
                    <div id="workerChart_{{ $testType }}" style="width:100%;height:100%;"></div>
                </div>
            </div>
        </div>
        @endforeach
    @endif

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Chart 2 — Location Monitoring (Sheet1) — cfu/plate          --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="card em-chart-card">
        <div class="card-header">
            <i class="bi bi-grid-3x3-gap" style="color:#5b9bd5;"></i>
            Surface / Location Monitoring — Date vs CFU/Plate
        </div>
        <div class="card-body p-2">
            <div class="em-chart-wrap tall" id="locationWrap">
                @if(empty($locationSeries))
                    <div class="em-no-data">
                        <i class="bi bi-clipboard-x"></i>
                        No surface location data for {{ $selectedYear }}
                    </div>
                @else
                    <div class="em-spinner-overlay" id="locationSpinner">
                        <div class="eso-ring"></div>
                    </div>
                    <div id="locationChart" style="width:100%;height:100%;"></div>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════ --}}
    {{-- Chart 3 — SP & AS Lain2 — cfu/4hrs                         --}}
    {{-- ══════════════════════════════════════════════════════════════ --}}
    <div class="card em-chart-card">
        <div class="card-header">
            <i class="bi bi-wind" style="color:#5b9bd5;"></i>
            Settle Plate &amp; Air Sampling (SP &amp; AS Lain2) — Date vs CFU/4hrs
        </div>
        <div class="card-body p-2">
            <div class="em-chart-wrap tall" id="spasWrap">
                @if(empty($spasSeries))
                    <div class="em-no-data">
                        <i class="bi bi-clipboard-x"></i>
                        No SP &amp; AS data for {{ $selectedYear }}
                    </div>
                @else
                    <div class="em-spinner-overlay" id="spasSpinner">
                        <div class="eso-ring"></div>
                    </div>
                    <div id="spasChart" style="width:100%;height:100%;"></div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
{{-- ── Inline JSON data blobs ────────────────────────────────────────── --}}
<script type="application/json" id="__workerSeries">@json($workerSeries)</script>
<script type="application/json" id="__locationSeries">@json($locationSeries)</script>
<script type="application/json" id="__surfaceLimit">@json($surfaceLimit)</script>
<script type="application/json" id="__spasSeries">@json($spasSeries)</script>
<script type="application/json" id="__spasLimit">@json($spasLimit)</script>

{{-- amCharts 4 --}}
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script>
(function () {
    'use strict';

    // ── Shared theme colours ────────────────────────────────────────────
    var BG_CHART   = '#2d3748';
    var TEXT_COLOR = '#f1f5f9';
    var GRID_COLOR = '#4a5568';
    var COLORS     = ['#60a5fa','#34d399','#fbbf24','#f87171','#a78bfa',
                      '#38bdf8','#fb923c','#4ade80','#e879f9','#f472b6'];

    function dismissSpinner(id) {
        var el = document.getElementById(id);
        if (!el) return;
        el.style.opacity = '0';
        setTimeout(function () { el.style.display = 'none'; }, 500);
    }

    function addScrollbar(chart) {
        var sb = new am4core.Scrollbar();
        chart.scrollbarX = sb;
        chart.scrollbarX.parent = chart.bottomAxesContainer;
        sb.thumb.background.fill    = am4core.color('#6ee7b7');
        sb.thumb.background.opacity = 0.8;
        sb.startGrip.background.fill = am4core.color('#f59e0b');
        sb.endGrip.background.fill   = am4core.color('#f59e0b');
        sb.background.fill           = am4core.color('#4a5568');
        sb.background.opacity        = 0.4;
    }

    function styledValueAxis(chart, title) {
        var ax = chart.yAxes.push(new am4charts.ValueAxis());
        ax.min = 0;
        ax.strictMinMax = true;
        ax.extraMax = 0.2;
        ax.title.text = title;
        ax.title.fill = am4core.color(TEXT_COLOR);
        ax.title.fontSize = 11;
        ax.renderer.labels.template.fill = am4core.color(TEXT_COLOR);
        ax.renderer.grid.template.stroke = am4core.color(GRID_COLOR);
        ax.renderer.grid.template.strokeOpacity = 0.5;
        ax.renderer.line.stroke = am4core.color(GRID_COLOR);
        ax.renderer.ticks.template.stroke = am4core.color(GRID_COLOR);
        return ax;
    }

    function addLimitLine(valAxis, limitVal, label) {
        if (limitVal === null || limitVal === undefined) return;
        var range = valAxis.axisRanges.create();
        range.value = limitVal;
        range.grid.stroke = am4core.color('#f87171');
        range.grid.strokeWidth = 1.5;
        range.grid.strokeDasharray = '5,3';
        range.label.text = label + ': ' + limitVal;
        range.label.fill = am4core.color('#f87171');
        range.label.inside = true;
        range.label.verticalCenter = 'bottom';
        range.label.fontSize = 10;
    }

    function styledChart(containerId) {
        var chart = am4core.create(containerId, am4charts.XYChart);
        chart.background.fill = am4core.color(BG_CHART);
        chart.background.fillOpacity = 1;
        chart.plotContainer.background.fillOpacity = 0;
        chart.cursor = new am4charts.XYCursor();
        chart.cursor.lineY.disabled = true;
        chart.legend = new am4charts.Legend();
        chart.legend.labels.template.fill = am4core.color(TEXT_COLOR);
        chart.legend.labels.template.fontSize = 11;
        chart.legend.position = 'bottom';
        chart.legend.maxHeight = 60;
        chart.paddingBottom = 10;
        return chart;
    }

    // ════════════════════════════════════════════════════════════════════
    // Charts 1-N — Personnel Hygiene, one chart per test_type
    // ════════════════════════════════════════════════════════════════════
    var workerSeriesRaw = JSON.parse(
        document.getElementById('__workerSeries').textContent || '{}'
    );

    function buildWorkerChart(testType, rows) {
        var containerId = 'workerChart_' + testType;
        var spinnerId   = 'workerSpinner_' + testType;
        if (!document.getElementById(containerId)) return;

        am4core.useTheme(am4themes_animated);
        var chart = styledChart(containerId);
        // No legend needed for single-series worker chart
        chart.legend.disabled = true;

        // Aggregate per worker: sum + count → average
        var agg = {};
        rows.forEach(function (row) {
            if (!agg[row.worker]) {
                agg[row.worker] = { worker: row.worker, sum: 0, cnt: 0, limit: row.limit };
            }
            agg[row.worker].sum += row.value;
            agg[row.worker].cnt += 1;
        });
        var data = Object.values(agg).map(function (r) {
            return { worker: r.worker, value: Math.round((r.sum / r.cnt) * 100) / 100, limit: r.limit };
        }).sort(function (a, b) { return a.worker < b.worker ? -1 : 1; });
        chart.data = data;

        var catAx = chart.xAxes.push(new am4charts.CategoryAxis());
        catAx.dataFields.category = 'worker';
        catAx.renderer.grid.template.location = 0;
        catAx.renderer.labels.template.rotation = -45;
        catAx.renderer.labels.template.horizontalCenter = 'right';
        catAx.renderer.labels.template.verticalCenter = 'middle';
        catAx.renderer.labels.template.fontSize = 10;
        catAx.renderer.labels.template.fill = am4core.color(TEXT_COLOR);
        catAx.renderer.grid.template.stroke = am4core.color(GRID_COLOR);
        catAx.renderer.grid.template.strokeOpacity = 0.5;
        catAx.renderer.minGridDistance = 30;
        catAx.title.text = 'Worker ID';
        catAx.title.fill = am4core.color(TEXT_COLOR);
        catAx.title.fontSize = 11;

        var valAx = styledValueAxis(chart, 'CFU / Glove');

        var col = chart.series.push(new am4charts.ColumnSeries());
        col.dataFields.valueY    = 'value';
        col.dataFields.categoryX = 'worker';
        col.name = testType.toUpperCase();
        col.columns.template.fill         = am4core.color(COLORS[0]);
        col.columns.template.stroke       = am4core.color(COLORS[0]);
        col.columns.template.fillOpacity  = 0.85;
        col.columns.template.width        = am4core.percent(60);
        col.tooltipText = 'Worker: {categoryX}\nAvg CFU/Glove: {valueY}';

        // Colour bars that exceed the limit red
        col.columns.template.adapter.add('fill', function (fill, target) {
            if (target.dataItem) {
                var lim = target.dataItem.dataContext.limit;
                if (lim !== null && lim !== undefined && target.dataItem.valueY >= lim) {
                    return am4core.color('#f87171');
                }
            }
            return fill;
        });

        var firstLimit = data.length > 0 ? (data[0].limit || null) : null;
        addLimitLine(valAx, firstLimit, 'Limit');

        if (data.length > 20) addScrollbar(chart);
        chart.events.on('ready', function () { dismissSpinner(spinnerId); });
    }

    Object.keys(workerSeriesRaw).forEach(function (testType) {
        buildWorkerChart(testType, workerSeriesRaw[testType]);
    });

    // ════════════════════════════════════════════════════════════════════
    // Chart 2 — Surface Locations (sheet1) — cfu/plate
    // ════════════════════════════════════════════════════════════════════
    var locationSeriesRaw = JSON.parse(
        document.getElementById('__locationSeries').textContent || '{}'
    );
    var surfaceLimit = JSON.parse(
        document.getElementById('__surfaceLimit').textContent
    );

    if (document.getElementById('locationChart')) {
        am4core.useTheme(am4themes_animated);
        var lChart = styledChart('locationChart');

        // Build unified date-keyed dataset
        // Collect all dates
        var allDates = {};
        var locKeys  = Object.keys(locationSeriesRaw);
        locKeys.forEach(function (lk) {
            locationSeriesRaw[lk].forEach(function (pt) {
                if (!allDates[pt.date]) allDates[pt.date] = { date: pt.date, label: pt.label };
                allDates[pt.date][lk] = pt.value;
            });
        });
        var lData = Object.values(allDates).sort(function (a, b) {
            return a.date < b.date ? -1 : a.date > b.date ? 1 : 0;
        });
        lChart.data = lData;

        // Category axis — date label
        var lCatAx = lChart.xAxes.push(new am4charts.CategoryAxis());
        lCatAx.dataFields.category = 'date';
        lCatAx.renderer.labels.template.text = '{label}';
        lCatAx.renderer.grid.template.location = 0;
        lCatAx.renderer.labels.template.rotation = -45;
        lCatAx.renderer.labels.template.horizontalCenter = 'right';
        lCatAx.renderer.labels.template.verticalCenter = 'middle';
        lCatAx.renderer.labels.template.fontSize = 10;
        lCatAx.renderer.labels.template.fill = am4core.color(TEXT_COLOR);
        lCatAx.renderer.grid.template.stroke = am4core.color(GRID_COLOR);
        lCatAx.renderer.grid.template.strokeOpacity = 0.5;
        lCatAx.renderer.minGridDistance = 40;
        lCatAx.title.text = 'Sample Date';
        lCatAx.title.fill = am4core.color(TEXT_COLOR);
        lCatAx.title.fontSize = 11;

        var lValAx = styledValueAxis(lChart, 'CFU / Plate');

        // One line per location
        locKeys.forEach(function (lk, idx) {
            var ls = lChart.series.push(new am4charts.LineSeries());
            ls.dataFields.valueY    = lk;
            ls.dataFields.categoryX = 'date';
            ls.name = lk;
            ls.stroke = am4core.color(COLORS[idx % COLORS.length]);
            ls.strokeWidth = 2;
            ls.fillOpacity = 0;
            ls.tensionX = 0.9;
            ls.tooltipText = '{name}\nDate: {date}\nCFU/Plate: {valueY}';

            var bullet = ls.bullets.push(new am4charts.CircleBullet());
            bullet.circle.radius = 3;
            bullet.circle.strokeWidth = 0;
            bullet.circle.fill = am4core.color(COLORS[idx % COLORS.length]);
        });

        addLimitLine(lValAx, surfaceLimit, 'Limit');
        if (lData.length > 15) addScrollbar(lChart);
        lChart.events.on('ready', function () { dismissSpinner('locationSpinner'); });
    }

    // ════════════════════════════════════════════════════════════════════
    // Chart 3 — SP & AS Lain2 — cfu/4hrs
    // ════════════════════════════════════════════════════════════════════
    var spasSeriesRaw = JSON.parse(
        document.getElementById('__spasSeries').textContent || '{}'
    );
    var spasLimit = JSON.parse(
        document.getElementById('__spasLimit').textContent
    );

    if (document.getElementById('spasChart')) {
        am4core.useTheme(am4themes_animated);
        var sChart = styledChart('spasChart');

        var spAllDates = {};
        var spKeys     = Object.keys(spasSeriesRaw);
        spKeys.forEach(function (sk) {
            spasSeriesRaw[sk].forEach(function (pt) {
                if (!spAllDates[pt.date]) spAllDates[pt.date] = { date: pt.date, label: pt.label };
                spAllDates[pt.date][sk] = pt.value;
            });
        });
        var sData = Object.values(spAllDates).sort(function (a, b) {
            return a.date < b.date ? -1 : a.date > b.date ? 1 : 0;
        });
        sChart.data = sData;

        var sCatAx = sChart.xAxes.push(new am4charts.CategoryAxis());
        sCatAx.dataFields.category = 'date';
        sCatAx.renderer.labels.template.text = '{label}';
        sCatAx.renderer.grid.template.location = 0;
        sCatAx.renderer.labels.template.rotation = -45;
        sCatAx.renderer.labels.template.horizontalCenter = 'right';
        sCatAx.renderer.labels.template.verticalCenter = 'middle';
        sCatAx.renderer.labels.template.fontSize = 10;
        sCatAx.renderer.labels.template.fill = am4core.color(TEXT_COLOR);
        sCatAx.renderer.grid.template.stroke = am4core.color(GRID_COLOR);
        sCatAx.renderer.grid.template.strokeOpacity = 0.5;
        sCatAx.renderer.minGridDistance = 40;
        sCatAx.title.text = 'Sample Date';
        sCatAx.title.fill = am4core.color(TEXT_COLOR);
        sCatAx.title.fontSize = 11;

        var sValAx = styledValueAxis(sChart, 'CFU / 4hrs');

        spKeys.forEach(function (sk, idx) {
            var ss = sChart.series.push(new am4charts.LineSeries());
            ss.dataFields.valueY    = sk;
            ss.dataFields.categoryX = 'date';
            ss.name = sk;
            ss.stroke = am4core.color(COLORS[idx % COLORS.length]);
            ss.strokeWidth = 2;
            ss.fillOpacity = 0;
            ss.tensionX = 0.9;
            ss.tooltipText = '{name}\nDate: {date}\nCFU/4hrs: {valueY}';

            var bullet = ss.bullets.push(new am4charts.CircleBullet());
            bullet.circle.radius = 3;
            bullet.circle.strokeWidth = 0;
            bullet.circle.fill = am4core.color(COLORS[idx % COLORS.length]);
        });

        addLimitLine(sValAx, spasLimit, 'Limit');
        if (sData.length > 15) addScrollbar(sChart);
        sChart.events.on('ready', function () { dismissSpinner('spasSpinner'); });
    }

})();
</script>
@endpush
