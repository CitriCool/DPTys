<?php

namespace App\Services;

use App\Models\EventoAuditoria;
use App\Support\RolActivo;
use Illuminate\Support\Facades\DB;

/**
 * Bitácora inalterable con cadena de hash: cada evento incluye el hash del anterior.
 */
class AuditoriaService
{
    public static function registrar(string $accion, string $entidadTipo, ?int $entidadId = null, ?int $tesisId = null, array $datos = []): EventoAuditoria
    {
        return DB::transaction(function () use ($accion, $entidadTipo, $entidadId, $tesisId, $datos) {
            $anterior = EventoAuditoria::query()->lockForUpdate()->orderByDesc('id')->first();

            $evento = new EventoAuditoria([
                'user_id' => auth()->id(),
                'rol_ejercido' => RolActivo::get(),
                'accion' => $accion,
                'entidad_tipo' => $entidadTipo,
                'entidad_id' => $entidadId,
                'tesis_id' => $tesisId,
                'datos' => $datos,
                'ip' => request()->ip(),
                'hash_anterior' => $anterior?->hash,
                'created_at' => now()->startOfSecond(),
            ]);
            $evento->hash = self::calcularHash($evento);
            $evento->save();

            return $evento;
        });
    }

    public static function calcularHash(EventoAuditoria $evento): string
    {
        // Los identificadores se normalizan a entero: según el driver llegan como texto o número
        $id = fn ($valor) => $valor === null ? null : (int) $valor;

        return hash('sha256', json_encode([
            $evento->hash_anterior,
            $id($evento->user_id),
            $evento->rol_ejercido,
            $evento->accion,
            $evento->entidad_tipo,
            $id($evento->entidad_id),
            $id($evento->tesis_id),
            self::normalizar($evento->datos),
            $evento->created_at->format('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE));
    }

    /** Valores escalares de "datos" como texto, para que el hash no dependa de su tipo */
    private static function normalizar($datos)
    {
        if (is_array($datos)) {
            return array_map([self::class, 'normalizar'], $datos);
        }
        return $datos === null || is_bool($datos) ? $datos : (string) $datos;
    }

    /**
     * Recorre la cadena completa y devuelve el primer evento alterado (o null si está íntegra).
     */
    public static function verificarCadena(): ?EventoAuditoria
    {
        $hashAnterior = null;
        foreach (EventoAuditoria::orderBy('id')->cursor() as $evento) {
            if ($evento->hash_anterior !== $hashAnterior || $evento->hash !== self::calcularHash($evento)) {
                return $evento;
            }
            $hashAnterior = $evento->hash;
        }
        return null;
    }
}
