<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transicion extends Model
{
    protected $table = 'transiciones';
    protected $guarded = ['id'];

    protected $casts = ['requiere_fundamento' => 'boolean', 'requiere_documento' => 'boolean'];

    public function estadoHacia()
    {
        return $this->belongsTo(EstadoTesis::class, 'estado_hacia', 'codigo');
    }
}
