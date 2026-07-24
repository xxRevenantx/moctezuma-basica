<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentoAlumno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos_alumnos';

    public const ESTADOS = [
        'pendiente',
        'recibido',
        'validado',
        'rechazado',
        'reemplazado',
        'emitida',
        'cancelada',
    ];

    protected $fillable = [
        'inscripcion_id',
        'organizacion_id',
        'tipo_documento_id',
        'nivel_id',
        'grado_id',
        'grupo_id',
        'ciclo_escolar_id',
        'fecha_documento',
        'folio',
        'origen',
        'tipo_movimiento',
        'motivo',
        'disco',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano_bytes',
        'paginas_total',
        'hash_sha256',
        'version',
        'es_actual',
        'es_fuente',
        'es_organizado',
        'estado',
        'observaciones',
        'subido_por',
        'validado_por',
        'validado_at',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'paginas_total' => 'integer',
        'version' => 'integer',
        'es_actual' => 'boolean',
        'es_fuente' => 'boolean',
        'es_organizado' => 'boolean',
        'validado_at' => 'datetime',
        'fecha_documento' => 'date',
    ];


    public function organizacion()
    {
        return $this->belongsTo(OrganizacionDocumentoAlumno::class, 'organizacion_id');
    }

    public function fuente()
    {
        return $this->hasOne(DocumentoAlumnoFuente::class, 'documento_alumno_id');
    }

    public function getEsOrganizableAttribute(): bool
    {
        return ! in_array((string) $this->origen, config('expedientes_organizador.protected_origins', ['generado']), true);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
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

    public function usuarioQueSubio()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function usuarioQueValido()
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function scopeActuales($query)
    {
        return $query->where('es_actual', true);
    }

    public function scopeDisponibles($query)
    {
        return $query->where('es_actual', true)
            ->where('es_fuente', false)
            ->whereNotIn('estado', ['pendiente', 'rechazado', 'reemplazado', 'cancelada']);
    }


    public function getArchivoExisteAttribute(): bool
    {
        if (blank($this->disco) || blank($this->ruta)) {
            return false;
        }

        try {
            return Storage::disk($this->disco)->exists($this->ruta);
        } catch (Throwable) {
            return false;
        }
    }

    public function getExtensionAttribute(): string
    {
        $extension = strtolower(pathinfo((string) $this->nombre_original, PATHINFO_EXTENSION));

        if ($extension !== '') {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }

        return match (strtolower((string) $this->mime_type)) {
            'application/pdf', 'application/x-pdf' => 'pdf',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    public function getEsPdfAttribute(): bool
    {
        return in_array(strtolower((string) $this->mime_type), ['application/pdf', 'application/x-pdf'], true)
            || $this->extension === 'pdf';
    }

    public function getEsImagenAttribute(): bool
    {
        return str_starts_with(strtolower((string) $this->mime_type), 'image/')
            || in_array($this->extension, ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    public function getTamanoLegibleAttribute(): string
    {
        $bytes = max((int) $this->tamano_bytes, 0);

        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 1) . ' MB';
    }
}
