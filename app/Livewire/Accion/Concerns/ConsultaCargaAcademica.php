<?php

namespace App\Livewire\Accion\Concerns;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\ReasignacionDocenteLote;
use App\Models\Semestre;
use App\Models\Inscripcion;
use App\Services\CicloNivelGateService;
use App\Services\PlantillaDocenteService;
use App\Services\ReasignacionDocenteMasivaService;
use App\Services\SincronizadorOrdenCargaAcademicaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

trait ConsultaCargaAcademica
{
    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel?->id === 4;
    }

    public function getCiclosEscolaresProperty(): Collection
    {
        return CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get();
    }

    public function getCicloSeleccionadoProperty(): ?CicloEscolar
    {
        return $this->ciclo_escolar_id
            ? CicloEscolar::query()->find($this->ciclo_escolar_id)
            : null;
    }

    public function getCiclosOrigenDisponiblesProperty(): Collection
    {
        $destino = $this->cicloSeleccionado;

        if (! $destino || ! $this->nivel?->id) {
            return collect();
        }

        $idsConCargas = AsignacionMateriaModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->confirmadas()
            ->whereNotNull('ciclo_escolar_id')
            ->distinct()
            ->pluck('ciclo_escolar_id');

        return CicloEscolar::query()
            ->whereIn('id', $idsConCargas)
            ->where('inicio_anio', '<', $destino->inicio_anio)
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get();
    }

    public function getCargasOrigenSeleccionadoProperty(): int
    {
        if (! $this->ciclo_origen_id || ! $this->nivel?->id) {
            return 0;
        }

        return AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_origen_id)
            ->where('nivel_id', $this->nivel->id)
            ->confirmadas()
            ->count();
    }

    public function getCicloSeleccionadoSinCargasProperty(): bool
    {
        if (! $this->ciclo_escolar_id || ! $this->nivel?->id) {
            return false;
        }

        return ! $this->consultaAsignacionesBase()->exists();
    }

    private function sincronizarCicloOrigen(): void
    {
        $disponibles = $this->ciclosOrigenDisponibles;
        $ids = $disponibles->pluck('id')->map(fn ($id) => (int) $id);

        if ($this->ciclo_origen_id && $ids->contains((int) $this->ciclo_origen_id)) {
            return;
        }

        $this->ciclo_origen_id = $disponibles->first()?->id;
    }

    public function getGruposProperty(): Collection
    {
        if (!$this->nivel?->id) {
            return collect();
        }

        /*
         * No limitar esta lista por inscripciones.grupo_id.
         *
         * En bachillerato la inscripción conserva el grupo/semestre actual del
         * alumno. Cuando la generación avanza, el grupo del semestre anterior
         * deja de aparecer en esa columna, aunque siga siendo un contexto válido
         * para consultar o editar cargas del mismo ciclo escolar (por ejemplo,
         * 3.er y 4.º semestre de la generación 2024-2027).
         *
         * La fuente correcta para este selector es la tabla grupos.
         */
        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,nivel_id,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,orden_global',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->get()
            ->sortBy(fn($grupo) => sprintf(
                '%04d|%03d|%03d|%s',
                9999 - (int) ($grupo->generacion?->anio_ingreso ?? 0),
                (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 999),
                (int) ($grupo->grado?->orden ?? 999),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? '')),
            ))
            ->values();
    }

    public function getGrupoSeleccionadoProperty(): ?Grupo
    {
        if (blank($this->grupo_id)) {
            return null;
        }

        return Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereKey($this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();
    }

    public function getMateriasDisponiblesProperty(): Collection
    {
        $grupo = $this->grupoSeleccionado;

        if (! $grupo) {
            return collect();
        }

        $asignadas = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->pluck('materia_id')
            ->map(fn ($id) => (int) $id);

        return $this->materiasParaGrupo($grupo)
            ->reject(fn (Materia $materia) => $asignadas->contains((int) $materia->id))
            ->values();
    }

    /**
     * Detecta contextos que ya tienen al menos una carga académica, pero a los
     * que les falta una o más materias del plan del mismo grado/semestre.
     *
     * Los grupos completamente vacíos no se consideran "incompletos" porque
     * pueden corresponder a semestres todavía no preparados. De esta forma la
     * alerta señala omisiones reales dentro de una carga que ya fue iniciada.
     */
    public function getCargasIncompletasProperty(): Collection
    {
        if (! $this->ciclo_escolar_id || ! $this->nivel?->id) {
            return collect();
        }

        $asignacionesPorGrupo = $this->consultaAsignacionesBase()
            ->whereNotNull('grupo_id')
            ->get(['grupo_id', 'materia_id'])
            ->groupBy('grupo_id');

        if ($asignacionesPorGrupo->isEmpty()) {
            return collect();
        }

        $grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,nivel_id,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,orden_global',
            ])
            ->whereIn('id', $asignacionesPorGrupo->keys())
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->get()
            ->sortBy(fn (Grupo $grupo) => sprintf(
                '%04d|%03d|%03d|%s',
                9999 - (int) ($grupo->generacion?->anio_ingreso ?? 0),
                (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 999),
                (int) ($grupo->grado?->orden ?? 999),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? '')),
            ))
            ->values();

        return $grupos
            ->map(function (Grupo $grupo) use ($asignacionesPorGrupo) {
                $idsAsignados = $asignacionesPorGrupo
                    ->get($grupo->id, collect())
                    ->pluck('materia_id')
                    ->map(fn ($id) => (int) $id);

                $pendientes = $this->materiasParaGrupo($grupo)
                    ->reject(fn (Materia $materia) => $idsAsignados->contains((int) $materia->id))
                    ->values();

                if ($pendientes->isEmpty()) {
                    return null;
                }

                return [
                    'grupo' => $grupo,
                    'materias' => $pendientes,
                    'total' => $pendientes->count(),
                ];
            })
            ->filter()
            ->values();
    }

    public function getTotalMateriasPendientesProperty(): int
    {
        return (int) $this->cargasIncompletas->sum('total');
    }

    /**
     * Cargas cuyo orden almacenado todavía no coincide con materias.orden.
     * La tabla ya se muestra con el orden oficial, pero esta incidencia permite
     * detectar y reparar datos históricos antes de que otros módulos los usen.
     */
    public function getOrdenesDesincronizadosProperty(): Collection
    {
        if (! $this->ciclo_escolar_id || ! $this->nivel?->id) {
            return collect();
        }

        return $this->consultaAsignacionesBase()
            ->with('materia:id,materia,clave,orden,receso')
            ->get(['id', 'materia_id', 'grupo_id', 'orden'])
            ->filter(fn (AsignacionMateriaModel $asignacion) =>
                $asignacion->materia
                && ! $asignacion->materia->receso
                && (int) $asignacion->orden !== (int) $asignacion->materia->orden
            )
            ->values();
    }

    public function getConflictosOrdenMateriasProperty(): Collection
    {
        if (! $this->nivel?->id) {
            return collect();
        }

        return app(SincronizadorOrdenCargaAcademicaService::class)
            ->conflictosNivel((int) $this->nivel->id);
    }

    public function getGrupoEdicionSeleccionadoProperty(): ?Grupo
    {
        if (blank($this->editar_grupo_id)) {
            return null;
        }

        return Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereKey($this->editar_grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();
    }

    public function getMateriasEdicionDisponiblesProperty(): Collection
    {
        return $this->materiasParaGrupo($this->grupoEdicionSeleccionado);
    }

    private function materiasParaGrupo(?Grupo $grupo): Collection
    {
        if (!$grupo) {
            return collect();
        }

        return Materia::query()
            ->where('nivel_id', $grupo->nivel_id)
            ->where('grado_id', $grupo->grado_id)
            ->when(
                $this->esBachillerato,
                fn($q) => $q->where('semestre_id', $grupo->semestre_id),
                fn($q) => $q->whereNull('semestre_id')
            )
            // Receso nunca genera carga. Los talleres conjuntos de secundaria
            // se administran en su módulo específico y no se duplican aquí.
            ->where('receso', false)
            ->when($this->nivel?->slug === 'secundaria', fn($q) => $q->where('slug', '!=', 'taller'))
            ->orderBy('orden')
            ->orderBy('materia')
            ->get();
    }

    public function getProfesoresProperty(): Collection
    {
        if (!$this->ciclo_escolar_id || !$this->nivel?->id) {
            return collect();
        }

        /*
         * Los docentes solo estarán disponibles cuando la plantilla del mismo
         * ciclo y nivel se encuentre publicada (o cerrada para consulta
         * histórica). El selector muestra la lista completa, sin buscador.
         */
        return PersonaNivelDetalle::query()
            ->with('cabecera.persona')
            ->vigenteEnCiclo((int) $this->ciclo_escolar_id)
            ->whereHas('personaRole.rolePersona', fn (Builder $q) => $q
                ->where('status', true)
                ->where('es_docente', true))
            ->whereHas('cabecera', fn (Builder $q) => $q
                ->where('nivel_id', $this->nivel->id)
                ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                ->whereHas('persona', fn (Builder $p) => $p
                    ->where('status', true)
                    ->where('estado_laboral', 'activo')))
            ->get()
            ->map(function (PersonaNivelDetalle $detalle) {
                $persona = $detalle->cabecera?->persona;
                $nombre = trim(($persona->titulo ?? '') . ' ' . ($persona->nombre ?? '') . ' '
                    . ($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? ''));

                return [
                    'id' => (int) ($persona?->id ?? 0),
                    'nombre' => $nombre,
                ];
            })
            ->filter(fn ($item) => $item['id'] > 0 && filled($item['nombre']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    private function consultaAsignacionesBase(): Builder
    {
        return AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id);
    }

    public function getGeneracionesFiltroProperty(): Collection
    {
        if (!$this->ciclo_escolar_id) {
            return collect();
        }

        $ids = $this->consultaAsignacionesBase()
            ->whereNotNull('generacion_id')
            ->distinct()
            ->pluck('generacion_id');

        return Generacion::query()
            ->whereIn('id', $ids)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();
    }

    public function getGradosFiltroProperty(): Collection
    {
        if (!$this->ciclo_escolar_id) {
            return collect();
        }

        $ids = $this->consultaAsignacionesBase()
            ->when(filled($this->filtro_generacion), fn(Builder $q) => $q->where('generacion_id', (int) $this->filtro_generacion))
            ->whereNotNull('grado_id')
            ->distinct()
            ->pluck('grado_id');

        return Grado::query()
            ->whereIn('id', $ids)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    public function getSemestresFiltroProperty(): Collection
    {
        if (!$this->esBachillerato || !$this->ciclo_escolar_id) {
            return collect();
        }

        $ids = $this->consultaAsignacionesBase()
            ->when(filled($this->filtro_generacion), fn(Builder $q) => $q->where('generacion_id', (int) $this->filtro_generacion))
            ->when(filled($this->filtro_grado), fn(Builder $q) => $q->where('grado_id', (int) $this->filtro_grado))
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id');

        return Semestre::query()
            ->whereIn('id', $ids)
            ->orderBy('orden_global')
            ->orderBy('numero')
            ->get();
    }

    public function getGruposFiltroProperty(): Collection
    {
        if (!$this->ciclo_escolar_id) {
            return collect();
        }

        $ids = $this->consultaAsignacionesBase()
            ->when(filled($this->filtro_generacion), fn(Builder $q) => $q->where('generacion_id', (int) $this->filtro_generacion))
            ->when(filled($this->filtro_grado), fn(Builder $q) => $q->where('grado_id', (int) $this->filtro_grado))
            ->when(filled($this->filtro_semestre), fn(Builder $q) => $q->where('semestre_id', (int) $this->filtro_semestre))
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id');

        return Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereIn('id', $ids)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->get()
            ->sortBy(fn($grupo) => sprintf(
                '%03d|%03d|%s|%04d',
                (int) ($grupo->grado?->orden ?? 999),
                (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 999),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? '')),
                (int) ($grupo->generacion?->anio_ingreso ?? 0),
            ))
            ->values();
    }

    private function consultaAsignacionesFiltradas(): Builder
    {
        return $this->consultaAsignacionesBase()
            ->with([
                'materia',
                'profesor',
                'cicloEscolar',
                'grupo.nivel',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'grupo.asignacionGrupo',
                'horarios' => fn($q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id),
            ])
            ->withCount([
                'horarios',
                'calificaciones',
                'bitacoraCalificaciones',
            ])
            ->when(filled($this->filtro_generacion), fn(Builder $q) => $q->where('generacion_id', (int) $this->filtro_generacion))
            ->when(filled($this->filtro_estado), fn(Builder $q) => $q->where('estado', $this->filtro_estado))
            ->when(filled($this->filtro_grado), fn(Builder $q) => $q->where('grado_id', (int) $this->filtro_grado))
            ->when(filled($this->filtro_semestre), fn(Builder $q) => $q->where('semestre_id', (int) $this->filtro_semestre))
            ->when(filled($this->filtro_grupo), fn(Builder $q) => $q->where('grupo_id', (int) $this->filtro_grupo))
            ->when($this->filtro_profesor === 'asignado', fn(Builder $q) => $q->whereNotNull('profesor_id'))
            ->when($this->filtro_profesor === 'pendiente', fn(Builder $q) => $q->whereNull('profesor_id'))
            ->when($this->filtro_horario === 'con', fn(Builder $q) => $q->whereHas(
                'horarios',
                fn(Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ))
            ->when($this->filtro_horario === 'sin', fn(Builder $q) => $q->whereDoesntHave(
                'horarios',
                fn(Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ))
            ->when(trim($this->buscar) !== '', function (Builder $q) {
                $buscar = '%' . trim($this->buscar) . '%';

                $q->where(function (Builder $sub) use ($buscar) {
                    $sub->whereHas('materia', fn(Builder $m) => $m
                        ->where('materia', 'like', $buscar)
                        ->orWhere('clave', 'like', $buscar))
                        ->orWhereHas('profesor', fn(Builder $p) => $p
                            ->where('nombre', 'like', $buscar)
                            ->orWhere('apellido_paterno', 'like', $buscar)
                            ->orWhere('apellido_materno', 'like', $buscar))
                        ->orWhereHas('grupo.asignacionGrupo', fn(Builder $g) => $g->where('nombre', 'like', $buscar))
                        ->orWhereHas('grupo.grado', fn(Builder $g) => $g->where('nombre', 'like', $buscar))
                        ->orWhereHas('grupo.generacion', fn(Builder $g) => $g
                            ->where('anio_ingreso', 'like', $buscar)
                            ->orWhere('anio_egreso', 'like', $buscar)
                            ->orWhere('nombre', 'like', $buscar));
                });
            });
    }

    public function getAsignacionesFiltradasProperty(): LengthAwarePaginator
    {
        if (!$this->ciclo_escolar_id) {
            return AsignacionMateriaModel::query()
                ->whereRaw('1 = 0')
                ->paginate($this->porPaginaMaterias, ['*'], 'materiasPage');
        }

        return $this->consultaAsignacionesFiltradas()
            ->orderBy('grado_id')
            ->orderByRaw('CASE WHEN semestre_id IS NULL THEN 999 ELSE semestre_id END')
            ->orderBy('grupo_id')
            // La vista siempre usa el catálogo Materias como fuente oficial,
            // incluso antes de reparar un registro histórico desincronizado.
            ->orderBy(
                Materia::query()
                    ->select('orden')
                    ->whereColumn('materias.id', 'asignacion_materias.materia_id')
                    ->limit(1)
            )
            ->orderBy('materia_id')
            ->paginate($this->porPaginaMaterias, ['*'], 'materiasPage');
    }

    public function getResumenCargasProperty(): array
    {
        if (!$this->ciclo_escolar_id) {
            return [
                'total' => 0,
                'borradores' => 0,
                'activas' => 0,
                'sin_horario' => 0,
                'sin_profesor' => 0,
            ];
        }

        $query = $this->consultaAsignacionesFiltradas();

        return [
            'total' => (clone $query)->count(),
            'borradores' => (clone $query)
                ->where('estado', AsignacionMateriaModel::ESTADO_BORRADOR)
                ->count(),
            'activas' => (clone $query)
                ->where('estado', AsignacionMateriaModel::ESTADO_ACTIVA)
                ->count(),
            'sin_horario' => (clone $query)
                ->whereDoesntHave(
                    'horarios',
                    fn(Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                )
                ->count(),
            'sin_profesor' => (clone $query)->whereNull('profesor_id')->count(),
        ];
    }

    public function getHayBorradoresFiltradosProperty(): bool
    {
        return $this->ciclo_escolar_id
            && $this->consultaAsignacionesFiltradas()
                ->where('estado', AsignacionMateriaModel::ESTADO_BORRADOR)
                ->exists();
    }

    public function getTotalBorradoresProperty(): int
    {
        if (! $this->ciclo_escolar_id || ! $this->nivel?->id) {
            return 0;
        }

        return $this->consultaAsignacionesBase()
            ->where('estado', AsignacionMateriaModel::ESTADO_BORRADOR)
            ->count();
    }

    public function getTieneFiltrosActivosProperty(): bool
    {
        return trim($this->buscar) !== ''
            || filled($this->filtro_generacion)
            || filled($this->filtro_estado)
            || filled($this->filtro_grado)
            || filled($this->filtro_semestre)
            || filled($this->filtro_grupo)
            || filled($this->filtro_horario)
            || filled($this->filtro_profesor);
    }

    public function getIdsPaginaActualProperty(): array
    {
        return $this->asignacionesFiltradas
            ->getCollection()
            ->reject(fn (AsignacionMateriaModel $asignacion) => (bool) $asignacion->materia?->receso)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function getPaginaSeleccionadaProperty(): bool
    {
        $ids = $this->idsPaginaActual;

        return $ids !== [] && collect($ids)->every(
            fn (int $id) => in_array($id, array_map('intval', $this->seleccionados), true)
        );
    }

    public function alternarSeleccionPagina(): void
    {
        $actuales = collect($this->seleccionados)->map(fn ($id) => (int) $id);
        $pagina = collect($this->idsPaginaActual);

        $this->seleccionados = $this->paginaSeleccionada
            ? $actuales->diff($pagina)->values()->all()
            : $actuales->merge($pagina)->unique()->values()->all();
    }

    public function getTotalSeleccionablesFiltradosProperty(): int
    {
        return $this->consultaAsignacionesFiltradas()
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false))
            ->count();
    }

    public function seleccionarTodosFiltrados(): void
    {
        $this->seleccionados = $this->consultaAsignacionesFiltradas()
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function limpiarSeleccionTabla(): void
    {
        $this->seleccionados = [];
    }

}
