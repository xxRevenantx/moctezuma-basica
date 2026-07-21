<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Inscripcion;

class PrepararCicloEscolarService
{
    public function __construct(
        private readonly PreparacionEstructuraCicloService $estructura,
        private readonly PlantillaPersonalCicloService $plantillaPersonal,
    ) {}

    /**
     * Prepara la estructura del siguiente ciclo sin depender de la tabla eliminada
     * trayectorias_academicas y sin mover alumnos automáticamente.
     *
     * Los alumnos quedan como pendientes de promoción/cierre para que el usuario
     * confirme cada resultado desde los flujos actuales de promoción y cierre.
     */
    public function ejecutar(
        CicloEscolar $cicloOrigen,
        CicloEscolar $cicloDestino,
        ?int $usuarioId = null
    ): array {
        if ($cicloOrigen->is($cicloDestino)) {
            return $this->resumenVacio(['El ciclo origen y destino son el mismo.']);
        }

        if ((int) $cicloDestino->inicio_anio !== (int) $cicloOrigen->inicio_anio + 1) {
            return $this->resumenVacio([
                'El ciclo destino debe ser consecutivo. Para capturas históricas utiliza los módulos de promoción o cambio académico.',
            ]);
        }

        $estructura = $this->estructura->preparar($cicloDestino, $usuarioId);
        $plantilla = $this->plantillaPersonal->prepararCiclo((int) $cicloDestino->id);

        $pendientes = Inscripcion::query()
            ->where('ciclo_escolar_id', $cicloOrigen->id)
            ->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])
            ->where('activo', true)
            ->count();

        return [
            'procesados' => 0,
            'promovidos' => 0,
            'no_promovidos' => 0,
            'egresados' => 0,
            'pendientes_cierre' => $pendientes,
            'existentes' => 0,
            'omitidos' => 0,
            'generaciones_creadas' => (int) ($estructura['generaciones_creadas'] ?? 0),
            'grupos_creados' => (int) ($estructura['grupos_nuevo_ingreso'] ?? 0)
                + (int) ($estructura['grupos_continuidad'] ?? 0)
                + (int) ($estructura['grupos_no_promovidos'] ?? 0),
            'plantillas_creadas' => (int) ($plantilla['creadas'] ?? 0),
            'asignaciones_personal_copiadas' => (int) ($plantilla['copiadas'] ?? 0),
            'errores' => array_values(array_unique(array_merge(
                $estructura['advertencias'] ?? [],
                ['La preparación no promueve alumnos automáticamente. Hay '.$pendientes.' alumno(s) pendientes de confirmar en promoción/cierre.']
            ))),
        ];
    }

    private function resumenVacio(array $errores = []): array
    {
        return [
            'procesados' => 0,
            'promovidos' => 0,
            'no_promovidos' => 0,
            'egresados' => 0,
            'pendientes_cierre' => 0,
            'existentes' => 0,
            'omitidos' => 0,
            'generaciones_creadas' => 0,
            'grupos_creados' => 0,
            'plantillas_creadas' => 0,
            'asignaciones_personal_copiadas' => 0,
            'errores' => $errores,
        ];
    }
}
