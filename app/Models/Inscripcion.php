<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Inscripcion extends Model
{
    public const ESTATUS_ACTIVOS = [
        'activo',
        'reingreso',
        'no_promovido',
    ];

    public const ESTATUS_BAJA_ADMINISTRATIVA = [
        'baja_temporal',
        'baja_definitiva',
        'traslado',
        'trasladado',
        'suspendido',
        'inactivo',
    ];

    public const ESTATUS_EGRESADO = 'egresado';

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
        'ciclo_escolar_id',

        // Foto
        'foto_path',

        'tutor_id',

        // Control
        'activo',
        'estatus',
        'fecha_estatus',
        'motivo_estatus',
        'indicador_reingreso',
        'tipo_ultimo_ingreso',
        'fecha_ultimo_ingreso',
        'documentacion_reingreso_pendiente',
        'usuario_acceso_activo',

        'fecha_baja',
        'motivo_baja',
        'observaciones_baja',
        'fecha_inscripcion',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_inscripcion' => 'date',
        'activo' => 'boolean',
        'fecha_estatus' => 'datetime',
        'indicador_reingreso' => 'boolean',
        'fecha_ultimo_ingreso' => 'date',
        'documentacion_reingreso_pendiente' => 'boolean',
        'usuario_acceso_activo' => 'boolean',
        'ciclo_escolar_id' => 'integer',
    ];

    /* =========================
     * Relaciones
     * ========================= */

    public function getFotoRutaAttribute(): ?string
    {
        if (blank($this->foto_path)) {
            return null;
        }

        return ltrim((string) $this->foto_path, '/');
    }

    public function getFotoExisteAttribute(): bool
    {
        if (! $this->foto_ruta) {
            return false;
        }

        try {
            return Storage::disk((string) config('filesystems.fotos_disk', 'public'))->exists($this->foto_ruta);
        } catch (Throwable) {
            return false;
        }
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto_existe) {
            return null;
        }

        try {
            $disco = Storage::disk((string) config('filesystems.fotos_disk', 'public'));

            if (config('filesystems.fotos_disk', 'public') !== 'public') {
                try {
                    return $disco->temporaryUrl($this->foto_ruta, now()->addMinutes(20));
                } catch (Throwable) {
                    // Algunos discos públicos o adaptadores no implementan URL temporal.
                }
            }

            return $disco->url($this->foto_ruta);
        } catch (Throwable) {
            return null;
        }
    }

    public function getFotoDataUriAttribute(): ?string
    {
        if (! $this->foto_existe) {
            return null;
        }

        try {
            $contenido = Storage::disk((string) config('filesystems.fotos_disk', 'public'))->get($this->foto_ruta);
            $extension = strtolower(pathinfo($this->foto_ruta, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($contenido);
        } catch (Throwable) {
            return null;
        }
    }

    public function estatusNormalizado(): string
    {
        $estatus = mb_strtolower(trim((string) $this->estatus));

        if ($estatus !== '') {
            return $estatus;
        }

        return $this->activo ? 'activo' : 'inactivo';
    }

    public function esEgresado(): bool
    {
        return $this->estatusNormalizado() === self::ESTATUS_EGRESADO;
    }

    public function esBajaAdministrativa(): bool
    {
        return in_array($this->estatusNormalizado(), self::ESTATUS_BAJA_ADMINISTRATIVA, true);
    }

    public function expedienteSoloLectura(): bool
    {
        return $this->trashed() || $this->esBajaAdministrativa();
    }

    public function getEtiquetaEstatusAttribute(): string
    {
        return match ($this->estatusNormalizado()) {
            'activo' => 'Activo',
            'preinscrito' => 'Preinscrito',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            'egresado' => 'Egresado',
            'traslado', 'trasladado' => 'Traslado',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'suspendido' => 'Suspendido',
            'inactivo' => 'Inactivo',
            default => ucfirst(str_replace('_', ' ', $this->estatusNormalizado())),
        };
    }

    public function getInicialesAttribute(): string
    {
        $primera = mb_substr(trim((string) $this->nombre), 0, 1);
        $segunda = mb_substr(trim((string) $this->apellido_paterno), 0, 1);

        return mb_strtoupper($primera . $segunda);
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



    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    //Relación con momento de ingreso
    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class);
    }


    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function observacionesInscripcion()
    {
        return $this->hasMany(ObservacionInscripcion::class, 'inscripcion_id')
            ->orderByDesc('ciclo_escolar_id');
    }

    public function historialObservacionesInscripcion()
    {
        return $this->hasMany(HistorialObservacionInscripcion::class, 'inscripcion_id')
            ->latest('created_at');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    public function calificacionesCamposFormativos()
    {
        return $this->hasMany(CalificacionCampoFormativo::class, 'inscripcion_id');
    }

    public function decisionesPromocionOficial()
    {
        return $this->hasMany(DecisionPromocionOficial::class, 'inscripcion_id');
    }

    // Relación con bitácora de calificaciones
    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class);
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

    public function cambiosAcademicos()
    {
        return $this->hasMany(CambioAcademico::class, 'inscripcion_id')
            ->orderByDesc('realizado_at')->orderByDesc('id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoAlumno::class, 'inscripcion_id');
    }

    public function preinscripcionesCiclos()
    {
        return $this->hasMany(PreinscripcionCiclo::class, 'inscripcion_id')
            ->orderByDesc('fecha_preinscripcion');
    }

    public function ciclosEscolaresHistorial()
    {
        return $this->hasMany(InscripcionCiclo::class, 'inscripcion_id')
            ->orderByDesc('ciclo_escolar_id');
    }

    public function cicloEscolarActualHistorial()
    {
        return $this->hasOne(InscripcionCiclo::class, 'inscripcion_id')
            ->where('estado', 'en_curso')
            ->latestOfMany();
    }

    public function ultimoMovimiento()
    {
        return $this->hasOne(MovimientoAlumno::class, 'inscripcion_id')->latestOfMany();
    }

    public function constanciasTraslado()
    {
        return $this->hasMany(ConstanciaTraslado::class, 'inscripcion_id');
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


    public function asistenciasFinalesBachillerato()
    {
        return $this->hasMany(AsistenciaFinalBachillerato::class, 'inscripcion_id');
    }

    public function emisionesDocumentosMediaSuperior()
    {
        return $this->hasMany(EmisionDocumentoMediaSuperior::class, 'inscripcion_id');
    }

    protected static function booted(): void
    {
        static::forceDeleting(function (Inscripcion $inscripcion) {
            $tieneHistorial = $inscripcion->cambiosAcademicos()->exists()
                || $inscripcion->movimientos()->exists()
                || $inscripcion->calificaciones()->exists()
                || $inscripcion->documentos()->exists()
                || $inscripcion->ciclosEscolaresHistorial()->exists()
                || $inscripcion->preinscripcionesCiclos()->exists();

            if ($tieneHistorial) {
                throw new \RuntimeException(
                    'El alumno tiene historial académico y no puede eliminarse definitivamente. Usa archivar o dar de baja.'
                );
            }
        });
    }


    public function fuentesDocumentales()
    {
        return $this->hasMany(DocumentoAlumnoFuente::class, 'inscripcion_id');
    }

    public function organizacionesDocumentales()
    {
        return $this->hasMany(OrganizacionDocumentoAlumno::class, 'inscripcion_id');
    }

    public function documentosNoAplican()
    {
        return $this->hasMany(DocumentoAlumnoNoAplica::class, 'inscripcion_id');
    }

}
