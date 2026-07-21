<?php

namespace App\Models;

use App\Observers\AsignacionMateriaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ObservedBy(AsignacionMateriaObserver::class)]
class AsignacionMateria extends Model
{
    use HasFactory;

    public const ESTADO_BORRADOR = 'borrador';
    public const ESTADO_ACTIVA = 'activa';
    public const ESTADO_CERRADA = 'cerrada';
    public const ESTADO_ARCHIVADA = 'archivada';

    public const ESTADOS = [
        self::ESTADO_BORRADOR,
        self::ESTADO_ACTIVA,
        self::ESTADO_CERRADA,
        self::ESTADO_ARCHIVADA,
    ];

    protected $fillable = [
        'materia_id',
        'grupo_id',
        'profesor_id',
        'ciclo_escolar_id',
        'nivel_id',
        'grado_id',
        'generacion_id',
        'semestre_id',
        'orden',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'asignacion_origen_id',
        'confirmada_at',
        'confirmada_por',
        'revision_ciclo_estado',
        'revision_ciclo_observacion',
        'revision_ciclo_at',
        'revision_ciclo_por',
    ];

    protected $casts = [
        'materia_id' => 'integer',
        'grupo_id' => 'integer',
        'profesor_id' => 'integer',
        'ciclo_escolar_id' => 'integer',
        'nivel_id' => 'integer',
        'grado_id' => 'integer',
        'generacion_id' => 'integer',
        'semestre_id' => 'integer',
        'orden' => 'integer',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'confirmada_at' => 'datetime',
        'confirmada_por' => 'integer',
        'revision_ciclo_at' => 'datetime',
        'revision_ciclo_por' => 'integer',
    ];

    public function materia()
    {
        return $this->belongsTo(Materia::class, 'materia_id');
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function profesor()
    {
        return $this->belongsTo(Persona::class, 'profesor_id');
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class, 'grado_id');
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    public function asignacionOrigen()
    {
        return $this->belongsTo(self::class, 'asignacion_origen_id');
    }

    public function copias()
    {
        return $this->hasMany(self::class, 'asignacion_origen_id');
    }

    public function usuarioConfirmacion()
    {
        return $this->belongsTo(User::class, 'confirmada_por');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'asignacion_materia_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'asignacion_materia_id');
    }


    public function asistenciasFinalesBachillerato()
    {
        return $this->hasMany(AsistenciaFinalBachillerato::class, 'asignacion_materia_id');
    }

    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class, 'asignacion_materia_id');
    }

    public function scopeDelCiclo(Builder $query, int|string|null $cicloEscolarId): Builder
    {
        return $query->when(filled($cicloEscolarId), fn (Builder $q) => $q->where('ciclo_escolar_id', $cicloEscolarId));
    }

    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('estado', '!=', self::ESTADO_ARCHIVADA);
    }

    public function scopeUtilizables(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::ESTADO_BORRADOR, self::ESTADO_ACTIVA, self::ESTADO_CERRADA]);
    }

    public function estaCerrada(): bool
    {
        return $this->estado === self::ESTADO_CERRADA;
    }

    public function estaArchivada(): bool
    {
        return $this->estado === self::ESTADO_ARCHIVADA;
    }

    public function tieneHistorial(): bool
    {
        return $this->horarios()->exists()
            || $this->calificaciones()->exists()
            || $this->bitacoraCalificaciones()->exists();
    }

    public function sincronizarContextoDesdeGrupo(): void
    {
        $grupo = $this->relationLoaded('grupo') ? $this->grupo : $this->grupo()->first();

        if (!$grupo) {
            return;
        }

        $this->nivel_id = $grupo->nivel_id;
        $this->grado_id = $grupo->grado_id;
        $this->generacion_id = $grupo->generacion_id;
        $this->semestre_id = $grupo->semestre_id;
    }

    public function usuarioRevisionCiclo()
    {
        return $this->belongsTo(User::class, 'revision_ciclo_por');
    }

}
