<?php

namespace App\Policies;

use App\Models\PersonaNivel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PersonaNivelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->canAccess('personal.consultar');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PersonaNivel $personaNivel): bool
    {
        return $user->canAccess('personal.consultar');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->canAccess('personal.crear');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PersonaNivel $personaNivel): bool
    {
        return $user->canAccess('personal.editar');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PersonaNivel $personaNivel): bool
    {
        return $user->canAccess('personal.eliminar');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PersonaNivel $personaNivel): bool
    {
        return $user->canAccess('personal.editar');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PersonaNivel $personaNivel): bool
    {
        return $user->canAccess('personal.eliminar');
    }
}
