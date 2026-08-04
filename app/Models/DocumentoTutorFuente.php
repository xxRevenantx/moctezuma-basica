<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DocumentoTutorFuente extends Model
{
    use HasFactory;

    protected $table = 'documentos_tutores_fuentes';

    protected $fillable = [
        'tutor_id',
        'documento_tutor_id',
        'disco',
        'ruta',
        'ruta_original',
        'nombre_original',
        'nombre_almacenado',
        'mime_type',
        'mime_original',
        'tamano_bytes',
        'hash_sha256',
        'paginas',
        'estado',
        'protegido',
        'subido_por',
        'metadatos',
    ];

    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
            'paginas' => 'integer',
            'protegido' => 'boolean',
            'metadatos' => 'array',
        ];
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function documentoTutor()
    {
        return $this->belongsTo(DocumentoTutor::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activo');
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
