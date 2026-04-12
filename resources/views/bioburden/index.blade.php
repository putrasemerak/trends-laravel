@extends('layouts.app')

@section('title', "Trending Analysis: Monitoring Bioburden Product ($prodline)")

@push('styles')
<style>
#chartdiv {
    width: 100%;
    height: 500px;
    max-width: 100%;
}
</style>
@endpush

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3 class="text-center">
                Monitoring Bioburden Product ({{ $prodline }})
                @if($accessLevel >= 2)
                    | <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">Add</button>
                @endif
            </h3>
            <hr>
        </div>
    </div>
</div>

{{-- Date Range Filter --}}
<div class="container">
    <div class="row justify-content-md-center">
        <div class="col-md-6 text-center">
            <form method="get" id="monthRangeForm" action="{{ route('bioburden.index') }}">
                <input type="hidden" name="prodline" value="{{ $prodline }}">
                <table class="table table-sm table-borderless">
                    <tr>
                        <th colspan="2">Month Start</th>
                        <th colspan="2">Month End</th>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>
                            <select class="form-control form-control-sm" name="monthStart" required>
                                <option value="">-- Select Month --</option>
                                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $name)
                                    <option value="{{ $i + 1 }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm" name="yearStart" required>
                                <option value="">-- Select Year --</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm" name="monthEnd" required>
                                <option value="">-- Select Month --</option>
                                @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $i => $name)
                                    <option value="{{ $i + 1 }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <select class="form-control form-control-sm" name="yearEnd" required>
                                <option value="">-- Select Year --</option>
                                @foreach($years as $year)
                                    <option value="{{ $year }}">{{ $year }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><button type="submit" class="btn btn-success btn-block btn-sm">Submit</button></td>
                        <td><a class="btn btn-primary btn-block btn-sm" href="{{ route('bioburden.index', ['prodline' => $prodline]) }}" role="button">Clear</a></td>
                    </tr>
                </table>
            </form>
            Showing results from {{ $dateStart->format('d-M-Y') }} to {{ $dateTo->format('d-M-Y') }}
        </div>
    </div>
</div>

{{-- Client-side validation for date range --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('monthRangeForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        var ms = parseInt(form.querySelector('select[name="monthStart"]').value, 10);
        var ys = parseInt(form.querySelector('select[name="yearStart"]').value, 10);
        var me = parseInt(form.querySelector('select[name="monthEnd"]').value, 10);
        var ye = parseInt(form.querySelector('select[name="yearEnd"]').value, 10);
        if (isNaN(ms) || isNaN(ys) || isNaN(me) || isNaN(ye)) return true;
        if (new Date(ys, ms - 1, 1) > new Date(ye, me, 0)) {
            e.preventDefault();
            alert('Start month/year cannot be later than End month/year.');
            return false;
        }
        return true;
    });
});
</script>

{{-- Chart --}}
<div id="chartdiv"></div>

