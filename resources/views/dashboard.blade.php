@extends('layouts.app')

@section('title', 'Trending Analysis: Dashboard')

@push('styles')
<style>
.prodline-card .card-body { padding: 8px 12px 4px; }
.prodline-card .spark-chart { width: 100%; height: 100px; }
.spark-no-data { width:100%; height:100px; display:flex; align-items:center; justify-content:center; font-size:11px; color:var(--text-muted); opacity:.6; letter-spacing:.03em; }
.prodline-card.card-alert { border-color: rgba(239,68,68,.6) !important; animation: pulse-red 2.5s ease-in-out infinite; }
@keyframes pulse-red {
    0%, 100% { box-shadow: 0 0 6px rgba(239,68,68,.35); }
    50%       { box-shadow: 0 0 18px rgba(239,68,68,.75), 0 0 6px rgba(239,68,68,.9); }
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <h5 class="mb-3"><i class="bi bi-speedometer2"></i> {{ __('app.dash_title') }}</h5>

    @if($prodlines->isEmpty())
        <div class="alert alert-info">{{ __('app.dash_no_data') }}</div>
    @else
        <div class="row">
            @foreach($prodlines as $pl)
                <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                    <a href="{{ route('dashboard.detail', $pl->prodline, false) }}" class="text-decoration-none">
                        <div class="card prodline-card {{ $pl->max_result >= $specLimit ? 'card-alert' : '' }}">
                            <div class="card-header">
                                <i class="bi bi-activity"></i> {{ $pl->prodline }}
                            </div>
                            <div class="card-body">
                                <div class="spark-chart" id="spark_{{ Str::slug($pl->prodline) }}"></div>
                                <div class="prodline-stats">
                                    <span>{{ __('app.dash_samples') }}: <span class="val">{{ $pl->total_samples }}</span></span>
                                    <span>{{ __('app.dash_avg') }}: <span class="val {{ round($pl->avg_result,1) >= $specLimit ? 'text-danger' : 'text-success' }}">{{ round($pl->avg_result, 2) }}</span></span>
                                    <span>{{ __('app.dash_max') }}: <span class="val {{ $pl->max_result >= $specLimit ? 'text-danger' : '' }}">{{ $pl->max_result }}</span></span>
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
    var textColor = isDark ? '#9ca3af' : '#666';
    var gridColor = isDark ? '#3a3f47' : '#eee';
    var specLimit = JSON.parse(document.getElementById('__specLimit').textContent);
    var sparklines = JSON.parse(document.getElementById('__sparklines').textContent);

    function slugify(s) { return s.replace(/[^a-z0-9]+/gi, '-').toLowerCase(); }

    Object.keys(sparklines).forEach(function(prodline) {
        var divId = 'spark_' + slugify(prodline);
        var el = document.getElementById(divId);
        if (!el) return;
        if (!sparklines[prodline] || !sparklines[prodline].length) {
            el.innerHTML = '<div class="spark-no-data"><i class="bi bi-graph-up me-1"></i> No chart data</div>';
            return;
        }

        var raw = sparklines[prodline];

        var chart = am4core.create(divId, am4charts.XYChart);
        chart.data = raw;
        chart.paddingTop = 5;
        chart.paddingBottom = 0;
        chart.paddingLeft = 0;
        chart.paddingRight = 0;

        var catAxis = chart.xAxes.push(new am4charts.CategoryAxis());
        catAxis.dataFields.category = 'i';
        catAxis.renderer.grid.template.disabled = true;
        catAxis.renderer.labels.template.disabled = true;  // no x labels — too many points

        var valAxis = chart.yAxes.push(new am4charts.ValueAxis());
        valAxis.min = 0;
        valAxis.strictMinMax = true;   // force axis to start at 0 always
        valAxis.extraMax = 0.15;       // small breathing room above max value
        valAxis.renderer.grid.template.stroke = am4core.color(gridColor);
        valAxis.renderer.grid.template.strokeDasharray = '2,2';
        valAxis.renderer.labels.template.fontSize = 9;
        valAxis.renderer.labels.template.fill = am4core.color(textColor);
        valAxis.renderer.minGridDistance = 30;

        // Colour line red if ANY record breaches spec, blue otherwise
        var hasSpike = raw.some(function(d) { return d.avg >= specLimit; });
        var lineColor = hasSpike ? '#ef4444' : '#3b82f6';

        var series = chart.series.push(new am4charts.LineSeries());
        series.dataFields.valueY = 'avg';
        series.dataFields.categoryX = 'i';
        series.strokeWidth = 2;
        series.stroke = am4core.color(lineColor);
        series.fillOpacity = 0;   // no fill — prevents closed-path distortion
        series.tensionX = 0.77;   // smooth bezier curve
        series.tooltipText = '{lbl}: {avg} CFU';

        // No dots — clean smooth line only; spike still visible as line turns red

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
