<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Grupo;
use App\Models\Materia;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SincronizadorOrdenCargaAcademicaService
{
    /**
     * El catálogo materias.orden es la única fuente oficial del orden académico.
     * Una carga nunca debe mantener un orden manual independiente.
     */
    public function asegurarContextoSinDuplicados(Materia $materia): void
    {
        if ($materia->receso) {
            return;
        }

        $duplicadas = Materia::query()
            ->where('nivel_id', $materia->nivel_id)
            ->where('grado_id', $materia->grado_id)
            ->when(
                $materia->semestre_id,
                fn ($query) => $query->where('semestre_id', $materia->semestre_id),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->where('receso', false)
            ->where('orden', (int) $materia->orden)
            ->where('id', '!=', $materia->id)
            ->get(['id', 'materia', 'clave', 'orden']);

        if ($duplicadas->isEmpty()) {
            return;
        }

        $nombres = $duplicadas
            ->prepend($materia)
            ->map(fn (Materia $item) => trim($item->materia . ($item->clave ? " ({$item->clave})" : '')))
            ->implode(', ');

        throw new DomainException(
            "El orden académico {$materia->orden} está duplicado en el catálogo de Materias: {$nombres}. " .
            'Corrige primero el orden en Materias; las cargas no se reordenarán mientras exista esta ambigüedad.'
        );
    }

    /**
     * Sincroniza una materia en todos sus grupos, ciclos y generaciones.
     */
    public function sincronizarMateria(Materia $materia): int
    {
        if ($materia->receso) {
            return 0;
        }

        $this->asegurarContextoSinDuplicados($materia);

        return AsignacionMateria::query()
            ->where('materia_id', $materia->id)
            ->where(function ($query) use ($materia) {
                $query->whereNull('orden')
                    ->orWhere('orden', '!=', (int) $materia->orden);
            })
            ->update(['orden' => (int) $materia->orden]);
    }

    /**
     * Sincroniza todas las cargas de un grupo usando materias.orden.
     * Los huecos son válidos: si el catálogo tiene 1,2,4, la carga conserva 1,2,4.
     */
    public function sincronizarGrupo(int $grupoId, ?int $cicloEscolarId = null): int
    {
        $grupo = Grupo::query()->find($grupoId);

        if (! $grupo) {
            return 0;
        }

        $materias = $this->materiasDelContexto(
            (int) $grupo->nivel_id,
            (int) $grupo->grado_id,
            $grupo->semestre_id ? (int) $grupo->semestre_id : null,
        );

        $this->asegurarColeccionSinDuplicados($materias);

        $ordenes = $materias->pluck('orden', 'id');

        if ($ordenes->isEmpty()) {
            return 0;
        }

        $actualizadas = 0;

        foreach ($ordenes as $materiaId => $orden) {
            $actualizadas += AsignacionMateria::query()
                ->where('grupo_id', $grupoId)
                ->when($cicloEscolarId, fn ($query) => $query->where('ciclo_escolar_id', $cicloEscolarId))
                ->where('materia_id', (int) $materiaId)
                ->where(function ($query) use ($orden) {
                    $query->whereNull('orden')
                        ->orWhere('orden', '!=', (int) $orden);
                })
                ->update(['orden' => (int) $orden]);
        }

        return $actualizadas;
    }

    /**
     * Sincroniza todas las asignaciones vinculadas a un contexto del catálogo.
     */
    public function sincronizarContextoCatalogo(int $nivelId, int $gradoId, ?int $semestreId): int
    {
        $materias = $this->materiasDelContexto($nivelId, $gradoId, $semestreId);
        $this->asegurarColeccionSinDuplicados($materias);

        $actualizadas = 0;

        foreach ($materias as $materia) {
            $actualizadas += AsignacionMateria::query()
                ->where('materia_id', $materia->id)
                ->where(function ($query) use ($materia) {
                    $query->whereNull('orden')
                        ->orWhere('orden', '!=', (int) $materia->orden);
                })
                ->update(['orden' => (int) $materia->orden]);
        }

        return $actualizadas;
    }

    /**
     * Repara el nivel/ciclo mostrado por el administrador. Los contextos con
     * órdenes duplicados se omiten y se reportan; nunca se desempata por nombre o ID.
     *
     * @return array{actualizadas:int, conflictos:Collection}
     */
    public function sincronizarNivelCiclo(int $nivelId, int $cicloEscolarId): array
    {
        $grupos = AsignacionMateria::query()
            ->where('nivel_id', $nivelId)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id')
            ->map(fn ($id) => (int) $id);

        $actualizadas = 0;
        $conflictos = collect();

        foreach ($grupos as $grupoId) {
            try {
                $actualizadas += $this->sincronizarGrupo($grupoId, $cicloEscolarId);
            } catch (DomainException $e) {
                $conflictos->push([
                    'grupo_id' => $grupoId,
                    'mensaje' => $e->getMessage(),
                ]);
            }
        }

        return [
            'actualizadas' => $actualizadas,
            'conflictos' => $conflictos,
        ];
    }

    /**
     * Diagnóstico de órdenes duplicados del catálogo por nivel/grado/semestre.
     */
    public function conflictosNivel(int $nivelId): Collection
    {
        return Materia::query()
            ->with(['grado:id,nombre', 'semestre:id,numero'])
            ->where('nivel_id', $nivelId)
            ->where('receso', false)
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('orden')
            ->orderBy('materia')
            ->get(['id', 'nivel_id', 'grado_id', 'semestre_id', 'materia', 'clave', 'orden'])
            ->groupBy(fn (Materia $materia) => $this->claveContexto($materia->nivel_id, $materia->grado_id, $materia->semestre_id))
            ->flatMap(function (Collection $materias) {
                return $materias
                    ->groupBy('orden')
                    ->filter(fn (Collection $items) => $items->count() > 1)
                    ->map(function (Collection $items, $orden) {
                        $primera = $items->first();

                        return [
                            'nivel_id' => (int) $primera->nivel_id,
                            'grado_id' => (int) $primera->grado_id,
                            'semestre_id' => $primera->semestre_id ? (int) $primera->semestre_id : null,
                            'grado' => $primera->grado?->nombre,
                            'semestre' => $primera->semestre?->numero,
                            'orden' => (int) $orden,
                            'materias' => $items->values(),
                        ];
                    });
            })
            ->values();
    }

    /**
     * Reordena el catálogo completo de un contexto y propaga el cambio a cargas.
     * Se exige recibir todas las materias del contexto para evitar renumerar una
     * vista filtrada y crear órdenes duplicados con filas ocultas.
     */
    public function reordenarCatalogo(array $ids): int
    {
        $ids = collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $seleccionadas = Materia::query()
            ->whereIn('id', $ids)
            ->get(['id', 'nivel_id', 'grado_id', 'semestre_id', 'materia', 'orden']);

        if ($seleccionadas->count() !== $ids->count()) {
            throw new DomainException('Una o más materias ya no existen. Recarga la pantalla antes de cambiar el orden.');
        }

        $primera = $seleccionadas->first();
        $mismoContexto = $seleccionadas->every(fn (Materia $materia) =>
            (int) $materia->nivel_id === (int) $primera->nivel_id
            && (int) $materia->grado_id === (int) $primera->grado_id
            && (int) ($materia->semestre_id ?? 0) === (int) ($primera->semestre_id ?? 0)
        );

        if (! $mismoContexto) {
            throw new DomainException('Solo puedes ordenar materias pertenecientes al mismo nivel, grado y semestre.');
        }

        $todas = Materia::query()
            ->where('nivel_id', $primera->nivel_id)
            ->where('grado_id', $primera->grado_id)
            ->when(
                $primera->semestre_id,
                fn ($query) => $query->where('semestre_id', $primera->semestre_id),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if ($todas->all() !== $ids->sort()->values()->all()) {
            throw new DomainException(
                'Para cambiar el orden debes mostrar todas las materias de este contexto. Limpia la búsqueda y los filtros e inténtalo nuevamente.'
            );
        }

        return DB::transaction(function () use ($ids, $primera) {
            foreach ($ids as $index => $id) {
                // Query Builder intencional: evita disparar sincronizaciones parciales
                // mientras todavía existen órdenes transitorios durante el drag & drop.
                Materia::query()
                    ->whereKey($id)
                    ->update(['orden' => $index + 1]);
            }

            return $this->sincronizarContextoCatalogo(
                (int) $primera->nivel_id,
                (int) $primera->grado_id,
                $primera->semestre_id ? (int) $primera->semestre_id : null,
            );
        });
    }

    private function materiasDelContexto(int $nivelId, int $gradoId, ?int $semestreId): Collection
    {
        return Materia::query()
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->when(
                $semestreId,
                fn ($query) => $query->where('semestre_id', $semestreId),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->where('receso', false)
            ->orderBy('orden')
            ->orderBy('materia')
            ->get(['id', 'nivel_id', 'grado_id', 'semestre_id', 'materia', 'clave', 'orden', 'receso']);
    }

    private function asegurarColeccionSinDuplicados(Collection $materias): void
    {
        $duplicadas = $materias
            ->groupBy('orden')
            ->filter(fn (Collection $items) => $items->count() > 1);

        if ($duplicadas->isEmpty()) {
            return;
        }

        $detalle = $duplicadas
            ->map(function (Collection $items, $orden) {
                $nombres = $items
                    ->map(fn (Materia $materia) => trim($materia->materia . ($materia->clave ? " ({$materia->clave})" : '')))
                    ->implode(', ');

                return "orden {$orden}: {$nombres}";
            })
            ->implode('; ');

        throw new DomainException(
            "Hay órdenes académicos duplicados en Materias ({$detalle}). Corrige el catálogo antes de sincronizar las cargas."
        );
    }

    private function claveContexto(int $nivelId, int $gradoId, ?int $semestreId): string
    {
        return $nivelId . '|' . $gradoId . '|' . ($semestreId ?? 0);
    }
}
