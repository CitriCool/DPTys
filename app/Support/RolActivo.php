<?php

namespace App\Support;

use App\Models\User;

/**
 * Rol que la persona está ejerciendo en la sesión actual.
 */
class RolActivo
{
    public static function get(?User $user = null): ?string
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }

        $roles = $user->getRoleNames();
        $rol = session('rol_activo');

        return $roles->contains($rol) ? $rol : $roles->first();
    }

    public static function set(string $rol): void
    {
        session(['rol_activo' => $rol]);
    }

    public static function es(string ...$roles): bool
    {
        return in_array(self::get(), $roles, true);
    }
}
