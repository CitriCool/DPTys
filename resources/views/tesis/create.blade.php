@extends('layouts.app')

@section('titulo', 'Nuevo expediente de tesis')

@section('contenido')
    <div class="row">
        <div class="col-xl-8">
            <form method="POST" action="{{ route('tesis.store') }}" class="card" novalidate>
                @csrf
                <div class="card-header"><h3 class="card-title">Apertura e inscripción</h3></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cohorte_id" class="form-label">Programa y cohorte <span class="text-danger">*</span></label>
                            <select id="cohorte_id" name="cohorte_id" class="form-select @error('cohorte_id') is-invalid @enderror" required>
                                <option value="">Seleccione…</option>
                                @foreach($cohortes as $c)
                                    <option value="{{ $c->id }}" data-regla="{{ $c->reglaVersion->nombre }} (v{{ $c->reglaVersion->version }})" @selected(old('cohorte_id') == $c->id)>
                                        {{ $c->programa->sigla }} — {{ $c->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text" id="reglaAplicable">Las reglas y hitos se toman de la cohorte seleccionada.</div>
                            @error('cohorte_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="doctorando_id" class="form-label">Doctorando/a <span class="text-danger">*</span></label>
                            <select id="doctorando_id" name="doctorando_id" class="form-select @error('doctorando_id') is-invalid @enderror" required>
                                <option value="">Seleccione…</option>
                                @foreach($doctorandos as $d)<option value="{{ $d->id }}" @selected(old('doctorando_id') == $d->id)>{{ $d->name }}</option>@endforeach
                            </select>
                            <div class="form-text">Solo personas del programa sin otro expediente en curso.</div>
                            @error('doctorando_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="titulo" class="form-label">Título o tema de la tesis <span class="text-danger">*</span></label>
                            <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" maxlength="500" class="form-control @error('titulo') is-invalid @enderror" required>
                            @error('titulo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label for="resumen" class="form-label">Resumen</label>
                            <textarea id="resumen" name="resumen" rows="3" maxlength="4000" class="form-control">{{ old('resumen') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="guia_id" class="form-label">Profesor/a guía <span class="text-danger">*</span></label>
                            <select id="guia_id" name="guia_id" class="form-select @error('guia_id') is-invalid @enderror" required>
                                <option value="">Seleccione…</option>
                                @foreach($guias as $g)<option value="{{ $g->id }}" @selected(old('guia_id') == $g->id)>{{ $g->name }}</option>@endforeach
                            </select>
                            @error('guia_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="coguia_id" class="form-label">Profesor/a coguía</label>
                            <select id="coguia_id" name="coguia_id" class="form-select @error('coguia_id') is-invalid @enderror">
                                <option value="">Sin coguía</option>
                                @foreach($guias as $g)<option value="{{ $g->id }}" @selected(old('coguia_id') == $g->id)>{{ $g->name }}</option>@endforeach
                            </select>
                            @error('coguia_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="fecha_inscripcion" class="form-label">Fecha de inscripción <span class="text-danger">*</span></label>
                            <input type="date" id="fecha_inscripcion" name="fecha_inscripcion" value="{{ old('fecha_inscripcion', now()->toDateString()) }}" max="{{ now()->toDateString() }}" class="form-control @error('fecha_inscripcion') is-invalid @enderror" required>
                            @error('fecha_inscripcion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="resolucion" class="form-label">Resolución</label>
                            <input type="text" id="resolucion" name="resolucion" value="{{ old('resolucion') }}" class="form-control" placeholder="Ej.: Res. Programa N° 01/2026">
                        </div>
                        <div class="col-md-4">
                            <label for="referencia_externa" class="form-label">Referencia de matrícula</label>
                            <input type="text" id="referencia_externa" name="referencia_externa" value="{{ old('referencia_externa') }}" class="form-control" placeholder="ID en el sistema oficial">
                            <div class="form-text">Enlaza el registro oficial sin duplicarlo.</div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex gap-2">
                    <button class="btn btn-primary"><i class="fas fa-folder-plus me-1"></i>Abrir expediente</button>
                    <a href="{{ route('tesis.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Qué ocurre al abrir el expediente</h3></div>
                <div class="card-body small">
                    <ol class="ps-3 mb-0">
                        <li>Se asigna un código correlativo (programa-año-número).</li>
                        <li>Se copian los hitos de la versión de reglas vigente de la cohorte, con sus plazos calculados desde la inscripción.</li>
                        <li>Se registra el equipo de guía.</li>
                        <li>El expediente queda «En inscripción» hasta que la dirección del programa lo valide.</li>
                        <li>Todo queda en la bitácora de auditoría y se avisa al doctorando/a y a la guía.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('cohorte_id').addEventListener('change', function () {
        const regla = this.selectedOptions[0]?.dataset.regla;
        document.getElementById('reglaAplicable').textContent = regla
            ? 'Reglas aplicables: ' + regla
            : 'Las reglas y hitos se toman de la cohorte seleccionada.';
    });
</script>
@endpush
