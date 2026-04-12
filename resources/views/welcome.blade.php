<!DOCTYPE html>
<html data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AINCCS - Trending Analysis</title>
    <script src="/assets/theme.js"></script>
    <link rel="stylesheet" href="/assets/login-assets/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/icons/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/theme.css">
</head>
<body>

<nav class="navbar fixed-top navbar-expand-md navbar-dark bg-dark">
    <a class="navbar-brand" href="{{ route('welcome', [], false) }}"> AMSB </a>
    <div class="ml-auto">
        <button class="theme-toggle" data-toggle-theme title="Toggle theme">
            <i class="bi bi-moon-fill theme-icon"></i>
        </button>
    </div>
</nav>

<br><br><br><br>
<div class="container text-center">
    <img src="/assets/img/logoain.png" width="20%"><br><br>
    <h2>Trending Analysis</h2>
    <p>Contamination Control Strategy</p>
    <br>
    <a class="btn btn-primary btn-lg" href="{{ route('login', [], false) }}">Login</a>
</div>

</body>
</html>