{{-- Data Table --}}
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <table class="table table-hover table-sm table-striped table-condensed display" id="example">
                <thead>
                    <tr>
                        <th rowspan="3"></th>
                        <th rowspan="3" class="align-middle text-center">Date Tested</th>
                        <th rowspan="3" class="align-middle text-center">Product Name</th>
                        <th rowspan="3" class="align-middle text-center">Batch No.</th>
                        <th rowspan="3" class="align-middle text-center">Run</th>
                        <th colspan="4" class="align-middle text-center">Bioburden Result</th>
                        <th rowspan="3" class="align-middle text-center">Average</th>
                        <th rowspan="3" class="align-middle text-center">Limit</th>
                        <th rowspan="3" class="align-middle text-center">Remove</th>
                    </tr>
                    <tr>
                        <th colspan="2" class="align-middle text-center">TAMC</th>
                        <th colspan="2" class="align-middle text-center">TYMC</th>
                    </tr>
                    <tr>
                        <th class="align-middle text-center">R1</th>
                        <th class="align-middle text-center">R2</th>
                        <th class="align-middle text-center">R1</th>
                        <th class="align-middle text-center">R2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($results as $index => $row)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-center">{{ $row->datetested->format('d-M-Y') }}</td>
                            <td class="text-center">{{ $row->prodname }}</td>
                            <td class="text-center">{{ $row->batch }}</td>
                            <td class="text-center">{{ $row->runno }}</td>
                            <td class="text-center">{{ $row->tamcr1 }}</td>
                            <td class="text-center">{{ $row->tamcr2 }}</td>
                            <td class="text-center">{{ $row->tymcr1 }}</td>
                            <td class="text-center">{{ $row->tymcr2 }}</td>
                            <td class="text-center">{{ $row->resultavg }}</td>
                            <td class="text-center">{{ $row->limit }}</td>
                            <td class="text-center">
                                @if($accessLevel >= 4)
                                    <form method="POST" action="{{ route('bioburden.remove') }}" style="display:inline;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $row->id }}">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                onclick="return confirm('Please confirm deletion');">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Monthly Remark Section --}}
            @if($isSameMonth)
                @if($dataExistsTS0011)
                    <div class="alert alert-success mt-2" role="alert">
                        <i class="bi bi-check-circle"></i> Monthly remark is available for {{ $dateStart->format('F Y') }}
                        @if($accessLevel >= 4)
                            <button type="button" class="btn btn-warning btn-sm float-right" data-toggle="modal" data-target="#updateRemarkModal">Update Monthly Remark</button>
                        @endif
                        @if($remark)
                            <br><strong>Remark:</strong> {{ $remark->remark }}
                            <br><strong>By:</strong> {{ $remark->AddUser }} | {{ \Carbon\Carbon::parse($remark->AddDate)->format('d-M-Y') }}
                        @endif
                    </div>
                @else
                    <div class="alert alert-warning mt-2" role="alert">
                        <i class="bi bi-exclamation-circle"></i> No monthly remark available for {{ $dateStart->format('F Y') }}
                        @if($accessLevel >= 4)
                            <button type="button" class="btn btn-warning btn-sm float-right" data-toggle="modal" data-target="#insertRemarkModal">Insert Monthly Remark</button>
                        @endif
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<br><br>

{{-- Add Bioburden Result Modal --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Bioburden Result</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('bioburden.store') }}">
                    @csrf
                    <input type="hidden" name="prodline" value="{{ $prodline }}">
                    <table class="table table-borderless">
                        <tr>
                            <td>
                                <label class="font-weight-bold">Batch & Product Name</label>
                                <input list="batchList" class="form-control form-control-sm" name="batch" placeholder="Insert Batch or Product Name" required>
                                <datalist id="batchList">
                                    <option value="--">
                                    @foreach($batches as $b)
                                        <option value="{{ $b->Batch }} - {{ $b->PBrand }}">
                                    @endforeach
                                </datalist>
                            </td>
                            <td>
                                <label class="font-weight-bold">Run</label>
                                <input type="text" class="form-control form-control-sm" name="runno" placeholder="Insert Run No. (eg. R1)" required>
                            </td>
                            <td>
                                <label class="font-weight-bold">Date Tested</label>
                                <input type="date" class="form-control form-control-sm" name="datetested" required>
                            </td>
                        </tr>
                    </table>
                    <table class="table table-borderless">
                        <tr>
                            <td>
                                <label class="font-weight-bold">Bioburden Result - TAMC</label><br>
                                R1 <input type="number" class="form-control form-control-sm" name="tamcr1" id="tamcr1" required>
                                R2 <input type="number" class="form-control form-control-sm" name="tamcr2" id="tamcr2" required>
                            </td>
                            <td>
                                <label class="font-weight-bold">Bioburden Result - TYMC</label><br>
                                R1 <input type="number" class="form-control form-control-sm" name="tymcr1" id="tymcr1" required>
                                R2 <input type="number" class="form-control form-control-sm" name="tymcr2" id="tymcr2" required>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <label class="font-weight-bold">Average</label>
                                <input type="number" class="form-control form-control-sm" name="resultavg" id="resultavg" readonly>
                            </td>
                            <td>
                                <label class="font-weight-bold">Limit</label>
                                <input type="text" class="form-control form-control-sm" name="limit" value="{{ $specLimit }}" readonly>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <button type="submit" class="btn btn-success btn-block btn-sm">Submit</button>
                            </td>
                        </tr>
                    </table>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#csvModal" data-dismiss="modal">Add .CSV</button>
            </div>
        </div>
    </div>
