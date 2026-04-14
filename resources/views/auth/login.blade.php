<!DOCTYPE html>
<html data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trending Analysis: Login</title>
    <script src="/assets/theme.js"></script>
    <link rel="stylesheet" href="/assets/login-assets/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/icons/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/theme.css">
    <style>
        body { margin: 0; }

        /* --- App Bar --- */
        .app-bar {
            background-color: var(--bg-navbar);
            color: #fff;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 700;
        }
        .app-bar img { height: 26px; }

        /* --- Page wrapper --- */
        .login-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 16px;
        }

        /* --- Card --- */
        .login-card {
            width: 100%;
            max-width: 370px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1.5rem 1.6rem 1.2rem;
            box-shadow: 0 6px 24px rgba(0,0,0,.10);
        }
        [data-theme="dark"] .login-card {
            box-shadow: 0 6px 24px rgba(0,0,0,.45);
        }

        /* --- Brand (logo + company name) inside card --- */
        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .login-brand img {
            height: 2.2em;
            width: auto;
            flex-shrink: 0;
        }
        .login-brand-name {
            font-size: 15px;
            font-weight: 800;
            color: var(--text-body);
            white-space: nowrap;
            line-height: 1;
        }
        [data-theme="dark"] .login-brand-name { color: #e0e0e0; }

        /* --- Title area --- */
        .login-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-body);
            margin-bottom: 2px;
        }
        [data-theme="dark"] .login-title { color: #e0e0e0; }
        .login-subtitle {
            font-size: 10px;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .login-datetime {
            font-size: 11px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        /* --- Input with icon --- */
        .input-icon-group {
            position: relative;
            margin-bottom: 10px;
        }
        .input-icon-group i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
        }
        .input-icon-group input {
            width: 100%;
            padding: 10px 12px 10px 36px;
            font-size: 13px;
            border: 1px solid var(--input-border);
            border-radius: 8px;
            background: var(--input-bg);
            color: var(--input-text);
            outline: none;
            transition: border-color .2s;
        }
        .input-icon-group input:focus {
            border-color: #5b9bd5;
            box-shadow: 0 0 0 2px rgba(91,155,213,.2);
        }
        .input-icon-group input::placeholder { color: var(--text-muted); }

        /* --- Theme Switch --- */
        .theme-switch-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 12px;
            user-select: none;
        }
        .theme-switch-row > i {
            font-size: 15px;
            color: var(--text-muted);
        }
        [data-theme="light"] .theme-switch-row > .tg-icon-sun { color: #e6a817; }
        [data-theme="dark"] .theme-switch-row > .tg-icon-moon { color: #e67e22; }
        .theme-toggle {
            display: flex;
            align-items: center;
            position: relative;
            width: 120px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            overflow: hidden;
            border: 1px solid var(--border-color);
            background: var(--bg-card);
        }
        .theme-toggle input { display: none; }
        /* Left and right halves */
        .theme-toggle .tg-half {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            z-index: 1;
            position: relative;
            color: var(--text-muted);
            transition: color .3s;
        }
        .theme-toggle .tg-half i { font-size: 13px; }
        /* Sliding thumb */
        .theme-toggle .tg-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: calc(50% - 2px);
            height: calc(100% - 4px);
            border-radius: 6px;
            background: #5b9bd5;
            transition: transform .3s ease, background .3s;
            z-index: 0;
        }
        /* Light mode: thumb on left (LIGHT side) — not checked */
        /* Dark mode: thumb on right (DARK side) — checked */
        .theme-toggle input:checked ~ .tg-thumb {
            transform: translateX(calc(100% + 2px));
            background: #e67e22;
        }
        /* Active side text goes white */
        /* unchecked = light mode = left half active */
        .theme-toggle input:not(:checked) ~ .tg-left { color: #fff; }
        .theme-toggle input:checked ~ .tg-right { color: #fff; }

        /* --- Buttons --- */
        .btn-login-main {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            font-weight: 700;
            border-radius: 8px;
            border: 1.5px solid;
            cursor: pointer;
            transition: opacity .2s;
        }
        .btn-login-main:hover { opacity: .85; }
        .btn-login-blue {
            background: #2196F3;
            color: #fff;
            border-color: #1565C0;
        }
        [data-theme="dark"] .btn-login-blue { border-color: #9E9E9E; }

        /* --- Credential hint --- */
        .cred-hint {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: -4px;
            margin-bottom: 10px;
            padding-left: 36px;
        }

        /* --- Footer --- */
        .login-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 10px;
            color: var(--text-muted);
            line-height: 1.6;
            opacity: .6;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-card">
        <!-- Brand: Logo + Company Name -->
        <div class="login-brand">
            <img src="/assets/img/ain_logo.png" alt="AIN Logo">
            <span class="login-brand-name">AIN MEDICARE SDN. BHD.</span>
        </div>

        <!-- Title -->
        <div class="text-center">
            <div class="login-title">Trending Analysis</div>
            <div class="login-subtitle">{{ config('app.url') }}</div>
            <div class="login-datetime">
                <i class="bi bi-clock"></i>
                <span id="liveClock"></span>
            </div>
        </div>

        @if($errors->has('login'))
            <div class="alert alert-danger" style="font-size:11px; padding:6px 10px;">
                <strong>Error:</strong> {{ $errors->first('login') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login', [], false) }}">
            @csrf

            <!-- Employee No -->
            <div class="input-icon-group">
                <i class="bi bi-person-fill"></i>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="Employee No" required autofocus>
            </div>

            <!-- Password -->
            <div class="input-icon-group">
                <i class="bi bi-lock-fill"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="cred-hint">
                <i class="bi bi-info-circle"></i>
                Login with <span style="color:#e74c3c;">AIN</span><span style="color:#3498db;">System</span> credential
            </div>

            <!-- Theme Toggle: Sun [LIGHT|DARK] Moon -->
            <div class="theme-switch-row">
                <i class="bi bi-sun-fill tg-icon-sun"></i>
                <label class="theme-toggle">
                    <input type="checkbox" id="themeSwitch">
                    <span class="tg-half tg-left">LIGHT</span>
                    <span class="tg-half tg-right">DARK</span>
                    <span class="tg-thumb"></span>
                </label>
                <i class="bi bi-moon-fill tg-icon-moon"></i>
            </div>

            <!-- Login Button -->
            <button type="submit" class="btn-login-main btn-login-blue">LOGIN</button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            Developed By:<br>
            Information Technology Division<br>
            System Development Department<br><br>
            &copy; <span id="footerYear"></span> Ain Medicare Sdn. Bhd. All rights reserved.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme switch
    var sw = document.getElementById('themeSwitch');
    sw.checked = document.documentElement.getAttribute('data-theme') === 'dark';
    sw.addEventListener('change', function() {
        window.AINTheme.apply(this.checked ? 'dark' : 'light');
    });

    // Live clock
    function updateClock() {
        var d = new Date();
        var pad = function(n) { return n < 10 ? '0' + n : n; };
        document.getElementById('liveClock').textContent =
            pad(d.getDate()) + '/' + pad(d.getMonth()+1) + '/' + d.getFullYear() + ' ' +
            pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
    }
    updateClock();
    setInterval(updateClock, 1000);

    // Footer year
    document.getElementById('footerYear').textContent = new Date().getFullYear();
});
</script>
</body>
</html>
