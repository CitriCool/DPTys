{{-- Avatar + nombre (+ detalle opcional) --}}
<span class="persona">
    @include('partials.avatar', ['nombre' => $nombre, 'tam' => $tam ?? 'sm'])
    <span class="persona-texto">
        <span class="persona-nombre">{{ $nombre }}</span>
        @if(!empty($detalle))<small class="d-block text-muted">{{ $detalle }}</small>@endif
    </span>
</span>
