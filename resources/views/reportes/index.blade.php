@extends('layouts.app')

@section('titulo', 'Reportes')

@section('acciones')
    <a href="{{ route('tesis.index', ['formato' => 'csv']) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-csv me-1"></i>Listado completo (CSV)</a>
@endsection

@section('contenido')
    <div class="row kpi">
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon bg-primary"><i class="fas fa-comments"></i></span>
                <div class="info-box-content"><span class="info-box-text">Observaciones abiertas</span><span class="info-box-number">{{ $indicadores['observaciones_abiertas'] }}</span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon bg-secondary"><i class="fas fa-user-graduate"></i></span>
                <div class="info-box-content"><span class="info-box-text">Defensas programadas</span><span class="info-box-number">{{ $indicadores['defensas'] }}</span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-edit"></i></span>
                <div class="info-box-content"><span class="info-box-text">Pendientes de cierre</span><span class="info-box-number">{{ $indicadores['pendientes_cierre'] }}</span></div></div>
        </div>
        <div class="col-md-3 col-6">
            <div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-file-signature"></i></span>
                <div class="info-box-content"><span class="info-box-text">Completitud documental</span>
                    <span class="info-box-number">{{ $indicadores['completitud'] !== null ? $indicadores['completitud'] . '%' : '—' }}</span>
                    <small class="text-muted">hitos vencidos aprobados</small></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Tesis por estado y cohorte</h3></div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="text-start">Cohorte</th>
                                @foreach($estados as $e)<th class="small"><i class="fas fa-circle" style="color: {{ $e->color }}"></i> {{ $e->nombre }}</th>@endforeach
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($matriz as $fila)
                            <tr>
                                <td class="text-start">{{ $fila['cohorte'] }}</td>
                                @foreach($estados as $e)
                                    <td>{{ $fila['estados'][$e->codigo] ?: '·' }}</td>
                                @endforeach
                                <td class="fw-bold">{{ $fila['estados']->sum() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $estados->count() + 2 }}" class="text-muted">Sin datos.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Hitos de tesis en curso</h3></div>
                <div class="card-body">
                    @php $etiquetas = ['vencido' => 'Vencidos', 'proximo' => 'Vencen en 30 días', 'en_plazo' => 'En plazo', 'cumplido' => 'Cumplidos']; @endphp
                    <table class="table table-sm mb-0">
                        @foreach($etiquetas as $clave => $etiqueta)
                            <tr>
                                <td><span class="semaforo semaforo-{{ $clave }}">{{ $etiqueta }}</span></td>
                                <td class="text-end fw-bold">{{ $semaforo[$clave] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </table>
                    <p class="small text-muted mt-3 mb-0">Las alertas son operativas: un hito vencido no equivale a un dictamen académico.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
