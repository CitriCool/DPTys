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

        // Pendientes: plazo cercano o vencido, más las entregas que esperan revisión
        $pendientes = TesisHito::with('tesis.doctorando')
            ->whereIn('tesis_id', $ids)
            ->where('estado', '!=', 'aprobado')
            ->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))
            ->where(fn ($q) => $q->whereDate('fecha_limite', '<=', now()->addDays(TesisHito::DIAS_ALERTA))
                ->orWhere('estado', 'entregado'))
            ->orderBy('fecha_limite')
            ->limit(12)
            ->get();

        // Vista propia para doctorando/a: su tesis, su próximo paso y lo que debe responder
        $miTesis = null;
        if ($rol === Roles::DOCTORANDO) {
            $miTesis = Tesis::with(['estadoActual', 'hitos.entregas.versiones', 'observaciones' => fn ($q) => $q->whereNull('parent_id')->where('resuelta', false)->with('hito', 'autor')])
                ->where('doctorando_id', $user->id)
                ->whereHas('estadoActual', fn ($q) => $q->where('final', false))
                ->first()
                ?? Tesis::with('estadoActual', 'hitos')->where('doctorando_id', $user->id)->latest('id')->first();
        }

        $porEstado = EstadoTesis::orderBy('orden')->get()->map(fn ($e) => [
            'etiqueta' => $e->nombre,
            'color' => $e->color,
            'total' => (clone $visibles)->where('estado', $e->codigo)->count(),
        ]);

        $kpi = [
            'tesis' => $ids->count(),
            'vencidos' => TesisHito::whereIn('tesis_id', $ids)->vencidos()
                ->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))->count(),
            'en_revision' => TesisHito::whereIn('tesis_id', $ids)->enRevision()
                ->whereHas('tesis.estadoActual', fn ($q) => $q->where('final', false))->count(),
            'proximos' => TesisHito::whereIn('tesis_id', $ids)->whereNotIn('estado', ['aprobado', 'entregado'])
                ->whereBetween('fecha_limite', [now()->toDateString(), now()->addDays(TesisHito::DIAS_ALERTA)->toDateString()])->count(),
            'observaciones' => Observacion::whereIn('tesis_id', $ids)->whereNull('parent_id')->where('resuelta', false)->count(),
            'defensas' => (clone $visibles)->where('estado', 'defensa_programada')->count(),
            'retroalimentacion' => $this->diasRetroalimentacion($ids->all()),
        ];

        $evaluaciones = $rol === Roles::EVALUADOR
            ? Evaluacion::with('tesis', 'hito')->where('evaluador_id', $user->id)->latest()->get()
            : collect();

        return view('inicio', compact('rol', 'pendientes', 'porEstado', 'kpi', 'evaluaciones', 'miTesis'));
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
