<?php

namespace App\Policies;

use App\Models\User;
use App\Models\periodos_basico;
use Illuminate\Auth\Access\Response;

class PeriodosBasicoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccess('academico.consultar');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, periodos_basico $periodosBasico): bool
    {
        return $user->canAccess('academico.consultar');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccess('academico.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, periodos_basico $periodosBasico): bool
    {
        return $user->canAccess('academico.editar');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, periodos_basico $periodosBasico): bool
    {
        return $user->canAccess('academico.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, periodos_basico $periodosBasico): bool
    {
        return $user->canAccess('academico.editar');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, periodos_basico $periodosBasico): bool
    {
        return $user->canAccess('academico.eliminar');
    }
}
