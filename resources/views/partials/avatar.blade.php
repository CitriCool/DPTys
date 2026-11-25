{{-- Avatar con iniciales: @include('partials.avatar', ['nombre' => $u->name, 'tam' => 'sm|md|lg']) --}}
@php
    $limpio = trim(preg_replace('/^(Dra?\.|Prof\.|Sr\.|Sra\.)\s+/u', '', $nombre ?? '?'));
    $partes = preg_split('/\s+/u', $limpio);
    $iniciales = mb_strtoupper(mb_substr($partes[0] ?? '?', 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : ''));
    $paleta = ['#2563eb', '#7c3aed', '#db2777', '#ea580c', '#059669', '#0891b2', '#4f46e5', '#b45309'];
    $colorAvatar = $paleta[crc32($limpio) % count($paleta)];
@endphp
<span class="avatar avatar-{{ $tam ?? 'sm' }}" style="--color-avatar: {{ $colorAvatar }}" title="{{ $nombre }}" aria-hidden="true">{{ $iniciales }}</span>
