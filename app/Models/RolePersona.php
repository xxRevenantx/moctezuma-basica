<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePersona extends Model
{
    public const SLUG_MAESTRO_FRENTE_GRUPO = 'maestro_frente_a_grupo';

    /**
     * Roles que, por su propia naturaleza, pueden representar al titular
     * responsable de un grupo en preescolar y primaria.
     *
     * Los slugs heredados se conservan para instalaciones anteriores.
     */
    public const SLUGS_TITULAR_GRUPO = [
        'docente_titular',
        self::SLUG_MAESTRO_FRENTE_GRUPO,
        'docente_grupo',
        'director_con_grupo',
    ];

    public const NIVELES_TITULAR_AUTOMATICO = [
        'preescolar',
        'primaria',
    ];

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

    /**
     * Determina si la función debe registrarse automáticamente como titular
     * principal cuando está ligada a un grupo de preescolar o primaria.
     */
    public function esTitularAutomaticoEnNivel(?string $nivelSlug): bool
    {
        return in_array((string) $nivelSlug, self::NIVELES_TITULAR_AUTOMATICO, true)
            && in_array((string) $this->slug, self::SLUGS_TITULAR_GRUPO, true);
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
