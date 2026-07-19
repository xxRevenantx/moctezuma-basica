<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'photo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin' => 'boolean',
            'permisos' => 'array',
            'activo' => 'boolean',
            'ultimo_acceso_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }


    public function canAccess(string $permission): bool
    {
        if (! ($this->activo ?? true)) {
            return false;
        }

        if ($this->is_admin) {
            return true;
        }

        $overrides = collect($this->permisos ?? [])->filter(fn ($value) => is_string($value));

        if ($overrides->contains(fn (string $value): bool => str_starts_with($value, '!') && Str::is(ltrim($value, '!'), $permission))) {
            return false;
        }

        if ($overrides->contains(fn (string $value): bool => ! str_starts_with($value, '!') && Str::is($value, $permission))) {
            return true;
        }

        $role = (string) ($this->rol_sistema ?: 'consulta');
        $permissions = config("system_permissions.roles.{$role}.permissions", []);

        return collect($permissions)->contains(
            fn (string $allowed): bool => $allowed === '*' || Str::is($allowed, $permission)
        );
    }

    public function roleLabel(): string
    {
        if ($this->is_admin) {
            return 'Administrador general';
        }

        return (string) config(
            'system_permissions.roles.'.($this->rol_sistema ?: 'consulta').'.label',
            'Usuario'
        );
    }

    // Relaciones
    public function bitacoraCalificaciones()
    {
        return $this->hasMany(BitacoraCalificacion::class);
    }

    public function documentosSubidos()
    {
        return $this->hasMany(DocumentoAlumno::class, 'subido_por');
    }

    public function documentosValidados()
    {
        return $this->hasMany(DocumentoAlumno::class, 'validado_por');
    }

    public function documentosPersonalSubidos()
    {
        return $this->hasMany(DocumentoPersonal::class, 'subido_por');
    }

    public function documentosPersonalValidados()
    {
        return $this->hasMany(DocumentoPersonal::class, 'validado_por');
    }

    public function movimientosPersonalRegistrados()
    {
        return $this->hasMany(MovimientoPersonal::class, 'registrado_por');
    }

    // Otros métodos relacionados con el usuario pueden ir aquí
}
