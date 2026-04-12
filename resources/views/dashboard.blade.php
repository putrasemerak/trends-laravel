@extends('layouts.app')

@section('title', 'Trending Analysis: Dashboard')

@push('styles')
<style>
.prodline-card {
    cursor: pointer;
    transition: transform .15s, box-shadow .15s;
    border-radius: 8px;
    overflow: hidden;
}
.prodline-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,.12);
}
[data-theme="dark"] .prodline-card:hover {
    box-shadow: 0 6px 20px rgba(0,0,0,.4);
}
.prodline-card .card-header {
    font-size: 13px;
    font-weight: 700;
    padding: 8px 12px;
}
.prodline-card .card-body { padding: 8px 12px 4px; }
.prodline-card .spark-chart { width: 100%; height: 100px; }
.prodline-stats { font-size: 10px; color: var(--text-muted); display: flex; justify-content: space-between; padding: 4px 0 2px; }
.prodline-stats .val { font-weight: 700; }
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h5 class="mb-3"><i class="bi bi-speedometer2"></i> Dashboard — Bioburden Trending Overview</h5>

    @if($prodlines->isEmpty())
        <div class="alert alert-info">No data available yet. Upload test results to get started.</div>
    @else
        <div class="row">
            @foreach($prodlines as $pl)
                <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                    <a href="{{ route('dashboard.detail', $pl->prodline) }}" class="text-decoration-none">
                        <div class="card prodline-card">
                            <div class="card-header">
                                <i class="bi bi-activity"></i> {{ $pl->prodline }}
                            </div>
                            <div class="card-body">
                                <div class="spark-chart" id="spark_{{ Str::slug($pl->prodline) }}"></div>
                                <div class="prodline-stats">
                                    <span>Samples: <span class="val">{{ $pl->total_samples }}</span></span>
                                    <span>Avg: <span class="val {{ round($pl->avg_result,1) >= $specLimit ? 'text-danger' : 'text-success' }}">{{ round($pl->avg_result, 2) }}</span></span>
                                    <span>Max: <span class="val {{ $pl->max_result >= $specLimit ? 'text-danger' : '' }}">{{ $pl->max_result }}</span></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script type="application/json" id="__sparklines">@json($sparklines)</script>
<script type="application/json" id="__specLimit">{{ $specLimit }}</script>

<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>

<script>
am4core.ready(function() {
    am4core.useTheme(am4themes_animated);

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#dee2e6' : '#666';
    var gridColor = isDark ? '#3a3f47' : '#eee';
    var specLimit = JSON.parse(document.getElementById('__specLimit').textContent);
    var sparklines = JSON.parse(document.getElementById('__sparklines').textContent);

    function slugify(s) { return s.replace(/[^a-z0-9]+/gi, '-').toLowerCase(); }

    Object.keys(sparklines).forEach(function(prodline) {
        var divId = 'spark_' + slugify(prodline);
        var el = document.getElementById(divId);
        if (!el || !sparklines[prodline].length) return;

        var chart = am4core.create(divId, am4charts.XYChart);
        chart.data = sparklines[prodline];
        chart.paddingTop = 5;
        chart.paddingBottom = 0;
        chart.paddingLeft = 0;
        chart.paddingRight = 0;

        var catAxis = chart.xAxes.push(new am4charts.CategoryAxis());
        catAxis.dataFields.category = 'month';
        catAxis.renderer.grid.template.disabled = true;
        catAxis.renderer.labels.template.fontSize = 9;
        catAxis.renderer.labels.template.fill = am4core.color(textColor);
        catAxis.renderer.minGridDistance = 40;

        var valAxis = chart.yAxes.push(new am4charts.ValueAxis());
        valAxis.min = 0;
        valAxis.renderer.grid.template.stroke = am4core.color(gridColor);
        valAxis.renderer.grid.template.strokeDasharray = '2,2';
        valAxis.renderer.labels.template.fontSize = 9;
        valAxis.renderer.labels.template.fill = am4core.color(textColor);
        valAxis.renderer.minGridDistance = 30;

        var series = chart.series.push(new am4charts.LineSeries());
        series.dataFields.valueY = 'avg';
        series.dataFields.categoryX = 'month';
        series.strokeWidth = 2;
        series.stroke = am4core.color('#3b82f6');
        series.fill = am4core.color('#3b82f6');
        series.fillOpacity = 0.08;
        series.tensionX = 0.8;
        series.tooltipText = '{month}: {avg} CFU';

        var bullet = series.bullets.push(new am4charts.CircleBullet());
        bullet.circle.radius = 3;
        bullet.circle.fill = am4core.color('#3b82f6');
        bullet.circle.strokeWidth = 0;

        // Spec limit line
        var range = valAxis.axisRanges.create();
        range.value = specLimit;
        range.grid.stroke = am4core.color('#ef4444');
        range.grid.strokeWidth = 1;
        range.grid.strokeDasharray = '3,3';
        range.grid.strokeOpacity = 0.6;

        chart.cursor = new am4charts.XYCursor();
        chart.cursor.lineY.disabled = true;
    });
});
</script>
@endpush
