<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TransformsRequest;

/**
 * Convierte a UTF-8 los textos que llegan en otra codificación (p. ej. Windows-1252
 * desde clientes o integraciones antiguas). Sin esto, SQL Server rechaza el texto
 * al guardarlo y la petición termina en error 500.
 */
class NormalizarUtf8 extends TransformsRequest
{
    protected function transform($key, $value)
    {
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            return mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
        }

        return $value;
    }
}
