<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoAlumnoNoAplica extends Model
{
    use HasFactory;

    protected $table = 'documentos_alumnos_no_aplica';

    protected $fillable = [
        'inscripcion_id',
        'tipo_documento_id',
        'nivel_id',
        'grado_id',
        'ciclo_escolar_id',
        'motivo',
        'activo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
