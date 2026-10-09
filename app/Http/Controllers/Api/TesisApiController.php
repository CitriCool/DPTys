<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tesis;
use App\Support\Permisos;
use App\Support\RolActivo;
use Illuminate\Http\Request;

/**
 * API REST de solo lectura (v1). Autenticación con token Sanctum.
 * El rol con que se consulta se indica en la cabecera X-Rol (por defecto, el primero de la cuenta).
 */
class TesisApiController extends Controller
{
    private function rol(Request $request): string
    {
        $rol = $request->header('X-Rol') ?: $request->user()->getRoleNames()->first();
        abort_unless($rol && $request->user()->hasRole($rol), 403, 'La cuenta no tiene el rol indicado en X-Rol.');
        abort_unless($request->user()->puedeIngresar(), 403, 'La cuenta está desactivada o su acceso expiró.');
        RolActivo::set($rol);

        return $rol;
    }

    public function index(Request $request)
    {
        $rol = $this->rol($request);

        $tesis = Tesis::visiblesPara($request->user(), $rol)
            ->with(['programa', 'cohorte', 'doctorando', 'estadoActual'])
            ->when($request->query('estado'), fn ($q, $v) => $q->where('estado', $v))
            ->when($request->query('programa'), fn ($q, $v) => $q->whereHas('programa', fn ($p) => $p->where('sigla', $v)))
            ->when($request->query('cohorte'), fn ($q, $v) => $q->whereHas('cohorte', fn ($c) => $c->where('anio', $v)))
            ->orderBy('codigo')
            ->paginate(min(max((int) $request->query('por_pagina', 25), 1), 100));

        return response()->json([
            'data' => $tesis->getCollection()->map(fn ($t) => $this->resumen($t))->values(),
            'meta' => ['pagina' => $tesis->currentPage(), 'total' => $tesis->total(), 'por_pagina' => $tesis->perPage()],
        ]);
    }

    public function show(Request $request, Tesis $tesis)
    {
        $this->rol($request);
        abort_unless(Permisos::ver($tesis), 403);

        $tesis->load(['programa', 'cohorte', 'doctorando', 'estadoActual', 'responsablesVigentes', 'hitos']);

        return response()->json(['data' => $this->resumen($tesis) + [
            'resumen' => $tesis->resumen,
            'resolucion' => $tesis->resolucion,
            'referencia_externa' => $tesis->referencia_externa,
            'responsables' => $tesis->responsablesVigentes->map(fn ($r) => [
                'tipo' => $r->tipo, 'nombre' => $r->user->name, 'desde' => $r->desde->toDateString(),
            ])->values(),
            'hitos' => $tesis->hitos->map(fn ($h) => [
                'id' => $h->id,
                'nombre' => $h->nombre,
                'orden' => $h->orden,
                'fecha_limite' => $h->fecha_limite->toDateString(),
                'estado' => $h->estado,
                'semaforo' => $h->semaforo,
                'fecha_cumplimiento' => $h->fecha_cumplimiento?->toIso8601String(),
                'referencia_normativa' => $h->referencia_normativa,
            ])->values(),
        ]]);
    }

    public function eventos(Request $request, Tesis $tesis)
    {
        $this->rol($request);
        abort_unless(Permisos::ver($tesis), 403);

        return response()->json(['data' => $tesis->eventos()->with('autor')->limit(200)->get()->map(fn ($e) => [
            'fecha' => $e->created_at->toIso8601String(),
            'accion' => $e->accion,
            'usuario' => $e->autor?->name,
            'rol' => $e->rol_ejercido,
            'entidad' => $e->entidad_tipo,
            'entidad_id' => $e->entidad_id,
            'hash' => $e->hash,
        ])->values()]);
    }

    private function resumen(Tesis $t): array
    {
        return [
            'id' => $t->id,
            'codigo' => $t->codigo,
            'titulo' => $t->titulo,
            'estado' => ['codigo' => $t->estado, 'nombre' => $t->estadoActual->nombre],
            'programa' => $t->programa->sigla,
            'cohorte' => $t->cohorte->anio,
            'doctorando' => $t->doctorando->name,
            'fecha_inscripcion' => $t->fecha_inscripcion->toDateString(),
        ];
    }
}
