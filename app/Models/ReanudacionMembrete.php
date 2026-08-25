<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReanudacionMembrete extends Model
{
    protected $table = 'reanudacion_membretes';

    protected $fillable = [
        'ciclo_escolar_id',
        'nivel_id',
        'version',
        'nombre_original',
        'archivo_path',
        'mime_type',
        'size_bytes',
        'margen_superior_mm',
        'activo',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'version' => 'integer',
        'size_bytes' => 'integer',
        'margen_superior_mm' => 'float',
        'activo' => 'boolean',
    ];

    public function cicloEscolar()
    {
        return $this->belongsTo(CicloEscolar::class, 'ciclo_escolar_id');
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
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
