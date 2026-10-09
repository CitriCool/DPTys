<?php

namespace Database\Seeders;

use App\Models\Cohorte;
use App\Models\Evaluacion;
use App\Models\Programa;
use App\Models\Tesis;
use App\Models\User;
use App\Services\AuditoriaService;
use App\Services\EstadoTesisService;
use App\Services\ExpedienteService;
use App\Support\RolActivo;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Datos de prueba FICTICIOS para el piloto. Contraseña de todas las cuentas: dptys2026
 */
class DemoSeeder extends Seeder
{
    const CLAVE = 'dptys2026';

    public function run()
    {
        $dptys = Programa::where('sigla', 'DPTYS')->firstOrFail();
        $dca = Programa::where('sigla', 'DCA')->firstOrFail();

        // Personas ---------------------------------------------------------
        $admin = $this->usuario('Administración Plataforma', 'admin@dptys.local', [Roles::ADMINISTRADOR]);
        $tecnico = $this->usuario('Soporte Técnico', 'soporte@dptys.local', [Roles::TECNICO]);
        // Una persona con dos roles: dirige el programa y también es guía
        $direccion = $this->usuario('Dra. Carolina Fuentes', 'direccion@dptys.local', [Roles::DIRECCION, Roles::GUIA], [$dptys]);
        $secretaria = $this->usuario('Marcela Rojas', 'secretaria@dptys.local', [Roles::SECRETARIA], [$dptys]);
        $guia = $this->usuario('Dr. Andrés Soto', 'guia@dptys.local', [Roles::GUIA], [$dptys]);
        $coguia = $this->usuario('Dra. Paula Vera', 'coguia@dptys.local', [Roles::GUIA], [$dptys]);
        $evaluador = $this->usuario('Dr. Roberto Lagos', 'evaluador@externo.local', [Roles::EVALUADOR], [], now()->addDays(60));

        $camila = $this->usuario('Camila Herrera', 'camila@dptys.local', [Roles::DOCTORANDO], [$dptys]);
        $matias = $this->usuario('Matías González', 'matias@dptys.local', [Roles::DOCTORANDO], [$dptys]);
        $javiera = $this->usuario('Javiera Pino', 'javiera@dptys.local', [Roles::DOCTORANDO], [$dptys]);
        $ignacio = $this->usuario('Ignacio Muñoz', 'ignacio@dptys.local', [Roles::DOCTORANDO], [$dptys]);

        // Segunda institución (aislada de la primera)
        $secretariaDemo = $this->usuario('Secretaría Universidad Demo', 'secretaria@udemo.local', [Roles::SECRETARIA], [$dca]);
        $direccionDemo = $this->usuario('Dirección Universidad Demo', 'direccion@udemo.local', [Roles::DIRECCION], [$dca]);
        $guiaDemo = $this->usuario('Dr. Tomás Riquelme', 'guia@udemo.local', [Roles::GUIA], [$dca]);
        $lucas = $this->usuario('Lucas Ibáñez', 'lucas@udemo.local', [Roles::DOCTORANDO], [$dca]);

        if (Tesis::exists()) {
            return; // los expedientes de ejemplo solo se crean una vez
        }

        $cohorte = fn (Programa $p, int $anio) => Cohorte::where('programa_id', $p->id)->where('anio', $anio)->value('id');

        // Expedientes ------------------------------------------------------
        $this->como($secretaria, Roles::SECRETARIA);

        $t1 = ExpedienteService::crear([
            'cohorte_id' => $cohorte($dptys, 2024), 'doctorando_id' => $camila->id,
            'titulo' => 'Gobernanza del borde costero y planificación territorial en comunas de La Araucanía',
            'resumen' => 'Analiza instrumentos de planificación y conflictos de uso en el borde costero.',
            'fecha_inscripcion' => '2024-04-01', 'resolucion' => 'Res. Programa N° 12/2024',
            'guia_id' => $guia->id, 'coguia_id' => $coguia->id,
        ]);
        $t2 = ExpedienteService::crear([
            'cohorte_id' => $cohorte($dptys, 2025), 'doctorando_id' => $matias->id,
            'titulo' => 'Indicadores de sustentabilidad urbana para ciudades intermedias del sur de Chile',
            'fecha_inscripcion' => '2025-05-05', 'resolucion' => 'Res. Programa N° 08/2025',
            'guia_id' => $direccion->id,
        ]);
        $t3 = ExpedienteService::crear([
            'cohorte_id' => $cohorte($dptys, 2024), 'doctorando_id' => $javiera->id,
            'titulo' => 'Riesgo hídrico y ordenamiento territorial en cuencas mediterráneas',
            'fecha_inscripcion' => '2024-01-15', 'resolucion' => 'Res. Programa N° 03/2024',
            'guia_id' => $guia->id,
        ]);
        $t4 = ExpedienteService::crear([
            'cohorte_id' => $cohorte($dptys, 2026), 'doctorando_id' => $ignacio->id,
            'titulo' => 'Movilidad cotidiana y accesibilidad en territorios rurales',
            'fecha_inscripcion' => now()->subDays(20)->toDateString(),
            'guia_id' => $coguia->id,
        ]);

        $this->como($secretariaDemo, Roles::SECRETARIA);
        $t5 = ExpedienteService::crear([
            'cohorte_id' => $cohorte($dca, 2025), 'doctorando_id' => $lucas->id,
            'titulo' => 'Servicios ecosistémicos en humedales urbanos',
            'fecha_inscripcion' => '2025-03-10', 'guia_id' => $guiaDemo->id,
        ]);

        // Decisiones de la dirección ---------------------------------------
        $this->como($direccion, Roles::DIRECCION);
        foreach ([$t1, $t2, $t3] as $tesis) {
            $this->transicion($tesis, 'en_desarrollo', 'Inscripción validada por el comité del programa.');
        }
        $this->como($direccionDemo, Roles::DIRECCION);
        $this->transicion($t5, 'en_desarrollo', 'Propuesta aprobada.');

        // Avance de Camila: tres hitos aprobados, Tesis II entregada con observaciones
        $this->avance($t1, $camila, $guia, ['SUF', 'INS', 'T1'], 'T2');

        // Matías: suficiencia aprobada, inscripción entregada y Tesis I vencida sin entrega
        $this->avance($t2, $matias, $direccion, ['SUF'], 'INS');

        // Javiera: todos los informes aprobados y tesis final en evaluación externa
        $this->avance($t3, $javiera, $guia, ['SUF', 'INS', 'T1', 'T2', 'T3'], 'TF');
        $this->como($direccion, Roles::DIRECCION);
        $this->transicion($t3, 'en_evaluacion', 'Documento final completo; se envía a evaluación externa.');
        $hitoFinal = $t3->hitos()->where('nombre', 'Tesis final')->first();
        $evaluacion = Evaluacion::create([
            'tesis_id' => $t3->id,
            'tesis_hito_id' => $hitoFinal->id,
            'entrega_version_id' => $hitoFinal->entregas()->first()->versiones()->first()->id,
            'evaluador_id' => $evaluador->id,
            'asignado_por' => $direccion->id,
            'acceso_hasta' => now()->addDays(45),
        ]);
        AuditoriaService::registrar('asignacion_evaluador', 'evaluacion', $evaluacion->id, $t3->id, ['evaluador' => $evaluador->name]);

        Auth::logout();
    }