</div>

{{-- CSV Upload Modal --}}
<div class="modal fade" id="csvModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload CSV — Bioburden Results</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('bioburden.upload-csv') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="prodline" value="{{ $prodline }}">
                    <div class="form-group">
                        <label class="font-weight-bold">CSV File</label>
                        <input type="file" class="form-control-file" name="csv_file" accept=".csv" required>
                        <small class="form-text text-muted">
                            CSV format: batch, prodname, datetested, runno, tamcr1, tamcr2, tymcr1, tymcr2, resultavg, limit
                        </small>
                    </div>
                    <button type="submit" class="btn btn-success btn-block btn-sm">Upload</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Insert Monthly Remark Modal --}}
<div class="modal fade" id="insertRemarkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Remark for {{ $dateStart->format('F Y') }} ({{ $prodline }})</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('bioburden.remark.store') }}">
                    @csrf
                    <label class="font-weight-bold">Remark</label>
                    <textarea class="form-control form-control-sm" name="remark" rows="4" required></textarea>
                    <input type="hidden" name="monthyear" value="{{ $dateStart->format('Y-m-d') }}">
                    <input type="hidden" name="prodline" value="{{ $prodline }}">
                    <br>
                    <button type="submit" class="btn btn-success btn-block btn-sm">Submit</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Update Monthly Remark Modal --}}
<div class="modal fade" id="updateRemarkModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Remark for {{ $dateStart->format('F Y') }} ({{ $prodline }})</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('bioburden.remark.update') }}">
                    @csrf
                    <label class="font-weight-bold">Remark</label>
                    <textarea class="form-control form-control-sm" name="remark" rows="4" required>{{ $remark->remark ?? '' }}</textarea>
                    <input type="hidden" name="monthyear" value="{{ $dateStart->format('Y-m-d') }}">
                    <input type="hidden" name="prodline" value="{{ $prodline }}">
                    <br>
                    <button type="submit" class="btn btn-success btn-block btn-sm">Update</button>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- amCharts 4 --}}
<script src="https://cdn.amcharts.com/lib/4/core.js"></script>
<script src="https://cdn.amcharts.com/lib/4/charts.js"></script>
<script src="https://cdn.amcharts.com/lib/4/themes/animated.js"></script>
<script src="https://cdn.amcharts.com/lib/4/plugins/export.js"></script>
<link rel="stylesheet" href="https://cdn.amcharts.com/lib/4/plugins/export.css">

{{-- DataTables init --}}
<script>
$(document).ready(function() {
    $('#example').DataTable({
        dom: 'Bfrtip',
        buttons: ['copyHtml5', 'excelHtml5', 'pdfHtml5']
    });
});
</script>

