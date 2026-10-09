<?php

namespace App\Models;

use App\Support\Roles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tesis extends Model
{
    protected $table = 'tesis';
    protected $guarded = ['id'];
    protected $casts = ['fecha_inscripcion' => 'date'];

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }

    public function cohorte()
    {
        return $this->belongsTo(Cohorte::class);
    }

    public function reglaVersion()
    {
        return $this->belongsTo(ReglaVersion::class);
    }

    public function doctorando()
    {
        return $this->belongsTo(User::class, 'doctorando_id');
    }

    public function estadoActual()
    {
        return $this->belongsTo(EstadoTesis::class, 'estado', 'codigo');
    }

    public function responsables()
    {
        return $this->hasMany(TesisResponsable::class);
    }

    /** Guía y coguía vigentes */
    public function responsablesVigentes()
    {
        return $this->hasMany(TesisResponsable::class)->whereNull('hasta')->with('user');
    }

    public function hitos()
    {
        return $this->hasMany(TesisHito::class)->orderBy('orden');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }

    public function observaciones()
    {
        return $this->hasMany(Observacion::class);
    }

    public function evaluaciones()
    {
        return $this->hasMany(Evaluacion::class);
    }

    public function decisiones()
    {
        return $this->hasMany(Decision::class)->latest();
    }

    public function actas()
    {
        return $this->hasMany(Acta::class)->orderBy('fecha');
    }

    public function eventos()
    {
        return $this->hasMany(EventoAuditoria::class)->orderByDesc('id');
    }

    /**
     * Tesis que una persona puede ver según el rol que está ejerciendo.
     */
    public function scopeVisiblesPara(Builder $query, User $user, string $rol): Builder
    {
        switch ($rol) {
            case Roles::ADMINISTRADOR:
            case Roles::TECNICO:
                return $query;

            case Roles::DIRECCION:
            case Roles::SECRETARIA:
                return $query->whereIn('programa_id', $user->programaIds($rol));

            case Roles::GUIA:
                return $query->whereHas('responsables', fn ($q) => $q->where('user_id', $user->id)->whereNull('hasta'));

            case Roles::DOCTORANDO:
                return $query->where('doctorando_id', $user->id);

            case Roles::EVALUADOR:
                return $query->whereHas('evaluaciones', fn ($q) => $q
                    ->where('evaluador_id', $user->id)
                    ->whereDate('acceso_hasta', '>=', now()->toDateString()));
        }

        return $query->whereRaw('1 = 0');
    }

    public function esGuia(User $user): bool
    {
        return $this->responsables()->where('user_id', $user->id)->whereNull('hasta')->exists();
    }
}
