<?php

namespace App\Services;

use App\Models\CicloEscolar;
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
     * Si un grupo todavía no tiene filas de receso en el ciclo solicitado, usa
     * como respaldo el receso oficial del nivel. Esto mantiene los formatos
     * individuales consistentes sin depender de horas escritas a mano.
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
        $horaDestinoPorClave = $this->horaDestinoPorClave($horas);

        if (empty($grupoIds) || $horaDestinoPorClave->isEmpty()) {
            return collect();
        }

        $detectados = Horario::query()
            ->with([
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'asignacionMateria.materia:id,receso',
            ])
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereIn('grupo_id', $grupoIds)
            ->get(['id', 'grupo_id', 'hora_id', 'asignacion_materia_id', 'taller_sesion_id'])
            ->groupBy(fn (Horario $horario) => (int) $horario->grupo_id)
            ->map(function (Collection $horariosGrupo) use ($horaDestinoPorClave) {
                return $horariosGrupo
                    ->filter(fn (Horario $horario) => $this->esReceso($horario))
                    ->map(function (Horario $horario) use ($horaDestinoPorClave) {
                        $clave = $this->claveHora($horario->hora);

                        return $clave !== null
                            ? $horaDestinoPorClave->get($clave)
                            : null;
                    })
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values();
            });

        $oficial = $this->oficialNivel(
            $nivelId,
            $cicloEscolarId,
            $grupos,
            $horas,
        );

        $recesoOficial = collect($oficial['hora_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        return collect($grupoIds)->mapWithKeys(function (int $grupoId) use ($detectados, $recesoOficial) {
            $recesoGrupo = collect($detectados->get($grupoId, []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->sort()
                ->values();

            return [
                $grupoId => $recesoGrupo->isNotEmpty()
                    ? $recesoGrupo
                    : $recesoOficial->values(),
            ];
        });
    }

    /**
     * Obtiene la configuración de receso oficial/predominante del nivel.
     *
     * Prioridad:
     * 1. Recesos realmente capturados en el ciclo solicitado.
     * 2. Si el ciclo aún no tiene recesos, la última configuración disponible
     *    de un ciclo escolar anterior del mismo nivel.
     *
     * La comparación se hace por rango de hora y no por un horario fijo en el
     * código. Los grupos sin receso se consideran una ausencia de configuración,
     * no una variante válida que pueda desplazar a los grupos sí configurados.
     *
     * @return array{
     *     hora_ids:array<int,int>,
     *     inconsistente:bool,
     *     variantes:int,
     *     grupos_evaluados:int,
     *     grupos_sin_receso:int,
     *     heredado:bool,
     *     fuente:string,
     *     ciclo_referencia_id:?int
     * }
     */
    public function oficialNivel(
        int $nivelId,
        int $cicloEscolarId,
        Collection $grupos,
        Collection $horas,
    ): array {
        $horaDestinoPorClave = $this->horaDestinoPorClave($horas);

        if ($horaDestinoPorClave->isEmpty()) {
            return $this->resultadoVacio();
        }

        /*
         * Para un receso "oficial del nivel" se evalúan todos los grupos que
         * tienen horario en el ciclo, aunque el llamador esté trabajando con
         * un solo grado o grupo. Los ids recibidos se agregan como respaldo.
         */
        $grupoIds = Horario::query()
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id')
            ->map(fn ($id) => (int) $id)
            ->merge($grupos->pluck('id')->map(fn ($id) => (int) $id))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $actual = $this->analizarCiclo(
            nivelId: $nivelId,
            cicloEscolarId: $cicloEscolarId,
            horaDestinoPorClave: $horaDestinoPorClave,
            grupoIds: $grupoIds,
        );

        if (!empty($actual['hora_ids'])) {
            return [
                ...$actual,
                'heredado' => false,
                'fuente' => 'ciclo_actual',
                'ciclo_referencia_id' => $cicloEscolarId,
            ];
        }

        $cicloActual = CicloEscolar::query()->find($cicloEscolarId);

        $ciclosAnteriores = CicloEscolar::query()
            ->when(
                $cicloActual,
                fn ($query) => $query->where('inicio_anio', '<', $cicloActual->inicio_anio),
                fn ($query) => $query->where('id', '<', $cicloEscolarId),
            )
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio']);

        foreach ($ciclosAnteriores as $cicloAnterior) {
            $historico = $this->analizarCiclo(
                nivelId: $nivelId,
                cicloEscolarId: (int) $cicloAnterior->id,
                horaDestinoPorClave: $horaDestinoPorClave,
                grupoIds: [],
            );

            if (empty($historico['hora_ids'])) {
                continue;
            }

            return [
                ...$historico,
                'heredado' => true,
                'fuente' => 'ciclo_anterior',
                'ciclo_referencia_id' => (int) $cicloAnterior->id,
            ];
        }

        return [
            ...$actual,
            'heredado' => false,
            'fuente' => 'sin_configuracion',
            'ciclo_referencia_id' => null,
        ];
    }

    /**
     * @param Collection<string, int> $horaDestinoPorClave
     * @param array<int, int> $grupoIds
     * @return array{hora_ids:array<int,int>,inconsistente:bool,variantes:int,grupos_evaluados:int,grupos_sin_receso:int}
     */
    private function analizarCiclo(
        int $nivelId,
        int $cicloEscolarId,
        Collection $horaDestinoPorClave,
        array $grupoIds,
    ): array {
        $consulta = Horario::query()
            ->with([
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'asignacionMateria.materia:id,receso',
            ])
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereNotNull('grupo_id');

        if (!empty($grupoIds)) {
            $consulta->whereIn('grupo_id', $grupoIds);
        }

        $horarios = $consulta->get([
            'id',
            'grupo_id',
            'hora_id',
            'asignacion_materia_id',
            'taller_sesion_id',
        ]);

        if ($horarios->isEmpty()) {
            return $this->analisisVacio();
        }

        $firmas = $horarios
            ->groupBy(fn (Horario $horario) => (int) $horario->grupo_id)
            ->map(function (Collection $registrosGrupo) use ($horaDestinoPorClave) {
                $claves = $registrosGrupo
                    ->filter(fn (Horario $horario) => $this->esReceso($horario))
                    ->map(fn (Horario $horario) => $this->claveHora($horario->hora))
                    ->filter(fn (?string $clave) => $clave !== null && $horaDestinoPorClave->has($clave))
                    ->unique()
                    ->sort()
                    ->values();

                return [
                    'firma' => $claves->implode(','),
                    'claves' => $claves->all(),
                ];
            })
            ->values();

        if ($firmas->isEmpty()) {
            return $this->analisisVacio();
        }

        $variantes = $firmas
            ->groupBy('firma')
            ->map(function (Collection $items, string $firma) {
                return [
                    'firma' => $firma,
                    'claves' => $items->first()['claves'] ?? [],
                    'total' => $items->count(),
                ];
            })
            ->values();

        $variantesConReceso = $variantes
            ->filter(fn (array $item) => $item['firma'] !== '')
            ->sort(function (array $a, array $b) {
                $porTotal = ((int) $b['total']) <=> ((int) $a['total']);

                if ($porTotal !== 0) {
                    return $porTotal;
                }

                return strcmp((string) $a['firma'], (string) $b['firma']);
            })
            ->values();

        $predominante = $variantesConReceso->first();
        $horaIds = collect($predominante['claves'] ?? [])
            ->map(fn (string $clave) => $horaDestinoPorClave->get($clave))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'hora_ids' => $horaIds,
            'inconsistente' => $variantes->count() > 1,
            'variantes' => $variantes->count(),
            'grupos_evaluados' => $firmas->count(),
            'grupos_sin_receso' => $firmas->where('firma', '')->count(),
        ];
    }

    /** @return Collection<string, int> */
    private function horaDestinoPorClave(Collection $horas): Collection
    {
        return $horas
            ->mapWithKeys(function ($hora) {
                $clave = $this->claveHora($hora);

                if ($clave === null || empty($hora->id)) {
                    return [];
                }

                return [$clave => (int) $hora->id];
            });
    }

    private function claveHora($hora): ?string
    {
        if (!$hora) {
            return null;
        }

        $inicio = trim((string) ($hora->hora_inicio ?? ''));
        $fin = trim((string) ($hora->hora_fin ?? ''));

        if ($inicio === '' || $fin === '') {
            return null;
        }

        return $inicio . '|' . $fin;
    }

    /** @return array{hora_ids:array<int,int>,inconsistente:bool,variantes:int,grupos_evaluados:int,grupos_sin_receso:int} */
    private function analisisVacio(): array
    {
        return [
            'hora_ids' => [],
            'inconsistente' => false,
            'variantes' => 0,
            'grupos_evaluados' => 0,
            'grupos_sin_receso' => 0,
        ];
    }

    /**
     * @return array{
     *     hora_ids:array<int,int>,
     *     inconsistente:bool,
     *     variantes:int,
     *     grupos_evaluados:int,
     *     grupos_sin_receso:int,
     *     heredado:bool,
     *     fuente:string,
     *     ciclo_referencia_id:?int
     * }
     */
    private function resultadoVacio(): array
    {
        return [
            ...$this->analisisVacio(),
            'heredado' => false,
            'fuente' => 'sin_configuracion',
            'ciclo_referencia_id' => null,
        ];
    }
}
