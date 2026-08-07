<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarioEvento extends Model
{
    use SoftDeletes;

    public const TIPOS = [
        'academico',
        'evaluacion',
        'inscripcion',
        'reinscripcion',
        'boletas',
        'cierre',
        'horario',
        'documentacion',
        'reunion',
        'administrativo',
        'respaldo',
        'otro',
    ];

    public const ESTADOS = ['programado', 'en_curso', 'completado', 'cancelado'];

    public const PRIORIDADES = ['normal', 'alta', 'critica'];

    public const AUDIENCIAS = ['todos', 'administrativos', 'docentes'];

    public const RECURRENCIAS = ['ninguna', 'diaria', 'semanal', 'mensual', 'anual'];

    protected $table = 'calendario_eventos';

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'estado',
        'prioridad',
        'audiencia',
        'inicia_at',
        'termina_at',
        'todo_el_dia',
        'ubicacion',
        'enlace',
        'recurrencia',
        'recurrencia_hasta',
        'recordatorio_dias',
        'ciclo_escolar_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'responsable_id',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'inicia_at' => 'datetime',
        'termina_at' => 'datetime',
        'todo_el_dia' => 'boolean',
        'recurrencia_hasta' => 'date',
        'recordatorio_dias' => 'integer',
    ];

    public function scopeVisiblesPara(Builder $query, ?User $usuario): Builder
    {
        if (! $usuario || $usuario->is_admin) {
            return $query;
        }

        if ($usuario->isProfessor()) {
            return $query->whereIn('audiencia', ['todos', 'docentes']);
        }

        return $query->whereIn('audiencia', ['todos', 'administrativos']);
    }

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

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function getEtiquetaTipoAttribute(): string
    {
        return match ($this->tipo) {
            'evaluacion' => 'Evaluación',
            'inscripcion' => 'Inscripción',
            'reinscripcion' => 'Reinscripción',
            'boletas' => 'Boletas',
            'cierre' => 'Cierre académico',
            'horario' => 'Horarios',
            'documentacion' => 'Documentación',
            'reunion' => 'Reunión',
            'administrativo' => 'Administrativo',
            'respaldo' => 'Respaldo',
            'otro' => 'Otro',
            default => 'Académico',
        };
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return match ($this->estado) {
            'en_curso' => 'En curso',
            'completado' => 'Completado',
            'cancelado' => 'Cancelado',
            default => 'Programado',
        };
    }
}
