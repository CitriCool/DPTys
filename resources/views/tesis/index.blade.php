@extends('layouts.app')

@section('titulo', 'Expedientes de tesis')

@section('acciones')
    <a href="{{ request()->fullUrlWithQuery(['formato' => 'csv']) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-csv me-1"></i>Exportar CSV</a>
    @if(\App\Support\Permisos::crearExpedientes())
        <a href="{{ route('tesis.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Nuevo expediente</a>
    @endif
@endsection

@section('contenido')
    <!-- Filtros -->
    <form method="GET" class="card card-body mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label small mb-1" for="buscar">Buscar</label>
                <input type="search" id="buscar" name="buscar" value="{{ request('buscar') }}" class="form-control form-control-sm" placeholder="Código, título o doctorando/a">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small mb-1" for="programa">Programa</label>
                <select id="programa" name="programa" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($programas as $p)<option value="{{ $p->id }}" @selected(request('programa') == $p->id)>{{ $p->sigla }} — {{ $p->nombre }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-1 col-md-4">
                <label class="form-label small mb-1" for="cohorte">Cohorte</label>
                <select id="cohorte" name="cohorte" class="form-select form-select-sm">
                    <option value="">Todas</option>
                    @foreach($cohortes as $c)<option value="{{ $c->id }}" @selected(request('cohorte') == $c->id)>{{ $c->anio }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label small mb-1" for="estado">Estado</label>
                <select id="estado" name="estado" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($estados as $e)<option value="{{ $e->codigo }}" @selected(request('estado') === $e->codigo)>{{ $e->nombre }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-4">
                <label class="form-label small mb-1" for="guia">Guía</label>
                <select id="guia" name="guia" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    @foreach($guias as $g)<option value="{{ $g->id }}" @selected(request('guia') == $g->id)>{{ $g->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label small mb-1" for="vencimiento">Vencimiento</label>
                <select id="vencimiento" name="vencimiento" class="form-select form-select-sm">
                    <option value="">Cualquiera</option>
                    <option value="vencidos" @selected(request('vencimiento') === 'vencidos')>Con hitos vencidos</option>
                    <option value="en_revision" @selected(request('vencimiento') === 'en_revision')>Con entregas por revisar</option>
                    <option value="proximos" @selected(request('vencimiento') === 'proximos')>Vencen en 30 días</option>
                </select>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-2">
                @if(request('orden'))<input type="hidden" name="orden" value="{{ request('orden') }}">@endif
                <button class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
                @if(collect(request()->except(['page', 'orden']))->filter()->isNotEmpty())
                    <a href="{{ route('tesis.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times me-1"></i>Quitar filtros</a>
                @endif
                <span class="ms-auto small text-muted">{{ $tesis->total() }} {{ $tesis->total() === 1 ? 'expediente' : 'expedientes' }}</span>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover mb-0">
                <thead>
                    @php
                        $ordenCodigo = request('orden') === 'codigo_desc' ? null : 'codigo_desc';
                        $flecha = fn ($campo) => request('orden') === $campo ? ' ↓' : '';
                    @endphp
                    <tr>
                        <th><a href="{{ request()->fullUrlWithQuery(['orden' => $ordenCodigo, 'page' => null]) }}" class="text-reset" title="Ordenar por código">Código{{ request('orden') === 'codigo_desc' ? ' ↓' : ' ↑' }}</a></th>
                        <th>Tesis</th><th>Programa / cohorte</th><th>Guía</th><th>Estado</th><th>Próximo hito</th>
                        <th class="text-center"><a href="{{ request()->fullUrlWithQuery(['orden' => request('orden') === 'vencidos' ? null : 'vencidos', 'page' => null]) }}" class="text-reset" title="Ordenar por hitos vencidos">Hitos vencidos{{ $flecha('vencidos') }}</a></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($tesis as $t)
                    <tr>
                        <td class="text-nowrap"><a href="{{ route('tesis.show', $t) }}" class="fw-bold">{{ $t->codigo }}</a></td>
                        <td>
                            {{ \Illuminate\Support\Str::limit($t->titulo, 90) }}
                            <small class="d-block text-muted">{{ $t->doctorando->name }}</small>
                        </td>
                        <td class="text-nowrap">{{ $t->programa->sigla }} · {{ $t->cohorte->anio }}</td>
                        <td>{{ $t->responsablesVigentes->firstWhere('tipo', 'guia')?->user->name ?? '—' }}</td>
                        <td>@include('partials.estado', ['estado' => $t->estadoActual])</td>
                        <td class="small">
                            @php $prox = $t->estadoActual->final ? null : $t->hitos->sortBy('fecha_limite')->first(); @endphp
                            @if($prox)
                                {{ $prox->nombre }}
                                <span class="d-block text-nowrap">{{ $prox->fecha_limite->format('d/m/Y') }} @include('partials.semaforo', ['hito' => $prox])</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($t->hitos_vencidos && !$t->estadoActual->final)
                                <span class="badge bg-danger">{{ $t->hitos_vencidos }}</span>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay expedientes que coincidan con los filtros. <a href="{{ route('tesis.index') }}">Quitar filtros</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($tesis->hasPages())
            <div class="card-footer">{{ $tesis->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
