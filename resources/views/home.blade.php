@extends('layouts.app')

@section('title', 'Trending Analysis: Home')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h3 class="text-center">Trending Analysis</h3>
            <table class="table table-hover table-sm table-striped table-condensed display" id="example">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Program ID</th>
                        <th class="text-center">Name</th>
                        <th class="text-center"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($programs as $index => $access)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}.</td>
                            <td class="text-center">{{ $access->ProgID }}</td>
                            <td class="text-center">{{ $access->program->ProgName ?? '-' }}</td>
                            <td class="text-center">
                                @php
                                    // Parse ProgFileName to extract prodline param
                                    $progFile = $access->program->ProgFileName ?? '';
                                    $parsedUrl = parse_url($progFile);
                                    parse_str($parsedUrl['query'] ?? '', $queryParams);
                                    $prodline = $queryParams['prodline'] ?? '';
                                @endphp
                                <a class="btn btn-primary btn-block btn-sm"
                                   href="{{ route('bioburden.index', ['prodline' => $prodline], false) }}"
                                   role="button">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<br><br>

@push('scripts')
<script>
$(document).ready(function() {
    $('#example').DataTable();
});
</script>
@endpush
@endsection
