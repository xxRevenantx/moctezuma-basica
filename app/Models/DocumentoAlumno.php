<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoAlumno extends Model
{
    use HasFactory;

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
        'fecha_documento' => 'date',
    ];

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
            ->whereNotIn('estado', ['pendiente', 'rechazado', 'reemplazado', 'cancelada']);
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
