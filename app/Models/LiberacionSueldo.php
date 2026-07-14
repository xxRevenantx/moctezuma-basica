<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiberacionSueldo extends Model
{
    use SoftDeletes;

    protected $table = 'liberaciones_sueldos';

    protected $fillable = [
        'persona_nivel_id',
        'persona_id',
        'nivel_id',
        'trabajador_nombre',
        'nivel_nombre',
        'encabezado_subsecretaria',
        'encabezado_direccion',
        'director_nombre',
        'director_cargo',
        'escuela_nombre',
        'cct',
        'localidad',
        'municipio',
        'supervisor_nombre',
        'supervisor_cargo',
        'fecha_documento',
        'quincena_inicio',
        'quincena_fin',
        'anio',
        'ciclo_escolar',
        'fecha_reanudacion',
        'clave_presupuestal',
        'logo_encabezado_path',
        'archivo_pdf_path',
        'archivo_word_path',
        'creado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha_documento' => 'date',
        'fecha_reanudacion' => 'date',
        'quincena_inicio' => 'integer',
        'quincena_fin' => 'integer',
        'anio' => 'integer',
    ];

    public function personaNivel()
    {
        return $this->belongsTo(PersonaNivel::class, 'persona_nivel_id');
    }

    public function persona()
    {
        return $this->belongsTo(Persona::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
