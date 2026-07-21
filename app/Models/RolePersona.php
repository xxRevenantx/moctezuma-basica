<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePersona extends Model
{
    protected $table = 'role_personas';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'status',
        'requiere_grupo',
        'permite_grupo',
        'permite_varios_grupos',
        'es_directivo',
        'es_docente',
        'aplica_bachillerato',
    ];

    protected $casts = [
        'status' => 'boolean',
        'requiere_grupo' => 'boolean',
        'permite_grupo' => 'boolean',
        'permite_varios_grupos' => 'boolean',
        'es_directivo' => 'boolean',
        'es_docente' => 'boolean',
        'aplica_bachillerato' => 'boolean',
    ];

    public function personas()
    {
        return $this->belongsToMany(
            Persona::class,
            'persona_role',
            'role_persona_id',
            'persona_id'
        )->withTimestamps();
    }

    public function permiteAsignacionGrupo(): bool
    {
        return $this->requiere_grupo || $this->permite_grupo;
    }

    public function personaRoles()
    {
        return $this->hasMany(PersonaRole::class, 'role_persona_id');
    }

    public function detalles()
    {
        return $this->hasManyThrough(
            PersonaNivelDetalle::class,
            PersonaRole::class,
            'role_persona_id',
            'persona_role_id'
        );
    }
}
