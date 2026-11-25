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
    $nombreEstado = \App\Models\EstadoTesis::pluck('nombre', 'codigo');
    // Panel que debe reabrirse si el último envío volvió con errores
    $panelConError = $errors->any() ? old('_panel') : null;
@endphp

@section('migas')
    <li class="breadcrumb-item"><a href="{{ route('tesis.index') }}">Expedientes de tesis</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $tesis->codigo }}</li>
@endsection

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
                    <h2 class="h5 mb-2">{{ $tesis->titulo }}</h2>
                    @include('partials.persona', ['nombre' => $tesis->doctorando->name, 'detalle' => $tesis->programa->nombre . ' · ' . $tesis->cohorte->nombre, 'tam' => 'md'])
                </div>
                <div class="text-end">@include('partials.estado', ['estado' => $tesis->estadoActual])</div>
            </div>
            <hr>
            <div class="row small ficha-datos">
                <div class="col-6 col-md-3 mb-2"><span class="text-muted d-block mb-1">Guía</span>@if($guia)@include('partials.persona', ['nombre' => $guia->user->name, 'tam' => 'xs'])@else — @endif</div>
                <div class="col-6 col-md-3 mb-2"><span class="text-muted d-block mb-1">Coguía</span>@if($coguia)@include('partials.persona', ['nombre' => $coguia->user->name, 'tam' => 'xs'])@else — @endif</div>
                <div class="col-6 col-md-2 mb-2"><span class="text-muted d-block">Inscripción</span>{{ $tesis->fecha_inscripcion->format('d/m/Y') }}</div>
                <div class="col-6 col-md-2 mb-2"><span class="text-muted d-block">Resolución</span>{{ $tesis->resolucion ?? '—' }}</div>
                <div class="col-6 col-md-2 mb-2"><span class="text-muted d-block">Reglas</span>{{ $tesis->reglaVersion->nombre }} v{{ $tesis->reglaVersion->version }}</div>
            </div>
            @if($tesis->resumen && !$esEvaluador)
                <p class="small mb-0 mt-2 d-none d-md-block">{{ $tesis->resumen }}</p>
                <details class="small mt-2 d-md-none"><summary>Resumen</summary>{{ $tesis->resumen }}</details>
            @endif
        </div>
    </div>

    @php
        $siguiente = $tesis->estadoActual->final || $esEvaluador ? null
            : $tesis->hitos->where('estado', '!=', 'aprobado')->sortBy('fecha_limite')->first();
        $obsAbiertas = $observaciones->where('resuelta', false)->count();
    @endphp
    @if($siguiente)
        <div class="card proximo-paso {{ $siguiente->semaforo === 'vencido' ? 'urgente' : '' }}">
            <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
                <div class="flex-grow-1">
                    <span class="text-muted small d-block">Próximo paso · le toca a {{ $siguiente->turno === 'guia' ? 'la guía' : 'el/la doctorando/a' }}</span>
                    <strong>
                        @if($siguiente->turno === 'guia')
                            Revisar la entrega de «{{ $siguiente->nombre }}»
                        @elseif($siguiente->estado === 'observado')
                            Corregir y volver a entregar «{{ $siguiente->nombre }}»
                        @else
                            {{ $siguiente->requiere_entrega ? 'Entregar' : 'Cumplir' }} «{{ $siguiente->nombre }}»
                        @endif
                    </strong>
                    <span class="small ms-1">· fecha límite {{ $siguiente->fecha_limite->format('d/m/Y') }}</span>
                    @include('partials.semaforo', ['hito' => $siguiente])
                    @if($obsAbiertas)
                        <a href="#tab-observaciones" class="small ms-2 ir-pestana" data-pestana="tab-observaciones">{{ $obsAbiertas }} {{ $obsAbiertas === 1 ? 'observación abierta' : 'observaciones abiertas' }}</a>
                    @endif
                </div>
                <a href="#hito-{{ $siguiente->id }}" class="btn btn-outline-primary btn-sm ir-pestana" data-pestana="tab-hitos">Ir al hito</a>
            </div>
        </div>
    @endif

    @if($esEvaluador && $evaluacionVigente)
        <div class="alert alert-info"><i class="fas fa-lock me-1"></i>
            Acceso de evaluación vigente hasta el <strong>{{ $evaluacionVigente->acceso_hasta->format('d/m/Y') }}</strong>.
            Solo puede ver y descargar la versión que se le asignó.
        </div>
    @endif

    <!-- Pestañas -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-hitos" type="button" role="tab" id="boton-hitos" aria-controls="tab-hitos">Hitos y entregas</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-observaciones" type="button" role="tab" id="boton-observaciones" aria-controls="tab-observaciones">Observaciones <span class="badge bg-secondary">{{ $observaciones->where('resuelta', false)->count() }}</span></button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-evaluaciones" type="button" role="tab" id="boton-evaluaciones" aria-controls="tab-evaluaciones">Evaluaciones</button></li>
        @unless($esEvaluador)
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-decisiones" type="button" role="tab" id="boton-decisiones" aria-controls="tab-decisiones">Decisiones y actas</button></li>
        @endunless
        @if($permisos['auditoria'])
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bitacora" type="button" role="tab" id="boton-bitacora" aria-controls="tab-bitacora">Bitácora</button></li>
        @endif
    </ul>

    <div class="tab-content card card-body border-top-0 rounded-0 rounded-bottom">
        <!-- ============ HITOS ============ -->
        <div class="tab-pane fade show active" id="tab-hitos">
            @if($hitosVisibles->isNotEmpty())
                <div class="mb-3">@include('partials.linea-tiempo', ['hitos' => $hitosVisibles])</div>
            @endif
            <div class="mb-3">@include('partials.leyenda-semaforo')</div>
            <ul class="timeline-hitos">
                @forelse($hitosVisibles as $hito)
                    @php
                        $versiones = $hito->entregas->flatMap->versiones->sortByDesc('numero');
                        if ($esEvaluador) { $versiones = $versiones->whereIn('id', $versionesEvaluador); }
                    @endphp
                    @php $plegado = $hito->semaforo === 'cumplido'; @endphp
                    <li id="hito-{{ $hito->id }}" class="{{ $plegado ? 'hito-cumplido' : '' }}">
                        <span class="punto punto-{{ $hito->semaforo }}"></span>
                        @if($plegado)<details><summary>@endif
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                @if($plegado)<i class="fas fa-chevron-right small text-muted me-1" aria-hidden="true"></i>@endif
                                <strong>{{ $hito->orden }}. {{ $hito->nombre }}</strong>
                                @if($hito->referencia_normativa)<small class="text-muted ms-1">({{ $hito->referencia_normativa }})</small>@endif
                                <div class="small text-muted">
                                    Fecha límite: {{ $hito->fecha_limite->format('d/m/Y') }} · Estado: {{ $hito->estado_nombre }}
                                    @if($hito->fecha_cumplimiento) · Cumplido el {{ $hito->fecha_cumplimiento->format('d/m/Y') }}@endif
                                </div>
                            </div>
                            <div>@include('partials.semaforo', ['hito' => $hito])</div>
                        </div>
                        @if($plegado)</summary>@endif

                        @if($versiones->isNotEmpty())
                            <table class="table table-sm mt-2 mb-1 tabla-apilable">
                                <thead class="small"><tr><th>Versión</th><th>Archivo</th><th>Subida</th><th class="d-none d-md-table-cell"><span data-bs-toggle="tooltip" title="Código que permite comprobar que el archivo no fue alterado después de subirlo">Huella <i class="far fa-question-circle text-muted"></i></span></th><th></th></tr></thead>
                                <tbody class="small">
                                @foreach($versiones as $v)
                                    <tr>
                                        <td data-etiqueta="Versión">v{{ $v->numero }}</td>
                                        <td>{{ $v->nombre_archivo }} <span class="text-muted">({{ number_format($v->tamano / 1024, 1) }} KB)</span>
                                            @if($v->comentario)<div class="text-muted fst-italic">{{ $v->comentario }}</div>@endif</td>
                                        <td class="text-nowrap" data-etiqueta="Subida">{{ $v->created_at->format('d/m/Y H:i') }}<br class="d-none d-md-inline"><span class="text-muted"> {{ $v->autor->name }}</span></td>
                                        <td class="d-none d-md-table-cell"><span class="hash" title="SHA-256: {{ $v->sha256 }}">{{ substr($v->sha256, 0, 8) }}</span></td>
                                        <td><a href="{{ route('version.descargar', $v) }}" class="btn btn-outline-primary btn-sm" title="Descargar v{{ $v->numero }}" aria-label="Descargar versión {{ $v->numero }}"><i class="fas fa-download"></i></a></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @elseif($hito->requiere_entrega)
                            <p class="small text-muted mt-2 mb-1">Sin entregas.</p>
                        @endif

                        <div class="d-flex flex-wrap gap-2 mt-2">
                            @if($permisos['subir'] && $hito->requiere_entrega && $hito->estado !== 'aprobado')
                                <details class="accion-hito" @if($panelConError === 'version-'.$hito->id) open @endif>
                                    <summary class="btn btn-outline-primary btn-sm"><i class="fas fa-upload me-1"></i>Subir versión</summary>
                                    <form method="POST" action="{{ route('expediente.version', [$tesis, $hito]) }}" enctype="multipart/form-data" class="card card-body mt-2" data-volver="hito-{{ $hito->id }}">
                                        @csrf
                                        <input type="hidden" name="_panel" value="version-{{ $hito->id }}">
                                        @if($panelConError === 'version-'.$hito->id)<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                                        <label class="form-label small mb-1" for="archivo-{{ $hito->id }}">Archivo de la versión {{ ($versiones->max('numero') ?? 0) + 1 }}</label>
                                        <input type="file" id="archivo-{{ $hito->id }}" name="archivo" class="form-control form-control-sm mb-2" required>
                                        <textarea name="comentario" rows="2" class="form-control form-control-sm mb-2" placeholder="Comentario (opcional): qué cambió en esta versión" aria-label="Comentario de la versión">{{ $panelConError === 'version-'.$hito->id ? old('comentario') : '' }}</textarea>
                                        <small class="text-muted mb-2">Máximo 50 MB. Las versiones anteriores se conservan.</small>
                                        <button class="btn btn-primary btn-sm align-self-start"><i class="fas fa-upload me-1"></i>Subir versión</button>
                                    </form>
                                </details>
                            @endif

                            @if($permisos['revisar'] && $hito->estado !== 'aprobado' && ($versiones->isNotEmpty() || !$hito->requiere_entrega))
                                <details class="accion-hito" @if($panelConError === 'revisar-'.$hito->id) open @endif>
                                    <summary class="btn btn-outline-success btn-sm"><i class="fas fa-clipboard-check me-1"></i>Revisar hito</summary>
                                    <form method="POST" action="{{ route('expediente.revisar', [$tesis, $hito]) }}" class="card card-body mt-2" data-volver="hito-{{ $hito->id }}">
                                        @csrf
                                        <input type="hidden" name="_panel" value="revisar-{{ $hito->id }}">
                                        @if($panelConError === 'revisar-'.$hito->id)<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                                        <label class="form-label small mb-1" for="comentario-revision-{{ $hito->id }}">Comentario para el/la doctorando/a</label>
                                        <textarea id="comentario-revision-{{ $hito->id }}" name="comentario" rows="3" class="form-control form-control-sm mb-1">{{ $panelConError === 'revisar-'.$hito->id ? old('comentario') : '' }}</textarea>
                                        <small class="text-muted mb-2">Obligatorio si devuelve con observaciones; opcional si aprueba.</small>
                                        <div class="d-flex flex-wrap gap-2">
                                            <button name="accion" value="aprobar" class="btn btn-success btn-sm"
                                                    data-confirmar-titulo="¿Aprobar «{{ $hito->nombre }}»?"
                                                    data-confirmar="El hito quedará cumplido y ya no admitirá nuevas versiones. No se puede deshacer desde la plataforma."><i class="fas fa-check me-1"></i>Aprobar hito</button>
                                            <button name="accion" value="observar" class="btn btn-warning btn-sm" data-requiere="#comentario-revision-{{ $hito->id }}"><i class="fas fa-comment-dots me-1"></i>Devolver con observaciones</button>
                                        </div>
                                    </form>
                                </details>
                            @endif

                            @if($permisos['decidir'] && $hito->estado !== 'aprobado' && !$tesis->estadoActual->final)
                                <details class="accion-hito" @if($panelConError === 'prorroga-'.$hito->id) open @endif>
                                    <summary class="btn btn-outline-secondary btn-sm"><i class="fas fa-calendar-plus me-1"></i>Prórroga</summary>
                                    <form method="POST" action="{{ route('expediente.prorroga', [$tesis, $hito]) }}" enctype="multipart/form-data" class="card card-body mt-2" data-volver="hito-{{ $hito->id }}"
                                          data-confirmar-titulo="¿Aprobar la prórroga?" data-confirmar="La nueva fecha reemplaza a la actual ({{ $hito->fecha_limite->format('d/m/Y') }}) y queda registrada como decisión.">
                                        @csrf
                                        <input type="hidden" name="_panel" value="prorroga-{{ $hito->id }}">
                                        @if($panelConError === 'prorroga-'.$hito->id)<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                                        <label class="form-label small mb-1" for="fecha-prorroga-{{ $hito->id }}">Nueva fecha límite <span class="text-muted">(actual: {{ $hito->fecha_limite->format('d/m/Y') }})</span></label>
                                        <input type="date" id="fecha-prorroga-{{ $hito->id }}" name="fecha_limite" value="{{ $panelConError === 'prorroga-'.$hito->id ? old('fecha_limite') : '' }}" min="{{ $hito->fecha_limite->copy()->addDay()->toDateString() }}" class="form-control form-control-sm mb-2" required>
                                        <label class="form-label small mb-1" for="fundamento-prorroga-{{ $hito->id }}">Fundamento</label>
                                        <textarea id="fundamento-prorroga-{{ $hito->id }}" name="fundamento" rows="2" class="form-control form-control-sm mb-2" required>{{ $panelConError === 'prorroga-'.$hito->id ? old('fundamento') : '' }}</textarea>
                                        <label class="form-label small mb-1" for="documento-prorroga-{{ $hito->id }}">Documento de respaldo (opcional)</label>
                                        <input type="file" id="documento-prorroga-{{ $hito->id }}" name="documento" class="form-control form-control-sm mb-2">
                                        <button class="btn btn-secondary btn-sm align-self-start">Aprobar prórroga</button>
                                    </form>
                                </details>
                            @endif
                        </div>
                        @if($plegado)</details>@endif
                    </li>
                @empty
                    <li class="border-0 p-0">@include('partials.vacio', ['dibujo' => 'documento', 'titulo' => 'No hay hitos visibles para su rol', 'texto' => 'Solo se muestran los hitos de las versiones que se le asignaron.'])</li>
                @endforelse
            </ul>
        </div>

        <!-- ============ OBSERVACIONES ============ -->
        <div class="tab-pane fade" id="tab-observaciones">
            @if($permisos['observar'])
                @php $obsNueva = $panelConError === 'observacion-nueva'; @endphp
                <form method="POST" action="{{ route('expediente.observar', $tesis) }}" class="mb-4" data-volver="tab-observaciones">
                    @csrf
                    <input type="hidden" name="_panel" value="observacion-nueva">
                    @if($panelConError === 'observacion-nueva')<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1" for="obs-hito">Sobre</label>
                            <select id="obs-hito" name="tesis_hito_id" class="form-select form-select-sm">
                                <option value="">Observación general</option>
                                @foreach($hitosVisibles as $h)<option value="{{ $h->id }}" @selected($obsNueva && old('tesis_hito_id') == $h->id)>{{ $h->nombre }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small mb-1" for="obs-texto">Observación</label>
                            <textarea id="obs-texto" name="texto" rows="2" class="form-control form-control-sm" required>{{ $obsNueva ? old('texto') : '' }}</textarea>
                        </div>
                        <div class="col-12"><button class="btn btn-primary btn-sm"><i class="fas fa-comment me-1"></i>Publicar observación</button></div>
                    </div>
                </form>
            @endif

            @forelse($observaciones as $obs)
                <div class="mb-3" id="observacion-{{ $obs->id }}">
                    <div class="observacion {{ $obs->resuelta ? 'resuelta' : '' }}">
                        <div class="d-flex justify-content-between small">
                            @include('partials.persona', ['nombre' => $obs->autor->name, 'detalle' => Roles::nombre($obs->rol_ejercido), 'tam' => 'sm'])
                            <span class="text-muted">{{ $obs->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($obs->hito)<div class="small text-muted">{{ $obs->hito->nombre }}@if($obs->version) · v{{ $obs->version->numero }}@endif</div>@endif
                        <p class="mb-1 mt-1">{{ $obs->texto }}</p>
                        <div class="d-flex gap-2 align-items-center">
                            @if($obs->resuelta)
                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>Resuelta</span>
                            @elseif($obs->user_id == auth()->id() || $permisos['revisar'])
                                <form method="POST" action="{{ route('expediente.resolver', [$tesis, $obs]) }}" data-volver="observacion-{{ $obs->id }}"
                                      data-confirmar-titulo="¿Marcar como resuelta?" data-confirmar="La observación se cerrará y ya no admitirá respuestas.">@csrf
                                    <button class="btn btn-link btn-sm p-0">Marcar como resuelta</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    @foreach($obs->respuestas as $resp)
                        <div class="observacion respuesta mt-1">
                            <div class="d-flex justify-content-between small">
                                @include('partials.persona', ['nombre' => $resp->autor->name, 'detalle' => Roles::nombre($resp->rol_ejercido), 'tam' => 'xs'])
                                <span class="text-muted">{{ $resp->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                            <p class="mb-0 mt-1">{{ $resp->texto }}</p>
                        </div>
                    @endforeach
                    @if($permisos['observar'] && !$obs->resuelta)
                        <form method="POST" action="{{ route('expediente.observar', $tesis) }}" class="respuesta-form mt-1 ms-4 d-flex gap-2" data-volver="observacion-{{ $obs->id }}">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $obs->id }}">
                            <input type="hidden" name="_panel" value="respuesta-{{ $obs->id }}">
                            @if($panelConError === 'respuesta-'.$obs->id)<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                            <input type="text" name="texto" value="{{ $panelConError === 'respuesta-'.$obs->id ? old('texto') : '' }}" class="form-control form-control-sm" placeholder="Responder…" aria-label="Respuesta a la observación" required>
                            <button class="btn btn-outline-primary btn-sm">Responder</button>
                        </form>
                    @endif
                </div>
            @empty
                @include('partials.vacio', ['dibujo' => 'conversacion', 'titulo' => 'Aún no hay observaciones', 'texto' => $permisos['observar'] ? 'Escriba la primera arriba. Puede asociarla a un hito o dejarla como observación general.' : 'Cuando la guía o el comité comenten la tesis, las observaciones aparecerán aquí.'])
            @endforelse
        </div>

        <!-- ============ EVALUACIONES ============ -->
        <div class="tab-pane fade" id="tab-evaluaciones">
            @php $evaluaciones = $esEvaluador ? $tesis->evaluaciones->where('evaluador_id', auth()->id()) : $tesis->evaluaciones; @endphp
            <table class="table table-sm tabla-tarjetas">
                <thead><tr><th>Evaluador/a</th><th>Hito / versión</th><th>Acceso hasta</th><th>Dictamen</th></tr></thead>
                <tbody>
                @forelse($evaluaciones as $ev)
                    <tr>
                        <td class="td-principal">@include('partials.persona', ['nombre' => $ev->evaluador->name])</td>
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
                    <tr><td colspan="4">@include('partials.vacio', ['dibujo' => 'documento', 'titulo' => 'Sin evaluaciones asignadas', 'texto' => $permisos['gestionar'] ? 'Asigne un evaluador/a a una versión entregada con el formulario de abajo.' : null, 'compacto' => true])</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($evaluacionVigente && !$evaluacionVigente->dictamen)
                <form method="POST" action="{{ route('expediente.dictamen', [$tesis, $evaluacionVigente]) }}" class="card card-body" data-volver="tab-evaluaciones"
                      data-confirmar-titulo="¿Registrar el dictamen?" data-confirmar="Una vez registrado, el dictamen no se puede modificar.">
                    @csrf
                    <input type="hidden" name="_panel" value="dictamen">
                    @if($panelConError === 'dictamen')<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                    <h3 class="h6">Registrar dictamen</h3>
                    <div class="mb-2">
                        @foreach(Evaluacion::DICTAMENES as $valor => $etiqueta)
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="dictamen" id="dictamen-{{ $valor }}" value="{{ $valor }}" @checked(old('dictamen') === $valor) required>
                                <label class="form-check-label" for="dictamen-{{ $valor }}">{{ $etiqueta }}</label>
                            </div>
                        @endforeach
                    </div>
                    <label class="form-label small mb-1" for="fundamento-dictamen">Fundamento de la evaluación</label>
                    <textarea id="fundamento-dictamen" name="fundamento" rows="4" class="form-control mb-2" required>{{ $panelConError === 'dictamen' ? old('fundamento') : '' }}</textarea>
                    <small class="text-muted mb-2">El dictamen informa a la dirección del programa; no reemplaza su decisión académica.</small>
                    <button class="btn btn-primary btn-sm align-self-start">Registrar dictamen</button>
                </form>
            @endif

            @if($permisos['gestionar'] && $versionesTesis->isNotEmpty())
                @php $asig = $panelConError === 'evaluador'; @endphp
                <form method="POST" action="{{ route('expediente.evaluador', $tesis) }}" class="card card-body mt-3" data-volver="tab-evaluaciones">
                    @csrf
                    <input type="hidden" name="_panel" value="evaluador">
                    @if($panelConError === 'evaluador')<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                    <h3 class="h6">Asignar evaluador/a</h3>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1" for="asig-evaluador">Evaluador/a</label>
                            <select id="asig-evaluador" name="evaluador_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione…</option>
                                @foreach($evaluadores as $e)<option value="{{ $e->id }}" @selected($asig && old('evaluador_id') == $e->id)>{{ $e->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small mb-1" for="asig-version">Versión a evaluar</label>
                            <select id="asig-version" name="entrega_version_id" class="form-select form-select-sm" required>
                                <option value="">Seleccione…</option>
                                @foreach($versionesTesis->sortByDesc(fn ($x) => $x['v']->created_at) as $x)
                                    <option value="{{ $x['v']->id }}" @selected($asig && old('entrega_version_id') == $x['v']->id)>{{ $x['hito']->nombre }} · v{{ $x['v']->numero }} ({{ $x['v']->created_at->format('d/m/Y') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1" for="asig-hasta">Acceso hasta</label>
                            <input type="date" id="asig-hasta" name="acceso_hasta" class="form-control form-control-sm" min="{{ now()->addDay()->toDateString() }}" value="{{ $asig ? old('acceso_hasta') : now()->addDays(30)->toDateString() }}" required>
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
            <table class="table table-sm tabla-tarjetas">
                <thead><tr><th>Fecha</th><th>Tipo</th><th>Detalle</th><th>Responsable</th><th></th></tr></thead>
                <tbody>
                @forelse($tesis->decisiones as $d)
                    <tr>
                        <td class="text-nowrap">{{ $d->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $d->tipo_nombre }}</td>
                        <td>
                            @if($d->estado_hacia)<span class="small text-muted">{{ $nombreEstado[$d->estado_desde] ?? $d->estado_desde }} → {{ $nombreEstado[$d->estado_hacia] ?? $d->estado_hacia }}</span><br>@endif
                            {{ $d->fundamento }}
                        </td>
                        <td>@include('partials.persona', ['nombre' => $d->autor->name, 'detalle' => Roles::nombre($d->rol_ejercido), 'tam' => 'xs'])</td>
                        <td>@if($d->documento_ruta)<a href="{{ route('expediente.decision.documento', [$tesis, $d]) }}" class="btn btn-outline-primary btn-sm" title="Descargar {{ $d->documento_nombre }}" aria-label="Descargar documento de respaldo"><i class="fas fa-paperclip"></i></a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5">@include('partials.vacio', ['dibujo' => 'documento', 'titulo' => 'Sin decisiones registradas', 'texto' => 'Los cambios de estado, prórrogas y cambios de guía quedarán aquí.', 'compacto' => true])</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($permisos['decidir'])
                @php $cg = $panelConError === 'guia'; @endphp
                <details class="mb-4 accion-hito" @if($cg) open @endif>
                    <summary class="btn btn-outline-secondary btn-sm"><i class="fas fa-user-friends me-1"></i>Cambiar guía o coguía</summary>
                    <form method="POST" action="{{ route('expediente.guia', $tesis) }}" class="card card-body mt-2" data-volver="tab-decisiones"
                          data-confirmar-titulo="¿Registrar el cambio de equipo de guía?" data-confirmar="Se avisará a las personas involucradas y quedará en la bitácora.">
                        @csrf
                        <input type="hidden" name="_panel" value="guia">
                        @if($panelConError === 'guia')<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-1" for="cg-tipo">Cargo</label>
                                <select id="cg-tipo" name="tipo" class="form-select form-select-sm"><option value="guia">Guía</option><option value="coguia" @selected($cg && old('tipo') === 'coguia')>Coguía</option></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1" for="cg-user">Nueva persona</label>
                                <select id="cg-user" name="user_id" class="form-select form-select-sm">
                                    <option value="">Sin asignar (solo coguía)</option>
                                    @foreach($guiasDisponibles as $g)<option value="{{ $g->id }}" @selected($cg && old('user_id') == $g->id)>{{ $g->name }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-md-5"><label class="form-label small mb-1" for="cg-fund">Fundamento</label><input type="text" id="cg-fund" name="fundamento" value="{{ $cg ? old('fundamento') : '' }}" class="form-control form-control-sm" required></div>
                            <div class="col-12"><button class="btn btn-secondary btn-sm">Registrar cambio</button></div>
                        </div>
                    </form>
                </details>
            @endif

            <h3 class="h6">Actas</h3>
            <table class="table table-sm tabla-tarjetas">
                <thead><tr><th>Tipo</th><th>Número</th><th>Fecha</th><th><span data-bs-toggle="tooltip" title="Código que permite comprobar que el archivo no fue alterado después de subirlo">Huella <i class="far fa-question-circle text-muted"></i></span></th><th></th></tr></thead>
                <tbody>
                @forelse($tesis->actas as $acta)
                    <tr>
                        <td>{{ Acta::TIPOS[$acta->tipo] ?? $acta->tipo }}</td>
                        <td>{{ $acta->numero ?? '—' }}</td>
                        <td>{{ $acta->fecha->format('d/m/Y') }}</td>
                        <td><span class="hash" title="SHA-256: {{ $acta->sha256 }}">{{ substr($acta->sha256, 0, 8) }}</span></td>
                        <td><a href="{{ route('expediente.acta.descargar', [$tesis, $acta]) }}" class="btn btn-outline-primary btn-sm" title="Descargar acta" aria-label="Descargar acta"><i class="fas fa-download"></i></a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">@include('partials.vacio', ['dibujo' => 'carpeta', 'titulo' => 'Sin actas registradas', 'texto' => $permisos['gestionar'] ? 'Registre la primera con el formulario de abajo.' : null, 'compacto' => true])</td></tr>
                @endforelse
                </tbody>
            </table>

            @if($permisos['gestionar'])
                @php $ac = $panelConError === 'acta'; @endphp
                <form method="POST" action="{{ route('expediente.acta', $tesis) }}" enctype="multipart/form-data" class="card card-body" data-volver="tab-decisiones">
                    @csrf
                    <input type="hidden" name="_panel" value="acta">
                    @if($panelConError === 'acta')<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                    <h3 class="h6">Registrar acta</h3>
                    <div class="row g-2">
                        <div class="col-md-3"><label class="form-label small mb-1" for="acta-tipo">Tipo</label><select id="acta-tipo" name="tipo" class="form-select form-select-sm" required>@foreach(Acta::TIPOS as $v => $e)<option value="{{ $v }}" @selected($ac && old('tipo') === $v)>{{ $e }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label small mb-1" for="acta-numero">Número</label><input type="text" id="acta-numero" name="numero" value="{{ $ac ? old('numero') : '' }}" class="form-control form-control-sm"></div>
                        <div class="col-md-3"><label class="form-label small mb-1" for="acta-fecha">Fecha</label><input type="date" id="acta-fecha" name="fecha" value="{{ $ac ? old('fecha') : '' }}" class="form-control form-control-sm" max="{{ now()->toDateString() }}" required></div>
                        <div class="col-md-4"><label class="form-label small mb-1" for="acta-archivo">Archivo</label><input type="file" id="acta-archivo" name="archivo" class="form-control form-control-sm" required></div>
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
                <table class="table table-sm tabla-tarjetas">
                    <thead><tr><th>Fecha</th><th>Acción</th><th>Persona</th><th>Rol</th><th><span data-bs-toggle="tooltip" title="Cada evento guarda la huella del anterior: cualquier alteración es detectable">Huella <i class="far fa-question-circle text-muted"></i></span></th></tr></thead>
                    <tbody>
                    @foreach($tesis->eventos as $e)
                        <tr>
                            <td class="text-nowrap small">{{ $e->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="small">{{ str_replace('_', ' ', $e->accion) }}</td>
                            <td class="small">@include('partials.persona', ['nombre' => $e->autor?->name ?? 'Sistema', 'tam' => 'xs'])</td>
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
                  data-confirmar="La decisión cambiará el estado del expediente y quedará registrada en la bitácora.">
                @csrf
                <input type="hidden" name="_panel" value="decision">
                @if($panelConError === 'decision')<div class="alert alert-danger py-1 px-2 small mb-2" role="alert"><i class="fas fa-exclamation-triangle me-1"></i>{{ $errors->first() }}</div>@endif
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDecisionLabel">Registrar decisión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">Estado actual: <strong>{{ $tesis->estadoActual->nombre }}</strong>. Acciones permitidas para su rol según {{ $tesis->reglaVersion->nombre }}:</p>
                    @foreach($transiciones as $t)
                        <div class="form-check mb-2">
                            <input class="form-check-input transicion" type="radio" name="transicion_id" id="transicion-{{ $t->id }}" value="{{ $t->id }}"
                                   data-documento="{{ $t->requiere_documento ? 1 : 0 }}" @checked(old('transicion_id') == $t->id) required>
                            <label class="form-check-label" for="transicion-{{ $t->id }}">
                                {{ $t->accion }} <span class="text-muted">→ {{ $t->estadoHacia->nombre }}</span>
                                @if($t->requiere_documento)<span class="badge bg-light text-dark border">Requiere documento</span>@endif
                            </label>
                        </div>
                    @endforeach
                    <label for="fundamento" class="form-label mt-2">Fundamento</label>
                    <textarea id="fundamento" name="fundamento" rows="3" class="form-control" required>{{ $panelConError === 'decision' ? old('fundamento') : '' }}</textarea>
                    <div class="mt-2" id="documentoDecision">
                        <label for="documento" class="form-label">Documento de respaldo (acta, resolución)</label>
                        <input type="file" id="documento" name="documento" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary">Registrar decisión</button>
                </div>
            </form>
        </div>
    </div>
    @endif
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mostrarPestana = id => {
            const boton = document.querySelector(`[data-bs-target="#${id}"]`);
            if (boton) bootstrap.Tab.getOrCreateInstance(boton).show();
        };

        // La pestaña abierta se refleja en la URL (#tab-observaciones), así se puede enlazar y no depende del navegador
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(b => b.addEventListener('shown.bs.tab', e => {
            history.replaceState(null, '', e.target.dataset.bsTarget);
        }));

        // Después de guardar (o de un error), volver a la pestaña y al elemento donde se actuó
        let volver = null;
        try { volver = JSON.parse(sessionStorage.getItem('volverA') || 'null'); sessionStorage.removeItem('volverA'); } catch (e) {}
        if (volver && volver.url === location.pathname) {
            if (volver.pestana) mostrarPestana(volver.pestana);
            const destino = document.getElementById(volver.ancla);
            if (destino) {
                setTimeout(() => {
                    destino.scrollIntoView({ block: 'center' });
                    destino.classList.add('recien-actualizado');
                }, 200);
            }
        } else if (location.hash.startsWith('#tab-')) {
            mostrarPestana(location.hash.substring(1));
        } else if (location.hash.startsWith('#hito-')) {
            document.querySelector(location.hash)?.classList.add('recien-actualizado');
        }

        // Enlaces que llevan a otra pestaña o a un hito plegado
        const abrirDestino = hash => {
            const el = hash && document.querySelector(hash);
            el?.querySelector(':scope > details')?.setAttribute('open', '');
            return el;
        };
        document.querySelectorAll('.ir-pestana').forEach(a => a.addEventListener('click', e => {
            e.preventDefault();
            mostrarPestana(a.dataset.pestana);
            const destino = a.getAttribute('href') !== '#' + a.dataset.pestana ? abrirDestino(a.getAttribute('href')) : null;
            setTimeout(() => (destino || document.getElementById(a.dataset.pestana)).scrollIntoView({ block: 'start', behavior: 'smooth' }), 150);
        }));
        if (location.hash.startsWith('#hito-')) {
            const el = abrirDestino(location.hash);
            if (el) setTimeout(() => el.scrollIntoView({ block: 'start' }), 100);
        }

        @if($panelConError === 'decision')
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDecision')).show();
        @endif

        // El documento es obligatorio solo para las transiciones que lo exigen
        const actualizarDocumento = () => {
            const elegida = document.querySelector('.transicion:checked');
            const doc = document.getElementById('documento');
            if (doc) doc.required = elegida?.dataset.documento === '1';
        };
        document.querySelectorAll('.transicion').forEach(radio => radio.addEventListener('change', actualizarDocumento));
        actualizarDocumento();
    });
</script>
@endpush
