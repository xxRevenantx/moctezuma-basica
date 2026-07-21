<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CicloEscolar extends Model
{
    use HasFactory;

    protected $table = 'ciclo_escolares';

    protected $fillable = [
        'inicio_anio',
        'fin_anio',
        'es_actual',
        'cerrado_at',
        'cerrado_por',
    ];

    protected $casts = [
        'es_actual' => 'boolean',
        'cerrado_at' => 'datetime',
    ];


    public function grupos()
    {
        return $this->hasMany(Grupo::class, 'ciclo_escolar_id');
    }

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'ciclo_escolar_id');
    }

    public function plantillasPersonal()
    {
        return $this->hasMany(PlantillaPersonalNivel::class, 'ciclo_escolar_id');
    }

    public function nivelesPreparacion()
    {
        return $this->hasMany(CicloEscolarNivel::class, 'ciclo_escolar_id');
    }

    public function periodosBasicos()
    {
        return $this->hasMany(Periodos::class, 'ciclo_escolar_id');
    }

    public function periodos()
    {
        return $this->hasMany(Periodos::class, 'ciclo_escolar_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'ciclo_escolar_id');
    }

    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class, 'ciclo_escolar_id');
    }

    public function asignacionMaterias()
    {
        return $this->hasMany(AsignacionMateria::class, 'ciclo_escolar_id');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'ciclo_escolar_id');
    }

    public function tallerSesiones()
    {
        return $this->hasMany(TallerSesion::class, 'ciclo_escolar_id');
    }

    public function usuarioQueCerro()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function getNombreAttribute(): string
    {
        return $this->inicio_anio . '-' . $this->fin_anio;
    }

    public function getEstadoAttribute(): string
    {
        if ($this->es_actual) {
            return 'actual';
        }

        return $this->cerrado_at ? 'cerrado' : 'historico';
    }
}
