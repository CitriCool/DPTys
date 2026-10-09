<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Evento de la bitácora inalterable. Solo admite inserciones (ver AuditoriaService).
 */
class EventoAuditoria extends Model
{
    protected $table = 'eventos_auditoria';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $casts = ['datos' => 'array', 'created_at' => 'datetime'];

    protected static function booted()
    {
        static::updating(fn () => throw new LogicException('La bitácora de auditoría no se puede modificar.'));
        static::deleting(fn () => throw new LogicException('La bitácora de auditoría no se puede eliminar.'));
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
