<?php

use App\Http\Controllers\Api\TesisApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API REST v1 (solo lectura) - documentada en public/docs/openapi.yaml
|--------------------------------------------------------------------------
| Autenticación: Authorization: Bearer <token>  (crear con: php artisan dptys:token correo)
| Rol de consulta: cabecera X-Rol (opcional)
*/

Route::prefix('v1')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    Route::get('/tesis', [TesisApiController::class, 'index']);
    Route::get('/tesis/{tesis}', [TesisApiController::class, 'show']);
    Route::get('/tesis/{tesis}/eventos', [TesisApiController::class, 'eventos']);
});
