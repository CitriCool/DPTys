<?php

namespace App\Console\Commands;

use App\Models\TesisHito;
use App\Notifications\AvisoExpediente;
use App\Support\Avisos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Recordatorios de plazos: avisa hitos próximos a vencer y vencidos.
 * Se ejecuta a diario (ver App\Console\Kernel) y no repite un aviso el mismo día.
 */
class EnviarAlertas extends Command
{
    protected $signature = 'dptys:alertas {--dias=' . TesisHito::DIAS_ALERTA . ' : Días de anticipación para avisar}';

    protected $description = 'Envía avisos de hitos próximos a vencer y vencidos';

    public function handle()
    {
        $limite = now()->addDays((int) $this->option('dias'))->toDateString();

        $hitos = TesisHito::with('tesis.doctorando')
            ->where('estado', '!=', 'aprobado')
            ->whereDate('fecha_limite', '<=', $limite)
            ->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))
            ->get();

        $enviados = 0;
        foreach ($hitos as $hito) {
            if ($this->yaAvisadoHoy($hito)) {
                continue;
            }

            $tesis = $hito->tesis;
            $vencido = $hito->semaforo === 'vencido';
            $dias = now()->startOfDay()->diffInDays($hito->fecha_limite, false);
            $mensaje = $vencido
                ? "«{$hito->nombre}» está vencido desde el {$hito->fecha_limite->format('d/m/Y')}."
                : "«{$hito->nombre}» vence en $dias día(s) ({$hito->fecha_limite->format('d/m/Y')}).";

            // Vencidos también se informan a dirección y secretaría del programa
            $destinatarios = collect([$tesis->doctorando])->merge(Avisos::guias($tesis));
            if ($vencido) {
                $destinatarios = $destinatarios->merge(Avisos::gestion($tesis));
            }

            Notification::send($destinatarios->filter()->unique('id'), new AvisoExpediente(
                "{$tesis->codigo}: $mensaje",
                route('tesis.show', $tesis),
                $vencido ? 'fas fa-exclamation-circle' : 'fas fa-clock',
                $vencido ? 'danger' : 'warning',
                ['tesis_id' => $tesis->id, 'hito_id' => $hito->id, 'alerta' => true]
            ));
            $enviados++;
        }

        $this->info("Alertas enviadas: $enviados de {$hitos->count()} hitos con plazo cercano o vencido.");
        return self::SUCCESS;
    }

    private function yaAvisadoHoy(TesisHito $hito): bool
    {
        return DB::table('notifications')
            ->where('type', AvisoExpediente::class)
            ->whereDate('created_at', now()->toDateString())
            ->where('data', 'like', '%"hito_id":' . $hito->id . ',%')
            ->exists();
    }
}
