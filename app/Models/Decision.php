<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decision extends Model
{
    protected $table = 'decisiones';
    protected $guarded = ['id'];

    const TIPOS = [
        'cambio_estado' => 'Cambio de estado',
        'prorroga' => 'Prórroga',
        'cambio_guia' => 'Cambio de guía',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getTipoNombreAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
