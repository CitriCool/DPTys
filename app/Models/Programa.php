<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'programas';
    protected $guarded = ['id'];

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function cohortes()
    {
        return $this->hasMany(Cohorte::class);
    }

    public function reglaVersiones()
    {
        return $this->hasMany(ReglaVersion::class);
    }
}
