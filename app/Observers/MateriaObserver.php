<?php

namespace App\Observers;

use App\Models\Materia;

class MateriaObserver
{
    /**
     * Asigna el orden automáticamente dentro del mismo nivel, grado y semestre.
     */
    public function creating(Materia $materia): void
    {
        $ultimoOrden = Materia::query()
            ->where('nivel_id', $materia->nivel_id)
            ->where('grado_id', $materia->grado_id)
            ->when(
                $materia->semestre_id,
                fn($query) => $query->where('semestre_id', $materia->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->max('orden');

        $materia->orden = ((int) $ultimoOrden) + 1;
    }

    /**
     * Reacomoda el orden cuando se elimina una materia del mismo contexto.
     */
    public function deleted(Materia $materia): void
    {
        Materia::query()
            ->where('nivel_id', $materia->nivel_id)
            ->where('grado_id', $materia->grado_id)
            ->when(
                $materia->semestre_id,
                fn($query) => $query->where('semestre_id', $materia->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->where('orden', '>', $materia->orden)
            ->decrement('orden');
    }
}
