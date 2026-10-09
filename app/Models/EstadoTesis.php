<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoTesis extends Model
{
    protected $table = 'estados_tesis';
    protected $primaryKey = 'codigo';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['final' => 'boolean'];
}
