<?php

namespace App\Http\Controllers;

use App\Services\AuditoriaService;
use App\Support\RolActivo;
use Illuminate\Http\Request;

class RolController extends Controller
{
    /** Cambia el rol que la persona ejerce en esta sesión */
    public function cambiar(Request $request)
    {
        $rol = $request->input('rol');
        abort_unless($request->user()->hasRole($rol), 403, 'No tiene ese rol asignado.');

        RolActivo::set($rol);
        AuditoriaService::registrar('cambio_rol', 'user', $request->user()->id, null, ['rol' => $rol]);

        return redirect()->route('inicio');
    }
}
