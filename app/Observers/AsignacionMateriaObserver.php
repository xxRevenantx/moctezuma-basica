<?php

namespace App\Observers;

use App\Models\AsignacionMateria;
use App\Models\Materia;
use App\Services\SincronizadorOrdenCargaAcademicaService;

class AsignacionMateriaObserver
{
    public function creating(AsignacionMateria $asignacionMateria): void
    {
        $asignacionMateria->sincronizarContextoDesdeGrupo();
        $asignacionMateria->estado ??= AsignacionMateria::ESTADO_BORRADOR;

        $this->aplicarOrdenOficial($asignacionMateria);
    }

    public function updating(AsignacionMateria $asignacionMateria): void
    {
        if ($asignacionMateria->isDirty('grupo_id')) {
            $asignacionMateria->unsetRelation('grupo');
            $asignacionMateria->sincronizarContextoDesdeGrupo();
        }

        // El orden de la carga nunca es manual: incluso si algún flujo intenta
        // escribir asignacion_materias.orden, se vuelve a tomar materias.orden.
        if ($asignacionMateria->isDirty('materia_id') || $asignacionMateria->isDirty('orden')) {
            $this->aplicarOrdenOficial($asignacionMateria);
        }
    }

    public function deleting(AsignacionMateria $asignacionMateria): void
    {
        if ($asignacionMateria->tieneHistorial()) {
            throw new \LogicException('La carga tiene horario, calificaciones o auditoría. Debe archivarse, no eliminarse.');
        }
    }

    public function deleted(AsignacionMateria $asignacionMateria): void
    {
        // No se compacta el orden de las cargas. El número oficial pertenece al
        // catálogo de Materias y puede contener huecos si una materia no está cargada.
    }

    private function aplicarOrdenOficial(AsignacionMateria $asignacionMateria): void
    {
        $materia = Materia::query()->find($asignacionMateria->materia_id);

        if (! $materia) {
            return;
        }

        app(SincronizadorOrdenCargaAcademicaService::class)
            ->asegurarContextoSinDuplicados($materia);

        $asignacionMateria->orden = (int) $materia->orden;
    }
}
