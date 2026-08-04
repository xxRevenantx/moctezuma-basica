<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentoTutorNoAplica extends Model
{
    use HasFactory;

    protected $table = 'documentos_tutores_no_aplica';

    protected $fillable = [
        'tutor_id',
        'tipo_documento_tutor_id',
        'motivo',
        'activo',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function tutor()
    {
        return $this->belongsTo(Tutor::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumentoTutor::class, 'tipo_documento_tutor_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
