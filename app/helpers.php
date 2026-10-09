<?php

if (!function_exists('asset_v')) {
    /**
     * URL de un archivo público con su fecha de modificación como versión,
     * para que el navegador descargue la versión nueva después de cada cambio.
     */
    function asset_v(string $path): string
    {
        $archivo = public_path($path);
        $version = is_file($archivo) ? filemtime($archivo) : null;

        return asset($path) . ($version ? '?v=' . $version : '');
    }
}
