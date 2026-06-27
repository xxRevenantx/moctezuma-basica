<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Generacion extends Model
{
    /** @use HasFactory<\Database\Factories\GeneracionFactory> */
    use HasFactory;

    protected $table = "generaciones";


    protected $fillable = [
        'nivel_id',
        'anio_ingreso',
        'anio_egreso',
        'status',
        'cerrada_at',
        'cerrada_por',
        'observaciones',
    ];

    protected $casts = [
        'status' => 'boolean',
        'cerrada_at' => 'datetime',
    ];

    // Relaciones y métodos adicionales pueden ser añadidos aquí
    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }

    // RELACIONES CON GRUPOS
    public function grupos()
    {
        return $this->hasMany(Grupo::class);
    }

    // RELACIONES CON PERIODOS
    public function periodosBachillerato()
    {
        return $this->hasMany(Periodos::class);
    }

    // RELACIONES CON INSCRIPCIONES
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    // RELACIONES CON HORARIOS
    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    // RELACIONES CON CALIFICACIONES
    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class);
    }

    // RELACIONES CON BITACORA DE CALIFICACIONES
    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class, 'generacion_id');
    }

    public function trayectoriasAcademicas()
    {
        return $this->hasMany(TrayectoriaAcademica::class, 'generacion_id');
    }

    public function usuarioQueCerro()
    {
        return $this->belongsTo(User::class, 'cerrada_por');
    }
}