    private function usuario(string $nombre, string $email, array $roles, array $programas = [], ?Carbon $accesoHasta = null): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $nombre,
            'password' => Hash::make(self::CLAVE),
            'acceso_hasta' => $accesoHasta,
        ]);
        $user->syncRoles($roles);

        foreach ($programas as $programa) {
            foreach ($roles as $rol) {
                DB::table('programa_user')->updateOrInsert(
                    ['programa_id' => $programa->id, 'user_id' => $user->id, 'rol' => $rol],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
        return $user;
    }

    /** Actúa como una persona y rol (para que la auditoría registre quién hizo cada cosa) */
    private function como(User $user, string $rol): void
    {
        Auth::setUser($user);
        RolActivo::set($rol);
    }

    private function transicion(Tesis $tesis, string $hacia, string $fundamento): void
    {
        $tesis->refresh();
        $transicion = EstadoTesisService::disponibles($tesis)->firstWhere('estado_hacia', $hacia);
        EstadoTesisService::aplicar($tesis, $transicion->id, $fundamento);
    }

    /**
     * Simula el avance: hitos aprobados con su entrega y un hito en curso
     * entregado (dos versiones) con observación del guía y respuesta.
     */
    private function avance(Tesis $tesis, User $doctorando, User $guia, array $aprobados, string $enCurso): void
    {
        $hitos = $tesis->hitos()->with('tesis')->get()->keyBy(fn ($h) => \App\Models\HitoPlantilla::find($h->hito_plantilla_id)->codigo);

        foreach ($aprobados as $codigo) {
            $hito = $hitos[$codigo];
            $this->como($doctorando, Roles::DOCTORANDO);
            $this->entrega($hito, $doctorando, 1, $hito->fecha_limite->copy()->subDays(10));
            $this->como($guia, Roles::GUIA);
            $hito->update(['estado' => 'aprobado', 'fecha_cumplimiento' => $hito->fecha_limite->copy()->subDays(5)]);
            AuditoriaService::registrar('hito_aprobado', 'tesis_hito', $hito->id, $tesis->id, ['hito' => $hito->nombre]);
        }

        $hito = $hitos[$enCurso];
        $this->como($doctorando, Roles::DOCTORANDO);
        $this->entrega($hito, $doctorando, 2, now()->subDays(12));
        $this->como($guia, Roles::GUIA);
        $version = $hito->entregas()->first()->versiones()->first();
        $observacion = $tesis->observaciones()->create([
            'tesis_hito_id' => $hito->id,
            'entrega_version_id' => $version->id,
            'user_id' => $guia->id,
            'rol_ejercido' => Roles::GUIA,
            'texto' => 'Revisar la coherencia entre la pregunta de investigación y el diseño metodológico del capítulo 3.',
        ]);
        AuditoriaService::registrar('observacion', 'observacion', $observacion->id, $tesis->id, ['hito' => $hito->nombre]);
        $hito->update(['estado' => 'observado']);

        $this->como($doctorando, Roles::DOCTORANDO);
        $tesis->observaciones()->create([
            'tesis_hito_id' => $hito->id,
            'parent_id' => $observacion->id,
            'user_id' => $doctorando->id,
            'rol_ejercido' => Roles::DOCTORANDO,
            'texto' => 'Gracias, ajustaré el diseño metodológico en la próxima versión.',
        ]);
    }

    private function entrega($hito, User $autor, int $versiones, Carbon $fecha): void
    {
        $entrega = $hito->entregas()->create([
            'tesis_id' => $hito->tesis_id,
            'user_id' => $autor->id,
            'titulo' => $hito->nombre,
        ]);

        for ($n = 1; $n <= $versiones; $n++) {
            $contenido = "Documento de ejemplo\nTesis: {$hito->tesis->codigo}\nHito: {$hito->nombre}\nVersión: $n\n";
            $ruta = "tesis/{$hito->tesis_id}/entregas/" . str($hito->nombre)->slug() . "-v$n.txt";
            Storage::put($ruta, $contenido);

            $version = $entrega->versiones()->create([
                'user_id' => $autor->id,
                'numero' => $n,
                'nombre_archivo' => str($hito->nombre)->slug() . "-v$n.txt",
                'ruta' => $ruta,
                'tamano' => strlen($contenido),
                'mime' => 'text/plain',
                'sha256' => hash('sha256', $contenido),
                'comentario' => $n > 1 ? 'Versión corregida según observaciones.' : null,
                'created_at' => $fecha->copy()->addDays($n - 1),
            ]);
            AuditoriaService::registrar('nueva_version', 'entrega_version', $version->id, $hito->tesis_id, ['hito' => $hito->nombre, 'version' => $n, 'sha256' => $version->sha256]);
        }

        $hito->update(['estado' => 'entregado']);
    }
}
