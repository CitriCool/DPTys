<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Guarda archivos del expediente con su huella SHA-256 para verificar su integridad.
 */
class ArchivoService
{
    const MAX_KB = 51200; // 50 MB

    public static function guardar(UploadedFile $archivo, string $carpeta): array
    {
        $sha256 = hash_file('sha256', $archivo->getRealPath());

        return [
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta' => $archivo->store($carpeta),
            'tamano' => $archivo->getSize(),
            'mime' => $archivo->getClientMimeType(),
            'sha256' => $sha256,
        ];
    }

    /** Comprueba que el archivo guardado no fue alterado desde que se subió */
    public static function integro(string $ruta, string $sha256): bool
    {
        return Storage::exists($ruta) && hash('sha256', Storage::get($ruta)) === $sha256;
    }
}
