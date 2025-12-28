<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    /** @use HasFactory<\Database\Factories\PersonaFactory> */
    use HasFactory;

    protected $table = 'personas';
    protected $fillable = [
        'titulo',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'foto',
        'curp',
        'rfc',
        'correo',
        'telefono_movil',
        'telefono_fijo',
        'fecha_nacimiento',
        'genero',
        'grado_estudios',
        'especialidad',
        'status',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio',
        'estado',
        'codigo_postal',
    ];

    public function rolesPersona()
{
    return $this->belongsToMany(\App\Models\RolePersona::class, 'persona_role', 'persona_id', 'role_persona_id')
        ->withTimestamps();
}

public function personaNiveles()
{
    return $this->hasMany(\App\Models\PersonaNivel::class);
}

public function docenteGrupos()
{
    return $this->hasMany(\App\Models\DocenteGrupo::class, 'persona_id');
}

public function gruposAsignados()
{
    return $this->belongsToMany(\App\Models\Grupo::class, 'docente_grupo', 'persona_id', 'grupo_id')
        ->withPivot(['ciclo_escolar_id', 'es_tutor'])
        ->withTimestamps();
}

public function ciclosEscolares()
{
    return $this->belongsToMany(\App\Models\CicloEscolar::class, 'docente_grupo', 'persona_id', 'ciclo_escolar_id')
        ->withPivot(['grupo_id', 'es_tutor'])
        ->withTimestamps();
}

// RELACION PERSONA ROLE
public function personaRoles()
{
    return $this->hasMany(\App\Models\PersonaRole::class, 'persona_id');
}




}
