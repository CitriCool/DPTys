<?php

namespace App\Console\Commands;

use App\Models\EventoAuditoria;
use App\Services\AuditoriaService;
use Illuminate\Console\Command;

class VerificarBitacora extends Command
{
    protected $signature = 'dptys:verificar-bitacora';

    protected $description = 'Verifica que la cadena de hash de la bitácora de auditoría esté íntegra';

    public function handle()
    {
        $alterado = AuditoriaService::verificarCadena();

        if ($alterado) {
            $this->error("La cadena se rompe en el evento #{$alterado->id} ({$alterado->accion}, {$alterado->created_at}).");
            return self::FAILURE;
        }

        $this->info('Bitácora íntegra: ' . EventoAuditoria::count() . ' eventos verificados.');
        return self::SUCCESS;
    }
}
