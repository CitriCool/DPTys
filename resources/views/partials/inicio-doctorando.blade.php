{{-- Inicio para doctorando/a: su tesis, el próximo paso y lo que debe responder --}}
@if(!$tesis)
    <div class="card">
        @include('partials.vacio', ['dibujo' => 'carpeta', 'titulo' => 'Aún no tiene un expediente de tesis', 'texto' => 'La secretaría del programa lo abre al inscribir su proyecto. Cuando exista, aquí verá su próximo paso y su avance.'])
    </div>
@else
    @php
        $abiertos = $tesis->hitos->where('estado', '!=', 'aprobado');
        $siguiente = $abiertos->sortBy('fecha_limite')->first();
        $cumplidos = $tesis->hitos->where('estado', 'aprobado')->count();
        $total = $tesis->hitos->count();
        $observaciones = $tesis->relationLoaded('observaciones') ? $tesis->observaciones : collect();
    @endphp

    <!-- Línea de tiempo de la tesis -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-stream me-2"></i>Mi recorrido</h3>
            <span class="small text-muted">{{ $cumplidos }} de {{ $total }} hitos cumplidos</span>
        </div>
        <div class="card-body">@include('partials.linea-tiempo', ['hitos' => $tesis->hitos, 'enlazarA' => $tesis])</div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <!-- Próximo paso -->
            <div class="card proximo-paso {{ $siguiente && $siguiente->semaforo === 'vencido' ? 'urgente' : '' }}">
                <div class="card-body">
                    <p class="text-muted small mb-1">Su próximo paso</p>
                    @if(!$siguiente)
                        <h2 class="h5 mb-0"><i class="fas fa-check-circle text-success me-1"></i>Todos los hitos están cumplidos.</h2>
                    @elseif($siguiente->turno === 'guia')
                        <h2 class="h5 mb-1">«{{ $siguiente->nombre }}»: su entrega está en revisión</h2>
                        <p class="mb-2">No necesita hacer nada por ahora. Le avisaremos cuando su guía la revise.</p>
                        <a href="{{ route('tesis.show', $tesis) }}#hito-{{ $siguiente->id }}" class="btn btn-outline-primary btn-sm">Ver el hito</a>
                    @else
                        <h2 class="h5 mb-1">
                            {{ $siguiente->estado === 'observado' ? 'Corregir y volver a entregar' : 'Entregar' }} «{{ $siguiente->nombre }}»
                        </h2>
                        <p class="mb-2">
                            Fecha límite: <strong>{{ $siguiente->fecha_limite->format('d/m/Y') }}</strong>
                            @include('partials.semaforo', ['hito' => $siguiente])
                            @if($siguiente->semaforo !== 'vencido')
                                <span class="text-muted">· quedan {{ now()->startOfDay()->diffInDays($siguiente->fecha_limite) }} días</span>
                            @endif
                        </p>
                        @if($siguiente->requiere_entrega)
                            <a href="{{ route('tesis.show', $tesis) }}#hito-{{ $siguiente->id }}" class="btn btn-primary btn-sm"><i class="fas fa-upload me-1"></i>Subir versión</a>
                        @else
                            <a href="{{ route('tesis.show', $tesis) }}#hito-{{ $siguiente->id }}" class="btn btn-outline-primary btn-sm">Ver el hito</a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Observaciones por responder -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-comments me-2"></i>Observaciones abiertas</h3></div>
                <div class="card-body">
                    @forelse($observaciones->sortByDesc('created_at')->take(5) as $obs)
                        <div class="observacion mb-2">
                            <div class="d-flex justify-content-between align-items-center small">
                                @include('partials.persona', ['nombre' => $obs->autor->name, 'tam' => 'xs'])
                                <span class="text-muted">{{ $obs->created_at->format('d/m/Y') }}</span>
                            </div>
                            @if($obs->hito)<div class="small text-muted">{{ $obs->hito->nombre }}</div>@endif
                            <p class="mb-0 mt-1">{{ \Illuminate\Support\Str::limit($obs->texto, 220) }}</p>
                        </div>
                    @empty
                        @include('partials.vacio', ['dibujo' => 'listo', 'titulo' => 'No tiene observaciones abiertas', 'texto' => 'Cuando su guía comente una entrega, la observación aparecerá aquí.', 'compacto' => true])
                    @endforelse
                    @if($observaciones->isNotEmpty())
                        <a href="{{ route('tesis.show', $tesis) }}#tab-observaciones" class="btn btn-link btn-sm px-0">Ver y responder observaciones</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <!-- Mi tesis -->
            <div class="card">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-book me-2"></i>Mi tesis</h3></div>
                <div class="card-body">
                    <p class="mb-1"><a href="{{ route('tesis.show', $tesis) }}" class="fw-bold">{{ $tesis->codigo }}</a></p>
                    <p class="small mb-2">{{ $tesis->titulo }}</p>
                    <p class="mb-3">@include('partials.estado', ['estado' => $tesis->estadoActual])</p>
                    <div class="d-flex align-items-center gap-3">
                        @include('partials.anillo', ['valor' => $total ? $cumplidos * 100 / $total : 0, 'tam' => 72, 'color' => '#16a34a'])
                        <div class="small text-muted">Avance<br><strong class="fs-5">{{ $cumplidos }} de {{ $total }}</strong><br>hitos cumplidos</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
