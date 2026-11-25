{{-- Línea de tiempo horizontal de hitos: @include('partials.linea-tiempo', ['hitos' => $tesis->hitos, 'enlazarA' => $tesis]) --}}
@php
    $total = $hitos->count();
    $cumplidos = $hitos->where('estado', 'aprobado')->count();
    $actual = $hitos->where('estado', '!=', 'aprobado')->sortBy('fecha_limite')->first();
@endphp
<div class="linea-tiempo" role="list" aria-label="Línea de tiempo de hitos: {{ $cumplidos }} de {{ $total }} cumplidos">
    <div class="linea-tiempo-pista" aria-hidden="true">
        <div class="linea-tiempo-avance" style="width: {{ $total > 1 ? round(max($cumplidos - 0.5, 0) * 100 / ($total - 1)) : ($cumplidos ? 100 : 0) }}%"></div>
    </div>
    @foreach($hitos as $h)
        @php $esActual = $actual && $actual->id === $h->id; @endphp
        <a role="listitem" href="{{ !empty($enlazarA) ? route('tesis.show', $enlazarA) . '#hito-' . $h->id : '#hito-' . $h->id }}"
           @if(empty($enlazarA)) data-pestana="tab-hitos" @endif
           class="linea-tiempo-paso paso-{{ $h->semaforo }} {{ $esActual ? 'actual' : '' }} {{ empty($enlazarA) ? 'ir-pestana' : '' }}"
           title="{{ $h->nombre }} · {{ $h->fecha_limite->format('d/m/Y') }} · {{ $h->estado_nombre }}">
            <span class="linea-tiempo-punto">
                @if($h->semaforo === 'cumplido')<i class="fas fa-check"></i>
                @elseif($h->semaforo === 'vencido')<i class="fas fa-exclamation"></i>
                @elseif($h->semaforo === 'en_revision')<i class="fas fa-hourglass-half"></i>
                @else{{ $h->orden }}@endif
            </span>
            <span class="linea-tiempo-nombre">{{ $h->nombre }}</span>
            <span class="linea-tiempo-fecha">{{ $h->fecha_limite->format('m/Y') }}</span>
        </a>
    @endforeach
</div>
