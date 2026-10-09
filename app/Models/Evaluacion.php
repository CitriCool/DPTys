<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';
    protected $guarded = ['id'];
    protected $casts = ['acceso_hasta' => 'date', 'fecha_dictamen' => 'datetime'];

    const DICTAMENES = [
        'aprobado' => 'Aprobado',
        'aprobado_con_observaciones' => 'Aprobado con observaciones',
        'rechazado' => 'Rechazado',
    ];

    public function tesis()
    {
        return $this->belongsTo(Tesis::class);
    }

    public function hito()
    {
        return $this->belongsTo(TesisHito::class, 'tesis_hito_id');
    }

    public function version()
    {
        return $this->belongsTo(EntregaVersion::class, 'entrega_version_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    public function asignador()
    {
        return $this->belongsTo(User::class, 'asignado_por');
    }

    public function getVigenteAttribute(): bool
    {
        return $this->acceso_hasta->endOfDay()->isFuture();
    }
}
