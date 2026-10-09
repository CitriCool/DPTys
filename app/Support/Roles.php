<?php

namespace App\Support;

/**
 * Roles de la plataforma (tabla "Usuarios, responsabilidades y permisos" de la propuesta).
 * Una persona puede tener varios roles; en cada sesión ejerce uno (rol activo),
 * y ese rol queda registrado en cada decisión y evento de auditoría.
 */
class Roles
{
    const ADMINISTRADOR = 'administrador';
    const DIRECCION = 'direccion_programa';
    const SECRETARIA = 'secretaria';
    const GUIA = 'guia';
    const DOCTORANDO = 'doctorando';
    const EVALUADOR = 'evaluador_externo';
    const TECNICO = 'tecnico';

    const NOMBRES = [
        self::ADMINISTRADOR => 'Administrador/a de la plataforma',
        self::DIRECCION => 'Comité y dirección de programa',
        self::SECRETARIA => 'Coordinación y secretaría',
        self::GUIA => 'Profesor/a guía o coguía',
        self::DOCTORANDO => 'Doctorando/a',
        self::EVALUADOR => 'Evaluador/a externo/a',
        self::TECNICO => 'DIRINF / Soporte técnico',
    ];

    // Roles que gestionan expedientes de un programa
    const GESTION = [self::ADMINISTRADOR, self::DIRECCION, self::SECRETARIA];

    public static function nombre(?string $rol): string
    {
        return self::NOMBRES[$rol] ?? (string) $rol;
    }
}
