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

        @php $verGraficos = $rol !== \App\Support\Roles::EVALUADOR && $kpi['tesis']; @endphp
        <div class="row">
            <!-- Pendientes -->
            <div class="{{ $verGraficos ? 'col-xl-8' : 'col-12' }}">
                <div class="card">
                    <div class="card-header"><h3 class="card-title"><i class="fas fa-traffic-light me-2"></i>Pendientes: plazos cercanos, vencidos y entregas por revisar</h3></div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover mb-0 tabla-tarjetas">
                            <thead><tr><th>Expediente</th><th>Hito</th><th>Fecha límite</th><th>Situación</th><th>Le toca a</th></tr></thead>
                            <tbody>
                            @forelse($pendientes as $hito)
                                <tr>
                                    <td class="td-principal">
                                        <a href="{{ route('tesis.show', $hito->tesis) }}#hito-{{ $hito->id }}" class="text-nowrap fw-semibold d-block mb-1">{{ $hito->tesis->codigo }}</a>
                                        @include('partials.persona', ['nombre' => $hito->tesis->doctorando->name, 'tam' => 'xs'])
                                    </td>
                                    <td>{{ $hito->nombre }}</td>
                                    <td class="text-nowrap">{{ $hito->fecha_limite->format('d/m/Y') }}</td>
                                    <td>@include('partials.semaforo', ['hito' => $hito])<small class="d-block text-muted">{{ $hito->estado_nombre }}</small></td>
                                    <td class="small">{{ $hito->turno === 'guia' ? 'Guía' : 'Doctorando/a' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5">@include('partials.vacio', ['dibujo' => 'listo', 'titulo' => 'Todo al día', 'texto' => 'No hay hitos vencidos, plazos en los próximos 30 días ni entregas esperando revisión.'])</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer">@include('partials.leyenda-semaforo')</div>
                </div>

                @if($rol === \App\Support\Roles::EVALUADOR)
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-user-check me-2"></i>Evaluaciones asignadas</h3></div>
                        <div class="card-body table-responsive p-0">
                            <table class="table mb-0 tabla-tarjetas">
                                <thead><tr><th>Expediente</th><th>Hito</th><th>Acceso hasta</th><th>Dictamen</th></tr></thead>
                                <tbody>
                                @forelse($evaluaciones as $ev)
                                    <tr>
                                        <td class="td-principal">@if($ev->vigente)<a href="{{ route('tesis.show', $ev->tesis) }}" class="fw-semibold">{{ $ev->tesis->codigo }}</a>@else{{ $ev->tesis->codigo }}@endif</td>
                                        <td>{{ $ev->hito?->nombre }}</td>
                                        <td>{{ $ev->acceso_hasta->format('d/m/Y') }} @unless($ev->vigente)<span class="badge bg-secondary">Expirado</span>@endunless</td>
                                        <td>{{ $ev->dictamen ? \App\Models\Evaluacion::DICTAMENES[$ev->dictamen] : 'Pendiente' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4">@include('partials.vacio', ['dibujo' => 'documento', 'titulo' => 'Sin evaluaciones asignadas', 'texto' => 'Cuando la dirección del programa le asigne una versión para evaluar, aparecerá aquí.'])</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            @if($verGraficos)
                <!-- Anillos de avance global -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-bullseye me-2"></i>Avance del programa</h3></div>
                        <div class="card-body">
                            <div class="avance-resumen">
                                @foreach($anillos as [$etiqueta, $valor, $color])
                                    <div>@include('partials.anillo', ['valor' => $valor, 'tam' => 84, 'color' => $color])<div class="mt-1">{{ $etiqueta }}</div></div>
                                @endforeach
                            </div>
                            <p class="small text-muted mt-3 mb-0">Calculado sobre los expedientes en curso visibles para su rol.</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if($verGraficos)
            <div class="row">
                <!-- Avance por expediente -->
                <div class="col-xl-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-tasks me-2"></i>Avance por expediente</h3></div>
                        <div class="card-body py-2">
                            @forelse($avance->take(6) as $a)
                                <a href="{{ route('tesis.show', $a['tesis']) }}" class="avance-expediente">
                                    @include('partials.anillo', ['valor' => $a['pct'], 'tam' => 48, 'color' => $a['pct'] >= 66 ? '#16a34a' : ($a['pct'] >= 33 ? '#2563eb' : '#f59e0b')])
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="fw-semibold">{{ $a['tesis']->codigo }}</div>
                                        @include('partials.persona', ['nombre' => $a['tesis']->doctorando->name, 'tam' => 'xs'])
                                    </div>
                                    <div class="text-end small text-muted text-nowrap">{{ $a['cumplidos'] }}/{{ $a['total'] }} hitos<br>@include('partials.estado', ['estado' => $a['tesis']->estadoActual])</div>
                                </a>
                            @empty
                                @include('partials.vacio', ['dibujo' => 'carpeta', 'titulo' => 'No hay expedientes en curso', 'compacto' => true])
                            @endforelse
                            @if($avance->count() > 6)
                                <a href="{{ route('tesis.index') }}" class="btn btn-link btn-sm px-0">Ver los {{ $avance->count() }} expedientes</a>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Barras por cohorte -->
                <div class="col-xl-4 col-md-7">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-bar me-2"></i>Expedientes por cohorte y estado</h3></div>
                        <div class="card-body">
                            <div class="grafico-caja" style="height: 260px">
                                <div class="esqueleto-capa" aria-hidden="true"><div class="d-flex align-items-end gap-3 w-75 h-75">@foreach([60, 85, 40, 70] as $alto)<div class="esqueleto flex-fill" style="height: {{ $alto }}%"></div>@endforeach</div></div>
                                <canvas id="graficoCohortes" role="img" aria-label="Expedientes por cohorte, apilados por estado"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dona por estado -->
                <div class="col-xl-3 col-md-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie me-2"></i>Por estado</h3></div>
                        <div class="card-body">
                            <div class="grafico-caja" style="height: 180px">
                                <div class="esqueleto-capa" aria-hidden="true"><div class="esqueleto esqueleto-circulo" style="width:150px;height:150px"></div></div>
                                <canvas id="graficoEstados" role="img" aria-label="Expedientes por estado"></canvas>
                            </div>
                            <div class="d-flex flex-wrap gap-1 mt-3">
                                @foreach($porEstado->where('total', '>', 0) as $e)
                                    <span class="etiqueta-estado" style="--color-estado: {{ $e['color'] }}">{{ $e['etiqueta'] }} · {{ $e['total'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
@endsection

@push('scripts')
    @if(($verGraficos ?? false))
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const css = getComputedStyle(document.body);
                const colorTexto = () => getComputedStyle(document.body).getPropertyValue('--dp-texto-suave').trim();
                const colorBorde = () => getComputedStyle(document.body).getPropertyValue('--dp-borde').trim();
                const colorSuperficie = () => getComputedStyle(document.body).getPropertyValue('--dp-superficie').trim();
                const listo = canvas => canvas.closest('.grafico-caja').classList.add('listo');
                Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;

                const datos = @json($porEstado->where('total', '>', 0)->values());
                const dona = new Chart(document.getElementById('graficoEstados'), {
                    type: 'doughnut',
                    data: {
                        labels: datos.map(d => d.etiqueta),
                        datasets: [{ data: datos.map(d => d.total), backgroundColor: datos.map(d => d.color), borderColor: colorSuperficie(), borderWidth: 3, hoverOffset: 6 }]
                    },
                    options: { maintainAspectRatio: false, cutout: '64%', animation: { animateRotate: true, duration: 900, onComplete: () => listo(document.getElementById('graficoEstados')) }, plugins: { legend: { display: false } } }
                });

                const cohortes = @json($porCohorte);
                const barras = new Chart(document.getElementById('graficoCohortes'), {
                    type: 'bar',
                    data: { labels: cohortes.etiquetas, datasets: cohortes.series.map(s => ({ ...s, borderRadius: 6, borderSkipped: false, maxBarThickness: 46 })) },
                    options: {
                        maintainAspectRatio: false,
                        animation: { duration: 900, onComplete: () => listo(document.getElementById('graficoCohortes')) },
                        scales: {
                            x: { stacked: true, grid: { display: false }, ticks: { color: colorTexto() } },
                            y: { stacked: true, beginAtZero: true, ticks: { precision: 0, color: colorTexto() }, grid: { color: colorBorde() }, title: { display: true, text: 'Expedientes', color: colorTexto() } }
                        },
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, color: colorTexto() } } }
                    }
                });
                // Por si la animación se desactiva (movimiento reducido)
                setTimeout(() => document.querySelectorAll('.grafico-caja').forEach(c => c.classList.add('listo')), 1500);

                // Repintar los gráficos al cambiar de tema
                document.addEventListener('dptys:tema', () => {
                    barras.options.scales.x.ticks.color = barras.options.scales.y.ticks.color = barras.options.scales.y.title.color = barras.options.plugins.legend.labels.color = colorTexto();
                    barras.options.scales.y.grid.color = colorBorde();
                    dona.data.datasets[0].borderColor = colorSuperficie();
                    barras.update(); dona.update();
                });
            });
        </script>
    @endif
@endpush
