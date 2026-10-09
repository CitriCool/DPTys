<?php

namespace App\Http\Controllers;

use App\Models\EstadoTesis;
use App\Models\Evaluacion;
use App\Models\Observacion;
use App\Models\Tesis;
use App\Models\TesisHito;
use App\Support\RolActivo;
use App\Support\Roles;
use Illuminate\Support\Facades\DB;

class InicioController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $rol = RolActivo::get();

        $visibles = Tesis::visiblesPara($user, $rol);
        $ids = (clone $visibles)->pluck('id');

        $pendientes = TesisHito::with('tesis.doctorando')
            ->whereIn('tesis_id', $ids)
            ->where('estado', '!=', 'aprobado')
            ->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))
            ->whereDate('fecha_limite', '<=', now()->addDays(TesisHito::DIAS_ALERTA))
            ->orderBy('fecha_limite')
            ->limit(12)
            ->get();

        $porEstado = EstadoTesis::orderBy('orden')->get()->map(fn ($e) => [
            'etiqueta' => $e->nombre,
            'color' => $e->color,
            'total' => (clone $visibles)->where('estado', $e->codigo)->count(),
        ]);

        $kpi = [
            'tesis' => $ids->count(),
            'vencidos' => TesisHito::whereIn('tesis_id', $ids)->where('estado', '!=', 'aprobado')
                ->whereDate('fecha_limite', '<', now()->toDateString())
                ->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))->count(),
            'proximos' => TesisHito::whereIn('tesis_id', $ids)->where('estado', '!=', 'aprobado')
                ->whereBetween('fecha_limite', [now()->toDateString(), now()->addDays(TesisHito::DIAS_ALERTA)->toDateString()])->count(),
            'observaciones' => Observacion::whereIn('tesis_id', $ids)->whereNull('parent_id')->where('resuelta', false)->count(),
            'defensas' => (clone $visibles)->where('estado', 'defensa_programada')->count(),
            'retroalimentacion' => $this->diasRetroalimentacion($ids->all()),
        ];

        $evaluaciones = $rol === Roles::EVALUADOR
            ? Evaluacion::with('tesis', 'hito')->where('evaluador_id', $user->id)->latest()->get()
            : collect();

        return view('inicio', compact('rol', 'pendientes', 'porEstado', 'kpi', 'evaluaciones'));
    }

    /**
     * Días promedio entre que se sube una versión y su primera observación.
     */
    private function diasRetroalimentacion(array $tesisIds): ?float
    {
        if (!$tesisIds) {
            return null;
        }

        // Cálculo en PHP para no depender de funciones de fecha de un motor en particular
        $horas = DB::table('entrega_versiones as v')
            ->join('entregas as e', 'e.id', '=', 'v.entrega_id')
            ->whereIn('e.tesis_id', $tesisIds)
            ->select('v.created_at as subida')
            ->selectSub('SELECT MIN(o.created_at) FROM observaciones o WHERE o.entrega_version_id = v.id', 'primera')
            ->get()
            ->filter(fn ($f) => $f->primera)
            ->map(fn ($f) => \Illuminate\Support\Carbon::parse($f->subida)->diffInHours(\Illuminate\Support\Carbon::parse($f->primera)));

        return $horas->isNotEmpty() ? round($horas->avg() / 24, 1) : null;
    }
}
