<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntregaVersion extends Model
{
    protected $table = 'entrega_versiones';
    protected $guarded = ['id'];

    public function entrega()
    {
        return $this->belongsTo(Entrega::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
