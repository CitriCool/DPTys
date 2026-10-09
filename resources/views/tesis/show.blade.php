@extends('layouts.app')

@section('titulo', $tesis->codigo)

@php
    use App\Models\Acta;
    use App\Models\Evaluacion;
    use App\Support\Roles;

    $guia = $tesis->responsablesVigentes->firstWhere('tipo', 'guia');
    $coguia = $tesis->responsablesVigentes->firstWhere('tipo', 'coguia');
    $hitosVisibles = $esEvaluador
        ? $tesis->hitos->filter(fn ($h) => $h->entregas->flatMap->versiones->pluck('id')->intersect($versionesEvaluador)->isNotEmpty())
        : $tesis->hitos;
    $observaciones = $tesis->observaciones->whereNull('parent_id')->sortByDesc('created_at');
    if ($esEvaluador) {
        $observaciones = $observaciones->filter(fn ($o) => in_array($o->entrega_version_id, $versionesEvaluador) || $o->user_id == auth()->id());
    }
    $versionesTesis = $tesis->hitos->flatMap(fn ($h) => $h->entregas->flatMap->versiones->map(fn ($v) => ['v' => $v, 'hito' => $h]));
@endphp

@section('acciones')
    @if($permisos['exportar'])
        <a href="{{ route('tesis.exportar', $tesis) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-archive me-1"></i>Exportar expediente</a>
    @endif
    @if($transiciones->isNotEmpty())
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalDecision"><i class="fas fa-gavel me-1"></i>Registrar decisión</button>
    @endif
@endsection

