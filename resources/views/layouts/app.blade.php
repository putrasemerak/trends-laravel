<!DOCTYPE html>
<html data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    <!-- Theme JS (load early to set data-theme before paint) -->
    <script src="/assets/theme.js"></script>

    <!-- jQuery -->
    <script src="/assets/jquery.min.js"></script>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/assets/bootstrap.min.css">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="/assets/icons/bootstrap-icons.css">

    <!-- Theme CSS (AFTER Bootstrap to override) -->
    <link rel="stylesheet" href="/assets/theme.css">

    <!-- Bootstrap JS -->
    <script src="/assets/bootstrap-toggle.min.js"></script>
    <script src="/assets/bootstrap.min.js"></script>

    <!-- DataTables -->
    <script src="/assets/datatables/jquery-3.5.1.js"></script>
    <script src="/assets/datatables/jquery.dataTables.min.js"></script>
    <script src="/assets/datatables/dataTables.buttons.min.js"></script>
    <script src="/assets/datatables/jszip.min.js"></script>
    <script src="/assets/datatables/pdfmake.min.js"></script>
    <script src="/assets/datatables/vfs_fonts.js"></script>
    <script src="/assets/datatables/buttons.html5.min.js"></script>
    <link rel="stylesheet" href="/assets/datatables/jquery.dataTables.min.css">

    @stack('styles')
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-md navbar-dark bg-dark">
        <a class="navbar-brand" href="{{ route('home', [], false) }}">
            Hello, {{ auth()->user()->details->display_name ?? auth()->user()->EmpNo }}
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard', [], false) }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('home', [], false) }}"><i class="bi bi-grid"></i> Programs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('bioburden.smart-upload', [], false) }}"><i class="bi bi-cloud-arrow-up"></i> Upload</a>
                </li>
            </ul>
            <button class="theme-toggle" data-toggle-theme title="Toggle theme">
                <i class="bi bi-moon-fill theme-icon"></i>
            </button>
            <form class="form-inline my-2 my-lg-0" method="POST" action="{{ route('logout', [], false) }}">
                @csrf
                <button type="submit" class="btn btn-danger my-2 my-sm-0">Logout</button>
            </form>
        </div>
    </nav>

    <div style="height:50px;"></div>

    <!-- Toast Notifications (desktop-style) -->
    <div id="toastContainer" style="position:fixed; top:60px; right:16px; z-index:9999; width:340px;"></div>

    @if(session('success') || session('error') || session('warning') || $errors->any())
    @php
        $__flash = [
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'validation' => $errors->any() ? implode("\n", $errors->all()) : null,
        ];
    @endphp
    <script type="application/json" id="__flashMessages">@json($__flash)</script>
    @endif

    @yield('content')

    @stack('scripts')

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var el = document.getElementById('__flashMessages');
        if (!el) return;
        var flash = JSON.parse(el.textContent);
        var toasts = [];
        if (flash.success)    toasts.push({type:'success', icon:'bi-check-circle-fill', title:'Success', msg:flash.success});
        if (flash.error)      toasts.push({type:'danger',  icon:'bi-x-circle-fill',     title:'Error',   msg:flash.error});
        if (flash.warning)    toasts.push({type:'warning', icon:'bi-exclamation-triangle-fill', title:'Warning', msg:flash.warning});
        if (flash.validation) toasts.push({type:'danger',  icon:'bi-x-circle-fill',     title:'Validation Error', msg:flash.validation});

        var colors = {success:'#27ae60', danger:'#e74c3c', warning:'#f39c12'};
        var container = document.getElementById('toastContainer');

        toasts.forEach(function(t, i) {
            var card = document.createElement('div');
            card.style.cssText = 'background:var(--bg-card);border:1px solid var(--border-color);border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.15);padding:0;margin-bottom:10px;overflow:hidden;opacity:0;transform:translateX(60px);transition:opacity .35s,transform .35s;';
            card.innerHTML =
                '<div style="display:flex;align-items:stretch;">' +
                    '<div style="width:5px;background:' + colors[t.type] + ';flex-shrink:0;"></div>' +
                    '<div style="flex:1;padding:12px 14px;display:flex;align-items:flex-start;gap:10px;">' +
                        '<i class="bi ' + t.icon + '" style="font-size:18px;color:' + colors[t.type] + ';margin-top:1px;"></i>' +
                        '<div style="flex:1;min-width:0;">' +
                            '<div style="font-size:12px;font-weight:700;color:var(--text-body);margin-bottom:2px;">' + t.title + '</div>' +
                            '<div style="font-size:11px;color:var(--text-muted);line-height:1.5;word-break:break-word;">' + t.msg.replace(/\n/g,'<br>') + '</div>' +
                        '</div>' +
                        '<span class="toast-close" style="cursor:pointer;font-size:16px;color:var(--text-muted);line-height:1;padding:0 2px;">&times;</span>' +
                    '</div>' +
                '</div>';
            container.appendChild(card);
            card.querySelector('.toast-close').addEventListener('click', function() { dismiss(card); });
            setTimeout(function() { card.style.opacity='1'; card.style.transform='translateX(0)'; }, 50 + i * 100);
            setTimeout(function() { dismiss(card); }, 5000 + i * 500);
        });

        function dismiss(el) {
            el.style.opacity = '0';
            el.style.transform = 'translateX(60px)';
            setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 350);
        }
    });
    </script>
</body>
</html>
