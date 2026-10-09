<!DOCTYPE html>
<html lang="es-cl">
<head>
    <meta charset="UTF-8">
    <title>Expediente {{ $tesis->codigo }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; margin: 32px; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 15px; border-bottom: 2px solid #194979; padding-bottom: 4px; margin-top: 28px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f1f4f8; }
        .muted { color: #666; }
        .hash { font-family: Consolas, monospace; font-size: 11px; word-break: break-all; }
    </style>
</head>
<body>
    <h1>Expediente de tesis {{ $tesis->codigo }}</h1>
    <p class="muted">Exportado el {{ now()->format('d/m/Y H:i') }}. Este documento resume el registro de la plataforma y no reemplaza actas ni resoluciones oficiales.</p>

    <table>
        <tr><th style="width: 25%">Título</th><td>{{ $tesis->titulo }}</td></tr>
        <tr><th>Doctorando/a</th><td>{{ $tesis->doctorando->name }}</td></tr>
        <tr><th>Programa</th><td>{{ $tesis->programa->nombre }} — {{ $tesis->cohorte->nombre }}</td></tr>
        <tr><th>Estado</th><td>{{ $tesis->estadoActual->nombre }}</td></tr>
        <tr><th>Inscripción</th><td>{{ $tesis->fecha_inscripcion->format('d/m/Y') }} {{ $tesis->resolucion ? '· ' . $tesis->resolucion : '' }}</td></tr>
        <tr><th>Equipo de guía</th><td>@foreach($tesis->responsablesVigentes as $r){{ ucfirst($r->tipo) }}: {{ $r->user->name }}<br>@endforeach</td></tr>
    </table>

    <h2>Hitos</h2>
    <table>
        <tr><th>#</th><th>Hito</th><th>Fecha límite</th><th>Estado</th><th>Cumplimiento</th></tr>
        @foreach($tesis->hitos as $h)
            <tr><td>{{ $h->orden }}</td><td>{{ $h->nombre }}</td><td>{{ $h->fecha_limite->format('d/m/Y') }}</td><td>{{ $h->estado_nombre }}</td><td>{{ $h->fecha_cumplimiento?->format('d/m/Y') }}</td></tr>
        @endforeach
    </table>

    <h2>Entregas y versiones</h2>
    <table>
        <tr><th>Hito</th><th>Versión</th><th>Archivo</th><th>Fecha</th><th>SHA-256</th></tr>
        @foreach($tesis->hitos as $h)
            @foreach($h->entregas as $e)
                @foreach($e->versiones as $v)
                    <tr><td>{{ $h->nombre }}</td><td>v{{ $v->numero }}</td><td>{{ $v->nombre_archivo }}</td><td>{{ $v->created_at->format('d/m/Y H:i') }}</td><td class="hash">{{ $v->sha256 }}</td></tr>
                @endforeach
            @endforeach
        @endforeach
    </table>

    <h2>Evaluaciones</h2>
    <table>
        <tr><th>Evaluador/a</th><th>Dictamen</th><th>Fecha</th><th>Fundamento</th></tr>
        @forelse($tesis->evaluaciones as $ev)
            <tr><td>{{ $ev->evaluador->name }}</td><td>{{ $ev->dictamen ? \App\Models\Evaluacion::DICTAMENES[$ev->dictamen] : 'Pendiente' }}</td><td>{{ $ev->fecha_dictamen?->format('d/m/Y') }}</td><td>{{ $ev->fundamento }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted">Sin evaluaciones.</td></tr>
        @endforelse
    </table>

    <h2>Decisiones</h2>
    <table>
        <tr><th>Fecha</th><th>Tipo</th><th>Fundamento</th><th>Responsable</th></tr>
        @forelse($tesis->decisiones as $d)
            <tr><td>{{ $d->created_at->format('d/m/Y H:i') }}</td><td>{{ $d->tipo_nombre }}</td><td>{{ $d->fundamento }}</td><td>{{ $d->autor->name }} ({{ \App\Support\Roles::nombre($d->rol_ejercido) }})</td></tr>
        @empty
            <tr><td colspan="4" class="muted">Sin decisiones.</td></tr>
        @endforelse
    </table>

    <h2>Actas</h2>
    <table>
        <tr><th>Tipo</th><th>Número</th><th>Fecha</th><th>SHA-256</th></tr>
        @forelse($tesis->actas as $a)
            <tr><td>{{ \App\Models\Acta::TIPOS[$a->tipo] ?? $a->tipo }}</td><td>{{ $a->numero }}</td><td>{{ $a->fecha->format('d/m/Y') }}</td><td class="hash">{{ $a->sha256 }}</td></tr>
        @empty
            <tr><td colspan="4" class="muted">Sin actas.</td></tr>
        @endforelse
    </table>

    <p class="muted" style="margin-top: 24px">La integridad de los archivos incluidos puede verificarse con SHA256SUMS.txt (por ejemplo: <code>sha256sum -c SHA256SUMS.txt</code>).</p>
</body>
</html>
