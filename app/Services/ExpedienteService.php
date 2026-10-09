<?php

namespace App\Services;

use App\Models\Cohorte;
use App\Models\Tesis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class ExpedienteService
{
    // Estado con que se abre todo expediente
    const ESTADO_INICIAL = 'inscripcion';

    /**
     * Abre un expediente: genera el código, copia los hitos de la regla vigente de la
     * cohorte con sus plazos y registra guía/coguía.
     */
    public static function crear(array $datos): Tesis
    {
        return DB::transaction(function () use ($datos) {
            $cohorte = Cohorte::with('programa.institucion', 'reglaVersion.hitos')->findOrFail($datos['cohorte_id']);
            $programa = $cohorte->programa;
            $fechaInscripcion = Carbon::parse($datos['fecha_inscripcion']);

            $tesis = Tesis::create([
                'institucion_id' => $programa->institucion_id,
                'programa_id' => $programa->id,
                'cohorte_id' => $cohorte->id,
                'regla_version_id' => $cohorte->regla_version_id,
                'doctorando_id' => $datos['doctorando_id'],
                'codigo' => self::siguienteCodigo($programa->sigla, $fechaInscripcion->year),
                'titulo' => $datos['titulo'],
                'resumen' => $datos['resumen'] ?? null,
                'estado' => self::ESTADO_INICIAL,
                'fecha_inscripcion' => $fechaInscripcion,
                'resolucion' => $datos['resolucion'] ?? null,
                'referencia_externa' => $datos['referencia_externa'] ?? null,
            ]);

            foreach ($cohorte->reglaVersion->hitos as $plantilla) {
                $tesis->hitos()->create([
                    'hito_plantilla_id' => $plantilla->id,
                    'nombre' => $plantilla->nombre,
                    'orden' => $plantilla->orden,
                    'fecha_limite' => $fechaInscripcion->copy()->addMonths($plantilla->plazo_meses),
                    'requiere_entrega' => $plantilla->requiere_entrega,
                    'requiere_evaluacion' => $plantilla->requiere_evaluacion,
                    'referencia_normativa' => $plantilla->referencia_normativa,
                ]);
            }

            foreach (['guia' => $datos['guia_id'], 'coguia' => $datos['coguia_id'] ?? null] as $tipo => $userId) {
                if ($userId) {
                    $tesis->responsables()->create(['user_id' => $userId, 'tipo' => $tipo, 'desde' => $fechaInscripcion]);
                }
            }

            AuditoriaService::registrar('apertura_expediente', 'tesis', $tesis->id, $tesis->id, [
                'codigo' => $tesis->codigo,
                'regla_version_id' => $tesis->regla_version_id,
                'hitos' => $cohorte->reglaVersion->hitos->count(),
            ]);

            return $tesis;
        });
    }

    /** Código correlativo por programa y año: DPTYS-2026-001 */
    public static function siguienteCodigo(string $sigla, int $anio): string
    {
        $prefijo = strtoupper($sigla) . '-' . $anio . '-';
        $ultimo = Tesis::where('codigo', 'like', $prefijo . '%')->lockForUpdate()->orderByDesc('codigo')->value('codigo');
        $numero = $ultimo ? ((int) substr($ultimo, strlen($prefijo))) + 1 : 1;

        return $prefijo . str_pad($numero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Paquete documental exportable: resumen, bitácora, entregas, decisiones y actas
     * con sus huellas SHA-256 para verificar integridad.
     */
    public static function exportarZip(Tesis $tesis): string
    {
        $tesis->load([
            'programa', 'cohorte', 'doctorando', 'estadoActual', 'responsablesVigentes',
            'hitos.entregas.versiones', 'evaluaciones.evaluador', 'decisiones.autor', 'actas', 'eventos.autor',
        ]);

        $ruta = storage_path("app/exportes/{$tesis->codigo}.zip");
        if (!is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0775, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo de exportación.');
        }

        $huellas = [];
        $agregar = function (string $origen, string $destino, string $sha256) use ($zip, &$huellas) {
            if (Storage::exists($origen)) {
                $zip->addFromString($destino, Storage::get($origen));
                $huellas[] = "$sha256  $destino";
            }
        };

        foreach ($tesis->hitos as $hito) {
            foreach ($hito->entregas as $entrega) {
                foreach ($entrega->versiones as $version) {
                    $agregar($version->ruta, sprintf('entregas/%02d-%s/v%d-%s', $hito->orden, str($hito->nombre)->slug(), $version->numero, $version->nombre_archivo), $version->sha256);
                }
            }
        }
        foreach ($tesis->actas as $acta) {
            $agregar($acta->ruta, "actas/{$acta->tipo}-{$acta->fecha->format('Y-m-d')}-{$acta->nombre_archivo}", $acta->sha256);
        }
        foreach ($tesis->decisiones as $decision) {
            if ($decision->documento_ruta) {
                $agregar($decision->documento_ruta, "decisiones/{$decision->id}-{$decision->documento_nombre}", $decision->documento_sha256);
            }
        }

        $zip->addFromString('expediente.html', view('tesis.exportar', compact('tesis'))->render());
        $zip->addFromString('expediente.json', json_encode(self::resumen($tesis), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('bitacora.csv', self::bitacoraCsv($tesis));
        $zip->addFromString('SHA256SUMS.txt', implode("\n", $huellas) . "\n");
        $zip->close();

        AuditoriaService::registrar('exportacion_expediente', 'tesis', $tesis->id, $tesis->id, ['archivos' => count($huellas)]);

        return $ruta;
    }

    public static function resumen(Tesis $tesis): array
    {
        return [
            'codigo' => $tesis->codigo,
            'titulo' => $tesis->titulo,
            'estado' => $tesis->estadoActual->nombre,
            'programa' => $tesis->programa->nombre,
            'cohorte' => $tesis->cohorte->nombre,
            'doctorando' => $tesis->doctorando->name,
            'fecha_inscripcion' => $tesis->fecha_inscripcion->toDateString(),
            'resolucion' => $tesis->resolucion,
            'responsables' => $tesis->responsablesVigentes->map(fn ($r) => ['tipo' => $r->tipo, 'nombre' => $r->user->name])->values(),
            'hitos' => $tesis->hitos->map(fn ($h) => [
                'nombre' => $h->nombre,
                'fecha_limite' => $h->fecha_limite->toDateString(),
                'estado' => $h->estado,
                'cumplido' => $h->fecha_cumplimiento?->toDateTimeString(),
            ])->values(),
            'exportado' => now()->toDateTimeString(),
        ];
    }

    private static function bitacoraCsv(Tesis $tesis): string
    {
        $filas = [['fecha', 'usuario', 'rol', 'accion', 'entidad', 'hash']];
        foreach ($tesis->eventos->sortBy('id') as $e) {
            $filas[] = [$e->created_at->toDateTimeString(), $e->autor?->name, $e->rol_ejercido, $e->accion, "{$e->entidad_tipo}#{$e->entidad_id}", $e->hash];
        }

        $salida = fopen('php://temp', 'r+');
        fwrite($salida, "\xEF\xBB\xBF"); // BOM para que Excel lea los acentos
        foreach ($filas as $fila) {
            fputcsv($salida, $fila, ';');
        }
        rewind($salida);
        return stream_get_contents($salida);
    }
}
