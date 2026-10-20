<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Iniciar sesión') · RestauManager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1b1f27 0%, #2a3140 55%, #3b4256 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .rm-login-card {
            width: 100%;
            max-width: 420px;
            border: 0;
            border-radius: 1rem;
            box-shadow: 0 20px 45px rgba(0, 0, 0, .35);
        }

        .rm-login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .65rem;
            font-weight: 700;
            font-size: 1.45rem;
            color: #1b1f27;
            margin-bottom: .25rem;
        }

        .rm-login-brand i {
            font-size: 2rem;
            color: #f97316;
        }

        .rm-login-icon {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: #1b1f27;
            color: #f97316;
            font-size: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -3.5rem auto 1rem;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .25);
        }
    </style>
</head>
<body>
    <div class="card rm-login-card">
        <div class="card-body p-4 p-sm-5">
            @yield('content')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
