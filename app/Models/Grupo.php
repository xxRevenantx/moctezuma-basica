<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grupo extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ciclo_escolar_id',
        'clave',
        'estado',
        'motivo_generacion_excepcional',
        'archivado_at',
        'archivado_por',
        'motivo_archivo',
        'asignacion_grupo_id',
        'nivel_id',
        'grado_id',
        'generacion_id',
        'semestre_id',
    ];

    protected $casts = [
        'ciclo_escolar_id' => 'integer',
        'archivado_at' => 'datetime',
    ];

    public function usuarioArchivo()
    {
        return $this->belongsTo(User::class, 'archivado_por');
    }

    // RELACIONES
    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    // RELACION CON PERSONA_NIVEL
    public function personaNiveles()
    {
        return $this->hasMany(PersonaNivel::class);
    }

    public function personaNivelDetalles()
    {
        return $this->hasMany(PersonaNivelDetalle::class, 'grupo_id');
    }

    public function asignacionGrupo()
    {
        return $this->belongsTo(\App\Models\AsignacionGrupo::class, 'asignacion_grupo_id');
    }

    public function docentes()
    {
        return $this->belongsToMany(\App\Models\Persona::class, 'docente_grupos', 'grupo_id', 'persona_id')
            ->withPivot(['status'])
            ->withTimestamps();
    }


    // RELACIÓN CON INSCRIPCIONES
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }


    public function asignacionMaterias()
    {
        return $this->hasMany(AsignacionMateria::class, 'grupo_id');
    }

    // RELACION CON HORARIOS
    public function horarios()
    {
        return $this->hasMany(Horario::class, 'grupo_id');
    }

    // RELACION CON CALIFICACIONES
    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'grupo_id');
    }
    // RELACION CON BITACORA DE CALIFICACIONES
    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class, 'grupo_id');
    }
}
