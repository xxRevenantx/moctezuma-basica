<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraCalificacion extends Model
{
    protected $table = 'bitacora_calificaciones';

    protected $fillable = [
        'nivel_id',
        'grado_id',
        'grupo_id',
        'generacion_id',
        'semestre_id',
        'ciclo_escolar_id',
        'periodo_id',
        'inscripcion_id',
        'asignacion_materia_id',
        'user_id',
        'accion',
        'calificacion_anterior',
        'calificacion_nueva',
        'valor_anterior_numerico',
        'valor_nuevo_numerico',
        'tipo_valor',
        'observacion',
        'motivo',
        'ip',
    ];

    protected $casts = [
        'valor_anterior_numerico' => 'decimal:2',
        'valor_nuevo_numerico' => 'decimal:2',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class, 'periodo_id');
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Acceso rápido al catálogo de materia.
     */
    public function materia()
    {
        return $this->hasOneThrough(
            Materia::class,
            AsignacionMateria::class,
            'id',
            'id',
            'asignacion_materia_id',
            'materia_id'
        );
    }

    /**
     * Devuelve el nombre de la materia de forma segura.
     */
    public function getNombreMateriaAttribute(): string
    {
        return $this->asignacionMateria?->materia?->materia ?? 'Sin materia';
    }

    /**
     * Devuelve el nombre del alumno de forma segura.
     */
    public function getNombreAlumnoAttribute(): string
    {
        $inscripcion = $this->inscripcion;

        if (!$inscripcion) {
            return 'Sin alumno';
        }

        return trim(
            ($inscripcion->nombre ?? '') . ' ' .
                ($inscripcion->apellido_paterno ?? '') . ' ' .
                ($inscripcion->apellido_materno ?? '')
        ) ?: 'Sin alumno';
    }
}
