<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cierra la sesión si la cuenta fue desactivada o si el acceso temporal
 * (evaluadores externos) ya expiró.
 */
class VerificarAcceso
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->puedeIngresar()) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Su acceso a la plataforma expiró o fue desactivado.']);
        }

        return $next($request);
    }
}
