<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\EstadoTesis;
use App\Models\Observacion;
use App\Models\Tesis;
use App\Models\TesisHito;
use App\Support\RolActivo;
use App\Support\Roles;

/**
 * Indicadores iniciales de la propuesta: tesis por estado y cohorte, hitos vencidos,
 * observaciones abiertas, defensas programadas y completitud documental.
 */
class ReporteController extends Controller
{
    public function index()
    {
        abort_unless(RolActivo::es(...Roles::GESTION), 403);

        $visibles = Tesis::visiblesPara(auth()->user(), RolActivo::get());
        $ids = (clone $visibles)->pluck('id');
        $estados = EstadoTesis::orderBy('orden')->get();

        // Matriz cohorte x estado
        $cohortes = Cohorte::with('programa')->whereIn('id', (clone $visibles)->select('cohorte_id'))->orderBy('anio')->get();
        $matriz = $cohortes->map(fn ($c) => [
            'cohorte' => "{$c->programa->sigla} {$c->anio}",
            'estados' => $estados->mapWithKeys(fn ($e) => [
                $e->codigo => (clone $visibles)->where('cohorte_id', $c->id)->where('estado', $e->codigo)->count(),
            ]),
        ]);

        // Hitos por semáforo (solo tesis en curso)
        $hitos = TesisHito::whereIn('tesis_id', $ids)->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))->get();
        $semaforo = $hitos->countBy(fn ($h) => $h->semaforo);

        // Completitud documental: hitos vencidos que requieren entrega y están aprobados
        $exigibles = $hitos->where('requiere_entrega', true)->filter(fn ($h) => $h->fecha_limite->isPast());
        $completitud = $exigibles->count()
            ? round($exigibles->where('estado', 'aprobado')->count() * 100 / $exigibles->count())
            : null;

        $indicadores = [
            'observaciones_abiertas' => Observacion::whereIn('tesis_id', $ids)->whereNull('parent_id')->where('resuelta', false)->count(),
            'defensas' => (clone $visibles)->where('estado', 'defensa_programada')->count(),
            'pendientes_cierre' => (clone $visibles)->where('estado', 'correcciones_finales')->count(),
            'completitud' => $completitud,
        ];

        return view('reportes.index', compact('estados', 'matriz', 'semaforo', 'indicadores'));
    }
}
