@extends('layouts.app')
@section('title', __('app.em_title'))

@push('styles')
<style>
.em-machine-card {
    border-left: 4px solid #5b9bd5;
    transition: box-shadow .2s;
}
.em-machine-card:hover {
    box-shadow: 0 4px 18px rgba(91,155,213,.25);
}
.em-empty-state {
    text-align: center;
    padding: 80px 20px;
    color: var(--text-muted);
}
.em-empty-state i {
    font-size: 56px;
    opacity: .35;
    display: block;
    margin-bottom: 16px;
}
.em-badge-count {
    font-size: 11px;
    font-weight: 600;
    background: rgba(91,155,213,.15);
    color: #5b9bd5;
    border-radius: 20px;
    padding: 2px 8px;
}
</style>
@endpush

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
        <h5 class="mb-0 fw-bold">
            <i class="bi bi-wind" style="color:#5b9bd5;"></i>
            {{ __('app.em_title') }}
        </h5>
        <a href="{{ route('em.upload', [], false) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-cloud-arrow-up"></i> {{ __('app.nav_em_upload') }}
        </a>
    </div>

    @if($machines->isEmpty())
        <div class="card">
            <div class="card-body em-empty-state">
                <i class="bi bi-wind"></i>
                <p class="mb-3" style="font-size:14px;">{{ __('app.em_no_data') }}</p>
                <a href="{{ route('em.upload', [], false) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-cloud-arrow-up"></i> {{ __('app.nav_em_upload') }}
                </a>
            </div>
        </div>
    @else
        <div class="row g-3">
            @foreach($machines as $m)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <a href="{{ route('em.detail', $m->machine_code, false) }}" style="text-decoration:none;">
                <div class="card em-machine-card h-100" style="cursor:pointer;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 fw-bold">{{ $m->machine_code }}</h6>
                            <span class="em-badge-count">{{ $m->file_count }} {{ $m->file_count == 1 ? 'file' : 'files' }}</span>
                        </div>
                        <div style="font-size:12px; color:var(--text-muted);">
                            {{ __('app.em_machine') }}
                        </div>
                        <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">
                            Latest: {{ str_pad($m->latest_month, 2, '0', STR_PAD_LEFT) }}/{{ $m->latest_year }}
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
