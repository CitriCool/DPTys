{{-- Inicio para doctorando/a: su tesis, el próximo paso y lo que debe responder --}}
@if(!$tesis)
    <div class="card card-body text-center text-muted py-5">
        <i class="fas fa-folder-open fa-2x mb-2"></i>
        <p class="mb-0">Aún no tiene un expediente de tesis abierto. La secretaría del programa lo crea al inscribir su proyecto.</p>
    </div>
@else
    @php
        $abiertos = $tesis->hitos->where('estado', '!=', 'aprobado');
        $siguiente = $abiertos->sortBy('fecha_limite')->first();
        $cumplidos = $tesis->hitos->where('estado', 'aprobado')->count();
        $total = $tesis->hitos->count();
        $observaciones = $tesis->relationLoaded('observaciones') ? $tesis->observaciones : collect();
    @endphp

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
                            <div class="d-flex justify-content-between small">
                                <strong>{{ $obs->autor->name }}</strong>
                                <span class="text-muted">{{ $obs->created_at->format('d/m/Y') }}</span>
                            </div>
                            @if($obs->hito)<div class="small text-muted">{{ $obs->hito->nombre }}</div>@endif
                            <p class="mb-0 mt-1">{{ \Illuminate\Support\Str::limit($obs->texto, 220) }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0"><i class="fas fa-check-circle text-success me-1"></i>No tiene observaciones abiertas.</p>
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
                    <p class="small text-muted mb-1">Avance: {{ $cumplidos }} de {{ $total }} hitos cumplidos</p>
                    <div class="progress" style="height: 8px" role="progressbar" aria-label="Hitos cumplidos" aria-valuenow="{{ $cumplidos }}" aria-valuemin="0" aria-valuemax="{{ $total }}">
                        <div class="progress-bar bg-success" style="width: {{ $total ? round($cumplidos * 100 / $total) : 0 }}%"></div>
                    </div>
                </div>
                <ul class="list-group list-group-flush small">
                    @foreach($tesis->hitos as $h)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>{{ $h->orden }}. {{ $h->nombre }}</span>
                            @include('partials.semaforo', ['hito' => $h])
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
