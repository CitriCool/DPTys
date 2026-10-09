<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cohorte extends Model
{
    protected $table = 'cohortes';
    protected $guarded = ['id'];

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function reglaVersion()
    {
        return $this->belongsTo(ReglaVersion::class);
    }
}
