<?php

namespace App\Models;

use App\Support\CalificacionBachillerato;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    protected $table = 'calificaciones';

    protected $fillable = [
        'inscripcion_id',
        'inscripcion_ciclo_id',
        'asignacion_materia_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'ciclo_escolar_id',
        'generacion_id',
        'semestre_id',
        'periodo_id',
        'calificacion',
        'valor_numerico',
        'es_numerica',
        'clave_especial',
        'observacion',
        'fuente',
        'escuela_procedencia',
        'documento_respaldo_id',
        'equivalencia_autorizada',
        'fecha_validacion',
        'validado_por',
        'capturado_por',
        'fecha_captura',
        'ip_captura',
    ];

    protected $casts = [
        'valor_numerico' => 'decimal:2',
        'es_numerica' => 'boolean',
        'fecha_captura' => 'datetime',
        'equivalencia_autorizada' => 'boolean',
        'fecha_validacion' => 'datetime',
    ];


    protected static function booted(): void
    {
        static::saving(function (Calificacion $calificacion): void {
            app(\App\Services\HistorialCicloEscolarService::class)->vincularRegistroAcademico($calificacion);

            if (
                ! ReglasMateriaBachillerato::esBachillerato($calificacion->nivel_id)
                || ! (bool) $calificacion->es_numerica
            ) {
                return;
            }

            $valor = $calificacion->valor_numerico ?? $calificacion->calificacion;
            $entero = CalificacionBachillerato::truncarParcial($valor);

            if ($entero === null) {
                return;
            }

            $calificacion->calificacion = (string) $entero;
            $calificacion->valor_numerico = $entero;
            $calificacion->clave_especial = null;
        });
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function inscripcionCiclo()
    {
        return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_id');
    }

    public function asignacionMateria()
    {
        return $this->belongsTo(AsignacionMateria::class, 'asignacion_materia_id');
    }

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

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class, 'generacion_id');
    }

    public function semestre()
    {
        return $this->belongsTo(Semestre::class, 'semestre_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodos::class, 'periodo_id');
    }

    public function capturador()
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }

    public function validador()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function documentoRespaldo()
    {
        return $this->belongsTo(DocumentoAlumno::class, 'documento_respaldo_id');
    }

    public function getMateriaAttribute()
    {
        return $this->asignacionMateria?->materia;
    }

    public function getProfesorAttribute()
    {
        return $this->asignacionMateria?->profesor;
    }
}
