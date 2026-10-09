<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Decision;
use App\Models\EntregaVersion;
use App\Models\Evaluacion;
use App\Models\Observacion;
use App\Models\Tesis;
use App\Models\TesisHito;
use App\Models\User;
use App\Services\ArchivoService;
use App\Services\AuditoriaService;
use App\Services\EstadoTesisService;
use App\Support\Avisos;
use App\Support\Permisos;
use App\Support\RolActivo;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Acciones sobre un expediente de tesis. Cada acción valida el permiso del rol en uso,
 * deja un evento en la bitácora y avisa a las personas involucradas.
 */
class ExpedienteController extends Controller
{
    private const ARCHIVO = 'required|file|max:' . ArchivoService::MAX_KB;

    private function hitoDe(Tesis $tesis, TesisHito $hito): void
    {
        abort_unless($hito->tesis_id == $tesis->id, 404);
    }

    // -----------------------------------------------------------------
    // Entregas y versiones
    // -----------------------------------------------------------------
    public function subirVersion(Request $request, Tesis $tesis, TesisHito $hito)
    {
        $this->hitoDe($tesis, $hito);
        abort_unless(Permisos::subirEntrega($tesis), 403, 'Solo el doctorando o doctoranda puede subir entregas a su expediente.');

        $request->validate([
            'archivo' => self::ARCHIVO,
            'comentario' => 'nullable|string|max:2000',
        ], [
            'archivo.required' => 'Seleccione el archivo de la entrega.',
            'archivo.max' => 'El archivo no puede superar los 50 MB.',
        ]);

        if ($hito->estado === 'aprobado') {
            return back()->withErrors(['archivo' => 'El hito ya está aprobado; no admite nuevas versiones.']);
        }

        $version = DB::transaction(function () use ($request, $tesis, $hito) {
            $entrega = $hito->entregas()->firstOrCreate(
                ['tesis_id' => $tesis->id],
                ['user_id' => auth()->id(), 'titulo' => $hito->nombre]
            );
            $numero = ($entrega->versiones()->reorder()->max('numero') ?? 0) + 1;
            $archivo = ArchivoService::guardar($request->file('archivo'), "tesis/{$tesis->id}/entregas/hito-{$hito->id}");

            $version = $entrega->versiones()->create($archivo + [
                'user_id' => auth()->id(),
                'numero' => $numero,
                'comentario' => $request->input('comentario'),
            ]);
            $hito->update(['estado' => 'entregado']);

            AuditoriaService::registrar('nueva_version', 'entrega_version', $version->id, $tesis->id, [
                'hito' => $hito->nombre, 'version' => $numero, 'sha256' => $version->sha256,
            ]);

            return $version;
        });

        Avisos::enviar(Avisos::guias($tesis), $tesis, "nueva versión ({$version->numero}) de «{$hito->nombre}».", 'fas fa-file-upload', 'info');

        return back()->with('exito', "Versión {$version->numero} de «{$hito->nombre}» subida correctamente.");
    }

    public function descargarVersion(EntregaVersion $version)
    {
        $tesis = $version->entrega->tesis;
        abort_unless(Permisos::ver($tesis), 403);

        if (RolActivo::es(Roles::EVALUADOR)) {
            abort_unless(in_array($version->id, Permisos::versionesPermitidasEvaluador($tesis)), 403, 'Solo puede descargar la versión que se le asignó.');
        }

        abort_unless(Storage::exists($version->ruta), 404);
        AuditoriaService::registrar('descarga', 'entrega_version', $version->id, $tesis->id);

        return Storage::download($version->ruta, $version->nombre_archivo, [
            'X-Content-SHA256' => $version->sha256,
            'X-Integridad' => ArchivoService::integro($version->ruta, $version->sha256) ? 'verificada' : 'ALTERADO',
        ]);
    }

