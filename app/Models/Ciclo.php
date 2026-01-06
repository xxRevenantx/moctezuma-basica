<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciclo extends Model
{
    /** @use HasFactory<\Database\Factories\CicloFactory> */
    use HasFactory;
    protected $table = 'ciclos';

    protected $fillable = [
        'ciclo',
    ];

    // Relación con inscripciones
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }
}