@section('contenido')
    <!-- Ficha -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <h2 class="h5 mb-1">{{ $tesis->titulo }}</h2>
                    <p class="text-muted mb-0">{{ $tesis->doctorando->name }} · {{ $tesis->programa->nombre }} · {{ $tesis->cohorte->nombre }}</p>
                </div>
                <div class="text-end">@include('partials.estado', ['estado' => $tesis->estadoActual])</div>
            </div>
            <hr>
            <div class="row small">
                <div class="col-md-3 mb-2"><span class="text-muted d-block">Guía</span>{{ $guia?->user->name ?? '—' }}</div>
                <div class="col-md-3 mb-2"><span class="text-muted d-block">Coguía</span>{{ $coguia?->user->name ?? '—' }}</div>
                <div class="col-md-2 mb-2"><span class="text-muted d-block">Inscripción</span>{{ $tesis->fecha_inscripcion->format('d/m/Y') }}</div>
                <div class="col-md-2 mb-2"><span class="text-muted d-block">Resolución</span>{{ $tesis->resolucion ?? '—' }}</div>
                <div class="col-md-2 mb-2"><span class="text-muted d-block">Reglas</span>{{ $tesis->reglaVersion->nombre }} v{{ $tesis->reglaVersion->version }}</div>
            </div>
            @if($tesis->resumen && !$esEvaluador)
                <p class="small mb-0 mt-2">{{ $tesis->resumen }}</p>
            @endif
        </div>
    </div>

    @if($esEvaluador && $evaluacionVigente)
        <div class="alert alert-info"><i class="fas fa-lock me-1"></i>
            Acceso de evaluación vigente hasta el <strong>{{ $evaluacionVigente->acceso_hasta->format('d/m/Y') }}</strong>.
            Solo puede ver y descargar la versión que se le asignó.
        </div>
    @endif

    <!-- Pestañas -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-hitos" type="button">Hitos y entregas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-observaciones" type="button">Observaciones <span class="badge bg-secondary">{{ $observaciones->where('resuelta', false)->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-evaluaciones" type="button">Evaluaciones</button></li>
        @unless($esEvaluador)
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-decisiones" type="button">Decisiones y actas</button></li>
        @endunless
        @if($permisos['auditoria'])
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bitacora" type="button">Bitácora</button></li>
        @endif
    </ul>

    <div class="tab-content card card-body border-top-0 rounded-0 rounded-bottom">
        <!-- ============ HITOS ============ -->
        <div class="tab-pane fade show active" id="tab-hitos">
            <ul class="timeline-hitos">
                @forelse($hitosVisibles as $hito)
                    @php
                        $versiones = $hito->entregas->flatMap->versiones->sortByDesc('numero');
                        if ($esEvaluador) { $versiones = $versiones->whereIn('id', $versionesEvaluador); }
                    @endphp
                    <li id="hito-{{ $hito->id }}">
                        <span class="punto punto-{{ $hito->semaforo }}"></span>
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <strong>{{ $hito->orden }}. {{ $hito->nombre }}</strong>
                                @if($hito->referencia_normativa)<small class="text-muted ms-1">({{ $hito->referencia_normativa }})</small>@endif
                                <div class="small text-muted">
                                    Fecha límite: {{ $hito->fecha_limite->format('d/m/Y') }} · Estado: {{ $hito->estado_nombre }}
                                    @if($hito->fecha_cumplimiento) · Cumplido el {{ $hito->fecha_cumplimiento->format('d/m/Y') }}@endif
                                </div>
                            </div>
                            <div>@include('partials.semaforo', ['hito' => $hito])</div>
                        </div>

                        @if($versiones->isNotEmpty())
                            <table class="table table-sm mt-2 mb-1">
                                <thead class="small"><tr><th>Versión</th><th>Archivo</th><th>Subida</th><th>Huella SHA-256</th><th></th></tr></thead>
                                <tbody class="small">
                                @foreach($versiones as $v)
                                    <tr>
                                        <td>v{{ $v->numero }}</td>
                                        <td>{{ $v->nombre_archivo }} <span class="text-muted">({{ number_format($v->tamano / 1024, 1) }} KB)</span>
                                            @if($v->comentario)<div class="text-muted fst-italic">{{ $v->comentario }}</div>@endif</td>
                                        <td class="text-nowrap">{{ $v->created_at->format('d/m/Y H:i') }}<br><span class="text-muted">{{ $v->autor->name }}</span></td>
                                        <td><span class="hash" title="{{ $v->sha256 }}">{{ substr($v->sha256, 0, 16) }}…</span></td>
                                        <td><a href="{{ route('version.descargar', $v) }}" class="btn btn-outline-primary btn-sm" title="Descargar"><i class="fas fa-download"></i></a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @elseif($hito->requiere_entrega)
                            <p class="small text-muted mt-2 mb-1">Sin entregas.</p>
                        @endif

                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if($permisos['subir'] && $hito->requiere_entrega && $hito->estado !== 'aprobado')
                                <details class="accion-hito">
                                    <summary class="btn btn-outline-primary btn-sm"><i class="fas fa-upload me-1"></i>Subir versión</summary>
                                    <form method="POST" action="{{ route('expediente.version', [$tesis, $hito]) }}" enctype="multipart/form-data" class="card card-body mt-2">
                                        @csrf
                                        <input type="file" name="archivo" class="form-control form-control-sm mb-2" required>
                                        <textarea name="comentario" rows="2" class="form-control form-control-sm mb-2" placeholder="Comentario (opcional): qué cambió en esta versión"></textarea>
                                        <small class="text-muted mb-2">Máx. 50 MB. Se registra la huella SHA-256 del archivo.</small>
                                        <button class="btn btn-primary btn-sm align-self-start">Subir</button>
                                    </form>
                                </details>
                            @endif

                            @if($permisos['revisar'] && $hito->estado !== 'aprobado' && ($versiones->isNotEmpty() || !$hito->requiere_entrega))
                                <details class="accion-hito">
                                    <summary class="btn btn-outline-success btn-sm"><i class="fas fa-clipboard-check me-1"></i>Revisar hito</summary>
                                    <form method="POST" action="{{ route('expediente.revisar', [$tesis, $hito]) }}" class="card card-body mt-2">
                                        @csrf
                                        <textarea name="comentario" rows="3" class="form-control form-control-sm mb-2" placeholder="Observación (obligatoria si se observa)"></textarea>
                                        <div class="d-flex gap-2">
                                            <button name="accion" value="aprobar" class="btn btn-success btn-sm"><i class="fas fa-check me-1"></i>Aprobar hito</button>
                                            <button name="accion" value="observar" class="btn btn-warning btn-sm"><i class="fas fa-comment-dots me-1"></i>Observar</button>
                                        </div>
                                    </form>
                                </details>
                            @endif

                            @if($permisos['decidir'] && $hito->estado !== 'aprobado' && !$tesis->estadoActual->final)
                                <details class="accion-hito">
                                    <summary class="btn btn-outline-secondary btn-sm"><i class="fas fa-calendar-plus me-1"></i>Prórroga</summary>
                                    <form method="POST" action="{{ route('expediente.prorroga', [$tesis, $hito]) }}" enctype="multipart/form-data" class="card card-body mt-2">
                                        @csrf
                                        <label class="form-label small mb-1">Nueva fecha límite</label>
                                        <input type="date" name="fecha_limite" min="{{ $hito->fecha_limite->copy()->addDay()->toDateString() }}" class="form-control form-control-sm mb-2" required>
                                        <textarea name="fundamento" rows="2" class="form-control form-control-sm mb-2" placeholder="Fundamento de la prórroga" required></textarea>
                                        <label class="form-label small mb-1">Documento de respaldo (opcional)</label>
                                        <input type="file" name="documento" class="form-control form-control-sm mb-2">
                                        <button class="btn btn-secondary btn-sm align-self-start">Aprobar prórroga</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="text-muted">No hay hitos visibles para su rol.</li>
                @endforelse
            </ul>
        </div>

        <!-- ============ OBSERVACIONES ============ -->
        <div class="tab-pane fade" id="tab-observaciones">
            @if($permisos['observar'])
                <form method="POST" action="{{ route('expediente.observar', $tesis) }}" class="mb-4">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="tesis_hito_id" class="form-select form-select-sm" aria-label="Hito relacionado">
                                <option value="">Observación general</option>
                                @foreach($hitosVisibles as $h)<option value="{{ $h->id }}">{{ $h->nombre }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <textarea name="texto" rows="2" class="form-control form-control-sm" placeholder="Escriba una observación" required></textarea>
                        </div>
                        <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-comment me-1"></i>Publicar observación</button></div>
                    </div>
                </form>
            @endif

            @forelse($observaciones as $obs)
                <div class="mb-3">
                    <div class="observacion {{ $obs->resuelta ? 'resuelta' : '' }}">
                        <div class="d-flex justify-content-between small">
                            <strong>{{ $obs->autor->name }} <span class="text-muted fw-normal">· {{ Roles::nombre($obs->rol_ejercido) }}</span></strong>
                            <span class="text-muted">{{ $obs->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($obs->hito)<div class="small text-muted">{{ $obs->hito->nombre }}@if($obs->version) · v{{ $obs->version->numero }}@endif</div>@endif
                        <p class="mb-1 mt-1">{{ $obs->texto }}</p>
                        <div class="d-flex gap-2 align-items-center">
                            @if($obs->resuelta)
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Resuelta</span>
                            @elseif($obs->user_id == auth()->id() || $permisos['revisar'])
                                <form method="POST" action="{{ route('expediente.resolver', [$tesis, $obs]) }}">@csrf
                                    <button class="btn btn-link btn-sm p-0">Marcar como resuelta</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @foreach($obs->respuestas as $resp)
                        <div class="observacion respuesta mt-1">
                            <div class="d-flex justify-content-between small">
                                <strong>{{ $resp->autor->name }} <span class="text-muted fw-normal">· {{ Roles::nombre($resp->rol_ejercido) }}</span></strong>
                                <span class="text-muted">{{ $resp->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mb-0 mt-1">{{ $resp->texto }}</p>
                        </div>
                    @endforeach
                    @if($permisos['observar'] && !$obs->resuelta)
                        <form method="POST" action="{{ route('expediente.observar', $tesis) }}" class="respuesta-form mt-1 ms-4 d-flex gap-2">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $obs->id }}">
                            <input type="text" name="texto" class="form-control form-control-sm" placeholder="Responder…" required>
                            <button class="btn btn-outline-primary btn-sm">Responder</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">Sin observaciones.</p>
            @endforelse
        </div>

        <!-- ============ EVALUACIONES ============ -->
        <div class="tab-pane fade" id="tab-evaluaciones">
            @php $evaluaciones = $esEvaluador ? $tesis->evaluaciones->where('evaluador_id', auth()->id()) : $tesis->evaluaciones; @endphp
            <table class="table table-sm">
                <thead><tr><th>Evaluador/a</th><th>Hito / versión</th><th>Acceso hasta</th><th>Dictamen</th></tr></thead>
                <tbody>
                @forelse($evaluaciones as $ev)
                    <tr>
                        <td>{{ $ev->evaluador->name }}</td>
                        <td>{{ $ev->hito?->nombre }} @if($ev->version) · v{{ $ev->version->numero }}@endif</td>
                        <td>{{ $ev->acceso_hasta->format('d/m/Y') }} @unless($ev->vigente)<span class="badge bg-secondary">Expirado</span>@endunless</td>
                        <td>
                            @if($ev->dictamen)
                                <strong>{{ Evaluacion::DICTAMENES[$ev->dictamen] }}</strong>
                                <small class="text-muted d-block">{{ $ev->fecha_dictamen->format('d/m/Y') }}</small>
                                <div class="small">{{ $ev->fundamento }}</div>
                            @else
                                <span class="text-muted">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Sin evaluaciones asignadas.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($evaluacionVigente && !$evaluacionVigente->dictamen)
                <form method="POST" action="{{ route('expediente.dictamen', [$tesis, $evaluacionVigente]) }}" class="card card-body">
                    @csrf
                    <h3 class="h6">Registrar dictamen</h3>
                    <div class="mb-2">
                        @foreach(Evaluacion::DICTAMENES as $valor => $etiqueta)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="dictamen" id="dictamen-{{ $valor }}" value="{{ $valor }}" required>
                                <label class="form-check-label" for="dictamen-{{ $valor }}">{{ $etiqueta }}</label>
                            </div>
                        @endforeach
                    </div>
                    <textarea name="fundamento" rows="4" class="form-control mb-2" placeholder="Fundamento de la evaluación" required></textarea>
                    <small class="text-muted mb-2">El dictamen informa a la dirección del programa; no reemplaza su decisión académica.</small>
                    <button class="btn btn-primary btn-sm align-self-start">Registrar dictamen</button>
                </form>
            @endif

            @if($permisos['gestionar'] && $versionesTesis->isNotEmpty())
                <form method="POST" action="{{ route('expediente.evaluador', $tesis) }}" class="card card-body mt-3">
                    @csrf
                    <h3 class="h6">Asignar evaluador/a</h3>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <select name="evaluador_id" class="form-select form-select-sm" required>
                                <option value="">Evaluador/a…</option>
                                @foreach($evaluadores as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <select name="entrega_version_id" class="form-select form-select-sm" required>
                                <option value="">Versión a evaluar…</option>
                                @foreach($versionesTesis->sortByDesc(fn ($x) => $x['v']->created_at) as $x)
                                    <option value="{{ $x['v']->id }}">{{ $x['hito']->nombre }} · v{{ $x['v']->numero }} ({{ $x['v']->created_at->format('d/m/Y') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" name="acceso_hasta" class="form-control form-control-sm" min="{{ now()->addDay()->toDateString() }}" value="{{ now()->addDays(30)->toDateString() }}" required title="Acceso hasta">
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block mb-2">El evaluador/a solo podrá ver y descargar la versión asignada, y su acceso expira en la fecha indicada.</small>
                            <button class="btn btn-primary btn-sm">Asignar</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>

        <!-- ============ DECISIONES Y ACTAS ============ -->
        @unless($esEvaluador)
        <div class="tab-pane fade" id="tab-decisiones">
            <h3 class="h6">Decisiones</h3>
            <table class="table table-sm">
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Responsable</th><th></th></tr></thead>
                <tbody>
                @forelse($tesis->decisiones as $d)
                    <tr>
                        <td class="text-nowrap">{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->tipo_nombre }}</td>
                        <td>
                            @if($d->estado_hacia)<span class="small text-muted">{{ $d->estado_desde }} → {{ $d->estado_hacia }}</span><br>@endif
                            {{ $d->fundamento }}
                        </td>
                        <td>{{ $d->autor->name }}<br><small class="text-muted">{{ Roles::nombre($d->rol_ejercido) }}</small></td>
                        <td>@if($d->documento_ruta)<a href="{{ route('expediente.decision.documento', [$tesis, $d]) }}" class="btn btn-outline-primary btn-sm" title="{{ $d->documento_nombre }}"><i class="fas fa-paperclip"></i></a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Sin decisiones registradas.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($permisos['decidir'])
                <details class="mb-4">
                    <summary class="btn btn-outline-secondary btn-sm"><i class="fas fa-user-friends me-1"></i>Cambiar guía o coguía</summary>
                    <form method="POST" action="{{ route('expediente.guia', $tesis) }}" class="card card-body mt-2">
                        @csrf
                        <div class="row g-2">
                            <div class="col-md-3">
                                <select name="tipo" class="form-select form-select-sm"><option value="guia">Guía</option><option value="coguia">Coguía</option></select>
                            </div>
                            <div class="col-md-4">
                                <select name="user_id" class="form-select form-select-sm">
                                    <option value="">Sin asignar (solo coguía)</option>
                                    @foreach($guiasDisponibles as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-5"><input type="text" name="fundamento" class="form-control form-control-sm" placeholder="Fundamento" required></div>
                            <div class="col-12"><button class="btn btn-secondary btn-sm">Registrar cambio</button></div>
                        </div>
                    </form>
                </details>
            @endif

            <h3 class="h6">Actas</h3>
            <table class="table table-sm">
                <thead><tr><th>Tipo</th><th>Número</th><th>Fecha</th><th>Huella SHA-256</th><th></th></tr></thead>
                <tbody>
                @forelse($tesis->actas as $acta)
                    <tr>
                        <td>{{ Acta::TIPOS[$acta->tipo] ?? $acta->tipo }}</td>
                        <td>{{ $acta->numero ?? '—' }}</td>
                        <td>{{ $acta->fecha->format('d/m/Y') }}</td>
                        <td><span class="hash" title="{{ $acta->sha256 }}">{{ substr($acta->sha256, 0, 16) }}…</span></td>
                        <td><a href="{{ route('expediente.acta.descargar', [$tesis, $acta]) }}" class="btn btn-outline-primary btn-sm"><i class="fas fa-download"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Sin actas registradas.</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($permisos['gestionar'])
                <form method="POST" action="{{ route('expediente.acta', $tesis) }}" enctype="multipart/form-data" class="card card-body">
                    @csrf
                    <h3 class="h6">Registrar acta</h3>
                    <div class="row g-2">
                        <div class="col-md-3"><select name="tipo" class="form-select form-select-sm" required>@foreach(Acta::TIPOS as $v => $e)<option value="{{ $v }}">{{ $e }}</option>@endforeach</select></div>
                        <div class="col-md-2"><input type="text" name="numero" class="form-control form-control-sm" placeholder="N°"></div>
                        <div class="col-md-3"><input type="date" name="fecha" class="form-control form-control-sm" max="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-4"><input type="file" name="archivo" class="form-control form-control-sm" required></div>
                        <div class="col-12"><button class="btn btn-primary btn-sm">Registrar acta</button></div>
                    </div>
                </form>
            @endif
        </div>
        @endunless

        <!-- ============ BITÁCORA ============ -->
        @if($permisos['auditoria'])
        <div class="tab-pane fade" id="tab-bitacora">
            <p class="small text-muted">Registro inalterable: cada evento guarda la huella del anterior, por lo que cualquier modificación es detectable.</p>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead><tr><th>Fecha</th><th>Acción</th><th>Persona</th><th>Rol</th><th>Huella</th></tr></thead>
                    <tbody>
                    @foreach($tesis->eventos as $e)
                        <tr>
                            <td class="text-nowrap small">{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="small">{{ str_replace('_', ' ', $e->accion) }}</td>
                            <td class="small">{{ $e->autor?->name ?? 'Sistema' }}</td>
                            <td class="small">{{ Roles::nombre($e->rol_ejercido) }}</td>
                            <td><span class="hash" title="{{ $e->hash }}">{{ substr($e->hash, 0, 12) }}…</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    <!-- Modal: decisión (cambio de estado) -->
    @if($transiciones->isNotEmpty())
    <div class="modal fade" id="modalDecision" tabindex="-1" aria-labelledby="modalDecisionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('expediente.estado', $tesis) }}" enctype="multipart/form-data" class="modal-content"
                  data-confirmar="La decisión quedará registrada en el expediente y en la bitácora.">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDecisionLabel">Registrar decisión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Estado actual: <strong>{{ $tesis->estadoActual->nombre }}</strong>. Acciones permitidas para su rol según {{ $tesis->reglaVersion->nombre }}:</p>
                    @foreach($transiciones as $t)
                        <div class="form-check mb-2">
                            <input class="form-check-input transicion" type="radio" name="transicion_id" id="transicion-{{ $t->id }}" value="{{ $t->id }}"
                                   data-documento="{{ $t->requiere_documento ? 1 : 0 }}" required>
                            <label class="form-check-label" for="transicion-{{ $t->id }}">
                                {{ $t->accion }} <span class="text-muted">→ {{ $t->estadoHacia->nombre }}</span>
                                @if($t->requiere_documento)<span class="badge bg-light text-dark border">Requiere documento</span>@endif
                            </label>
                        </div>
                    @endforeach
                    <label for="fundamento" class="form-label mt-2">Fundamento</label>
                    <textarea id="fundamento" name="fundamento" rows="3" class="form-control" required></textarea>
                    <div class="mt-2" id="documentoDecision">
                        <label for="documento" class="form-label">Documento de respaldo (acta, resolución)</label>
                        <input type="file" id="documento" name="documento" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Registrar</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    // Mantener la pestaña al recargar (por ejemplo, después de guardar)
    document.addEventListener('DOMContentLoaded', function () {
        const clave = 'tab-{{ $tesis->id }}';
        let guardada = null;
        try { guardada = sessionStorage.getItem(clave); } catch (e) {}
        if (guardada && !location.hash) {
            const boton = document.querySelector(`[data-bs-target="${guardada}"]`);
            if (boton) bootstrap.Tab.getOrCreateInstance(boton).show();
        }
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(b => b.addEventListener('shown.bs.tab', e => {
            try { sessionStorage.setItem(clave, e.target.dataset.bsTarget); } catch (err) {}
        }));

        // El documento es obligatorio solo para las transiciones que lo exigen
        document.querySelectorAll('.transicion').forEach(radio => radio.addEventListener('change', function () {
            document.getElementById('documento').required = this.dataset.documento === '1';
        }));
    });
</script>
@endpush
