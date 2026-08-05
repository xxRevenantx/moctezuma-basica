<?php

namespace App\Services;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InstitutionalTeacherAccountService
{
    /** @return array{user:User,password:string,created:bool} */
    public function createFor(Persona $persona): array
    {
        $this->validateTeacher($persona);

        $existing = User::query()->where('persona_id', $persona->id)->first();
        if ($existing) {
            return ['user' => $existing, 'password' => '', 'created' => false];
        }

        $password = $this->generatePassword();

        $user = DB::transaction(function () use ($persona, $password): User {
            return User::query()->create([
                'persona_id' => $persona->id,
                'name' => $this->fullName($persona),
                'email' => $this->uniqueInstitutionalEmail($persona),
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'is_admin' => false,
                'rol_sistema' => 'profesor',
                'permisos' => null,
                'activo' => $this->personaIsActive($persona),
                'must_change_password' => (bool) config('teacher_accounts.require_password_change', true),
                'temporary_password_issued_at' => now(),
            ]);
        });

        return ['user' => $user, 'password' => $password, 'created' => true];
    }

    /** @return array{user:User,password:string} */
    public function resetTemporaryPassword(User $user): array
    {
        abort_unless($user->isProfessor(), 422, 'La cuenta seleccionada no corresponde a un profesor.');

        $password = $this->generatePassword();
        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
            'temporary_password_issued_at' => now(),
        ])->save();

        return ['user' => $user->fresh(), 'password' => $password];
    }

    public function syncStatus(Persona $persona): void
    {
        $persona->usuario?->forceFill([
            'activo' => $this->personaIsActive($persona),
        ])->save();
    }

    public function isEligible(Persona $persona): bool
    {
        return $persona->rolesPersona()->where('es_docente', true)->exists()
            || $persona->asignacionMaterias()->exists()
            || $persona->tallerSesiones()->exists()
            || $persona->docenteGrupos()->where('status', true)->exists();
    }

    public function assertEligible(Persona $persona, string $field = 'newTeacherPersonaId'): void
    {
        $this->validateTeacher($persona, $field);
    }

    public function isInstitutionalEmail(?string $email): bool
    {
        $domain = trim((string) config('teacher_accounts.domain', 'profesor.moctezuma.local'), '@ ');

        return $email !== null
            && Str::endsWith(Str::lower(trim($email)), '@'.Str::lower($domain));
    }

    private function validateTeacher(Persona $persona, string $field = 'newTeacherPersonaId'): void
    {
        if (! $this->personaIsActive($persona)) {
            throw ValidationException::withMessages([
                $field => 'La persona debe encontrarse activa para habilitar una cuenta docente.',
            ]);
        }

        if (! $this->isEligible($persona)) {
            throw ValidationException::withMessages([
                $field => 'La persona seleccionada no tiene una función docente ni materias asignadas.',
            ]);
        }

        $curp = mb_strtoupper(trim((string) $persona->curp));
        if (! preg_match('/^[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d$/', $curp)) {
            throw ValidationException::withMessages([
                $field => 'El profesor debe tener una CURP válida de 18 caracteres antes de crear su acceso.',
            ]);
        }
    }

    private function uniqueInstitutionalEmail(Persona $persona): string
    {
        $base = collect([
            Str::of((string) $persona->nombre)->squish()->explode(' ')->first(),
            $persona->apellido_paterno,
        ])
            ->filter()
            ->map(fn ($part) => Str::slug(Str::ascii((string) $part), '.'))
            ->filter()
            ->implode('.');

        $base = $base !== '' ? Str::lower($base) : 'docente'.$persona->id;
        $domain = trim((string) config('teacher_accounts.domain', 'profesor.moctezuma.local'), '@ ');

        $candidate = "{$base}@{$domain}";
        $suffix = 2;

        while (User::query()->where('email', $candidate)->exists()) {
            $candidate = "{$base}{$suffix}@{$domain}";
            $suffix++;
        }

        return $candidate;
    }

    private function generatePassword(): string
    {
        $length = max(12, (int) config('teacher_accounts.password_length', 14));
        $required = [
            chr(random_int(97, 122)),
            chr(random_int(65, 90)),
            (string) random_int(0, 9),
            collect(['!', '@', '#', '$', '%', '&', '*'])->random(),
        ];

        $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $remaining = collect(range(1, max(0, $length - count($required))))
            ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
            ->implode('');

        return collect(str_split(implode('', $required).$remaining))
            ->shuffle()
            ->implode('');
    }

    private function fullName(Persona $persona): string
    {
        return collect([
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->map(fn ($part) => trim((string) $part))->implode(' ');
    }

    private function personaIsActive(Persona $persona): bool
    {
        return (bool) $persona->status
            && ! in_array(mb_strtolower((string) $persona->estado_laboral), ['baja', 'inactivo', 'suspendido'], true);
    }
}