    // -----------------------------------------------------------------
    // Hitos: revisión y prórroga
    // -----------------------------------------------------------------
    public function revisarHito(Request $request, Tesis $tesis, TesisHito $hito)
    {
        $this->hitoDe($tesis, $hito);
        abort_unless(Permisos::revisarHito($tesis), 403);

        $datos = $request->validate([
            'accion' => 'required|in:aprobar,observar',
            'comentario' => 'required_if:accion,observar|nullable|string|max:4000',
        ], [
            'comentario.required_if' => 'Para observar el hito debe escribir la observación.',
        ]);

        if ($hito->requiere_entrega && !$hito->entregas()->reorder()->exists()) {
            return back()->withErrors(['accion' => "«{$hito->nombre}» aún no tiene entregas para revisar."]);
        }

        DB::transaction(function () use ($datos, $tesis, $hito) {
            if ($datos['accion'] === 'aprobar') {
                $hito->update(['estado' => 'aprobado', 'fecha_cumplimiento' => now()]);
            } else {
                $hito->update(['estado' => 'observado']);
            }

            if (!empty($datos['comentario'])) {
                $tesis->observaciones()->create([
                    'tesis_hito_id' => $hito->id,
                    'entrega_version_id' => optional(optional($hito->entregas()->first())->versiones()->first())->id,
                    'user_id' => auth()->id(),
                    'rol_ejercido' => RolActivo::get(),
                    'texto' => $datos['comentario'],
                ]);
            }

            AuditoriaService::registrar($datos['accion'] === 'aprobar' ? 'hito_aprobado' : 'hito_observado', 'tesis_hito', $hito->id, $tesis->id, [
                'hito' => $hito->nombre,
            ]);
        });

        $aprobado = $datos['accion'] === 'aprobar';
        Avisos::enviar([$tesis->doctorando], $tesis,
            $aprobado ? "«{$hito->nombre}» fue aprobado." : "«{$hito->nombre}» tiene observaciones.",
            $aprobado ? 'fas fa-check-circle' : 'fas fa-comment-dots', $aprobado ? 'success' : 'warning');

        return back()->with('exito', $aprobado ? "Hito «{$hito->nombre}» aprobado." : "Observación registrada en «{$hito->nombre}».");
    }

    public function prorroga(Request $request, Tesis $tesis, TesisHito $hito)
    {
        $this->hitoDe($tesis, $hito);
        abort_unless(Permisos::decidir($tesis), 403, 'Las prórrogas las aprueba la dirección del programa.');

        $datos = $request->validate([
            'fecha_limite' => 'required|date|after:' . $hito->fecha_limite->toDateString(),
            'fundamento' => 'required|string|max:4000',
            'documento' => 'nullable|file|max:' . ArchivoService::MAX_KB,
        ], [
            'fecha_limite.after' => 'La nueva fecha debe ser posterior a la actual (' . $hito->fecha_limite->format('d/m/Y') . ').',
            'fundamento.required' => 'Indique el fundamento de la prórroga.',
        ]);

        DB::transaction(function () use ($request, $datos, $tesis, $hito) {
            $archivo = $request->hasFile('documento') ? ArchivoService::guardar($request->file('documento'), "tesis/{$tesis->id}/decisiones") : null;
            $anterior = $hito->fecha_limite->toDateString();

            $decision = Decision::create([
                'tesis_id' => $tesis->id,
                'user_id' => auth()->id(),
                'rol_ejercido' => RolActivo::get(),
                'tipo' => 'prorroga',
                'fundamento' => "«{$hito->nombre}»: plazo del $anterior al {$datos['fecha_limite']}. {$datos['fundamento']}",
                'documento_nombre' => $archivo['nombre_archivo'] ?? null,
                'documento_ruta' => $archivo['ruta'] ?? null,
                'documento_sha256' => $archivo['sha256'] ?? null,
            ]);
            $hito->update(['fecha_limite' => $datos['fecha_limite']]);

            AuditoriaService::registrar('prorroga', 'tesis_hito', $hito->id, $tesis->id, [
                'desde' => $anterior, 'hasta' => $datos['fecha_limite'], 'decision_id' => $decision->id,
            ]);
        });

        Avisos::enviar([$tesis->doctorando, ...Avisos::guias($tesis)], $tesis,
            "se aprobó prórroga de «{$hito->nombre}» hasta el " . \Carbon\Carbon::parse($datos['fecha_limite'])->format('d/m/Y') . '.',
            'fas fa-calendar-plus', 'info');

        return back()->with('exito', 'Prórroga registrada.');
    }

