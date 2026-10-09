<?php

namespace App\Services;

use App\Models\Decision;
use App\Models\Tesis;
use App\Models\Transicion;
use App\Support\RolActivo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Motor de estados: las transiciones permitidas vienen de la versión de reglas
 * de la tesis (por programa y cohorte), no del código.
 */
class EstadoTesisService
{
    /** Transiciones que el rol activo puede aplicar sobre la tesis */
    public static function disponibles(Tesis $tesis, ?string $rol = null): Collection
    {
        $rol = $rol ?? RolActivo::get();

        return Transicion::with('estadoHacia')
            ->where('regla_version_id', $tesis->regla_version_id)
            ->where('estado_desde', $tesis->estado)
            ->where('rol', $rol)
            ->get();
    }

    public static function aplicar(Tesis $tesis, int $transicionId, ?string $fundamento, ?UploadedFile $documento = null): Decision
    {
        $transicion = self::disponibles($tesis)->firstWhere('id', $transicionId);

        if (!$transicion) {
            throw ValidationException::withMessages([
                'transicion' => 'Esta acción no está permitida para su rol en el estado actual de la tesis.',
            ]);
        }
        if ($transicion->requiere_fundamento && !trim((string) $fundamento)) {
            throw ValidationException::withMessages(['fundamento' => 'Debe indicar el fundamento de la decisión.']);
        }
        if ($transicion->requiere_documento && !$documento) {
            throw ValidationException::withMessages(['documento' => 'Esta decisión requiere adjuntar el documento que la respalda.']);
        }

        return DB::transaction(function () use ($tesis, $transicion, $fundamento, $documento) {
            $archivo = $documento ? ArchivoService::guardar($documento, "tesis/{$tesis->id}/decisiones") : null;
            $desde = $tesis->estado;

            $decision = Decision::create([
                'tesis_id' => $tesis->id,
                'user_id' => auth()->id(),
                'rol_ejercido' => RolActivo::get(),
                'tipo' => 'cambio_estado',
                'estado_desde' => $desde,
                'estado_hacia' => $transicion->estado_hacia,
                'fundamento' => trim((string) $fundamento) ?: $transicion->accion,
                'documento_nombre' => $archivo['nombre_archivo'] ?? null,
                'documento_ruta' => $archivo['ruta'] ?? null,
                'documento_sha256' => $archivo['sha256'] ?? null,
            ]);

            $tesis->update(['estado' => $transicion->estado_hacia]);

            AuditoriaService::registrar('cambio_estado', 'tesis', $tesis->id, $tesis->id, [
                'desde' => $desde,
                'hacia' => $transicion->estado_hacia,
                'accion' => $transicion->accion,
                'decision_id' => $decision->id,
                'documento_sha256' => $archivo['sha256'] ?? null,
            ]);

            return $decision;
        });
    }
}
