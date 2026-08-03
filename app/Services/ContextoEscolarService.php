<?php

namespace App\Services;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Fuente única para los selectores académicos dependientes.
 *
 * La tabla grupos representa la combinación válida de ciclo, nivel,
 * generación, grado, semestre y sección. Consultar catálogos por separado
 * permite formar combinaciones que nunca existieron en el ciclo seleccionado.
 */
class ContextoEscolarService
{
    public function consultaGrupos(
        int $nivelId,
        int $cicloEscolarId,
        bool $soloActivos = true,
    ): Builder {
        return Grupo::query()
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->when($soloActivos, fn (Builder $query) => $query->where('estado', 'activo'));
    }

    public function generaciones(
        int $nivelId,
        int $cicloEscolarId,
        bool $soloGruposActivos = true,
    ): Collection {
        $ids = $this->consultaGrupos($nivelId, $cicloEscolarId, $soloGruposActivos)
            ->whereNotNull('generacion_id')
            ->distinct()
            ->pluck('generacion_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Generacion::query()
            ->where('nivel_id', $nivelId)
            ->whereIn('id', $ids)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->orderByDesc('id')
            ->get();
    }

    public function grados(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        bool $soloGruposActivos = true,
    ): Collection {
        $ids = $this->consultaGrupos($nivelId, $cicloEscolarId, $soloGruposActivos)
            ->when($generacionId, fn (Builder $query) => $query->where('generacion_id', $generacionId))
            ->whereNotNull('grado_id')
            ->distinct()
            ->pluck('grado_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Grado::query()
            ->where('nivel_id', $nivelId)
            ->whereIn('id', $ids)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();
    }

    public function semestres(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId,
        ?int $gradoId,
        bool $soloGruposActivos = true,
    ): Collection {
        if (!$gradoId) {
            return collect();
        }

        $ids = $this->consultaGrupos($nivelId, $cicloEscolarId, $soloGruposActivos)
            ->when($generacionId, fn (Builder $query) => $query->where('generacion_id', $generacionId))
            ->where('grado_id', $gradoId)
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id');

        if ($ids->isEmpty()) {
            return collect();
        }

        return Semestre::query()
            ->where('grado_id', $gradoId)
            ->whereIn('id', $ids)
            ->orderByRaw('COALESCE(orden_global, 255)')
            ->orderBy('numero')
            ->orderBy('id')
            ->get();
    }

    public function grupos(
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $semestreId = null,
        bool $bachillerato = false,
        bool $soloActivos = true,
    ): Collection {
        $consulta = $this->consultaGrupos($nivelId, $cicloEscolarId, $soloActivos)
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso,status',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
            ])
            ->when($generacionId, fn (Builder $query) => $query->where('generacion_id', $generacionId))
            ->when($gradoId, fn (Builder $query) => $query->where('grado_id', $gradoId));

        if ($bachillerato) {
            $semestreId
                ? $consulta->where('semestre_id', $semestreId)
                : $consulta->whereNotNull('semestre_id');
        } else {
            $consulta->whereNull('semestre_id');
        }

        return $consulta
            ->get()
            ->sortBy(function (Grupo $grupo): string {
                return sprintf(
                    '%06d-%06d-%s-%06d-%06d',
                    (int) ($grupo->grado?->orden ?? 999999),
                    (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
                    Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
                    999999 - (int) ($grupo->generacion?->anio_ingreso ?? 0),
                    (int) $grupo->id,
                );
            })
            ->values();
    }

    public function grupoValido(
        int $grupoId,
        int $nivelId,
        int $cicloEscolarId,
        ?int $generacionId = null,
        ?int $gradoId = null,
        ?int $semestreId = null,
        bool $bachillerato = false,
        bool $soloActivo = true,
    ): ?Grupo {
        return $this->consultaGrupos($nivelId, $cicloEscolarId, $soloActivo)
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso,status',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
            ])
            ->whereKey($grupoId)
            ->when($generacionId, fn (Builder $query) => $query->where('generacion_id', $generacionId))
            ->when($gradoId, fn (Builder $query) => $query->where('grado_id', $gradoId))
            ->when(
                $bachillerato,
                fn (Builder $query) => $query->where('semestre_id', $semestreId),
                fn (Builder $query) => $query->whereNull('semestre_id'),
            )
            ->first();
    }
}
