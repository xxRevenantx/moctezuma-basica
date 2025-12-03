<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escuela extends Model
{
    /** @use HasFactory<\Database\Factories\EscuelaFactory> */
    use HasFactory;

     protected $table = 'escuela';

    protected $fillable = [
        'nombre',
        'calle',
        'no_exterior',
        'no_interior',
        'colonia',
        'codigo_postal',
        'ciudad',
        'municipio',
        'estado',
        'telefono',
        'correo',
        'pagina_web',
    ];


}
