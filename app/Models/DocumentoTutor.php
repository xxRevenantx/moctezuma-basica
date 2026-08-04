<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentoTutor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'documentos_tutores';

    public const ESTADOS = [
        'pendiente',
        'recibido',
        'validado',
        'rechazado',
        'reemplazado',
        'cancelado',
    ];

    protected $fillable = [
        'tutor_id',
        'organizacion_id',
        'tipo_documento_tutor_id',
        'fecha_documento',
        'folio',
        'origen',
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

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
            'tamano_bytes' => 'integer',
            'paginas_total' => 'integer',
            'version' => 'integer',
            'es_actual' => 'boolean',
            'es_fuente' => 'boolean',
            'es_organizado' => 'boolean',
            'validado_at' => 'datetime',
        ];
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function organizacion()
    {
        return $this->belongsTo(OrganizacionDocumentoTutor::class, 'organizacion_id');
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumentoTutor::class, 'tipo_documento_tutor_id');
    }

    public function fuente()
    {
        return $this->hasOne(DocumentoTutorFuente::class, 'documento_tutor_id');
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
            ->whereNotIn('estado', ['pendiente', 'rechazado', 'reemplazado', 'cancelado']);
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
