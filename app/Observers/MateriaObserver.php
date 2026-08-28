<?php

namespace App\Observers;

use App\Models\Materia;
use App\Services\SincronizadorOrdenCargaAcademicaService;
use App\Support\ReglasMateriaBachillerato;
use DomainException;

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
     * Evita combinaciones incompatibles y órdenes oficiales ambiguos.
     */
    public function updating(Materia $materia): void
    {
        if (ReglasMateriaBachillerato::esBachillerato($materia->nivel_id)) {
            ReglasMateriaBachillerato::normalizarModelo($materia);
        }

        if ($materia->isDirty(['nivel_id', 'grado_id', 'semestre_id', 'orden'])) {
            app(SincronizadorOrdenCargaAcademicaService::class)
                ->asegurarContextoSinDuplicados($materia);
        }
    }

    /**
     * Si cambia el orden oficial, se propaga de inmediato a todas las cargas
     * existentes de esa materia, sin importar ciclo, grupo o generación.
     */
    public function updated(Materia $materia): void
    {
        if (! $materia->wasChanged(['nivel_id', 'grado_id', 'semestre_id', 'orden'])) {
            return;
        }

        app(SincronizadorOrdenCargaAcademicaService::class)
            ->sincronizarMateria($materia);
    }

    /**
     * Reacomoda el catálogo cuando se elimina una materia del mismo contexto.
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

        try {
            app(SincronizadorOrdenCargaAcademicaService::class)
                ->sincronizarContextoCatalogo(
                    (int) $materia->nivel_id,
                    (int) $materia->grado_id,
                    $materia->semestre_id ? (int) $materia->semestre_id : null,
                );
        } catch (DomainException $e) {
            // No se fuerza un desempate automático. El diagnóstico aparecerá
            // en Asignación de materias para que el catálogo se corrija primero.
            report($e);
        }
    }
}
