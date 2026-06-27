<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrayectoriaAcademica extends Model
{
    use HasFactory;

    public const ESTATUS = [
        'activo',
        'inactivo',
        'suspendido',
        'baja_temporal',
        'baja_definitiva',
        'traslado',
        'reingreso',
        'reincorporacion',
        'egresado',
        'no_promovido',
        'promovido',
        'archivado',
    ];

    protected $table = 'trayectorias_academicas';

    protected $fillable = [
        'inscripcion_id',
        'ciclo_escolar_id',
        'ciclo_id',
        'nivel_id',
        'grado_id',
        'generacion_id',
        'grupo_id',
        'semestre_id',
        'activo',
        'estatus',
        'fecha_baja',
        'motivo_baja',
        'observaciones_baja',
        'fecha_inscripcion',
        'fecha_inicio',
        'fecha_fin',
        'numero_estancia',
        'vigente_en_corte',
        'es_actual',
        'origen',
        'tipo_ingreso',
        'continuidad',
        'escuela_procedencia',
        'cct_procedencia',
        'ciclo_procedencia',
        'ultimo_grado_procedencia',
        'observaciones_procedencia',
        'documentacion_pendiente',
        'datos_reconstruidos',
        'promovido',
        'fecha_promocion',
        'trayectoria_origen_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_baja' => 'datetime',
        'fecha_inscripcion' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'numero_estancia' => 'integer',
        'vigente_en_corte' => 'boolean',
        'es_actual' => 'boolean',
        'documentacion_pendiente' => 'boolean',
        'datos_reconstruidos' => 'boolean',
        'promovido' => 'boolean',
        'fecha_promocion' => 'datetime',
    ];

    public function scopeEnContexto(Builder $query, int $cicloEscolarId, int $cicloId): Builder
    {
        return $query
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('ciclo_id', $cicloId);
    }

    public function scopeVigentesEnCorte(Builder $query): Builder
    {
        return $query->where('vigente_en_corte', true);
    }

    public function scopeActuales(Builder $query): Builder
    {
        return $query->where('es_actual', true);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id')->withTrashed();
    }

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
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

    public function grupo()
    {
        return $this->belongsTo(Grupo::class, 'grupo_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    public function trayectoriaOrigen()
    {
        return $this->belongsTo(TrayectoriaAcademica::class, 'trayectoria_origen_id');
    }

    public function trayectoriasDerivadas()
    {
        return $this->hasMany(TrayectoriaAcademica::class, 'trayectoria_origen_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAlumno::class, 'trayectoria_academica_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoAlumno::class, 'trayectoria_academica_id');
    }

    public function constanciasTraslado()
    {
        return $this->hasMany(ConstanciaTraslado::class, 'trayectoria_academica_id');
    }

    public function getEtiquetaEstatusAttribute(): string
    {
        return match ($this->estatus) {
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'traslado' => 'Traslado',
            'reingreso' => 'Reingreso',
            'reincorporacion' => 'Reincorporación',
            'egresado' => 'Egresado',
            'no_promovido' => 'No promovido',
            'promovido' => 'Promovido',
            'archivado' => 'Archivado',
            'inactivo' => 'Inactivo',
            'suspendido' => 'Suspendido',
            default => 'Activo',
        };
    }
}
