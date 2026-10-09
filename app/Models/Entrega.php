<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';
    protected $guarded = ['id'];

    public function tesis()
    {
        return $this->belongsTo(Tesis::class);
    }

    public function hito()
    {
        return $this->belongsTo(TesisHito::class, 'tesis_hito_id');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function versiones()
    {
        return $this->hasMany(EntregaVersion::class)->orderByDesc('numero');
    }
}
