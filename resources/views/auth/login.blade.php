<!DOCTYPE html>
<html lang="es-cl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · DPTyS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/all.min.css">
    <link rel="stylesheet" href="{{ asset_v('css/dptys.css') }}">
</head>
<body class="login-page d-flex align-items-center justify-content-center p-3">
    <main class="card shadow-lg border-0" style="max-width: 420px; width: 100%;">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-4">
                <img src="{{ asset('img/logo.svg') }}" alt="" width="56" height="56" class="mb-2">
                <h1 class="h4 mb-0">Plataforma de Gestión Doctoral</h1>
                <p class="text-muted small mb-0">Módulo de tesis</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger py-2 small" role="alert">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ url('/login') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Correo</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" autocomplete="username" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="recordar" id="recordar">
                    <label class="form-check-label small" for="recordar">Mantener la sesión iniciada</label>
                </div>
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-sign-in-alt me-1"></i> Ingresar</button>
            </form>

            @if (config('app.env') === 'local')
                <details class="mt-4 small text-muted">
                    <summary>Cuentas de prueba (solo entorno local)</summary>
                    <p class="mb-1 mt-2">Contraseña de todas: <code>dptys2026</code></p>
                    <ul class="mb-0 ps-3">
                        <li><code>direccion@dptys.local</code> — dirección y guía</li>
                        <li><code>secretaria@dptys.local</code> — secretaría</li>
                        <li><code>guia@dptys.local</code> — profesor guía</li>
                        <li><code>camila@dptys.local</code> — doctoranda</li>
                        <li><code>evaluador@externo.local</code> — evaluador externo</li>
                        <li><code>admin@dptys.local</code> — administración</li>
                        <li><code>secretaria@udemo.local</code> — otra universidad</li>
                    </ul>
                </details>
            @endif
        </div>
    </main>
</body>
</html>
