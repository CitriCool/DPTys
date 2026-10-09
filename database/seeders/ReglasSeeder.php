<?php

namespace Database\Seeders;

use App\Models\Cohorte;
use App\Models\EstadoTesis;
use App\Models\Institucion;
use App\Models\Programa;
use App\Models\ReglaVersion;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Estructura académica y reglas versionadas.
 *
 * Los plazos de los hitos son REFERENCIALES: deben validarse con la dirección del
 * programa y ESPOS antes de usarse en producción (ver propuesta, sección "Traducción
 * de normativa a reglas de sistema").
 */
class ReglasSeeder extends Seeder
{
    public function run()
    {
        foreach (Roles::NOMBRES as $rol => $nombre) {
            Role::findOrCreate($rol, 'web');
        }

        $estados = [
            ['inscripcion', 'En inscripción', '#6c757d', 1, false],
            ['en_desarrollo', 'En desarrollo', '#0d6efd', 2, false],
            ['en_evaluacion', 'En evaluación', '#fd7e14', 3, false],
            ['defensa_programada', 'Defensa programada', '#6f42c1', 4, false],
            ['correcciones_finales', 'Correcciones finales', '#d63384', 5, false],
            ['cerrada', 'Cerrada', '#198754', 6, true],
            ['suspendida', 'Suspendida', '#ffc107', 7, false],
            ['retirada', 'Retirada', '#dc3545', 8, true],
        ];
        foreach ($estados as [$codigo, $nombre, $color, $orden, $final]) {
            EstadoTesis::updateOrCreate(['codigo' => $codigo], compact('nombre', 'color', 'orden', 'final'));
        }

        // ---------------------------------------------------------------
        // Rama institucional: programa piloto
        // ---------------------------------------------------------------
        $uct = Institucion::firstOrCreate(['sigla' => 'UCT'], ['nombre' => 'Universidad Católica de Temuco']);
        $dptys = Programa::firstOrCreate(
            ['institucion_id' => $uct->id, 'sigla' => 'DPTYS'],
            ['nombre' => 'Doctorado en Planificación Territorial y Sustentabilidad']
        );
        $reglaUct = ReglaVersion::firstOrCreate(
            ['programa_id' => $dptys->id, 'version' => '2024.1'],
            ['nombre' => 'Reglamento interno del programa', 'normativa' => 'Resolución VIP 28/2024 e instructivo VIP 06/2024', 'vigente_desde' => '2024-03-01']
        );

        $this->hitos($reglaUct, [
            ['SUF', 'Suficiencia investigativa', 'Acreditación de la suficiencia investigativa cuando aplique.', 2, true, true, 'VIP 28/2024, art. 61'],
            ['INS', 'Proyecto e inscripción de tesis', 'Propuesta de tesis, designación de guía/coguía y resolución de inscripción.', 4, true, true, 'VIP 28/2024, arts. 64–67'],
            ['T1', 'Tesis I', 'Primer informe de avance de tesis.', 10, true, true, 'VIP 28/2024, arts. 64–67'],
            ['T2', 'Tesis II', 'Segundo informe de avance de tesis.', 16, true, true, 'VIP 28/2024, arts. 64–67'],
            ['T3', 'Tesis III', 'Tercer informe de avance de tesis.', 22, true, true, 'VIP 28/2024, arts. 64–67'],
            ['TF', 'Tesis final', 'Documento de tesis: ética, propiedad intelectual, revisión y correcciones.', 30, true, true, 'VIP 28/2024, arts. 68–71'],
            ['EX', 'Examen de grado', 'Examen, acta y correcciones finales.', 36, false, false, 'VIP 28/2024, arts. 73–80'],
            ['REP', 'Entrega a repositorio', 'Acta, PDF validado y autorización de publicación para ESPOS y Bibliotecas.', 38, true, false, 'VIP 06/2024'],
        ]);
        $this->transiciones($reglaUct);

        foreach ([2024, 2025, 2026] as $anio) {
            Cohorte::firstOrCreate(
                ['programa_id' => $dptys->id, 'anio' => $anio],
                ['nombre' => "Cohorte $anio", 'regla_version_id' => $reglaUct->id]
            );
        }

        // ---------------------------------------------------------------
        // Rama genérica: segunda institución de contraste (ficticia)
        // ---------------------------------------------------------------
        $demo = Institucion::firstOrCreate(['sigla' => 'UDEMO'], ['nombre' => 'Universidad Demo (configuración de contraste)']);
        $dca = Programa::firstOrCreate(
            ['institucion_id' => $demo->id, 'sigla' => 'DCA'],
            ['nombre' => 'Doctorado en Ciencias Ambientales']
        );
        $reglaDemo = ReglaVersion::firstOrCreate(
            ['programa_id' => $dca->id, 'version' => '1.0'],
            ['nombre' => 'Reglamento genérico de ejemplo', 'normativa' => 'Plantilla multiuniversidad', 'vigente_desde' => '2025-01-01']
        );
        $this->hitos($reglaDemo, [
            ['PROP', 'Propuesta de tesis', 'Presentación y aprobación de la propuesta.', 6, true, true, null],
            ['AV1', 'Avance anual', 'Informe anual de avance.', 18, true, true, null],
            ['FIN', 'Documento final', 'Entrega del documento final.', 36, true, true, null],
            ['DEF', 'Defensa', 'Defensa pública.', 42, false, false, null],
        ]);
        $this->transiciones($reglaDemo);
        Cohorte::firstOrCreate(
            ['programa_id' => $dca->id, 'anio' => 2025],
            ['nombre' => 'Cohorte 2025', 'regla_version_id' => $reglaDemo->id]
        );
    }

    private function hitos(ReglaVersion $regla, array $hitos): void
    {
        foreach ($hitos as $i => [$codigo, $nombre, $descripcion, $plazo, $entrega, $evaluacion, $referencia]) {
            $regla->hitos()->updateOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'orden' => $i + 1,
                'plazo_meses' => $plazo,
                'requiere_entrega' => $entrega,
                'requiere_evaluacion' => $evaluacion,
                'referencia_normativa' => $referencia,
            ]);
        }
    }

    /**
     * Quién puede mover una tesis entre estados. Dirección resuelve las decisiones
     * académicas; secretaría solo programa la defensa (gestión operativa).
     */
    private function transiciones(ReglaVersion $regla): void
    {
        $d = Roles::DIRECCION;
        $reglas = [
            // desde, hacia, rol, acción, requiere_fundamento, requiere_documento
            ['inscripcion', 'en_desarrollo', $d, 'Validar inscripción', true, false],
            ['inscripcion', 'retirada', $d, 'Registrar retiro', true, true],
            ['en_desarrollo', 'en_evaluacion', $d, 'Enviar a evaluación final', true, false],
            ['en_desarrollo', 'en_evaluacion', Roles::GUIA, 'Proponer envío a evaluación final', true, false],
            ['en_desarrollo', 'suspendida', $d, 'Suspender tesis', true, true],
            ['en_desarrollo', 'retirada', $d, 'Registrar retiro', true, true],
            ['en_evaluacion', 'en_desarrollo', $d, 'Devolver para correcciones', true, false],
            ['en_evaluacion', 'defensa_programada', $d, 'Programar examen de grado', true, false],
            ['en_evaluacion', 'defensa_programada', Roles::SECRETARIA, 'Programar examen de grado', true, false],
            ['defensa_programada', 'correcciones_finales', $d, 'Registrar examen con correcciones', true, true],
            ['defensa_programada', 'cerrada', $d, 'Registrar examen aprobado y cerrar', true, true],
            ['correcciones_finales', 'cerrada', $d, 'Aprobar correcciones y cerrar expediente', true, false],
            ['suspendida', 'en_desarrollo', $d, 'Reanudar tesis', true, false],
            ['suspendida', 'retirada', $d, 'Registrar retiro', true, true],
        ];

        foreach ($reglas as [$desde, $hacia, $rol, $accion, $fundamento, $documento]) {
            $regla->transiciones()->updateOrCreate(
                ['estado_desde' => $desde, 'estado_hacia' => $hacia, 'rol' => $rol],
                ['accion' => $accion, 'requiere_fundamento' => $fundamento, 'requiere_documento' => $documento]
            );
        }
    }
}
