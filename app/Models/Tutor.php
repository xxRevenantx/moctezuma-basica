<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tutor extends Model
{
    /** @use HasFactory<\Database\Factories\TutorFactory> */
    use HasFactory;

    protected $table = 'tutores';

    protected $fillable = [
        'curp',
        'parentesco',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'genero',
        'fecha_nacimiento',
        'ciudad_nacimiento',
        'estado_nacimiento',
        'municipio_nacimiento',
        'calle',
        'colonia',
        'ciudad',
        'municipio',
        'estado',
        'numero',
        'codigo_postal',
        'telefono_casa',
        'telefono_celular',
        'correo_electronico',
    ];

}
