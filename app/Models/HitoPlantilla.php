<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HitoPlantilla extends Model
{
    protected $table = 'hito_plantillas';
    protected $guarded = ['id'];

    protected $casts = ['requiere_entrega' => 'boolean', 'requiere_evaluacion' => 'boolean'];
}
