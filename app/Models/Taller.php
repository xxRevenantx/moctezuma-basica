<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Taller extends Model
{
    use HasFactory;

    protected $table = 'talleres';
    protected $fillable = [
        'nivel_id',
        'nombre',
        'slug',
        'clave',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'nivel_id' => 'integer',
        'activo' => 'boolean',
    ];

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function sesiones()
    {
        return $this->hasMany(TallerSesion::class);
    }
}
