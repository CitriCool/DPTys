<?php

namespace App\Support;

use App\Models\Tesis;
use App\Models\User;
use App\Notifications\AvisoExpediente;
use Illuminate\Support\Facades\Notification;

class Avisos
{
    /**
     * Envía un aviso sobre un expediente a las personas indicadas (sin incluir a quien actúa).
     */
    public static function enviar(iterable $destinatarios, Tesis $tesis, string $mensaje, string $icono = 'fas fa-info-circle', string $color = 'primary'): void
    {
        $usuarios = collect($destinatarios)->filter()->unique('id')->reject(fn (User $u) => $u->id == auth()->id());

        if ($usuarios->isNotEmpty()) {
            Notification::send($usuarios, new AvisoExpediente(
                "{$tesis->codigo}: $mensaje",
                route('tesis.show', $tesis),
                $icono,
                $color,
                ['tesis_id' => $tesis->id]
            ));
        }
    }

    /** Guía y coguía vigentes de la tesis */
    public static function guias(Tesis $tesis)
    {
        return $tesis->responsablesVigentes()->get()->pluck('user');
    }

    /** Dirección y secretaría del programa */
    public static function gestion(Tesis $tesis)
    {
        return User::whereIn('id', \DB::table('programa_user')
            ->where('programa_id', $tesis->programa_id)
            ->whereIn('rol', [Roles::DIRECCION, Roles::SECRETARIA])
            ->pluck('user_id'))->get();
    }
}
