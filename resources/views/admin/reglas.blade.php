@extends('layouts.app')

@section('titulo', 'Reglas y programas')

@section('contenido')
    <p class="text-muted">
        La normativa de cada programa se traduce en una <strong>versión de reglas</strong>: hitos con plazos y transiciones de estado
        permitidas por rol. Cada cohorte usa una versión, de modo que un cambio de reglamento no altera los expedientes ya iniciados.
    </p>

    @foreach($programas as $programa)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ $programa->nombre }} <small class="ms-2 opacity-75">{{ $programa->institucion->nombre }}</small></h3>
            </div>
            <div class="card-body">
                <p class="small mb-2"><strong>Cohortes:</strong>
                    @foreach($programa->cohortes->sortBy('anio') as $c)
                        <span class="badge bg-light text-dark border">{{ $c->anio }} → v{{ $c->reglaVersion->version }}</span>
                    @endforeach
                </p>

                @foreach($programa->reglaVersiones as $regla)
                    <h4 class="h6 mt-3">{{ $regla->nombre }} · versión {{ $regla->version }}
                        <small class="text-muted">— {{ $regla->normativa }}, vigente desde {{ $regla->vigente_desde->format('d/m/Y') }}</small>
                    </h4>
                    <div class="row">
                        <div class="col-xl-7">
                            <table class="table table-sm">
                                <thead><tr><th>#</th><th>Hito</th><th>Plazo</th><th>Entrega</th><th>Evaluación</th><th>Referencia</th></tr></thead>
                                <tbody>
                                @foreach($regla->hitos as $h)
                                    <tr>
                                        <td>{{ $h->orden }}</td>
                                        <td>{{ $h->nombre }}<small class="d-block text-muted">{{ $h->descripcion }}</small></td>
                                        <td class="text-nowrap">{{ $h->plazo_meses }} meses</td>
                                        <td>{!! $h->requiere_entrega ? '<i class="fas fa-check text-success"></i>' : '—' !!}</td>
                                        <td>{!! $h->requiere_evaluacion ? '<i class="fas fa-check text-success"></i>' : '—' !!}</td>
                                        <td class="small">{{ $h->referencia_normativa }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-xl-5">
                            <table class="table table-sm">
                                <thead><tr><th>Desde → hacia</th><th>Rol</th><th>Acción</th></tr></thead>
                                <tbody>
                                @foreach($regla->transiciones->sortBy('estado_desde') as $t)
                                    <tr class="small">
                                        <td class="text-nowrap">{{ $t->estado_desde }} → {{ $t->estado_hacia }}</td>
                                        <td>{{ \App\Support\Roles::nombre($t->rol) }}</td>
                                        <td>{{ $t->accion }} @if($t->requiere_documento)<i class="fas fa-paperclip text-muted" title="Requiere documento"></i>@endif</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="alert alert-warning small">
        <i class="fas fa-exclamation-triangle me-1"></i>
        Los plazos cargados son referenciales y deben validarse con la dirección del programa y ESPOS antes de usarse con datos reales.
    </div>
@endsection