{{-- amCharts 4 chart --}}
<script type="application/json" id="__chartData">@json($chartData)</script>
<script type="application/json" id="__specLimit">{{ $specLimit }}</script>
<script>
am4core.ready(function() {
    am4core.useTheme(am4themes_animated);

    var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    var textColor = isDark ? '#dee2e6' : '#212529';
    var gridColor = isDark ? '#3a3f47' : '#e0e0e0';

    var chart = am4core.create("chartdiv", am4charts.XYChart);

    // Export menu
    chart.exporting.menu = new am4core.ExportMenu();
    chart.exporting.menu.items = [{
        "label": "....",
        "menu": [
            { "type": "png", "label": "PNG" },
            { "type": "jpg", "label": "JPG" },
            { "type": "pdf", "label": "PDF" },
            { "type": "xlsx", "label": "XLSX" },
            { "type": "csv", "label": "CSV" }
        ]
    }];
    chart.exporting.menu.align = "left";
    chart.exporting.menu.verticalAlign = "top";
    chart.exporting.filePrefix = "bioburden_trend";
    chart.exporting.backgroundColor = isDark ? "#22262d" : "#ffffff";
    chart.paddingTop = 60;

    // Data
    chart.data = JSON.parse(document.getElementById('__chartData').textContent);

    // X axis (Category)
    var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
    categoryAxis.dataFields.category = "Batch";
    categoryAxis.renderer.grid.template.location = 0;
    categoryAxis.renderer.minGridDistance = 30;
    categoryAxis.renderer.labels.template.rotation = 60;
    categoryAxis.renderer.labels.template.horizontalCenter = "middle";
    categoryAxis.renderer.labels.template.verticalCenter = "middle";
    categoryAxis.renderer.labels.template.fill = am4core.color(textColor);
    categoryAxis.renderer.grid.template.stroke = am4core.color(gridColor);

    // Y axis (Value)
    var valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
    var highestValue = Math.max(...chart.data.map(d => d.Average));
    var limitValue = JSON.parse(document.getElementById('__specLimit').textContent);
    valueAxis.min = 0;
    valueAxis.max = Math.max(highestValue, limitValue) + 1;
    valueAxis.renderer.labels.template.fill = am4core.color(textColor);
    valueAxis.renderer.grid.template.stroke = am4core.color(gridColor);

    // Line series
    var series = chart.series.push(new am4charts.LineSeries());
    series.dataFields.valueY = "Average";
    series.dataFields.categoryX = "Batch";
    series.name = "Average";
    series.strokeWidth = 2;

    // Tooltip
    series.tooltip.keepTargetHover = true;
    series.tooltip.background.strokeWidth = 1;
    series.tooltip.background.cornerRadius = 3;
    series.tooltip.background.stroke = am4core.color(isDark ? "#555" : "#000000");
    series.tooltip.background.fill = am4core.color(isDark ? "#2c3038" : "#ffffff");
    series.tooltip.getFillFromObject = false;
    series.tooltipHTML = '<div style="text-align:center; padding:5px; color:' + textColor + ';"><span style="font-weight:bold;">{Batch}</span><br/>Average: <span style="font-weight:bold;">{Average} CFU/mL</span></div>';
    series.tooltip.label.fontSize = 12;
    series.tooltip.autoTextColor = false;
    series.tooltip.label.fill = am4core.color(textColor);
    series.tooltip.background.fillOpacity = isDark ? 0.95 : 0.7;

    // Bullets
    var bullet = series.bullets.push(new am4charts.CircleBullet());
    bullet.circle.stroke = am4core.color(isDark ? "#22262d" : "#fff");
    bullet.circle.strokeWidth = 2;

    // Scrollbar
    chart.scrollbarX = new am4core.Scrollbar();

    // Spec limit guideline
    var range = valueAxis.axisRanges.create();
    range.value = limitValue;
    range.grid.stroke = am4core.color("#FF0000");
    range.grid.strokeWidth = 2;
    range.grid.strokeOpacity = 0.7;
    range.label.inside = true;
    range.label.text = "Specification Limit <" + limitValue + " cfu/100 mL";
    range.label.fill = range.grid.stroke;
    range.label.verticalCenter = "bottom";
});
</script>

{{-- Average auto-calculation --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tamcr1 = document.getElementById('tamcr1');
    const tamcr2 = document.getElementById('tamcr2');
    const tymcr1 = document.getElementById('tymcr1');
    const tymcr2 = document.getElementById('tymcr2');
    const resultavg = document.getElementById('resultavg');

    function calculateAverage() {
        const a = parseFloat(tamcr1.value);
        const b = parseFloat(tamcr2.value);
        const c = parseFloat(tymcr1.value);
        const d = parseFloat(tymcr2.value);
        if (!isNaN(a) && !isNaN(b) && !isNaN(c) && !isNaN(d)) {
            resultavg.value = Math.ceil(((a + c) + (b + d)) / 2);
        } else {
            resultavg.value = '';
        }
    }

    [tamcr1, tamcr2, tymcr1, tymcr2].forEach(el => el.addEventListener('input', calculateAverage));

    // Batch validation
    const modalForm = document.querySelector('#addModal form');
    if (modalForm) {
        modalForm.addEventListener('submit', function(e) {
            const batchInput = this.querySelector('input[name="batch"]');
            if (!batchInput) return;
            const parts = batchInput.value.trim().split(' - ');
            if (parts.length !== 2 || !parts[0].trim() || !parts[1].trim()) {
                e.preventDefault();
                alert('Please select a valid product with both batch number and product name.');
                batchInput.focus();
                return false;
            }
        });
    }
});
</script>
@endpush

@endsection
