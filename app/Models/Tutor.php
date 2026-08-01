<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    /** @use HasFactory<\Database\Factories\TutorFactory> */
    use HasFactory;

    protected $table = 'tutores';

    protected $fillable = [
        'curp',
        'identificador_alternativo',
        'motivo_sin_curp',
        'parentesco',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'genero',
        'fecha_nacimiento',
        'ciudad_nacimiento',
        'estado_nacimiento',
        'municipio_nacimiento',
        'calle',
        'colonia',
        'ciudad',
        'municipio',
        'estado',
        'numero',
        'codigo_postal',
        'telefono_casa',
        'telefono_celular',
        'correo_electronico',
        'activo',
        'archivado_at',
        'archivado_por',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'activo' => 'boolean',
        'archivado_at' => 'datetime',
    ];

    /** Relación legado temporal; no usar para nuevas funciones. */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'tutor_id');
    }

    public function alumnos()
    {
        return $this->belongsToMany(Inscripcion::class, 'inscripcion_tutor')
            ->using(InscripcionTutor::class)
            ->withPivot([
                'id', 'parentesco', 'es_principal', 'orden_contacto',
                'es_tutor_legal', 'estado_tutela', 'vive_con_alumno',
                'recibe_avisos', 'recibe_calificaciones',
                'contacto_emergencia', 'autorizado_recoger',
                'responsable_economico', 'activo', 'fecha_inicio',
                'fecha_fin', 'motivo_fin', 'observaciones',
                'created_by', 'updated_by',
            ])
            ->withTimestamps();
    }

    public function relaciones()
    {
        return $this->hasMany(InscripcionTutor::class, 'tutor_id');
    }

    public function relacionesActivas()
    {
        return $this->relaciones()->activas();
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(collect([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->join(' '));
    }

    public function getIdentidadProtegidaAttribute(): string
    {
        $identidad = trim((string) ($this->curp ?: $this->identificador_alternativo));

        if ($identidad === '') {
            return 'Sin identificador';
        }

        if (mb_strlen($identidad) <= 8) {
            return mb_substr($identidad, 0, 2)
                . str_repeat('•', max(1, mb_strlen($identidad) - 4))
                . mb_substr($identidad, -2);
        }

        return mb_substr($identidad, 0, 4)
            . str_repeat('•', max(1, mb_strlen($identidad) - 8))
            . mb_substr($identidad, -4);
    }

}
