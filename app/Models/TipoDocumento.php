<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'tipos_documentos';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'es_general',
        'requiere_nivel',
        'es_obligatorio',
        'activo',
        'orden',
    ];

    protected $casts = [
        'es_general' => 'boolean',
        'requiere_nivel' => 'boolean',
        'es_obligatorio' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    public function documentos()
    {
        return $this->hasMany(DocumentoAlumno::class, 'tipo_documento_id');
    }
}
