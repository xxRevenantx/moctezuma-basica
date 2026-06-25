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
        'photo'
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
