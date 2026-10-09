<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Console\Command;

class CrearTokenApi extends Command
{
    protected $signature = 'dptys:token {email : Correo de la cuenta} {--nombre=integracion : Nombre del token}';

    protected $description = 'Crea un token de acceso a la API REST para una cuenta';

    public function handle()
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No existe una cuenta con ese correo.');
            return self::FAILURE;
        }

        $token = $user->createToken($this->option('nombre'), ['lectura']);
        AuditoriaService::registrar('token_api', 'user', $user->id, null, ['nombre' => $this->option('nombre')]);

        $this->info("Token para {$user->name} (guárdelo, no se volverá a mostrar):");
        $this->line($token->plainTextToken);
        return self::SUCCESS;
    }
}
