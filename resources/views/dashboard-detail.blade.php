@extends('layouts.app')

@section('title', $prodline . ' — Detail Dashboard')

@push('styles')
<style>
#monthlyChart { width: 100%; height: 280px; }
#batchChart   { width: 100%; height: 260px; }
.tab-pills .nav-link {
    font-size: 11px;
    padding: 4px 10px;
    border-radius: 6px;
    margin-right: 4px;
    margin-bottom: 4px;
    color: var(--text-body);
    border: 1px solid var(--border-color);
    background: var(--bg-card);
}
.tab-pills .nav-link.active {
    background: #3b82f6;
    color: #fff;
    border-color: #3b82f6;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4">

    {{-- Back + Title --}}
    <div class="d-flex align-items-center mb-2" style="gap:10px;">
        <a href="{{ route('dashboard', [], false) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> All Products
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
                <div class="stat-label">Total Samples</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-success">{{ round($stats->avg_result, 2) }}</div>
                <div class="stat-label">Average CFU</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number {{ $stats->max_result >= $specLimit ? 'text-danger' : '' }}">{{ $stats->max_result }}</div>
                <div class="stat-label">Max CFU</div>
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <div class="stat-card">
                <div class="stat-number text-info" style="font-size:1.1rem;">
                    {{ $stats->latest_test ? \Carbon\Carbon::parse($stats->latest_test)->format('d-M-Y') : '-' }}
                </div>
                <div class="stat-label">Latest Test</div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="row mb-3">
        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-header"><strong><i class="bi bi-graph-up"></i> Monthly Trend (12 Months)</strong></div>
                <div class="card-body"><div id="monthlyChart"></div></div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="card">
                <div class="card-header"><strong><i class="bi bi-bar-chart"></i> Batch Results (Last 60 Days)</strong></div>
                <div class="card-body"><div id="batchChart"></div></div>
            </div>
        </div>
    </div>

    {{-- Recent Entries Table --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><strong><i class="bi bi-clock-history"></i> Recent Entries</strong></div>
                <div class="card-body">
                    <table class="table table-sm table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th>Batch</th>
                                <th class="text-center">Run</th>
                                <th class="text-center">TAMC R1</th>
                                <th class="text-center">TAMC R2</th>
                                <th class="text-center">TYMC R1</th>
                                <th class="text-center">TYMC R2</th>
                                <th class="text-center">Avg</th>
                                <th>Added By</th>
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
</div>
@endsection

@push('scripts')
<script type="application/json" id="__monthlyData">@json($monthlyChartData)</script>
<script type="application/json" id="__batchData">@json($batchData)</script>
<script type="application/json" id="__specLimit">{{ $specLimit }}</script>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script>
am4core.ready(function() {
    am4core.useTheme(am4themes_animated);

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#dee2e6' : '#212529';
    var gridColor = isDark ? '#3a3f47' : '#e0e0e0';
    var specLimit = JSON.parse(document.getElementById('__specLimit').textContent);

    // ========== Monthly Trend ==========
    var chart1 = am4core.create('monthlyChart', am4charts.XYChart);
    chart1.data = JSON.parse(document.getElementById('__monthlyData').textContent);

    var catAxis1 = chart1.xAxes.push(new am4charts.CategoryAxis());
    catAxis1.dataFields.category = 'month';
    catAxis1.renderer.grid.template.location = 0;
    catAxis1.renderer.labels.template.rotation = 45;
    catAxis1.renderer.labels.template.horizontalCenter = 'right';
    catAxis1.renderer.minGridDistance = 30;
    catAxis1.renderer.labels.template.fill = am4core.color(textColor);
    catAxis1.renderer.grid.template.stroke = am4core.color(gridColor);

    var valAxis1 = chart1.yAxes.push(new am4charts.ValueAxis());
    valAxis1.min = 0;
    valAxis1.title.text = 'Average CFU';
    valAxis1.title.fill = am4core.color(textColor);
    valAxis1.renderer.labels.template.fill = am4core.color(textColor);
    valAxis1.renderer.grid.template.stroke = am4core.color(gridColor);

    var series1 = chart1.series.push(new am4charts.LineSeries());
    series1.dataFields.valueY = 'avg';
    series1.dataFields.categoryX = 'month';
    series1.strokeWidth = 3;
    series1.stroke = am4core.color('#3b82f6');
    series1.fill = am4core.color('#3b82f6');
    series1.fillOpacity = 0.05;
    series1.tooltipText = '{month}\nAvg: {avg} CFU\nSamples: {count}';

    var bullet1 = series1.bullets.push(new am4charts.CircleBullet());
    bullet1.circle.radius = 4;
    bullet1.circle.strokeWidth = 2;
    bullet1.circle.fill = am4core.color(isDark ? '#22262d' : '#fff');

    var range1 = valAxis1.axisRanges.create();
    range1.value = specLimit;
    range1.grid.stroke = am4core.color('#ef4444');
    range1.grid.strokeWidth = 2;
    range1.grid.strokeDasharray = '4,4';
    range1.label.text = 'Limit: ' + specLimit;
    range1.label.fill = am4core.color('#ef4444');
    range1.label.inside = true;
    range1.label.verticalCenter = 'bottom';

    chart1.cursor = new am4charts.XYCursor();

    // ========== Batch Results ==========
    var chart2 = am4core.create('batchChart', am4charts.XYChart);
    chart2.data = JSON.parse(document.getElementById('__batchData').textContent);

    var catAxis2 = chart2.xAxes.push(new am4charts.CategoryAxis());
    catAxis2.dataFields.category = 'label';
    catAxis2.renderer.grid.template.location = 0;
    catAxis2.renderer.labels.template.rotation = -60;
    catAxis2.renderer.labels.template.horizontalCenter = 'right';
    catAxis2.renderer.labels.template.verticalCenter = 'middle';
    catAxis2.renderer.minGridDistance = 20;
    catAxis2.renderer.labels.template.fontSize = 9;
    catAxis2.renderer.labels.template.fill = am4core.color(textColor);
    catAxis2.renderer.grid.template.stroke = am4core.color(gridColor);

    var valAxis2 = chart2.yAxes.push(new am4charts.ValueAxis());
    valAxis2.min = 0;
    valAxis2.title.text = 'Result Avg (CFU)';
    valAxis2.title.fill = am4core.color(textColor);
    valAxis2.renderer.labels.template.fill = am4core.color(textColor);
    valAxis2.renderer.grid.template.stroke = am4core.color(gridColor);

    var series2 = chart2.series.push(new am4charts.ColumnSeries());
    series2.dataFields.valueY = 'avg';
    series2.dataFields.categoryX = 'label';
    series2.columns.template.tooltipText = '{product}\n{label}\n{date}: {avg} CFU';
    series2.columns.template.fill = am4core.color('#10b981');
    series2.columns.template.strokeWidth = 0;
    series2.columns.template.column.cornerRadiusTopLeft = 3;
    series2.columns.template.column.cornerRadiusTopRight = 3;

    // Color bars red if >= limit
    series2.columns.template.adapter.add('fill', function(fill, target) {
        if (target.dataItem && target.dataItem.valueY >= specLimit) {
            return am4core.color('#ef4444');
        }
        return fill;
    });

    var range2 = valAxis2.axisRanges.create();
    range2.value = specLimit;
    range2.grid.stroke = am4core.color('#ef4444');
    range2.grid.strokeWidth = 1.5;
    range2.grid.strokeDasharray = '4,4';

    chart2.cursor = new am4charts.XYCursor();
    chart2.scrollbarX = new am4core.Scrollbar();
});
</script>
@endpush
