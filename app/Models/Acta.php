<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acta extends Model
{
    protected $table = 'actas';
    protected $guarded = ['id'];
    protected $casts = ['fecha' => 'date'];

    const TIPOS = [
        'inscripcion' => 'Acta de inscripción',
        'examen' => 'Acta de examen de grado',
        'cierre' => 'Acta de cierre',
        'otra' => 'Otra',
    ];

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
