<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TesisController;
use App\Http\Middleware\VerificarAcceso;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| DPTyS - Módulo de tesis
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'form'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware(['auth', VerificarAcceso::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/rol', [RolController::class, 'cambiar'])->name('rol.cambiar');

    Route::get('/', [InicioController::class, 'index'])->name('inicio');

    // Avisos
    Route::get('/avisos/{id}', [AvisoController::class, 'abrir'])->name('avisos.abrir');
    Route::post('/avisos/leer-todos', [AvisoController::class, 'leerTodos'])->name('avisos.leer-todos');

    // Expedientes de tesis
    Route::get('/tesis', [TesisController::class, 'index'])->name('tesis.index');
    Route::get('/tesis/nuevo', [TesisController::class, 'create'])->name('tesis.create');
    Route::post('/tesis', [TesisController::class, 'store'])->name('tesis.store');
    Route::get('/tesis/{tesis}', [TesisController::class, 'show'])->name('tesis.show');
    Route::get('/tesis/{tesis}/exportar', [TesisController::class, 'exportar'])->name('tesis.exportar');

    // Acciones sobre el expediente
    Route::prefix('/tesis/{tesis}')->name('expediente.')->controller(ExpedienteController::class)->group(function () {
        Route::post('/hitos/{hito}/versiones', 'subirVersion')->name('version');
        Route::post('/hitos/{hito}/revision', 'revisarHito')->name('revisar');
        Route::post('/hitos/{hito}/prorroga', 'prorroga')->name('prorroga');
        Route::post('/observaciones', 'observar')->name('observar');
        Route::post('/observaciones/{observacion}/resolver', 'resolverObservacion')->name('resolver');
        Route::post('/evaluaciones', 'asignarEvaluador')->name('evaluador');
        Route::post('/evaluaciones/{evaluacion}/dictamen', 'dictamen')->name('dictamen');
        Route::post('/estado', 'cambiarEstado')->name('estado');
        Route::post('/guia', 'cambiarGuia')->name('guia');
        Route::post('/actas', 'subirActa')->name('acta');
        Route::get('/actas/{acta}', 'descargarActa')->name('acta.descargar');
        Route::get('/decisiones/{decision}/documento', 'descargarDocumentoDecision')->name('decision.documento');
    });
    Route::get('/versiones/{version}/descargar', [ExpedienteController::class, 'descargarVersion'])->name('version.descargar');

    // Reportes y administración
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/administracion/reglas', [AdminController::class, 'reglas'])->name('admin.reglas');
    Route::get('/administracion/usuarios', [AdminController::class, 'usuarios'])->name('admin.usuarios');
    Route::post('/administracion/usuarios', [AdminController::class, 'guardarUsuario'])->name('admin.usuarios.guardar');
    Route::post('/administracion/usuarios/{user}/activo', [AdminController::class, 'activarUsuario'])->name('admin.usuarios.activo');
    Route::get('/administracion/bitacora', [AdminController::class, 'bitacora'])->name('admin.bitacora');

    // Documentación de la API (OpenAPI)
    Route::view('/api/docs', 'api-docs')->name('api.docs');
});
