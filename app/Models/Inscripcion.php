<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inscripcion extends Model
{
    /** @use HasFactory<\Database\Factories\InscripcionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'inscripciones';

    protected $fillable = [
        // Identificación escolar
        'curp',
        'matricula',
        'folio',

        // Datos personales
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fecha_nacimiento',
        'genero',

        // Nacimiento
        'pais_nacimiento',
        'estado_nacimiento',
        'lugar_nacimiento',

        // Domicilio
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'codigo_postal',
        'municipio',
        'estado_residencia',
        'ciudad_residencia',

        // Asignación académica
        'nivel_id',
        'grado_id',
        'generacion_id',
        'grupo_id',
        'semestre_id',

        // Foto
        'foto_path',

        // Control
        'activo',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'activo' => 'boolean',
    ];

    /* =========================
     * Relaciones
     * ========================= */

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function grado()
    {
        return $this->belongsTo(Grado::class);
    }

    public function generacion()
    {
        return $this->belongsTo(Generacion::class);
    }

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }


}
