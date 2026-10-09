<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observaciones';
    protected $guarded = ['id'];
    protected $casts = ['resuelta' => 'boolean'];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function hito()
    {
        return $this->belongsTo(TesisHito::class, 'tesis_hito_id');
    }

    public function version()
    {
        return $this->belongsTo(EntregaVersion::class, 'entrega_version_id');
    }

    public function respuestas()
    {
        return $this->hasMany(Observacion::class, 'parent_id')->oldest();
    }
}
