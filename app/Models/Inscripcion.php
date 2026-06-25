<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscripcion extends Model
{
    /** @use HasFactory<\Database\Factories\InscripcionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'inscripciones';

    protected $fillable = [
        // Identificación escolar
        'curp',
        'matricula',
        'folio',

        // Datos personales
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'genero',

        // Nacimiento
        'pais_nacimiento',
        'estado_nacimiento',
        'lugar_nacimiento',

        // Domicilio
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado_residencia',
        'ciudad_residencia',

        // Asignación académica
        'nivel_id',
        'grado_id',
        'generacion_id',
        'grupo_id',
        'semestre_id',

        'ciclo_id',

        // Foto
        'foto_path',

        'tutor_id',

        // Control
        'activo',

        'fecha_baja',
        'motivo_baja',
        'observaciones_baja',
        'fecha_inscripcion',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'activo' => 'boolean',
    ];

    /* =========================
     * Relaciones
     * ========================= */

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



    //Relación con ciclo
    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }


    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    // Relación con bitácora de calificaciones
    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class);
    }

    public function trayectoriasAcademicas()
    {
        return $this->hasMany(TrayectoriaAcademica::class, 'inscripcion_id');
    }

    public function trayectoriaActual()
    {
        return $this->hasOne(TrayectoriaAcademica::class, 'inscripcion_id')
            ->where('es_actual', true)
            ->latestOfMany();
    }

    public function matriculasAlumno()
    {
        return $this->hasMany(MatriculaAlumno::class, 'inscripcion_id')
            ->orderByDesc('vigente')
            ->orderByDesc('fecha_asignacion');
    }

    public function matriculaVigente()
    {
        return $this->hasOne(MatriculaAlumno::class, 'inscripcion_id')
            ->where('vigente', true)
            ->latestOfMany();
    }

    public function constancias()
    {
        return $this->hasMany(\App\Models\Constancia::class, 'inscripcion_id');
    }
    public function oficios()
    {
        return $this->hasMany(\App\Models\Oficio::class, 'inscripcion_id');
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoAlumno::class, 'inscripcion_id');
    }

    public function documentosActuales()
    {
        return $this->hasMany(DocumentoAlumno::class, 'inscripcion_id')
            ->where('es_actual', true);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoAlumno::class, 'inscripcion_id');
    }

    public function ultimoMovimiento()
    {
        return $this->hasOne(MovimientoAlumno::class, 'inscripcion_id')->latestOfMany();
    }

    // Relación con ficha descriptiva
    public function fichasDescriptivas()
    {
        return $this->hasMany(FichaDescriptiva::class, 'inscripcion_id');
    }

    public function lugarPreescolar()
    {
        return $this->hasOne(LugarPreescolar::class, 'inscripcion_id');
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (Inscripcion $inscripcion) {
            $tieneHistorial = $inscripcion->trayectoriasAcademicas()->exists()
                || $inscripcion->movimientos()->exists()
                || $inscripcion->calificaciones()->exists()
                || $inscripcion->documentos()->exists();

            if ($tieneHistorial) {
                throw new \RuntimeException(
                    'El alumno tiene historial académico y no puede eliminarse definitivamente. Usa archivar o dar de baja.'
                );
            }
        });
    }

}
