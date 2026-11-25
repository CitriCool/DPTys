@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    @php
        $e = $editando;
        // Valor del campo: lo enviado (si hubo error), o el de la cuenta en edición
        $val = fn ($campo, $actual = null) => old($campo, $actual);
        $rolesMarcados = old('roles', $e ? $e->getRoleNames()->all() : []);
        $programasMarcados = array_map('intval', old('programas', $e ? $e->programas->pluck('id')->unique()->all() : []));
    @endphp
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Cuentas</h3></div>
                <div class="card-body pb-0">
                    <input type="search" class="form-control form-control-sm mb-3" placeholder="Buscar por nombre, correo o rol…" data-filtrar="#tablaUsuarios">
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover mb-0" id="tablaUsuarios">
                        <thead><tr><th>Nombre</th><th>Roles</th><th>Programas</th><th>Acceso</th><th class="text-end">Acciones</th></tr></thead>
                        <tbody>
                        @foreach($usuarios as $u)
                            <tr class="{{ $u->puedeIngresar() ? '' : 'text-muted' }} {{ $e && $e->id === $u->id ? 'table-primary' : '' }}">
                                <td>{{ $u->name }}<small class="d-block text-muted">{{ $u->email }}</small></td>
                                <td>@foreach($u->roles as $r)<span class="badge bg-light text-dark border mb-1">{{ \App\Support\Roles::nombre($r->name) }}</span> @endforeach</td>
                                <td class="small">{{ $u->programas->pluck('sigla')->unique()->join(', ') ?: '—' }}</td>
                                <td class="small">
                                    @if(!$u->activo)<span class="badge bg-secondary">Desactivada</span>
                                    @elseif($u->acceso_hasta)Hasta {{ $u->acceso_hasta->format('d/m/Y') }} @unless($u->puedeIngresar())<span class="badge bg-secondary">Expirado</span>@endunless
                                    @else Permanente @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <a href="{{ route('admin.usuarios', ['editar' => $u->id]) }}#formCuenta" class="btn btn-sm btn-outline-primary" title="Editar la cuenta de {{ $u->name }}"><i class="fas fa-pen"></i><span class="visually-hidden">Editar</span></a>
                                        @if($u->id !== auth()->id())
                                            <form method="POST" action="{{ route('admin.usuarios.activo', $u) }}" data-confirmar="{{ $u->activo ? 'La persona no podrá ingresar a la plataforma.' : 'La persona podrá volver a ingresar.' }}">
                                                @csrf
                                                <button class="btn btn-sm {{ $u->activo ? 'btn-outline-danger' : 'btn-outline-success' }}">{{ $u->activo ? 'Desactivar' : 'Activar' }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <form method="POST" action="{{ $e ? route('admin.usuarios.actualizar', $e) : route('admin.usuarios.guardar') }}" class="card {{ $e ? 'border-primary' : '' }}" id="formCuenta" novalidate>
                @csrf
                <div class="card-header"><h3 class="card-title">{{ $e ? 'Editar cuenta' : 'Nueva cuenta' }}</h3></div>
                @if($e)
                    <div class="card-body pb-0">
                        <p class="small text-muted mb-0">Editando a <strong>{{ $e->name }}</strong>. Los cambios quedan en la bitácora.</p>
                    </div>
                @endif
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="name">Nombre</label>
                        <input type="text" id="name" name="name" value="{{ $val('name', $e?->name) }}" class="form-control form-control-sm @error('name') is-invalid @enderror" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="email">Correo</label>
                        <input type="email" id="email" name="email" value="{{ $val('email', $e?->email) }}" class="form-control form-control-sm @error('email') is-invalid @enderror" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="rut">RUT (opcional)</label>
                        <input type="text" id="rut" name="rut" value="{{ $val('rut', $e?->rut) }}" class="form-control form-control-sm">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="password">{{ $e ? 'Nueva contraseña' : 'Contraseña inicial' }}</label>
                        <input type="password" id="password" name="password" class="form-control form-control-sm @error('password') is-invalid @enderror" minlength="8" autocomplete="new-password" @unless($e) required @endunless>
                        <div class="form-text">{{ $e ? 'Déjela en blanco para mantener la actual.' : 'Mínimo 8 caracteres.' }}</div>
                    </div>
                    <fieldset class="mb-2">
                        <legend class="form-label small mb-1">Roles</legend>
                        @foreach(\App\Support\Roles::NOMBRES as $valor => $nombre)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $valor }}" id="rol-{{ $valor }}" @checked(in_array($valor, $rolesMarcados))>
                                <label class="form-check-label small" for="rol-{{ $valor }}">{{ $nombre }}</label>
                            </div>
                        @endforeach
                    </fieldset>
                    <fieldset class="mb-2">
                        <legend class="form-label small mb-1">Programas</legend>
                        @foreach($programas as $p)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="programas[]" value="{{ $p->id }}" id="programa-{{ $p->id }}" @checked(in_array($p->id, $programasMarcados))>
                                <label class="form-check-label small" for="programa-{{ $p->id }}">{{ $p->sigla }} — {{ $p->institucion->sigla }}</label>
                            </div>
                        @endforeach
                        <div class="form-text">Ámbito de los roles de gestión, guía y doctorando.</div>
                    </fieldset>
                    <div class="mb-2">
                        <label class="form-label small mb-1" for="acceso_hasta">Acceso hasta (evaluadores externos)</label>
                        <input type="date" id="acceso_hasta" name="acceso_hasta" value="{{ $val('acceso_hasta', $e?->acceso_hasta?->toDateString()) }}" min="{{ now()->addDay()->toDateString() }}" class="form-control form-control-sm @error('acceso_hasta') is-invalid @enderror">
                        <div class="form-text">Obligatorio si la cuenta tiene el rol de evaluador externo.</div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary btn-sm">{{ $e ? 'Guardar cambios' : 'Crear cuenta' }}</button>
                    @if($e)<a href="{{ route('admin.usuarios') }}" class="btn btn-outline-secondary btn-sm">Cancelar</a>@endif
                </div>
            </form>
        </div>
    </div>
@endsection
