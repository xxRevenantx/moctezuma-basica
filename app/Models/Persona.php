<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Throwable;

class Persona extends Model
{
    use HasFactory, SoftDeletes;

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
        'estado_laboral',
        'calle',
        'numero_exterior',
        'numero_interior',
        'colonia',
        'municipio',
        'estado',
        'codigo_postal',
    ];


    protected $casts = [
        'status' => 'boolean',
    ];

    public function getFotoRutaAttribute(): ?string
    {
        if (blank($this->foto)) {
            return null;
        }

        $foto = ltrim((string) $this->foto, '/');

        return str_starts_with($foto, 'personal/') ? $foto : 'personal/' . $foto;
    }

    public function getFotoExisteAttribute(): bool
    {
        if (! $this->foto_ruta) {
            return false;
        }

        try {
            return Storage::disk((string) config('filesystems.fotos_disk', 'public'))->exists($this->foto_ruta);
        } catch (Throwable) {
            return false;
        }
    }

    public function getFotoUrlAttribute(): ?string
    {
        if (! $this->foto_existe) {
            return null;
        }

        try {
            $disco = Storage::disk((string) config('filesystems.fotos_disk', 'public'));

            if (config('filesystems.fotos_disk', 'public') !== 'public') {
                try {
                    return $disco->temporaryUrl($this->foto_ruta, now()->addMinutes(20));
                } catch (Throwable) {
                    // Algunos discos públicos o adaptadores no implementan URL temporal.
                }
            }

            return $disco->url($this->foto_ruta);
        } catch (Throwable) {
            return null;
        }
    }

    public function getFotoDataUriAttribute(): ?string
    {
        if (! $this->foto_existe) {
            return null;
        }

        try {
            $contenido = Storage::disk((string) config('filesystems.fotos_disk', 'public'))->get($this->foto_ruta);
            $extension = strtolower(pathinfo($this->foto_ruta, PATHINFO_EXTENSION));
            $mime = match ($extension) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
                default => 'application/octet-stream',
            };

            return 'data:' . $mime . ';base64,' . base64_encode($contenido);
        } catch (Throwable) {
            return null;
        }
    }

    public function getInicialesAttribute(): string
    {
        $primera = mb_substr(trim((string) $this->nombre), 0, 1);
        $segunda = mb_substr(trim((string) $this->apellido_paterno), 0, 1);

        return mb_strtoupper($primera . $segunda);
    }

    public function rolesPersona()
    {
        return $this->belongsToMany(
            RolePersona::class,
            'persona_role',
            'persona_id',
            'role_persona_id'
        )->withTimestamps();
    }

    public function personaRoles()
    {
        return $this->hasMany(PersonaRole::class, 'persona_id');
    }

    public function personaNiveles()
    {
        return $this->hasMany(PersonaNivel::class, 'persona_id');
    }

    public function asignacionMaterias()
    {
        return $this->hasMany(AsignacionMateria::class, 'profesor_id');
    }

    public function tallerSesiones()
    {
        return $this->hasMany(TallerSesion::class, 'profesor_id');
    }

    public function docenteGrupos()
    {
        return $this->hasMany(DocenteGrupo::class, 'persona_id');
    }

    public function gruposAsignados()
    {
        return $this->belongsToMany(Grupo::class, 'docente_grupo', 'persona_id', 'grupo_id')
            ->withPivot(['ciclo_escolar_id', 'es_tutor'])
            ->withTimestamps();
    }

    public function documentosPersonal()
    {
        return $this->hasMany(DocumentoPersonal::class, 'persona_id');
    }

    public function movimientosLaborales()
    {
        return $this->hasMany(MovimientoPersonal::class, 'persona_id');
    }

    public function ciclosEscolares()
    {
        return $this->belongsToMany(CicloEscolar::class, 'docente_grupo', 'persona_id', 'ciclo_escolar_id')
            ->withPivot(['grupo_id', 'es_tutor'])
            ->withTimestamps();
    }
}
