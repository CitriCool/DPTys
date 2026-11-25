{{-- Anillo de avance SVG: @include('partials.anillo', ['valor' => 0..100, 'tam' => 64, 'color' => '#198754']) --}}
@php $r = 15.9155; $v = max(0, min(100, (int) round($valor))); @endphp
<svg viewBox="0 0 36 36" width="{{ $tam ?? 64 }}" height="{{ $tam ?? 64 }}" class="anillo" role="img" aria-label="{{ $v }}% de avance">
    <circle cx="18" cy="18" r="{{ $r }}" class="anillo-fondo"/>
    <circle cx="18" cy="18" r="{{ $r }}" class="anillo-valor" style="stroke: {{ $color ?? 'var(--dp-primario)' }}" stroke-dasharray="{{ $v }} {{ 100 - $v }}" transform="rotate(-90 18 18)"/>
    <text x="18" y="20.5" class="anillo-texto">{{ $v }}%</text>
</svg>
