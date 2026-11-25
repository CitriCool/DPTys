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
     * Hitos con plazo vencido que siguen esperando una entrega o corrección del doctorando/a.
     * Un hito entregado y en espera de revisión no cuenta como vencido: se informa aparte.
     */
    public function scopeVencidos($q)
    {
        return $q->whereNotIn('estado', ['aprobado', 'entregado'])
            ->whereDate('fecha_limite', '<', now()->toDateString());
    }

    /**
     * Hitos con una entrega que espera la revisión de la guía.
     */
    public function scopeEnRevision($q)
    {
        return $q->where('estado', 'entregado');
    }

    /**
     * Semáforo del hito: cumplido, en revisión, vencido, próximo o en plazo.
     */
    public function getSemaforoAttribute(): string
    {
        if ($this->estado === 'aprobado') {
            return 'cumplido';
        }
        if ($this->estado === 'entregado') {
            return 'en_revision';
        }
        if ($this->fecha_limite->isPast() && !$this->fecha_limite->isToday()) {
            return 'vencido';
        }
        if ($this->fecha_limite->lte(now()->addDays(self::DIAS_ALERTA))) {
            return 'proximo';
        }
        return 'en_plazo';
    }

    /**
     * Quién debe dar el siguiente paso: 'doctorando', 'guia' o null si el hito está cumplido.
     */
    public function getTurnoAttribute(): ?string
    {
        return match ($this->estado) {
            'aprobado' => null,
            'entregado' => 'guia',
            default => 'doctorando',
        };
    }

    public function getEstadoNombreAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
