<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TesisHito extends Model
{
    protected $table = 'tesis_hitos';
    protected $guarded = ['id'];
    protected $casts = [
        'fecha_limite' => 'date',
        'fecha_cumplimiento' => 'datetime',
        'requiere_entrega' => 'boolean',
        'requiere_evaluacion' => 'boolean',
    ];

    // Días antes del vencimiento en que un hito pasa a "próximo"
    const DIAS_ALERTA = 30;

    const ESTADOS = [
        'pendiente' => 'Pendiente',
        'entregado' => 'Entregado',
        'observado' => 'Con observaciones',
        'aprobado' => 'Aprobado',
    ];

    public function tesis()
    {
        return $this->belongsTo(Tesis::class);
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class)->latest();
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }

    /**
     * Semáforo del hito: cumplido, vencido, próximo o en plazo.
     */
    public function getSemaforoAttribute(): string
    {
        if ($this->estado === 'aprobado') {
            return 'cumplido';
        }
        if ($this->fecha_limite->isPast() && !$this->fecha_limite->isToday()) {
            return 'vencido';
        }
        if ($this->fecha_limite->lte(now()->addDays(self::DIAS_ALERTA))) {
            return 'proximo';
        }
        return 'en_plazo';
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
