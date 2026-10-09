<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use App\Support\RolActivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function form()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required' => 'Ingrese su correo.',
            'email.email' => 'El correo no es válido.',
            'password.required' => 'Ingrese su contraseña.',
        ]);

        if (!Auth::attempt($credenciales, $request->boolean('recordar'))) {
            throw ValidationException::withMessages(['email' => 'Correo o contraseña incorrectos.']);
        }

        if (!Auth::user()->puedeIngresar()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Su cuenta está desactivada o su acceso temporal expiró.']);
        }

        $request->session()->regenerate();
        RolActivo::set(Auth::user()->getRoleNames()->first());
        AuditoriaService::registrar('inicio_sesion', 'user', Auth::id());

        return redirect()->intended(route('inicio'));
    }

    public function logout(Request $request)
    {
        AuditoriaService::registrar('cierre_sesion', 'user', Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
