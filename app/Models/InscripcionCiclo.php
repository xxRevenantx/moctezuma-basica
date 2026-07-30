<?php

namespace App\Models;

use App\Enums\EstadoInscripcionCiclo;
use App\Enums\EstatusAlumnoCiclo;
use App\Enums\ResultadoInscripcionCiclo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InscripcionCiclo extends Model
{
    public const ESTADO_EN_CURSO = 'en_curso';
    public const ESTADO_CERRADO = 'cerrado';
    public const ESTADO_ANULADO = 'anulado';

    protected $table = 'inscripcion_ciclos';

    protected $guarded = [];

    protected $casts = [
        'fecha_ingreso' => 'date',
        'fecha_salida' => 'date',
        'promovido' => 'boolean',
        'cerrado_at' => 'datetime',
        'snapshot_ingreso' => 'array',
        'snapshot_cierre' => 'array',
        'reconstruido' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (InscripcionCiclo $historial): void {
            $historial->estado = EstadoInscripcionCiclo::normalizar($historial->estado);
            $historial->estatus_ingreso = EstatusAlumnoCiclo::normalizar(
                $historial->estatus_ingreso,
                true
            );
            $historial->estatus_actual_ciclo = EstatusAlumnoCiclo::normalizar(
                $historial->estatus_actual_ciclo,
                $historial->estado === self::ESTADO_EN_CURSO
            );
            $historial->resultado_final = ResultadoInscripcionCiclo::normalizar($historial->resultado_final);

            if ($historial->estado === self::ESTADO_EN_CURSO) {
                $historial->fecha_salida = null;
                $historial->cerrado_at = null;
                $historial->cerrado_por = null;
                $historial->motivo_cierre = null;
                $historial->snapshot_cierre = null;
            }
        });
    }

    public function scopeEnCurso(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_EN_CURSO);
    }

    public function scopeCerrados(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_CERRADO);
    }

    public function scopeDelAlumno(Builder $query, int $inscripcionId): Builder
    {
        return $query->where('inscripcion_id', $inscripcionId);
    }

    public function scopeDelCiclo(Builder $query, int $cicloEscolarId): Builder
    {
        return $query->where('ciclo_escolar_id', $cicloEscolarId);
    }

    public function estaEnCurso(): bool
    {
        return $this->estado === self::ESTADO_EN_CURSO;
    }

    public function estaCerrado(): bool
    {
        return $this->estado === self::ESTADO_CERRADO;
    }

    public function estaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }

    public function getEtiquetaEstadoAttribute(): string
    {
        return EstadoInscripcionCiclo::tryFrom((string) $this->estado)?->etiqueta()
            ?? ucfirst(str_replace('_', ' ', (string) $this->estado));
    }

    public function getEtiquetaEstatusAttribute(): string
    {
        return match (EstatusAlumnoCiclo::normalizar($this->estatus_actual_ciclo)) {
            'activo' => 'Activo',
            'preinscrito' => 'Preinscrito',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            'pendiente_reinscripcion' => 'Pendiente de reinscripción',
            'no_reinscrito' => 'No reinscrito',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'trasladado' => 'Trasladado',
            'egresado' => 'Egresado',
            'no_iniciado' => 'No inició el ciclo',
            'suspendido' => 'Suspendido',
            default => 'Inactivo',
        };
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class)->withTrashed();
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

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class);
    }

    public function asignaciones()
    {
        return $this->hasMany(InscripcionCicloAsignacion::class)->orderBy('fecha_inicio')->orderBy('id');
    }

    public function asignacionActual()
    {
        return $this->hasOne(InscripcionCicloAsignacion::class)
            ->where('es_actual', true)
            ->latestOfMany();
    }

    public function destino()
    {
        return $this->belongsTo(self::class, 'inscripcion_ciclo_destino_id');
    }

    public function origenes()
    {
        return $this->hasMany(self::class, 'inscripcion_ciclo_destino_id');
    }

    public function usuarioCerro()
    {
        return $this->belongsTo(User::class, 'cerrado_por');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoAlumno::class, 'inscripcion_ciclo_id');
    }

    public function cambiosAcademicos()
    {
        return $this->hasMany(CambioAcademico::class, 'inscripcion_ciclo_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'inscripcion_ciclo_id');
    }

    public function fichasDescriptivas()
    {
        return $this->hasMany(FichaDescriptiva::class, 'inscripcion_ciclo_id');
    }

    public function calificacionesCamposFormativos()
    {
        return $this->hasMany(CalificacionCampoFormativo::class, 'inscripcion_ciclo_id');
    }

    public function asistenciasFinalesBachillerato()
    {
        return $this->hasMany(AsistenciaFinalBachillerato::class, 'inscripcion_ciclo_id');
    }

    public function decisionesPromocionOficial()
    {
        return $this->hasMany(DecisionPromocionOficial::class, 'inscripcion_ciclo_id');
    }

    public function lugaresPreescolar()
    {
        return $this->hasMany(LugarPreescolar::class, 'inscripcion_ciclo_id');
    }
}
