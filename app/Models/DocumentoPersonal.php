<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentoPersonal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos_personal';

    public const ESTADOS = [
        'pendiente',
        'recibido',
        'validado',
        'rechazado',
        'reemplazado',
    ];

    protected $fillable = [
        'persona_id',
        'tipo_documento_personal_id',
        'serie_uuid',
        'subtipo_identificacion',
        'nombre_estudio',
        'institucion',
        'nivel_academico',
        'numero_cedula',
        'disco',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano_bytes',
        'hash_sha256',
        'version',
        'es_actual',
        'estado',
        'observaciones',
        'subido_por',
        'validado_por',
        'validado_at',
    ];

    protected $casts = [
        'tamano_bytes' => 'integer',
        'version' => 'integer',
        'es_actual' => 'boolean',
        'validado_at' => 'datetime',
    ];

    public function persona()
    {
        return $this->belongsTo(Persona::class, 'persona_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumentoPersonal::class, 'tipo_documento_personal_id');
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
            ->whereIn('estado', ['recibido', 'validado']);
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

    public function getEtiquetaDetalleAttribute(): string
    {
        if ($this->tipoDocumento?->slug === 'identificacion-oficial') {
            return match ($this->subtipo_identificacion) {
                'ine' => 'INE',
                'pasaporte' => 'Pasaporte',
                'cedula' => 'Cédula profesional',
                'otra' => 'Otra identificación',
                default => 'Identificación oficial',
            };
        }

        if ($this->tipoDocumento?->slug === 'cedula-profesional') {
            return $this->nombre_estudio
                ?: ($this->numero_cedula ? 'Cédula ' . $this->numero_cedula : 'Cédula profesional');
        }

        if ($this->tipoDocumento?->slug === 'titulo-profesional') {
            return $this->nombre_estudio ?: 'Título profesional';
        }

        return $this->tipoDocumento?->nombre ?? 'Documento';
    }
}
