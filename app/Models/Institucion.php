<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institucion extends Model
{
    protected $table = 'instituciones';
    protected $guarded = ['id'];

    public function programas()
    {
        return $this->hasMany(Programa::class);
    }
}
