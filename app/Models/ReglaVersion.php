<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReglaVersion extends Model
{
    protected $table = 'regla_versiones';
    protected $guarded = ['id'];

    protected $casts = ['vigente_desde' => 'date', 'activa' => 'boolean'];

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function hitos()
    {
        return $this->hasMany(HitoPlantilla::class)->orderBy('orden');
    }

    public function transiciones()
    {
        return $this->hasMany(Transicion::class);
    }
}
