<?php

namespace App\Http\Controllers;

use App\Models\Cohorte;
use App\Models\EstadoTesis;
use App\Models\Evaluacion;
use App\Models\Programa;
use App\Models\Tesis;
use App\Models\TesisHito;
use App\Models\User;
use App\Services\EstadoTesisService;
use App\Services\ExpedienteService;
use App\Support\Avisos;
use App\Support\Permisos;
use App\Support\RolActivo;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TesisController extends Controller
{
    /**
     * Listado con filtros por programa, cohorte, estado, responsable y vencimiento.
     */
    public function index(Request $request)
    {
        $consulta = $this->consultaFiltrada($request);

        if ($request->query('formato') === 'csv') {
            return $this->exportarCsv($consulta->get());
        }

        $tesis = $consulta->paginate(20)->withQueryString();

        $visibles = Tesis::visiblesPara(auth()->user(), RolActivo::get());
        $programas = Programa::whereIn('id', (clone $visibles)->select('programa_id'))->orderBy('nombre')->get();
        $cohortes = Cohorte::whereIn('id', (clone $visibles)->select('cohorte_id'))->orderByDesc('anio')->get();
        $estados = EstadoTesis::orderBy('orden')->get();
        $guias = User::whereIn('id', \DB::table('tesis_responsables')->whereNull('hasta')
            ->whereIn('tesis_id', (clone $visibles)->select('id'))->select('user_id'))->orderBy('name')->get();

        return view('tesis.index', compact('tesis', 'programas', 'cohortes', 'estados', 'guias'));
    }

    private function consultaFiltrada(Request $request): Builder
    {
        $consulta = Tesis::visiblesPara(auth()->user(), RolActivo::get())
            ->with(['programa', 'cohorte', 'doctorando', 'estadoActual', 'responsablesVigentes'])
            ->withCount(['hitos as hitos_vencidos' => fn ($q) => $q->where('estado', '!=', 'aprobado')
                ->whereDate('fecha_limite', '<', now()->toDateString())]);

        $consulta->when($request->query('programa'), fn ($q, $v) => $q->where('programa_id', $v))
            ->when($request->query('cohorte'), fn ($q, $v) => $q->where('cohorte_id', $v))
            ->when($request->query('estado'), fn ($q, $v) => $q->where('estado', $v))
            ->when($request->query('guia'), fn ($q, $v) => $q->whereHas('responsables', fn ($r) => $r->where('user_id', $v)->whereNull('hasta')))
            ->when($request->query('buscar'), fn ($q, $v) => $q->where(fn ($w) => $w
                ->where('titulo', 'like', "%$v%")->orWhere('codigo', 'like', "%$v%")
                ->orWhereHas('doctorando', fn ($d) => $d->where('name', 'like', "%$v%"))));

        switch ($request->query('vencimiento')) {
            case 'vencidos':
                $consulta->whereHas('hitos', fn ($q) => $q->where('estado', '!=', 'aprobado')->whereDate('fecha_limite', '<', now()->toDateString()));
                break;
            case 'proximos':
                $consulta->whereHas('hitos', fn ($q) => $q->where('estado', '!=', 'aprobado')
                    ->whereBetween('fecha_limite', [now()->toDateString(), now()->addDays(TesisHito::DIAS_ALERTA)->toDateString()]));
                break;
        }

        return $consulta->orderBy('codigo');
    }

    private function exportarCsv($tesis)
    {
        $nombre = 'reporte-tesis-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($tesis) {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, ['Código', 'Título', 'Doctorando/a', 'Programa', 'Cohorte', 'Estado', 'Guía', 'Inscripción', 'Hitos vencidos'], ';');
            foreach ($tesis as $t) {
                fputcsv($salida, [
                    $t->codigo, $t->titulo, $t->doctorando->name, $t->programa->sigla, $t->cohorte->anio,
                    $t->estadoActual->nombre, $t->responsablesVigentes->pluck('user.name')->join(', '),
                    $t->fecha_inscripcion->format('d/m/Y'), $t->hitos_vencidos,
                ], ';');
            }
            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        abort_unless(Permisos::crearExpedientes(), 403);

        $programaIds = auth()->user()->programaIds(RolActivo::get());
        $cohortes = Cohorte::with('programa', 'reglaVersion')->whereIn('programa_id', $programaIds)->orderByDesc('anio')->get();
        $doctorandos = User::role(Roles::DOCTORANDO)->whereHas('programas', fn ($q) => $q->whereIn('programas.id', $programaIds))
            ->whereDoesntHave('tesisPropias', fn ($q) => $q->whereHas('estadoActual', fn ($e) => $e->where('final', false)))
            ->orderBy('name')->get();
        $guias = User::role(Roles::GUIA)->whereHas('programas', fn ($q) => $q->whereIn('programas.id', $programaIds))->orderBy('name')->get();

        return view('tesis.create', compact('cohortes', 'doctorandos', 'guias'));
    }

    public function store(Request $request)
    {
        abort_unless(Permisos::crearExpedientes(), 403);

        $datos = $request->validate([
            'cohorte_id' => 'required|integer|exists:cohortes,id',
            'doctorando_id' => 'required|integer|exists:users,id',
            'titulo' => 'required|string|max:500',
            'resumen' => 'nullable|string|max:4000',
            'fecha_inscripcion' => 'required|date|before_or_equal:today',
            'resolucion' => 'nullable|string|max:255',
            'referencia_externa' => 'nullable|string|max:255',
            'guia_id' => 'required|integer|exists:users,id',
            'coguia_id' => 'nullable|integer|exists:users,id|different:guia_id',
        ], [
            'cohorte_id.required' => 'Seleccione la cohorte.',
            'doctorando_id.required' => 'Seleccione al doctorando o doctoranda.',
            'titulo.required' => 'Ingrese el título o tema de la tesis.',
            'fecha_inscripcion.required' => 'Ingrese la fecha de inscripción.',
            'fecha_inscripcion.before_or_equal' => 'La fecha de inscripción no puede ser futura.',
            'guia_id.required' => 'Seleccione al profesor o profesora guía.',
            'coguia_id.different' => 'La coguía debe ser una persona distinta a la guía.',
        ]);

        $cohorte = Cohorte::findOrFail($datos['cohorte_id']);
        abort_unless(in_array($cohorte->programa_id, auth()->user()->programaIds(RolActivo::get())), 403);

        $tesis = ExpedienteService::crear($datos);

        Avisos::enviar(
            [$tesis->doctorando, ...Avisos::guias($tesis)],
            $tesis, 'se abrió el expediente de tesis y se generó su ruta de hitos.', 'fas fa-folder-plus', 'success'
        );

        return redirect()->route('tesis.show', $tesis)->with('exito', "Expediente {$tesis->codigo} creado con {$tesis->hitos()->reorder()->count()} hitos.");
    }

    /**
     * Expediente completo. Los evaluadores externos solo ven lo que se les asignó.
     */
    public function show(Tesis $tesis)
    {
        abort_unless(Permisos::ver($tesis), 403, 'No tiene acceso a este expediente.');

        $tesis->load([
            'programa.institucion', 'cohorte', 'reglaVersion', 'doctorando', 'estadoActual', 'responsablesVigentes',
            'hitos.entregas.versiones.autor', 'observaciones.autor', 'observaciones.respuestas.autor',
            'observaciones.hito', 'observaciones.version', 'evaluaciones.evaluador', 'evaluaciones.hito',
            'decisiones.autor', 'actas', 'eventos.autor',
        ]);

        $esEvaluador = RolActivo::es(Roles::EVALUADOR);
        $versionesEvaluador = $esEvaluador ? Permisos::versionesPermitidasEvaluador($tesis) : [];

        $permisos = [
            'gestionar' => Permisos::gestionar($tesis),
            'decidir' => Permisos::decidir($tesis),
            'subir' => Permisos::subirEntrega($tesis),
            'revisar' => Permisos::revisarHito($tesis),
            'observar' => Permisos::observar($tesis),
            'auditoria' => !$esEvaluador && !RolActivo::es(Roles::DOCTORANDO),
            'exportar' => $this->puedeExportar($tesis),
        ];

        $transiciones = EstadoTesisService::disponibles($tesis);
        $evaluacionVigente = Permisos::evaluacionVigente($tesis);
        $evaluadores = $permisos['gestionar'] ? User::role(Roles::EVALUADOR)->orderBy('name')->get() : collect();
        $guiasDisponibles = $permisos['decidir']
            ? User::role(Roles::GUIA)->whereHas('programas', fn ($q) => $q->where('programas.id', $tesis->programa_id))->orderBy('name')->get()
            : collect();

        return view('tesis.show', compact(
            'tesis', 'permisos', 'transiciones', 'esEvaluador', 'versionesEvaluador',
            'evaluacionVigente', 'evaluadores', 'guiasDisponibles'
        ));
    }

    /** Exportan el paquete documental: gestión del programa, administración y guías de la tesis */
    private function puedeExportar(Tesis $tesis): bool
    {
        return Permisos::gestionar($tesis)
            || RolActivo::es(Roles::ADMINISTRADOR)
            || (RolActivo::es(Roles::GUIA) && $tesis->esGuia(auth()->user()));
    }

    public function exportar(Tesis $tesis)
    {
        abort_unless($this->puedeExportar($tesis), 403);

        return response()->download(ExpedienteService::exportarZip($tesis))->deleteFileAfterSend();
    }
}
