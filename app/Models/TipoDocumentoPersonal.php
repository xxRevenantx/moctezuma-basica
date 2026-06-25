<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumentoPersonal extends Model
{
    use HasFactory;

    protected $table = 'tipos_documentos_personal';

    protected $fillable = [
        'nombre',
        'slug',
        'categoria',
        'descripcion',
        'permite_varios',
        'es_obligatorio',
        'activo',
        'orden',
    ];

    protected $casts = [
        'permite_varios' => 'boolean',
        'es_obligatorio' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function documentos()
    {
        return $this->hasMany(DocumentoPersonal::class, 'tipo_documento_personal_id');
    }
}
