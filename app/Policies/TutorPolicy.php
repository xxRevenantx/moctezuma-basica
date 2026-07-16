<?php

namespace App\Policies;

use App\Models\Tutor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TutorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccess('alumnos.consultar');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Tutor $tutor): bool
    {
        return $user->canAccess('alumnos.consultar');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccess('alumnos.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Tutor $tutor): bool
    {
        return $user->canAccess('alumnos.editar');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Tutor $tutor): bool
    {
        return $user->canAccess('alumnos.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Tutor $tutor): bool
    {
        return $user->canAccess('alumnos.editar');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Tutor $tutor): bool
    {
        return $user->canAccess('alumnos.eliminar');
    }
}
