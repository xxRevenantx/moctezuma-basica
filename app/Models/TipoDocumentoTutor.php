<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumentoTutor extends Model
{
    use HasFactory;

    protected $table = 'tipos_documentos_tutores';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'es_obligatorio',
        'obligatorio_parentescos',
        'activo',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'es_obligatorio' => 'boolean',
            'obligatorio_parentescos' => 'array',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function documentos()
    {
        return $this->hasMany(DocumentoTutor::class, 'tipo_documento_tutor_id');
    }
}
