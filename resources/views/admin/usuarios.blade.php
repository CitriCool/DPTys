@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Cuentas</h3></div>
                <div class="card-body pb-0">
                    <input type="search" class="form-control form-control-sm mb-3" placeholder="Buscar por nombre, correo o rol…" data-filtrar="#tablaUsuarios">
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0" id="tablaUsuarios">
                        <thead><tr><th>Nombre</th><th>Roles</th><th>Programas</th><th>Acceso</th><th></th></tr></thead>
                        <tbody>
                        @foreach($usuarios as $u)
                            <tr class="{{ $u->puedeIngresar() ? '' : 'text-muted' }}">
                                <td>{{ $u->name }}<small class="d-block text-muted">{{ $u->email }}</small></td>
                                <td>@foreach($u->roles as $r)<span class="badge bg-light text-dark border mb-1">{{ \App\Support\Roles::nombre($r->name) }}</span> @endforeach</td>
                                <td class="small">{{ $u->programas->pluck('sigla')->unique()->join(', ') ?: '—' }}</td>
                                <td class="small">
                                    @if(!$u->activo)<span class="badge bg-secondary">Desactivada</span>
                                    @elseif($u->acceso_hasta)Hasta {{ $u->acceso_hasta->format('d/m/Y') }} @unless($u->puedeIngresar())<span class="badge bg-secondary">Expirado</span>@endunless
                                    @else Permanente @endif
                                </td>
                                <td>
                                    @if($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.usuarios.activo', $u) }}" data-confirmar="{{ $u->activo ? 'La persona no podrá ingresar a la plataforma.' : 'La persona podrá volver a ingresar.' }}">
                                            @csrf
                                            <button class="btn btn-sm {{ $u->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <form method="POST" action="{{ route('admin.usuarios.guardar') }}" class="card" novalidate>
                @csrf
                <div class="card-header"><h3 class="card-title">Nueva cuenta</h3></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="name">Nombre</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="email">Correo</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="rut">RUT (opcional)</label>
                        <input type="text" id="rut" name="rut" value="{{ old('rut') }}" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="password">Contraseña inicial</label>
                        <input type="password" id="password" name="password" class="form-control form-control-sm" minlength="8" autocomplete="new-password" required>
                    </div>
                    <fieldset class="mb-2">
                        <legend class="form-label small mb-1">Roles</legend>
                        @foreach(\App\Support\Roles::NOMBRES as $valor => $nombre)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $valor }}" id="rol-{{ $valor }}" @checked(in_array($valor, old('roles', [])))>
                                <label class="form-check-label small" for="rol-{{ $valor }}">{{ $nombre }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="programas">Programas</label>
                        <select id="programas" name="programas[]" class="form-select form-select-sm" multiple size="3">
                            @foreach($programas as $p)<option value="{{ $p->id }}" @selected(in_array($p->id, old('programas', [])))>{{ $p->sigla }} — {{ $p->institucion->sigla }}</option>@endforeach
                        </select>
                        <div class="form-text">Ámbito de los roles de gestión, guía y doctorando.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="acceso_hasta">Acceso hasta (evaluadores externos)</label>
                        <input type="date" id="acceso_hasta" name="acceso_hasta" value="{{ old('acceso_hasta') }}" min="{{ now()->addDay()->toDateString() }}" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary btn-sm">Crear cuenta</button></div>
            </form>
        </div>
    </div>
@endsection
