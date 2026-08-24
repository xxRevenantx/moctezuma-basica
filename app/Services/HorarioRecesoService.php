<?php

namespace App\Services;

use App\Models\Horario;
use Illuminate\Support\Collection;

class HorarioRecesoService
{
    public function esReceso(Horario $horario): bool
    {
        return !$horario->taller_sesion_id
            && (int) ($horario->asignacionMateria?->materia?->receso ?? 0) === 1;
    }

    /**
     * Devuelve las horas de receso detectadas en el horario normal de cada grupo.
     *
     * @return Collection<int, Collection<int, int>>
     */
    public function porGrupo(
        int $nivelId,
        int $cicloEscolarId,
        Collection $grupos,
        Collection $horas,
    ): Collection {
        $grupoIds = $grupos->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        $horaIds = $horas->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();

        if (empty($grupoIds) || empty($horaIds)) {
            return collect();
        }

        return Horario::query()
            ->with('asignacionMateria.materia:id,receso')
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('grupo_id', $grupoIds)
            ->whereIn('hora_id', $horaIds)
            ->get(['id', 'grupo_id', 'hora_id', 'asignacion_materia_id', 'taller_sesion_id'])
            ->filter(fn (Horario $horario) => $this->esReceso($horario))
            ->groupBy(fn (Horario $horario) => (int) $horario->grupo_id)
            ->map(fn (Collection $horariosGrupo) => $horariosGrupo
                ->pluck('hora_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values());
    }

    /**
     * Obtiene la configuración de receso predominante del nivel a partir de los
     * horarios normales ya capturados. Los grupos sin ningún horario todavía no
     * participan en el cálculo para no convertir la ausencia de datos en una
     * configuración de receso falsa.
     *
     * @return array{hora_ids:array<int,int>,inconsistente:bool,variantes:int,grupos_evaluados:int}
     */
    public function oficialNivel(
        int $nivelId,
        int $cicloEscolarId,
        Collection $grupos,
        Collection $horas,
    ): array {
        $grupoIds = $grupos->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();
        $horaIds = $horas->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();

        if (empty($grupoIds) || empty($horaIds)) {
            return $this->resultadoVacio();
        }

        $horarios = Horario::query()
            ->with('asignacionMateria.materia:id,receso')
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('grupo_id', $grupoIds)
            ->whereIn('hora_id', $horaIds)
            ->get(['id', 'grupo_id', 'hora_id', 'asignacion_materia_id', 'taller_sesion_id']);

        if ($horarios->isEmpty()) {
            return $this->resultadoVacio();
        }

        $firmas = $horarios
            ->groupBy(fn (Horario $horario) => (int) $horario->grupo_id)
            ->map(function (Collection $registrosGrupo) {
                $ids = $registrosGrupo
                    ->filter(fn (Horario $horario) => $this->esReceso($horario))
                    ->pluck('hora_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();

                return [
                    'firma' => $ids->implode(','),
                    'hora_ids' => $ids->all(),
                ];
            })
            ->values();

        if ($firmas->isEmpty()) {
            return $this->resultadoVacio();
        }

        $variantes = $firmas
            ->groupBy('firma')
            ->map(function (Collection $items, string $firma) {
                return [
                    'firma' => $firma,
                    'hora_ids' => $items->first()['hora_ids'] ?? [],
                    'total' => $items->count(),
                ];
            })
            ->sortByDesc(fn (array $item) => sprintf(
                '%08d-%d',
                (int) $item['total'],
                $item['firma'] !== '' ? 1 : 0,
            ))
            ->values();

        $predominante = $variantes->first();

        return [
            'hora_ids' => array_values(array_map('intval', $predominante['hora_ids'] ?? [])),
            'inconsistente' => $variantes->count() > 1,
            'variantes' => $variantes->count(),
            'grupos_evaluados' => $firmas->count(),
        ];
    }

    /** @return array{hora_ids:array<int,int>,inconsistente:bool,variantes:int,grupos_evaluados:int} */
    private function resultadoVacio(): array
    {
        return [
            'hora_ids' => [],
            'inconsistente' => false,
            'variantes' => 0,
            'grupos_evaluados' => 0,
        ];
    }
}
