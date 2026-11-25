{{-- Etiqueta de estado de la tesis: color + ícono --}}
@php
    $iconosEstado = [
        'inscripcion' => 'fas fa-file-signature', 'en_desarrollo' => 'fas fa-pencil-alt', 'en_evaluacion' => 'fas fa-search',
        'defensa_programada' => 'fas fa-calendar-check', 'correcciones_finales' => 'fas fa-edit', 'cerrada' => 'fas fa-graduation-cap',
        'suspendida' => 'fas fa-pause-circle', 'retirada' => 'fas fa-times-circle',
    ];
@endphp
<span class="etiqueta-estado" style="--color-estado: {{ $estado->color }}"><i class="{{ $iconosEstado[$estado->codigo] ?? 'fas fa-circle' }}" aria-hidden="true"></i>{{ $estado->nombre }}</span>
