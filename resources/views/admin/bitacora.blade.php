@extends('layouts.app')

@section('titulo', 'Bitácora y auditoría')

@section('acciones')
    <a href="{{ request()->fullUrlWithQuery(['verificar' => 1]) }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-link me-1"></i>Verificar integridad</a>
@endsection

@section('contenido')
    @if($verificacion)
        @if($verificacion['alterado'])
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Cadena rota</strong> en el evento #{{ $verificacion['alterado']->id }} ({{ $verificacion['alterado']->accion }}).
                La bitácora fue modificada fuera de la plataforma.
            </div>
        @else
            <div class="alert alert-success">
                <i class="fas fa-shield-alt me-1"></i>
                <strong>Bitácora íntegra:</strong> se verificaron {{ $verificacion['total'] }} eventos y la cadena de huellas SHA-256 está completa.
            </div>
        @endif
    @endif

    <form method="GET" class="d-flex gap-2 mb-3">
        <select name="accion" class="form-select form-select-sm" style="max-width: 260px" aria-label="Filtrar por acción">
            <option value="">Todas las acciones</option>
            @foreach($acciones as $a)<option value="{{ $a }}" @selected(request('accion') === $a)>{{ str_replace('_', ' ', $a) }}</option>@endforeach
        </select>
        <button class="btn btn-primary btn-sm">Filtrar</button>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>#</th><th>Fecha</th><th>Acción</th><th>Persona / rol</th><th>Entidad</th><th>IP</th><th>Huella</th></tr></thead>
                <tbody>
                @foreach($eventos as $e)
                    <tr class="small">
                        <td>{{ $e->id }}</td>
                        <td class="text-nowrap">{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                        <td>{{ str_replace('_', ' ', $e->accion) }}</td>
                        <td>{{ $e->autor?->name ?? 'Sistema' }}<br><span class="text-muted">{{ \App\Support\Roles::nombre($e->rol_ejercido) }}</span></td>
                        <td>{{ $e->entidad_tipo }}#{{ $e->entidad_id }}
                            @if($e->tesis_id)<a href="{{ route('tesis.show', $e->tesis_id) }}" class="d-block">expediente</a>@endif</td>
                        <td>{{ $e->ip }}</td>
                        <td><span class="hash" title="Anterior: {{ $e->hash_anterior }}">{{ substr($e->hash, 0, 16) }}…</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($eventos->hasPages())
            <div class="card-footer">{{ $eventos->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
