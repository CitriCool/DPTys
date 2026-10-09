<!DOCTYPE html>
<html lang="es-cl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Inicio') · DPTyS</title>
    <link rel="stylesheet" href="{{ asset('css/adminlte.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset_v('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/sidebar.css') }}">
    <link rel="stylesheet" href="{{ asset_v('css/dptys.css') }}">
    @stack('estilos')
</head>
<body class="sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Barra superior -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Mostrar u ocultar menú"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto align-items-center">
            <!-- Avisos -->
            <li class="nav-item dropdown">
                <a class="nav-link position-relative" data-bs-toggle="dropdown" href="#" aria-label="Avisos">
                    <i class="far fa-bell"></i>
                    @if($avisosPendientes)
                        <span class="badge bg-danger navbar-badge">{{ $avisosPendientes }}</span>
                    @endif
                </a>
                <div class="dropdown-menu dropdown-menu-end avisos-menu p-0">
                    <div class="dropdown-header d-flex justify-content-between align-items-center">
                        <span>{{ $avisosPendientes }} aviso(s) sin leer</span>
                        @if($avisosPendientes)
                            <form method="POST" action="{{ route('avisos.leer-todos') }}">@csrf
                                <button class="btn btn-link btn-sm p-0">Marcar todos como leídos</button>
                            </form>
                        @endif
                    </div>
                    @forelse($avisos as $aviso)
                        <a href="{{ route('avisos.abrir', $aviso->id) }}" class="dropdown-item aviso-item">
                            <i class="{{ $aviso->data['icono'] ?? 'fas fa-info-circle' }} me-2 text-{{ $aviso->data['color'] ?? 'secondary' }}"></i>
                            <span class="aviso-texto">{{ $aviso->data['mensaje'] ?? '' }}</span>
                            <small class="d-block text-muted">{{ $aviso->created_at->diffForHumans() }}</small>
                        </a>
                    @empty
                        <span class="dropdown-item text-muted small">No hay avisos nuevos</span>
                    @endforelse
                </div>
            </li>
            <li class="nav-item d-none d-md-block">
                <span class="nav-link text-muted small">{{ $usuario->name }}</span>
            </li>
            <li class="nav-item">
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="btn btn-link nav-link" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></button>
                </form>
            </li>
        </ul>
    </nav>

    <!-- Barra lateral -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-image: url('{{ asset('img/p.png') }}');">
        <a href="{{ route('inicio') }}" class="brand-link">
            <img src="{{ asset('img/logo.svg') }}" alt="" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text fw-bold text-white">DPTyS</span>
        </a>

        <div class="brand-link px-2 text-center">
            <p class="brand-text text-white mb-0 text-truncate">{{ $usuario->name }}</p>
            <p class="brand-text text-white-50 small mb-2 text-truncate">{{ $usuario->email }}</p>
            <form method="POST" action="{{ route('rol.cambiar') }}" id="rolForm" class="text-start">
                @csrf
                <label for="rolActivo" class="text-white small mb-1">Rol en uso:</label>
                <select class="form-select form-select-sm" id="rolActivo" name="rol" {{ $rolesUsuario->count() < 2 ? 'disabled' : '' }}>
                    @foreach($rolesUsuario as $rol)
                        <option value="{{ $rol }}" @selected($rol === $rolActivo)>{{ \App\Support\Roles::nombre($rol) }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="sidebar">
            <nav class="pt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">
                    @foreach($menu as $item)
                        <li class="nav-item">
                            <a href="{{ route($item['ruta']) }}" class="nav-link {{ request()->routeIs($item['ruta']) ? 'active' : '' }}">
                                <i class="nav-icon {{ $item['icono'] }}"></i>
                                <p>{{ $item['nombre'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Contenido -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h1 class="mb-0">@yield('titulo', 'Inicio')</h1>
                <div>@yield('acciones')</div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                @include('partials.mensajes')
                @yield('contenido')
            </div>
        </section>
    </div>

    <footer class="main-footer small">
        <strong>DPTyS</strong> · Plataforma de Gestión Doctoral — Módulo de tesis
        <span class="float-end text-muted">La plataforma registra hitos y evidencias; no reemplaza actas ni resoluciones oficiales.</span>
    </footer>
</div>

<script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script src="{{ asset('js/adminlte.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="{{ asset_v('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