    // -----------------------------------------------------------------
    // Observaciones
    // -----------------------------------------------------------------
    public function observar(Request $request, Tesis $tesis)
    {
        abort_unless(Permisos::observar($tesis), 403);

        $datos = $request->validate([
            'texto' => 'required|string|max:4000',
            'parent_id' => 'nullable|integer|exists:observaciones,id',
            'tesis_hito_id' => 'nullable|integer|exists:tesis_hitos,id',
        ], ['texto.required' => 'Escriba la observación o respuesta.']);

        $padre = !empty($datos['parent_id']) ? Observacion::where('tesis_id', $tesis->id)->findOrFail($datos['parent_id']) : null;

        $observacion = $tesis->observaciones()->create([
            'parent_id' => $padre?->id,
            'tesis_hito_id' => $padre?->tesis_hito_id ?? ($datos['tesis_hito_id'] ?? null),
            'entrega_version_id' => $padre?->entrega_version_id,
            'user_id' => auth()->id(),
            'rol_ejercido' => RolActivo::get(),
            'texto' => $datos['texto'],
        ]);
        AuditoriaService::registrar($padre ? 'respuesta_observacion' : 'observacion', 'observacion', $observacion->id, $tesis->id);

        // Si escribe el doctorando avisa a los guías; si escribe otra persona, al doctorando
        $destinatarios = RolActivo::es(Roles::DOCTORANDO) ? Avisos::guias($tesis) : [$tesis->doctorando];
        if ($padre) {
            $destinatarios[] = $padre->autor;
        }
        Avisos::enviar($destinatarios, $tesis, $padre ? 'nueva respuesta a una observación.' : 'nueva observación en el expediente.', 'fas fa-comments', 'primary');

        return back()->with('exito', $padre ? 'Respuesta registrada.' : 'Observación registrada.');
    }

    public function resolverObservacion(Tesis $tesis, Observacion $observacion)
    {
        abort_unless($observacion->tesis_id == $tesis->id, 404);
        abort_unless($observacion->user_id == auth()->id() || Permisos::revisarHito($tesis), 403);

        $observacion->update(['resuelta' => true]);
        AuditoriaService::registrar('observacion_resuelta', 'observacion', $observacion->id, $tesis->id);

        return back()->with('exito', 'Observación marcada como resuelta.');
    }

    // -----------------------------------------------------------------
    // Evaluaciones
    // -----------------------------------------------------------------
    public function asignarEvaluador(Request $request, Tesis $tesis)
    {
        abort_unless(Permisos::gestionar($tesis), 403);

        $datos = $request->validate([
            'evaluador_id' => 'required|integer|exists:users,id',
            'entrega_version_id' => 'required|integer|exists:entrega_versiones,id',
            'acceso_hasta' => 'required|date|after:today',
        ], [
            'evaluador_id.required' => 'Seleccione al evaluador o evaluadora.',
            'entrega_version_id.required' => 'Seleccione la versión que se evaluará.',
            'acceso_hasta.after' => 'El acceso debe vencer en una fecha futura.',
        ]);

        $evaluador = User::role(Roles::EVALUADOR)->findOrFail($datos['evaluador_id']);
        $version = EntregaVersion::with('entrega')->findOrFail($datos['entrega_version_id']);
        abort_unless($version->entrega->tesis_id == $tesis->id, 422);

        $yaAsignada = Evaluacion::where('evaluador_id', $evaluador->id)
            ->where('entrega_version_id', $version->id)
            ->whereNull('dictamen')
            ->exists();
        if ($yaAsignada) {
            return back()->withErrors(['evaluador_id' => "{$evaluador->name} ya tiene asignada esa versión y su dictamen está pendiente."]);
        }

        $evaluacion = DB::transaction(function () use ($datos, $tesis, $evaluador, $version) {
            $evaluacion = Evaluacion::create([
                'tesis_id' => $tesis->id,
                'tesis_hito_id' => $version->entrega->tesis_hito_id,
                'entrega_version_id' => $version->id,
                'evaluador_id' => $evaluador->id,
                'asignado_por' => auth()->id(),
                'acceso_hasta' => $datos['acceso_hasta'],
            ]);

            // La cuenta del evaluador externo queda activa hasta el último acceso asignado
            if (!$evaluador->acceso_hasta || $evaluador->acceso_hasta->lt($evaluacion->acceso_hasta)) {
                $evaluador->update(['acceso_hasta' => $evaluacion->acceso_hasta]);
            }

            AuditoriaService::registrar('asignacion_evaluador', 'evaluacion', $evaluacion->id, $tesis->id, [
                'evaluador' => $evaluador->name, 'version_id' => $version->id, 'acceso_hasta' => $datos['acceso_hasta'],
            ]);
            return $evaluacion;
        });

        Avisos::enviar([$evaluador], $tesis, 'se le asignó una evaluación (acceso hasta el ' . $evaluacion->acceso_hasta->format('d/m/Y') . ').', 'fas fa-user-check', 'primary');

        return back()->with('exito', "Evaluación asignada a {$evaluador->name}.");
    }

