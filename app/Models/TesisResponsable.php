<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TesisResponsable extends Model
{
    protected $table = 'tesis_responsables';
    protected $guarded = ['id'];
    protected $casts = ['desde' => 'date', 'hasta' => 'date'];

    public function tesis()
    {
        return $this->belongsTo(Tesis::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
