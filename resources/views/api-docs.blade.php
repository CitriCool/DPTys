@extends('layouts.app')

@section('titulo', 'API REST (OpenAPI)')

@push('estilos')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui.css">
@endpush

@section('contenido')
    <div class="alert alert-info small">
        <i class="fas fa-key me-1"></i>
        La API es de solo lectura y usa tokens personales. Para crear uno:
        <code>php artisan dptys:token correo@institucion.cl</code>
        (con Docker: <code>docker compose exec app php artisan dptys:token correo@institucion.cl</code>).
        Cada respuesta respeta los permisos del rol indicado en la cabecera <code>X-Rol</code>.
    </div>
    <div class="card"><div class="card-body" id="swagger-ui"></div></div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script>
        SwaggerUIBundle({ url: @json(asset_v('docs/openapi.yaml')), dom_id: '#swagger-ui', deepLinking: true });
    </script>
@endpush
