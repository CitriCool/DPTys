<?php

namespace App\Http\Controllers;

class AvisoController extends Controller
{
    /** Abre el aviso (lo marca como leído) y lleva al expediente */
    public function abrir(string $id)
    {
        $aviso = auth()->user()->notifications()->findOrFail($id);
        $aviso->markAsRead();

        return redirect($aviso->data['url'] ?? route('inicio'));
    }

    public function leerTodos()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back();
    }
}
