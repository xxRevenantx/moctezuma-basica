<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InscripcionCiclo extends Model
{
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

    public function scopeEnCurso(Builder $query): Builder
    {
        return $query->where('estado', 'en_curso');
    }

    public function inscripcion() { return $this->belongsTo(Inscripcion::class)->withTrashed(); }
    public function cicloEscolar() { return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id'); }
    public function nivel() { return $this->belongsTo(Nivel::class); }
    public function grado() { return $this->belongsTo(Grado::class); }
    public function generacion() { return $this->belongsTo(Generacion::class); }
    public function grupo() { return $this->belongsTo(Grupo::class); }
    public function semestre() { return $this->belongsTo(Semestre::class); }
    public function asignaciones() { return $this->hasMany(InscripcionCicloAsignacion::class)->orderBy('fecha_inicio')->orderBy('id'); }
    public function asignacionActual() { return $this->hasOne(InscripcionCicloAsignacion::class)->where('es_actual', true)->latestOfMany(); }
    public function destino() { return $this->belongsTo(self::class, 'inscripcion_ciclo_destino_id'); }
    public function origenes() { return $this->hasMany(self::class, 'inscripcion_ciclo_destino_id'); }
    public function usuarioCerro() { return $this->belongsTo(User::class, 'cerrado_por'); }
    public function movimientos() { return $this->hasMany(MovimientoAlumno::class, 'inscripcion_ciclo_id'); }
    public function cambiosAcademicos() { return $this->hasMany(CambioAcademico::class, 'inscripcion_ciclo_id'); }
    public function calificaciones() { return $this->hasMany(Calificacion::class, 'inscripcion_ciclo_id'); }
    public function fichasDescriptivas() { return $this->hasMany(FichaDescriptiva::class, 'inscripcion_ciclo_id'); }
    public function calificacionesCamposFormativos() { return $this->hasMany(CalificacionCampoFormativo::class, 'inscripcion_ciclo_id'); }
    public function asistenciasFinalesBachillerato() { return $this->hasMany(AsistenciaFinalBachillerato::class, 'inscripcion_ciclo_id'); }
    public function decisionesPromocionOficial() { return $this->hasMany(DecisionPromocionOficial::class, 'inscripcion_ciclo_id'); }
    public function lugaresPreescolar() { return $this->hasMany(LugarPreescolar::class, 'inscripcion_ciclo_id'); }
}
