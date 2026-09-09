<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfesorDocumentoPlantilla extends Model
{
    protected $table = 'profesor_documento_plantillas';

    protected $fillable = [
        'nivel_id',
        'tipo_documento',
        'nombre',
        'version',
        'archivo_path',
        'nombre_original',
        'mime_type',
        'size_bytes',
        'activo',
        'configuracion',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'version' => 'integer',
        'size_bytes' => 'integer',
        'activo' => 'boolean',
        'configuracion' => 'array',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class, 'nivel_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
