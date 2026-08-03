<?php

namespace App\Services;

use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class PlantillaDocenteService
{
    /**
     * Verifica que la persona tenga una función docente activa y confirmada en
     * la plantilla publicada del mismo ciclo y nivel. Las plantillas cerradas
     * se aceptan únicamente para conservar la consulta histórica.
     */
    public function pertenece(int $personaId, int $cicloEscolarId, int $nivelId): bool
    {
        return PersonaNivelDetalle::query()
            ->vigenteEnCiclo($cicloEscolarId)
            ->whereHas('cabecera', fn (Builder $cabecera) => $cabecera
                ->where('persona_id', $personaId)
                ->where('nivel_id', $nivelId)
                ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                ->whereHas('persona', fn (Builder $persona) => $persona
                    ->where('status', true)
                    ->where('estado_laboral', 'activo')))
            ->whereHas('personaRole.rolePersona', fn (Builder $rol) => $rol
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
                'profesor_id' => 'El profesor debe pertenecer a la plantilla publicada del mismo ciclo y nivel con una función docente activa y confirmada.',
                'editar_profesor_id' => 'El profesor debe pertenecer a la plantilla publicada del mismo ciclo y nivel con una función docente activa y confirmada.',
            ]);
        }
    }
}