    public function dictamen(Request $request, Tesis $tesis, Evaluacion $evaluacion)
    {
        abort_unless($evaluacion->tesis_id == $tesis->id, 404);
        abort_unless(RolActivo::es(Roles::EVALUADOR) && $evaluacion->evaluador_id == auth()->id() && $evaluacion->vigente, 403);

        if ($evaluacion->dictamen) {
            return back()->withErrors(['dictamen' => 'Esta evaluación ya tiene dictamen registrado.']);
        }

        $datos = $request->validate([
            'dictamen' => 'required|in:' . implode(',', array_keys(Evaluacion::DICTAMENES)),
            'fundamento' => 'required|string|max:8000',
        ], [
            'dictamen.required' => 'Seleccione el resultado de la evaluación.',
            'fundamento.required' => 'Escriba el fundamento de la evaluación.',
        ]);

        $evaluacion->update($datos + ['fecha_dictamen' => now()]);
        AuditoriaService::registrar('dictamen', 'evaluacion', $evaluacion->id, $tesis->id, ['dictamen' => $datos['dictamen']]);

        Avisos::enviar([...Avisos::gestion($tesis), ...Avisos::guias($tesis)], $tesis,
            'se registró el dictamen de evaluación: ' . Evaluacion::DICTAMENES[$datos['dictamen']] . '.', 'fas fa-gavel', 'primary');

        return back()->with('exito', 'Dictamen registrado. La decisión académica final corresponde a la dirección del programa.');
    }

    // -----------------------------------------------------------------
    // Decisiones
    // -----------------------------------------------------------------
    public function cambiarEstado(Request $request, Tesis $tesis)
    {
        abort_unless(Permisos::ver($tesis), 403);

        $request->validate([
            'transicion_id' => 'required|integer',
            'fundamento' => 'nullable|string|max:4000',
            'documento' => 'nullable|file|max:' . ArchivoService::MAX_KB,
        ]);

        $decision = EstadoTesisService::aplicar($tesis, (int) $request->input('transicion_id'), $request->input('fundamento'), $request->file('documento'));
        $estado = $tesis->fresh('estadoActual')->estadoActual->nombre;

        Avisos::enviar([$tesis->doctorando, ...Avisos::guias($tesis), ...Avisos::gestion($tesis)], $tesis,
            "el expediente pasó a «{$estado}».", 'fas fa-exchange-alt', 'primary');

        return back()->with('exito', "Decisión registrada: el expediente está ahora «{$estado}».");
    }

