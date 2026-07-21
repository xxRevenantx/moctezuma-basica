<?php

namespace App\Services;

use App\Models\PersonaNivelDetalle;
use App\Models\PlantillaPersonalNivel;
use Illuminate\Validation\ValidationException;

class PlantillaDocenteService
{
    public function pertenece(int $personaId, int $cicloEscolarId, int $nivelId): bool
    {
        $plantillaIds = PlantillaPersonalNivel::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->whereIn('estado', [PlantillaPersonalNivel::ESTADO_PUBLICADA, PlantillaPersonalNivel::ESTADO_CERRADA])
            ->pluck('id');

        if ($plantillaIds->isEmpty()) {
            return false;
        }

        return PersonaNivelDetalle::query()
            ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
            ->where('confirmado', true)
            ->whereNull('archivado_at')
            ->whereHas('cicloAsignacion', fn ($q) => $q
                ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                ->where('estado', 'activo')
                ->whereHas('personaNivel', fn ($pn) => $pn
                    ->where('persona_id', $personaId)
                    ->where('nivel_id', $nivelId)))
            ->whereHas('personaRole.rolePersona', fn ($rol) => $rol
                ->where('status', true)
                ->where('es_docente', true))
            ->exists();
    }

    public function validar(?int $personaId, int $cicloEscolarId, int $nivelId): void
    {
        if (!$personaId) {
            return;
        }

        if (!$this->pertenece($personaId, $cicloEscolarId, $nivelId)) {
            throw ValidationException::withMessages([
                'profesor_id' => 'El profesor debe pertenecer a la plantilla publicada del mismo ciclo y nivel con una función docente activa.',
                'editar_profesor_id' => 'El profesor debe pertenecer a la plantilla publicada del mismo ciclo y nivel con una función docente activa.',
            ]);
        }
    }
}
