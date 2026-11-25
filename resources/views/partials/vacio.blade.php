{{-- Estado vacío ilustrado: @include('partials.vacio', ['dibujo' => 'carpeta|lupa|conversacion|listo|documento', 'titulo' => '', 'texto' => '', 'accion' => url, 'accionTexto' => '']) --}}
<div class="estado-vacio {{ $compacto ?? false ? 'compacto' : '' }}">
    <svg viewBox="0 0 120 90" class="estado-vacio-dibujo" aria-hidden="true">
        <ellipse cx="60" cy="82" rx="42" ry="5" class="sombra"/>
        @switch($dibujo ?? 'carpeta')
            @case('lupa')
                <rect x="22" y="14" width="54" height="64" rx="6" class="papel"/>
                <line x1="32" y1="28" x2="66" y2="28" class="linea"/><line x1="32" y1="38" x2="60" y2="38" class="linea"/><line x1="32" y1="48" x2="54" y2="48" class="linea"/>
                <circle cx="78" cy="52" r="15" class="lente"/><line x1="89" y1="63" x2="100" y2="74" class="mango"/>
                @break
            @case('conversacion')
                <rect x="16" y="16" width="62" height="40" rx="10" class="papel"/><path d="M30 56 l-6 14 l18 -14z" class="papel"/>
                <rect x="50" y="34" width="54" height="34" rx="10" class="acento"/><path d="M90 68 l6 10 l-16 -10z" class="acento"/>
                <line x1="28" y1="30" x2="64" y2="30" class="linea"/><line x1="28" y1="40" x2="54" y2="40" class="linea"/>
                @break
            @case('listo')
                <circle cx="60" cy="44" r="30" class="acento"/><path d="M46 45 l10 10 l19 -21" class="check"/>
                @break
            @case('documento')
                <rect x="34" y="10" width="52" height="66" rx="6" class="papel"/><path d="M70 10 v14 h16" class="pliegue"/>
                <line x1="44" y1="36" x2="76" y2="36" class="linea"/><line x1="44" y1="46" x2="76" y2="46" class="linea"/><line x1="44" y1="56" x2="64" y2="56" class="linea"/>
                @break
            @default
                <path d="M18 26 h28 l8 8 h48 v40 a4 4 0 0 1 -4 4 h-76 a4 4 0 0 1 -4 -4z" class="papel"/>
                <path d="M18 38 h84" class="linea"/>
                <circle cx="88" cy="22" r="10" class="acento"/><line x1="88" y1="17" x2="88" y2="27" class="mas"/><line x1="83" y1="22" x2="93" y2="22" class="mas"/>
        @endswitch
    </svg>
    <p class="estado-vacio-titulo">{{ $titulo }}</p>
    @if(!empty($texto))<p class="estado-vacio-texto">{{ $texto }}</p>@endif
    @if(!empty($accion))<a href="{{ $accion }}" class="btn btn-outline-primary btn-sm mt-1">{{ $accionTexto ?? 'Continuar' }}</a>@endif
</div>
