@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <p class="text-muted">Hola, {{ auth()->user()->name }}. Estás trabajando como <strong>{{ \App\Support\Roles::nombre($rol) }}</strong>.</p>

    @if($rol === \App\Support\Roles::TECNICO)
        <div class="alert alert-info">
            <i class="fas fa-tools me-1"></i>
            El rol técnico administra infraestructura, integraciones y auditoría, sin acceso a decisiones académicas.
            Revise la <a href="{{ route('admin.bitacora') }}">bitácora</a> o la <a href="{{ route('api.docs') }}">documentación de la API</a>.
        </div>
    @elseif($rol === \App\Support\Roles::DOCTORANDO)
        @include('partials.inicio-doctorando', ['tesis' => $miTesis])
    @else
        @php
            $tarjetas = [
                ['Expedientes', $kpi['tesis'], 'bg-info', 'fas fa-folder-open', route('tesis.index'), null],
                ['Hitos vencidos', $kpi['vencidos'], 'bg-danger', 'fas fa-exclamation-circle', route('tesis.index', ['vencimiento' => 'vencidos']), 'Esperan entrega o corrección'],
                ['Entregas por revisar', $kpi['en_revision'], 'bg-primary', 'fas fa-inbox', route('tesis.index', ['vencimiento' => 'en_revision']), 'Esperan la revisión de la guía'],
                ['Vencen en 30 días', $kpi['proximos'], 'bg-warning', 'fas fa-clock', route('tesis.index', ['vencimiento' => 'proximos']), null],
                ['Observaciones abiertas', $kpi['observaciones'], 'bg-secondary', 'fas fa-comments', null, 'Sin marcar como resueltas'],
                ['Tiempo de respuesta', $kpi['retroalimentacion'] !== null ? $kpi['retroalimentacion'] . ' días' : '—', 'bg-success', 'fas fa-reply', null, 'Promedio desde la entrega hasta la primera observación'],
            ];
        @endphp
        <div class="row kpi">
            @foreach($tarjetas as [$etiqueta, $valor, $color, $icono, $enlace, $ayuda])
                <div class="col-xxl-2 col-lg-4 col-sm-6">
                    @php $contenido = view('partials.kpi', compact('etiqueta', 'valor', 'color', 'icono', 'ayuda'))->render(); @endphp
                    @if($enlace)
                        <a href="{{ $enlace }}" class="info-box" title="Ver los expedientes">{!! $contenido !!}</a>
                    @else
                        <div class="info-box">{!! $contenido !!}</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="row">
            <!-- Semáforo de pendientes -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-traffic-light me-2"></i>Pendientes: plazos cercanos, vencidos y entregas por revisar</h3></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Expediente</th><th>Hito</th><th>Fecha límite</th><th>Situación</th><th>Le toca a</th></tr></thead>
                            <tbody>
                            @forelse($pendientes as $hito)
                                <tr>
                                    <td>
                                        <a href="{{ route('tesis.show', $hito->tesis) }}#hito-{{ $hito->id }}" class="text-nowrap">{{ $hito->tesis->codigo }}</a>
                                        <small class="d-block text-muted">{{ $hito->tesis->doctorando->name }}</small>
                                    </td>
                                    <td>{{ $hito->nombre }}</td>
                                    <td class="text-nowrap">{{ $hito->fecha_limite->format('d/m/Y') }}</td>
                                    <td>@include('partials.semaforo', ['hito' => $hito])<small class="d-block text-muted">{{ $hito->estado_nombre }}</small></td>
                                    <td class="small">{{ $hito->turno === 'guia' ? 'Guía' : 'Doctorando/a' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success me-1"></i>No hay pendientes en los próximos 30 días.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white">@include('partials.leyenda-semaforo')</div>
                </div>

                @if($rol === \App\Support\Roles::EVALUADOR)
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-check me-2"></i>Evaluaciones asignadas</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table mb-0">
                                <thead><tr><th>Expediente</th><th>Hito</th><th>Acceso hasta</th><th>Dictamen</th></tr></thead>
                                <tbody>
                                @forelse($evaluaciones as $ev)
                                    <tr>
                                        <td>@if($ev->vigente)<a href="{{ route('tesis.show', $ev->tesis) }}">{{ $ev->tesis->codigo }}</a>@else{{ $ev->tesis->codigo }}@endif</td>
                                        <td>{{ $ev->hito?->nombre }}</td>
                                        <td>{{ $ev->acceso_hasta->format('d/m/Y') }} @unless($ev->vigente)<span class="badge bg-secondary">Expirado</span>@endunless</td>
                                        <td>{{ $ev->dictamen ? \App\Models\Evaluacion::DICTAMENES[$ev->dictamen] : 'Pendiente' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Sin evaluaciones asignadas</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Tesis por estado -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie me-2"></i>Expedientes por estado</h3></div>
                    <div class="card-body">
                        @if($kpi['tesis'])
                            <div style="height: 240px"><canvas id="graficoEstados" aria-label="Expedientes por estado"></canvas></div>
                            <table class="table table-sm mt-3 mb-0">
                                @foreach($porEstado->where('total', '>', 0) as $e)
                                    <tr><td><i class="fas fa-circle me-1" style="color: {{ $e['color'] }}"></i>{{ $e['etiqueta'] }}</td><td class="text-end">{{ $e['total'] }}</td></tr>
                                @endforeach
                            </table>
                        @else
                            <p class="text-muted text-center mb-0">Sin expedientes visibles para este rol.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    @if(($kpi['tesis'] ?? false) && $rol !== \App\Support\Roles::DOCTORANDO && $rol !== \App\Support\Roles::TECNICO)
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const datos = @json($porEstado->where('total', '>', 0)->values());
                new Chart(document.getElementById('graficoEstados'), {
                    type: 'doughnut',
                    data: {
                        labels: datos.map(d => d.etiqueta),
                        datasets: [{ data: datos.map(d => d.total), backgroundColor: datos.map(d => d.color), borderColor: '#fff', borderWidth: 2 }]
                    },
                    options: { maintainAspectRatio: false, cutout: '60%', plugins: { legend: { display: false } } }
                });
            });
        </script>
    @endif
@endpush
