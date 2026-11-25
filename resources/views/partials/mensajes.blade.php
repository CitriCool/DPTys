{{-- Errores de validación y mensajes de resultado (reutilizado de SGQA) --}}
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong><i class="fas fa-exclamation-triangle me-1"></i>No se pudo completar la acción:</strong>
        <ul class="mb-0 mt-1">
            @foreach (array_unique($errors->all()) as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

@if (session('exito'))
    {{-- Se muestra como aviso tipo toast (ver app.js) --}}
    <template id="toastInicial" data-mensaje="{{ session('exito') }}"></template>
@endif
