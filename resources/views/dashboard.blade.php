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

/* Dashboard page-load spinner overlay */
#dashSpinner {
    position: fixed; inset: 0; z-index: 1055;
    background: rgba(0,0,0,.52);
    backdrop-filter: blur(3px);
    display: flex; align-items: center; justify-content: center;
    animation: dsFadeIn .2s ease;
    transition: opacity .5s ease;
}
@keyframes dsFadeIn { from { opacity:0; } to { opacity:1; } }
#dashSpinner .ds-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 14px;
    padding: 36px 40px 28px;
    min-width: 300px; max-width: 360px; width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
    animation: dsSlideUp .25s ease;
}
@keyframes dsSlideUp { from { transform: translateY(16px); opacity:0; } to { transform: translateY(0); opacity:1; } }
.ds-brand { font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #5b9bd5; margin-bottom: 18px; opacity: .85; }
.ds-brand i { font-size: 13px; margin-right: 4px; }
.ds-ring-wrap {
    width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 16px;
    display: flex; align-items: center; justify-content: center;
}
.ds-ring {
    width: 48px; height: 48px;
    border: 4px solid rgba(96,165,250,.25);
    border-top-color: #60a5fa;
    border-radius: 50%;
    animation: dsSpin .85s linear infinite;
}
@keyframes dsSpin { to { transform: rotate(360deg); } }
.ds-title { font-size: 15px; font-weight: 700; color: var(--text-body); margin-bottom: 4px; }
.ds-sub { font-size: 12px; color: var(--text-muted); }
.ds-done { color: #34d399; font-size: 13px; font-weight: 700; }
</style>
@endpush

@section('content')
{{-- Page-load spinner — visible until all sparklines are ready --}}
<div id="dashSpinner">
    <div class="ds-card">
        <div class="ds-brand"><i class="bi bi-graph-up"></i> Laboratory Trending Analysis</div>
        <div class="ds-ring-wrap"><div class="ds-ring"></div></div>
        <div class="ds-title" id="dsTitle">{{ __('app.dash_title') }}</div>
        <div class="ds-sub" id="dsSub">{{ __('app.lbl_loading_charts') }}</div>
    </div>
</div>

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

    var chartCount   = Object.keys(sparklines).filter(function(k) {
        return sparklines[k] && sparklines[k].length > 0;
    }).length;
    var chartsReady = 0;

    function onChartReady() {
        chartsReady++;
        if (chartsReady >= chartCount) {
            var spinner = document.getElementById('dashSpinner');
            var title   = document.getElementById('dsTitle');
            var sub     = document.getElementById('dsSub');
            var ring    = spinner ? spinner.querySelector('.ds-ring') : null;
            if (ring)  { ring.style.animation = 'none'; ring.style.borderColor = '#34d399'; }
            if (title) title.textContent = 'Selesai ✓';
            if (sub)   { sub.textContent = ''; sub.className = 'ds-done'; }
            setTimeout(function() {
                if (spinner) {
                    spinner.style.opacity = '0';
                    setTimeout(function() { spinner.style.display = 'none'; }, 500);
                }
            }, 800);
        }
    }

    // If no charts to render, dismiss immediately
    if (chartCount === 0) onChartReady();

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

        chart.events.on('ready', onChartReady);
    });
});
</script>
@endpush
