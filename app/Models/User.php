<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'rut', 'password', 'activo', 'acceso_hasta'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'acceso_hasta' => 'date',
        'activo' => 'boolean',
    ];

    public function programas()
    {
        return $this->belongsToMany(Programa::class, 'programa_user')->withPivot('rol')->withTimestamps();
    }

    /** Tesis en las que la persona es el doctorando/a */
    public function tesisPropias()
    {
        return $this->hasMany(Tesis::class, 'doctorando_id');
    }

    /** Programas en los que la persona tiene un rol determinado */
    public function programaIds(string $rol): array
    {
        return DB::table('programa_user')->where('user_id', $this->id)->where('rol', $rol)->pluck('programa_id')->all();
    }

    /** El acceso de evaluadores externos expira; el resto depende de "activo" */
    public function puedeIngresar(): bool
    {
        return $this->activo && (!$this->acceso_hasta || $this->acceso_hasta->endOfDay()->isFuture());
    }
}
