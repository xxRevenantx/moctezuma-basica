<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoTutorPendienteVincular extends Model
{
    use HasFactory;

    protected $table = 'documentos_tutores_pendientes_vincular';

    protected $fillable = [
        'documento_alumno_id',
        'inscripcion_id',
        'tutor_sugerido_id',
        'tipo_origen_slug',
        'tipo_destino_slug',
        'estado',
        'motivo',
        'resuelto_por',
        'resuelto_at',
    ];

    protected function casts(): array
    {
        return ['resuelto_at' => 'datetime'];
    }

    public function documentoAlumno()
    {
        return $this->belongsTo(DocumentoAlumno::class);
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function tutorSugerido()
    {
        return $this->belongsTo(Tutor::class, 'tutor_sugerido_id');
    }
}
