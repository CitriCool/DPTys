<?php

namespace App\Support;

use App\Models\Evaluacion;
use App\Models\Tesis;
use App\Models\User;

/**
 * Reglas de acceso por rol (tabla "Usuarios, responsabilidades y permisos").
 * Todas consideran el rol que la persona está ejerciendo, no todos sus roles.
 */
class Permisos
{
    private static function usuario(): User
    {
        return auth()->user();
    }

    public static function ver(Tesis $tesis): bool
    {
        return Tesis::visiblesPara(self::usuario(), RolActivo::get())->whereKey($tesis->id)->exists();
    }

    /** Dirección o secretaría del programa de la tesis */
    public static function gestionar(Tesis $tesis): bool
    {
        $rol = RolActivo::get();
        return in_array($rol, [Roles::DIRECCION, Roles::SECRETARIA], true)
            && in_array($tesis->programa_id, self::usuario()->programaIds($rol));
    }

    /** Crear expedientes en algún programa */
    public static function crearExpedientes(): bool
    {
        $rol = RolActivo::get();
        return in_array($rol, [Roles::DIRECCION, Roles::SECRETARIA], true) && self::usuario()->programaIds($rol);
    }

    /** Decisiones académicas: solo dirección del programa */
    public static function decidir(Tesis $tesis): bool
    {
        return RolActivo::es(Roles::DIRECCION) && in_array($tesis->programa_id, self::usuario()->programaIds(Roles::DIRECCION));
    }

    /** El doctorando sube sus propias entregas */
    public static function subirEntrega(Tesis $tesis): bool
    {
        return RolActivo::es(Roles::DOCTORANDO) && $tesis->doctorando_id == self::usuario()->id && !$tesis->estadoActual->final;
    }

    /** Aprobar u observar un hito: guía de la tesis o dirección */
    public static function revisarHito(Tesis $tesis): bool
    {
        return (RolActivo::es(Roles::GUIA) && $tesis->esGuia(self::usuario())) || self::decidir($tesis);
    }

    /** Comentar: participantes académicos que pueden ver la tesis */
    public static function observar(Tesis $tesis): bool
    {
        return !RolActivo::es(Roles::ADMINISTRADOR, Roles::TECNICO) && self::ver($tesis);
    }

    /** Evaluación vigente asignada a la persona en esta tesis (evaluadores externos) */
    public static function evaluacionVigente(Tesis $tesis): ?Evaluacion
    {
        if (!RolActivo::es(Roles::EVALUADOR)) {
            return null;
        }
        return $tesis->evaluaciones()
            ->where('evaluador_id', self::usuario()->id)
            ->whereDate('acceso_hasta', '>=', now()->toDateString())
            ->latest()
            ->first();
    }

    /** IDs de versiones que un evaluador externo puede descargar */
    public static function versionesPermitidasEvaluador(Tesis $tesis): array
    {
        return $tesis->evaluaciones()
            ->where('evaluador_id', self::usuario()->id)
            ->whereDate('acceso_hasta', '>=', now()->toDateString())
            ->pluck('entrega_version_id')
            ->filter()
            ->all();
    }
}