    public function cambiarGuia(Request $request, Tesis $tesis)
    {
        abort_unless(Permisos::decidir($tesis), 403, 'El cambio de guía lo resuelve la dirección del programa.');

        $datos = $request->validate([
            'tipo' => 'required|in:guia,coguia',
            'user_id' => 'nullable|integer|exists:users,id',
            'fundamento' => 'required|string|max:4000',
        ], ['fundamento.required' => 'Indique el fundamento del cambio.']);

        if ($datos['tipo'] === 'guia' && empty($datos['user_id'])) {
            return back()->withErrors(['user_id' => 'La tesis debe mantener un profesor o profesora guía.']);
        }

        DB::transaction(function () use ($datos, $tesis) {
            $actual = $tesis->responsables()->where('tipo', $datos['tipo'])->whereNull('hasta')->first();
            $actual?->update(['hasta' => now()]);

            $nuevo = !empty($datos['user_id']) ? User::findOrFail($datos['user_id']) : null;
            if ($nuevo) {
                $tesis->responsables()->create(['user_id' => $nuevo->id, 'tipo' => $datos['tipo'], 'desde' => now()]);
            }

            $etiqueta = $datos['tipo'] === 'guia' ? 'guía' : 'coguía';
            $decision = Decision::create([
                'tesis_id' => $tesis->id,
                'user_id' => auth()->id(),
                'rol_ejercido' => RolActivo::get(),
                'tipo' => 'cambio_guia',
                'fundamento' => "Cambio de $etiqueta: " . ($actual?->user->name ?? 'sin asignar') . ' → ' . ($nuevo?->name ?? 'sin asignar') . ". {$datos['fundamento']}",
            ]);
            AuditoriaService::registrar('cambio_guia', 'tesis', $tesis->id, $tesis->id, [
                'tipo' => $datos['tipo'], 'anterior' => $actual?->user_id, 'nuevo' => $nuevo?->id, 'decision_id' => $decision->id,
            ]);
        });

        Avisos::enviar([$tesis->doctorando, ...Avisos::guias($tesis)], $tesis, 'se modificó el equipo de guía de la tesis.', 'fas fa-user-friends', 'info');

        return back()->with('exito', 'Cambio de guía registrado.');
    }

    // -----------------------------------------------------------------
    // Actas y documentos de decisiones
    // -----------------------------------------------------------------
    public function subirActa(Request $request, Tesis $tesis)
    {
        abort_unless(Permisos::gestionar($tesis), 403);

        $datos = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(Acta::TIPOS)),
            'numero' => 'nullable|string|max:100',
            'fecha' => 'required|date|before_or_equal:today',
            'archivo' => self::ARCHIVO,
        ], ['archivo.required' => 'Adjunte el acta firmada.']);

        $archivo = ArchivoService::guardar($request->file('archivo'), "tesis/{$tesis->id}/actas");
        $acta = $tesis->actas()->create([
            'user_id' => auth()->id(),
            'tipo' => $datos['tipo'],
            'numero' => $datos['numero'] ?? null,
            'fecha' => $datos['fecha'],
            'nombre_archivo' => $archivo['nombre_archivo'],
            'ruta' => $archivo['ruta'],
            'sha256' => $archivo['sha256'],
        ]);
        AuditoriaService::registrar('acta', 'acta', $acta->id, $tesis->id, ['tipo' => $acta->tipo, 'sha256' => $acta->sha256]);

        return back()->with('exito', Acta::TIPOS[$acta->tipo] . ' registrada.');
    }

    public function descargarActa(Tesis $tesis, Acta $acta)
    {
        abort_unless($acta->tesis_id == $tesis->id && Permisos::ver($tesis) && !RolActivo::es(Roles::EVALUADOR), 403);
        AuditoriaService::registrar('descarga', 'acta', $acta->id, $tesis->id);

        return Storage::download($acta->ruta, $acta->nombre_archivo);
    }

    public function descargarDocumentoDecision(Tesis $tesis, Decision $decision)
    {
        abort_unless($decision->tesis_id == $tesis->id && $decision->documento_ruta && Permisos::ver($tesis) && !RolActivo::es(Roles::EVALUADOR), 403);
        AuditoriaService::registrar('descarga', 'decision', $decision->id, $tesis->id);

        return Storage::download($decision->documento_ruta, $decision->documento_nombre);
    }
}
