<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso sobre un expediente: aparece en la campana y se envía por correo.
 */
class AvisoExpediente extends Notification
{
    use Queueable;

    public function __construct(
        public string $mensaje,
        public string $url,
        public string $icono = 'fas fa-info-circle',
        public string $color = 'primary',
        public array $extra = []
    ) {
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('DPTyS: ' . $this->mensaje)
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->mensaje)
            ->action('Ver en la plataforma', $this->url)
            ->line('Este aviso es operativo y no constituye un dictamen académico.');
    }

    public function toArray($notifiable)
    {
        return array_merge([
            'mensaje' => $this->mensaje,
            'url' => $this->url,
            'icono' => $this->icono,
            'color' => $this->color,
        ], $this->extra);
    }
}
