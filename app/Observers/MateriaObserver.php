<?php

namespace App\Observers;

use App\Models\Materia;
use App\Support\ReglasMateriaBachillerato;

class MateriaObserver
{
    /**
     * Asigna el orden automáticamente dentro del mismo nivel, grado y semestre.
     */
    public function creating(Materia $materia): void
    {
        if (ReglasMateriaBachillerato::esBachillerato($materia->nivel_id)) {
            ReglasMateriaBachillerato::normalizarModelo($materia);
        }

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
     * Evita combinaciones incompatibles al crear o editar materias.
     */
    public function updating(Materia $materia): void
    {
        if (ReglasMateriaBachillerato::esBachillerato($materia->nivel_id)) {
            ReglasMateriaBachillerato::normalizarModelo($materia);
        }
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
