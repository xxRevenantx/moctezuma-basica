<?php

namespace App\Livewire\Accion;

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
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AsignacionMateria extends Component
{
    use WithPagination;
    public string $slug_nivel = '';
    public $nivel = null;

    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_origen_id = null;
    public bool $copiar_profesores = true;
    public bool $copiar_horarios = false;

    public string $buscar = '';
    public string $filtro_generacion = '';
    public string $filtro_estado = '';
    public string $filtro_grado = '';
    public string $filtro_semestre = '';
    public string $filtro_grupo = '';
    public string $filtro_horario = '';
    public string $filtro_profesor = '';
    public int $porPaginaMaterias = 10;
    public ?int $editandoId = null;
    public bool $modalEditarAbierto = false;
    public bool $edicionTieneHistorial = false;
    public $editar_grupo_id = '';
    public $editar_materia_id = '';
    public $editar_profesor_id = '';

    /** Selección y reasignación masiva de docentes. */
    public array $seleccionados = [];
    public bool $modalReasignacionAbierto = false;
    public bool $modalHistorialReasignacionesAbierto = false;
    public string $reasignacion_paso = 'seleccion';
    public string $reasignacion_modo = 'seleccion';
    public string $reasignacion_origen = '';
    public string $reasignacion_destino_id = '';
    public array $reasignacion_ids_base = [];
    public array $reasignacion_seleccionados = [];
    public string $reasignacion_buscar = '';
    public string $reasignacion_generacion = '';
    public string $reasignacion_estado = '';
    public string $reasignacion_grado = '';
    public string $reasignacion_semestre = '';
    public string $reasignacion_grupo = '';
    public string $reasignacion_horario = '';
    public bool $reasignacion_incluir_cerradas = false;
    public bool $reasignacion_incluir_archivadas = false;
    public bool $reasignacion_autorizar_conflictos = false;
    public string $reasignacion_motivo_conflictos = '';
    public array $reasignacion_preview = [];

    public $grupo_id = '';
    public $materia_id = '';
    public $profesor_id = '';
    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    public function mount($slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $actual = CicloEscolar::query()->where('es_actual', true)->first()
            ?? CicloEscolar::query()->orderByDesc('inicio_anio')->first();

        $this->ciclo_escolar_id = $actual?->id;
        $this->ciclo_origen_id = CicloEscolar::query()
            ->when($actual, fn($q) => $q->where('id', '!=', $actual->id)->where('inicio_anio', '<=', $actual->inicio_anio))
            ->orderByDesc('inicio_anio')
            ->value('id');
    }

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
        return $this->materiasParaGrupo($this->grupoSeleccionado);
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
            ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('orden')
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

    public function getProfesoresOrigenReasignacionProperty(): Collection
    {
        if (! $this->ciclo_escolar_id) {
            return collect();
        }

        $estados = [AsignacionMateriaModel::ESTADO_BORRADOR, AsignacionMateriaModel::ESTADO_ACTIVA];

        if ($this->reasignacion_incluir_cerradas) {
            $estados[] = AsignacionMateriaModel::ESTADO_CERRADA;
        }

        if ($this->reasignacion_incluir_archivadas) {
            $estados[] = AsignacionMateriaModel::ESTADO_ARCHIVADA;
        }

        $base = $this->consultaAsignacionesBase()
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false))
            ->whereIn('estado', array_values(array_unique($estados)));

        $conteos = (clone $base)
            ->selectRaw('profesor_id, COUNT(*) as total')
            ->groupBy('profesor_id')
            ->pluck('total', 'profesor_id');

        $personas = Persona::query()
            ->withTrashed()
            ->whereIn('id', $conteos->keys()->filter())
            ->get()
            ->map(fn (Persona $persona) => [
                'valor' => (string) $persona->id,
                'nombre' => trim(($persona->titulo ?? '') . ' ' . ($persona->nombre ?? '') . ' '
                    . ($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? ''))
                    . ($persona->trashed() ? ' · registro eliminado' : ''),
                'total' => (int) ($conteos[$persona->id] ?? 0),
            ]);

        if ((int) ($conteos[''] ?? $conteos[null] ?? 0) > 0 || (clone $base)->whereNull('profesor_id')->exists()) {
            $personas->push([
                'valor' => 'sin_docente',
                'nombre' => 'Sin docente asignado',
                'total' => (clone $base)->whereNull('profesor_id')->count(),
            ]);
        }

        return $personas->sortBy(fn (array $item) => mb_strtolower($item['nombre']))->values();
    }

    private function consultaOpcionesReasignacion(): Builder
    {
        return $this->consultaAsignacionesBase()
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false));
    }

    public function getReasignacionGeneracionesProperty(): Collection
    {
        $ids = $this->consultaOpcionesReasignacion()
            ->whereNotNull('generacion_id')
            ->distinct()
            ->pluck('generacion_id');

        return Generacion::query()
            ->whereIn('id', $ids)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();
    }

    public function getReasignacionGradosProperty(): Collection
    {
        $ids = $this->consultaOpcionesReasignacion()
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->whereNotNull('grado_id')
            ->distinct()
            ->pluck('grado_id');

        return Grado::query()->whereIn('id', $ids)->orderBy('orden')->orderBy('nombre')->get();
    }

    public function getReasignacionSemestresProperty(): Collection
    {
        if (! $this->esBachillerato) {
            return collect();
        }

        $ids = $this->consultaOpcionesReasignacion()
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->when(filled($this->reasignacion_grado), fn (Builder $q) => $q->where('grado_id', (int) $this->reasignacion_grado))
            ->whereNotNull('semestre_id')
            ->distinct()
            ->pluck('semestre_id');

        return Semestre::query()->whereIn('id', $ids)->orderBy('orden_global')->orderBy('numero')->get();
    }

    public function getReasignacionGruposProperty(): Collection
    {
        $ids = $this->consultaOpcionesReasignacion()
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->when(filled($this->reasignacion_grado), fn (Builder $q) => $q->where('grado_id', (int) $this->reasignacion_grado))
            ->when(filled($this->reasignacion_semestre), fn (Builder $q) => $q->where('semestre_id', (int) $this->reasignacion_semestre))
            ->whereNotNull('grupo_id')
            ->distinct()
            ->pluck('grupo_id');

        return Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereIn('id', $ids)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->get()
            ->sortBy(fn (Grupo $grupo) => sprintf(
                '%03d|%03d|%s',
                (int) ($grupo->grado?->orden ?? 999),
                (int) ($grupo->semestre?->orden_global ?? 999),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? '')),
            ))
            ->values();
    }

    private function consultaCandidatosReasignacion(): Builder
    {
        $estados = [AsignacionMateriaModel::ESTADO_BORRADOR, AsignacionMateriaModel::ESTADO_ACTIVA];

        if ($this->reasignacion_incluir_cerradas) {
            $estados[] = AsignacionMateriaModel::ESTADO_CERRADA;
        }

        if ($this->reasignacion_incluir_archivadas) {
            $estados[] = AsignacionMateriaModel::ESTADO_ARCHIVADA;
        }

        $query = $this->consultaAsignacionesBase()
            ->with([
                'materia',
                'profesor',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'grupo.asignacionGrupo',
            ])
            ->withCount(['horarios', 'calificaciones', 'bitacoraCalificaciones'])
            ->whereHas('materia', fn (Builder $materia) => $materia->where('receso', false))
            ->whereIn('estado', array_values(array_unique($estados)));

        if ($this->reasignacion_modo === 'seleccion') {
            $ids = collect($this->reasignacion_ids_base)->map(fn ($id) => (int) $id)->filter()->unique();
            $query->when($ids->isNotEmpty(), fn (Builder $q) => $q->whereIn('id', $ids))
                ->when($ids->isEmpty(), fn (Builder $q) => $q->whereRaw('1 = 0'));
        } else {
            $query->when(
                $this->reasignacion_origen === 'sin_docente',
                fn (Builder $q) => $q->whereNull('profesor_id'),
                fn (Builder $q) => filled($this->reasignacion_origen)
                    ? $q->where('profesor_id', (int) $this->reasignacion_origen)
                    : $q->whereRaw('1 = 0')
            );
        }

        return $query
            ->when(filled($this->reasignacion_generacion), fn (Builder $q) => $q->where('generacion_id', (int) $this->reasignacion_generacion))
            ->when(filled($this->reasignacion_estado), fn (Builder $q) => $q->where('estado', $this->reasignacion_estado))
            ->when(filled($this->reasignacion_grado), fn (Builder $q) => $q->where('grado_id', (int) $this->reasignacion_grado))
            ->when(filled($this->reasignacion_semestre), fn (Builder $q) => $q->where('semestre_id', (int) $this->reasignacion_semestre))
            ->when(filled($this->reasignacion_grupo), fn (Builder $q) => $q->where('grupo_id', (int) $this->reasignacion_grupo))
            ->when($this->reasignacion_horario === 'con', fn (Builder $q) => $q->whereHas('horarios', fn (Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)))
            ->when($this->reasignacion_horario === 'sin', fn (Builder $q) => $q->whereDoesntHave('horarios', fn (Builder $h) => $h->where('ciclo_escolar_id', $this->ciclo_escolar_id)))
            ->when(trim($this->reasignacion_buscar) !== '', function (Builder $q): void {
                $buscar = '%' . trim($this->reasignacion_buscar) . '%';
                $q->where(function (Builder $sub) use ($buscar): void {
                    $sub->whereHas('materia', fn (Builder $m) => $m
                        ->where('materia', 'like', $buscar)
                        ->orWhere('clave', 'like', $buscar))
                        ->orWhereHas('grupo.asignacionGrupo', fn (Builder $g) => $g->where('nombre', 'like', $buscar))
                        ->orWhereHas('grupo.grado', fn (Builder $g) => $g->where('nombre', 'like', $buscar));
                });
            });
    }

    public function getCandidatosReasignacionProperty(): Collection
    {
        return $this->consultaCandidatosReasignacion()
            ->orderBy('grado_id')
            ->orderByRaw('CASE WHEN semestre_id IS NULL THEN 999 ELSE semestre_id END')
            ->orderBy('grupo_id')
            ->orderBy('orden')
            ->limit(250)
            ->get();
    }

    public function getTotalCandidatosReasignacionProperty(): int
    {
        return $this->consultaCandidatosReasignacion()->count();
    }

    public function getHistorialReasignacionesProperty(): Collection
    {
        if (! $this->ciclo_escolar_id) {
            return collect();
        }

        return ReasignacionDocenteLote::query()
            ->with(['profesorOrigen', 'profesorDestino', 'usuarioAplicacion', 'usuarioReversion'])
            ->withCount('detalles')
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->latest('aplicado_at')
            ->latest('id')
            ->limit(15)
            ->get();
    }

    public function abrirReasignacionMasiva(): void
    {
        $this->autorizarAdministracion();
        $seleccion = collect($this->seleccionados)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        $this->resetEstadoReasignacion();
        $this->reasignacion_modo = $seleccion !== [] ? 'seleccion' : 'profesor';
        $this->reasignacion_ids_base = $seleccion;
        $this->reasignacion_seleccionados = $seleccion;
        $this->modalReasignacionAbierto = true;
    }

    public function cerrarReasignacionMasiva(): void
    {
        $this->modalReasignacionAbierto = false;
        $this->resetEstadoReasignacion();
    }

    private function resetEstadoReasignacion(): void
    {
        $this->reset([
            'reasignacion_paso',
            'reasignacion_modo',
            'reasignacion_origen',
            'reasignacion_destino_id',
            'reasignacion_ids_base',
            'reasignacion_seleccionados',
            'reasignacion_buscar',
            'reasignacion_generacion',
            'reasignacion_estado',
            'reasignacion_grado',
            'reasignacion_semestre',
            'reasignacion_grupo',
            'reasignacion_horario',
            'reasignacion_incluir_cerradas',
            'reasignacion_incluir_archivadas',
            'reasignacion_autorizar_conflictos',
            'reasignacion_motivo_conflictos',
            'reasignacion_preview',
        ]);
        $this->reasignacion_paso = 'seleccion';
        $this->reasignacion_modo = 'seleccion';
        $this->resetValidation();
    }

    public function updatedReasignacionModo(): void
    {
        $this->reasignacion_preview = [];
        $this->reasignacion_paso = 'seleccion';
        $this->reasignacion_origen = '';
        $this->reasignacion_seleccionados = [];

        if ($this->reasignacion_modo === 'seleccion') {
            $this->reasignacion_ids_base = collect($this->seleccionados)
                ->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
            $this->reasignacion_seleccionados = $this->reasignacion_ids_base;
        }
    }

    public function updatedReasignacionOrigen(): void
    {
        $this->reasignacion_seleccionados = [];
        $this->reasignacion_preview = [];
        $this->reasignacion_paso = 'seleccion';
    }

    public function updatedReasignacionBuscar(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionGeneracion(): void
    {
        $this->reset(['reasignacion_grado', 'reasignacion_semestre', 'reasignacion_grupo']);
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionEstado(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionGrado(): void
    {
        $this->reset(['reasignacion_semestre', 'reasignacion_grupo']);
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionSemestre(): void
    {
        $this->reset(['reasignacion_grupo']);
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionGrupo(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionHorario(): void
    {
        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionIncluirCerradas(bool $incluir): void
    {
        if (! $incluir && $this->reasignacion_estado === AsignacionMateriaModel::ESTADO_CERRADA) {
            $this->reasignacion_estado = '';
        }

        $this->depurarSeleccionReasignacion();
    }

    public function updatedReasignacionIncluirArchivadas(bool $incluir): void
    {
        if (! $incluir && $this->reasignacion_estado === AsignacionMateriaModel::ESTADO_ARCHIVADA) {
            $this->reasignacion_estado = '';
        }

        $this->depurarSeleccionReasignacion();
    }

    private function depurarSeleccionReasignacion(): void
    {
        $permitidos = $this->consultaCandidatosReasignacion()
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $this->reasignacion_seleccionados = collect($this->reasignacion_seleccionados)
            ->map(fn ($id) => (int) $id)
            ->intersect($permitidos)
            ->values()
            ->all();
        $this->reasignacion_preview = [];
        $this->reasignacion_paso = 'seleccion';
    }

    public function seleccionarTodosCandidatosReasignacion(): void
    {
        $this->reasignacion_seleccionados = $this->consultaCandidatosReasignacion()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function limpiarSeleccionReasignacion(): void
    {
        $this->reasignacion_seleccionados = [];
        $this->reasignacion_preview = [];
    }

    public function previsualizarReasignacion(): void
    {
        $this->autorizarAdministracion();

        $this->validate([
            'reasignacion_destino_id' => ['required', 'integer', 'exists:personas,id'],
            'reasignacion_seleccionados' => ['required', 'array', 'min:1'],
            'reasignacion_seleccionados.*' => ['integer', 'exists:asignacion_materias,id'],
        ], [], [
            'reasignacion_destino_id' => 'profesor destino',
            'reasignacion_seleccionados' => 'materias seleccionadas',
        ]);

        if ($this->reasignacion_modo === 'profesor'
            && ctype_digit($this->reasignacion_origen)
            && (int) $this->reasignacion_origen === (int) $this->reasignacion_destino_id) {
            $this->addError('reasignacion_destino_id', 'El profesor destino debe ser diferente al profesor actual.');
            return;
        }

        $this->reasignacion_preview = app(ReasignacionDocenteMasivaService::class)->previsualizar(
            asignacionIds: $this->reasignacion_seleccionados,
            profesorDestinoId: (int) $this->reasignacion_destino_id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel->id,
            incluirCerradas: $this->reasignacion_incluir_cerradas,
            incluirArchivadas: $this->reasignacion_incluir_archivadas,
        );
        $this->reasignacion_paso = 'preview';
        $this->reasignacion_autorizar_conflictos = false;
        $this->reasignacion_motivo_conflictos = '';
    }

    public function volverSeleccionReasignacion(): void
    {
        $this->reasignacion_paso = 'seleccion';
        $this->reasignacion_preview = [];
        $this->resetValidation(['reasignacion_autorizar_conflictos', 'reasignacion_motivo_conflictos']);
    }

    public function aplicarReasignacionMasiva(): void
    {
        $this->autorizarAdministracion();

        $lote = app(ReasignacionDocenteMasivaService::class)->aplicar(
            asignacionIds: $this->reasignacion_seleccionados,
            profesorDestinoId: (int) $this->reasignacion_destino_id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel->id,
            modo: $this->reasignacion_modo,
            profesorOrigenId: $this->reasignacion_modo === 'profesor' && ctype_digit($this->reasignacion_origen)
                ? (int) $this->reasignacion_origen
                : null,
            incluirCerradas: $this->reasignacion_incluir_cerradas,
            incluirArchivadas: $this->reasignacion_incluir_archivadas,
            autorizarConflictos: $this->reasignacion_autorizar_conflictos,
            motivoConflictos: $this->reasignacion_motivo_conflictos,
            usuarioId: auth()->id(),
        );

        $total = $lote->total_asignaciones;
        $this->modalReasignacionAbierto = false;
        $this->resetEstadoReasignacion();
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Reasignación masiva aplicada',
            'text' => "Se actualizaron {$total} carga(s), sus horarios operativos y las propuestas editables.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function abrirHistorialReasignaciones(): void
    {
        $this->autorizarAdministracion();
        $this->modalHistorialReasignacionesAbierto = true;
    }

    public function cerrarHistorialReasignaciones(): void
    {
        $this->modalHistorialReasignacionesAbierto = false;
    }

    public function revertirReasignacion(int $loteId): void
    {
        $this->autorizarAdministracion();

        $lote = ReasignacionDocenteLote::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($loteId);

        $resultado = app(ReasignacionDocenteMasivaService::class)->revertir($lote, auth()->id());

        $this->dispatch('swal', [
            'title' => $resultado['omitidas'] > 0 ? 'Reversión parcial' : 'Reasignación revertida',
            'text' => "Restauradas: {$resultado['revertidas']}. Omitidas por cambios posteriores: {$resultado['omitidas']}.",
            'icon' => $resultado['omitidas'] > 0 ? 'warning' : 'success',
            'position' => 'top-end',
        ]);
    }

    protected function rules(): array
    {
        return [
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'profesor_id' => ['nullable', 'integer', 'exists:personas,id'],
        ];
    }

    public function updatedCicloEscolarId(): void
    {
        $this->limpiarFormulario();
        $this->cerrarModalEdicion();
        $this->modalReasignacionAbierto = false;
        $this->modalHistorialReasignacionesAbierto = false;
        $this->resetEstadoReasignacion();
        $this->limpiarSeleccionTabla();
        $this->limpiarFiltros();
        $this->resetPage('materiasPage');
    }

    public function updatedBuscar(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroGeneracion(): void
    {
        $this->limpiarSeleccionTabla();
        $this->reset(['filtro_grado', 'filtro_semestre', 'filtro_grupo']);
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroEstado(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroGrado(): void
    {
        $this->limpiarSeleccionTabla();
        $this->reset(['filtro_semestre', 'filtro_grupo']);
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroSemestre(): void
    {
        $this->limpiarSeleccionTabla();
        $this->reset(['filtro_grupo']);
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroGrupo(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroHorario(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroProfesor(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedPorPaginaMaterias($value): void
    {
        $this->limpiarSeleccionTabla();
        $permitidos = [10, 15, 25, 50];
        $this->porPaginaMaterias = in_array((int) $value, $permitidos, true) ? (int) $value : 10;
        $this->resetPage('materiasPage');
    }

    public function updatedGrupoId(): void
    {
        $this->reset(['materia_id']);
        $this->resetValidation(['grupo_id', 'materia_id']);
    }

    public function updatedEditarGrupoId(): void
    {
        $this->editar_materia_id = '';
        $this->resetValidation(['editar_grupo_id', 'editar_materia_id']);
    }

    public function guardarMateria(): void
    {
        $this->validate();

        app(CicloNivelGateService::class)->asegurar(
            (int) $this->ciclo_escolar_id,
            (int) $this->nivel->id,
            'asignacion_materias'
        );

        $grupo = Grupo::query()
            ->whereKey($this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();
        $materia = Materia::query()->find($this->materia_id);

        if (!$grupo || !$materia) {
            $this->addError('grupo_id', 'El grupo o la materia ya no están disponibles.');
            return;
        }

        if (
            (int) $materia->nivel_id !== (int) $grupo->nivel_id
            || (int) $materia->grado_id !== (int) $grupo->grado_id
            || ($this->esBachillerato && (int) $materia->semestre_id !== (int) $grupo->semestre_id)
        ) {
            $this->addError('materia_id', 'La materia no corresponde al contexto académico del grupo.');
            return;
        }

        $duplicada = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->where('materia_id', $materia->id)
            ->exists();

        if ($duplicada) {
            $this->addError('materia_id', 'Esta materia ya tiene una carga en el grupo y ciclo seleccionados.');
            return;
        }

        $profesorId = $materia->receso ? null : (filled($this->profesor_id) ? (int) $this->profesor_id : null);
        app(PlantillaDocenteService::class)->validar($profesorId, (int) $this->ciclo_escolar_id, (int) $this->nivel->id);

        DB::transaction(function () use ($grupo, $materia, $profesorId) {
            $asignacion = AsignacionMateriaModel::query()->create([
                'materia_id' => $materia->id,
                'grupo_id' => $grupo->id,
                'profesor_id' => $profesorId,
                'ciclo_escolar_id' => $this->ciclo_escolar_id,
                'nivel_id' => $grupo->nivel_id,
                'grado_id' => $grupo->grado_id,
                'generacion_id' => $grupo->generacion_id,
                'semestre_id' => $grupo->semestre_id,
                'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
            ]);

            $this->ultimoRegistroId = $asignacion->id;
            $this->ultimoMovimiento = 'registrada';
        });

        $this->limpiarFormularioDespuesDeGuardar();
        $this->dispatch('swal', [
            'title' => 'Carga académica registrada',
            'text' => 'Se guardó dentro del ciclo seleccionado sin modificar ciclos anteriores.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function editar(int $id): void
    {
        $asignacion = AsignacionMateriaModel::query()
            ->with(['profesor'])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $this->resetValidation([
            'editar_grupo_id',
            'editar_materia_id',
            'editar_profesor_id',
        ]);

        $this->editandoId = $asignacion->id;
        $this->edicionTieneHistorial = $asignacion->tieneHistorial();
        $this->editar_grupo_id = $asignacion->grupo_id;
        $this->editar_materia_id = $asignacion->materia_id;
        $this->editar_profesor_id = $asignacion->profesor_id ?: '';

        $this->modalEditarAbierto = true;
    }

    public function actualizarMateria(): void
    {
        $this->validate([
            'editandoId' => ['required', 'integer', 'exists:asignacion_materias,id'],
            'editar_grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'editar_materia_id' => ['required', 'integer', 'exists:materias,id'],
            'editar_profesor_id' => ['nullable', 'integer', 'exists:personas,id'],
        ], [], [
            'editar_grupo_id' => 'grupo',
            'editar_materia_id' => 'materia',
            'editar_profesor_id' => 'profesor',
        ]);

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($this->editandoId);

        if (
            $asignacion->tieneHistorial()
            && (
                (int) $asignacion->grupo_id !== (int) $this->editar_grupo_id
                || (int) $asignacion->materia_id !== (int) $this->editar_materia_id
            )
        ) {
            $this->addError(
                'editar_grupo_id',
                'La carga ya tiene historial. Para proteger horarios y calificaciones solo puedes cambiar el profesor responsable.'
            );
            return;
        }

        $grupo = Grupo::query()
            ->whereKey($this->editar_grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();

        $materia = Materia::query()->find($this->editar_materia_id);

        if (!$grupo || !$materia) {
            $this->addError('editar_grupo_id', 'El grupo o la materia ya no están disponibles.');
            return;
        }

        if (
            (int) $materia->nivel_id !== (int) $grupo->nivel_id
            || (int) $materia->grado_id !== (int) $grupo->grado_id
            || ($this->esBachillerato && (int) $materia->semestre_id !== (int) $grupo->semestre_id)
        ) {
            $this->addError('editar_materia_id', 'La materia no corresponde al contexto académico del grupo.');
            return;
        }

        $duplicada = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->where('materia_id', $materia->id)
            ->whereKeyNot($asignacion->id)
            ->exists();

        if ($duplicada) {
            $this->addError('editar_materia_id', 'Esta materia ya tiene una carga en el grupo y ciclo seleccionados.');
            return;
        }

        $profesorId = $materia->receso
            ? null
            : (filled($this->editar_profesor_id) ? (int) $this->editar_profesor_id : null);

        app(PlantillaDocenteService::class)->validar($profesorId, (int) $this->ciclo_escolar_id, (int) $this->nivel->id);

        $profesorAnteriorId = $asignacion->profesor_id ? (int) $asignacion->profesor_id : null;

        DB::transaction(function () use ($asignacion, $grupo, $materia, $profesorId, $profesorAnteriorId) {
            $asignacion->update([
                'materia_id' => $materia->id,
                'grupo_id' => $grupo->id,
                'profesor_id' => $profesorId,
                'nivel_id' => $grupo->nivel_id,
                'grado_id' => $grupo->grado_id,
                'generacion_id' => $grupo->generacion_id,
                'semestre_id' => $grupo->semestre_id,
            ]);

            app(ReasignacionDocenteMasivaService::class)->sincronizarProfesorIndividual(
                asignacion: $asignacion,
                profesorAnteriorId: $profesorAnteriorId,
                profesorNuevoId: $profesorId,
                usuarioId: auth()->id(),
            );

            $this->ultimoRegistroId = $asignacion->id;
            $this->ultimoMovimiento = 'actualizada';
        });

        $this->cerrarModalEdicion();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Carga académica actualizada',
            'text' => 'Los cambios se aplicaron únicamente a la carga seleccionada.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function cerrarModalEdicion(): void
    {
        $this->modalEditarAbierto = false;
        $this->reset([
            'editandoId',
            'edicionTieneHistorial',
            'editar_grupo_id',
            'editar_materia_id',
            'editar_profesor_id',
        ]);
        $this->resetValidation([
            'editandoId',
            'editar_grupo_id',
            'editar_materia_id',
            'editar_profesor_id',
        ]);
    }

    public function confirmar(int $id): void
    {
        $this->cambiarEstado(
            $id,
            AsignacionMateriaModel::ESTADO_ACTIVA,
            'Carga confirmada',
            'La carga quedó activa y disponible para los procesos académicos.'
        );
    }

    public function cerrar(int $id): void
    {
        $this->cambiarEstado(
            $id,
            AsignacionMateriaModel::ESTADO_CERRADA,
            'Carga cerrada',
            'Se conservan sus horarios, calificaciones y registros históricos.'
        );
    }

    public function archivar(int $id): void
    {
        $this->cambiarEstado(
            $id,
            AsignacionMateriaModel::ESTADO_ARCHIVADA,
            'Carga archivada',
            'La carga dejó de estar activa, pero todo su historial permanece disponible.'
        );

        if ((int) $this->editandoId === $id) {
            $this->cerrarModalEdicion();
        }
    }

    public function reactivar(int $id): void
    {
        $this->cambiarEstado(
            $id,
            AsignacionMateriaModel::ESTADO_ACTIVA,
            'Carga reactivada',
            'La carga volvió a estar disponible sin perder información histórica.'
        );
    }

    public function eliminar(int $id): void
    {
        $this->autorizarAdministracion();

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        if ($asignacion->tieneHistorial()) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'Esta carga ya tiene horarios, calificaciones o movimientos de auditoría. Archívala para conservar el historial.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        DB::transaction(fn() => $asignacion->delete());

        if ((int) $this->editandoId === $id) {
            $this->cerrarModalEdicion();
        }

        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Carga eliminada',
            'text' => 'La materia se eliminó únicamente de este ciclo. No se modificaron otros ciclos.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function confirmarTodas(): void
    {
        $this->autorizarAdministracion();

        AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('estado', AsignacionMateriaModel::ESTADO_BORRADOR)
            ->update([
                'estado' => AsignacionMateriaModel::ESTADO_ACTIVA,
                'confirmada_at' => now(),
                'confirmada_por' => auth()->id(),
                'fecha_inicio' => DB::raw('COALESCE(fecha_inicio, CURRENT_DATE)'),
            ]);

        $this->dispatch('swal', ['title' => 'Cargas confirmadas', 'icon' => 'success', 'position' => 'top-end']);
    }

    private function cambiarEstado(int $id, string $estado, string $titulo, string $texto): void
    {
        $this->autorizarAdministracion();

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $datos = ['estado' => $estado];

        if ($estado === AsignacionMateriaModel::ESTADO_ACTIVA) {
            $datos['confirmada_at'] = now();
            $datos['confirmada_por'] = auth()->id();
            $datos['fecha_inicio'] = $asignacion->fecha_inicio ?: now()->toDateString();
            $datos['fecha_fin'] = null;
        }

        if (in_array($estado, [AsignacionMateriaModel::ESTADO_CERRADA, AsignacionMateriaModel::ESTADO_ARCHIVADA], true)) {
            $datos['fecha_fin'] = now()->toDateString();
        }

        $asignacion->update($datos);

        $this->dispatch('swal', [
            'title' => $titulo,
            'text' => $texto,
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    /**
     * Copia por nivel las cargas del ciclo origen. Siempre crea IDs nuevos.
     * Los horarios se copian únicamente cuando el administrador lo solicita.
     */
    public function copiarDesdeCiclo(): void
    {
        $this->autorizarAdministracion();

        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'ciclo_origen_id' => ['required', 'integer', 'exists:ciclo_escolares,id', Rule::notIn([(int) $this->ciclo_escolar_id])],
        ]);

        $creadas = 0;
        $omitidas = 0;
        $horariosCopiados = 0;

        DB::transaction(function () use (&$creadas, &$omitidas, &$horariosCopiados) {
            $origenes = AsignacionMateriaModel::query()
                ->with(['grupo', 'horarios' => fn($q) => $q->where('ciclo_escolar_id', $this->ciclo_origen_id)])
                ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                ->where('nivel_id', $this->nivel->id)
                ->where('estado', '!=', AsignacionMateriaModel::ESTADO_ARCHIVADA)
                ->get();

            foreach ($origenes as $origen) {
                $grupoDestino = $this->resolverGrupoDestino($origen);

                if (!$grupoDestino) {
                    $omitidas++;
                    continue;
                }

                $existe = AsignacionMateriaModel::query()
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('grupo_id', $grupoDestino->id)
                    ->where('materia_id', $origen->materia_id)
                    ->first();

                if ($existe) {
                    $omitidas++;
                    continue;
                }

                $nueva = AsignacionMateriaModel::query()->create([
                    'materia_id' => $origen->materia_id,
                    'grupo_id' => $grupoDestino->id,
                    'profesor_id' => $this->copiar_profesores ? $origen->profesor_id : null,
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    'nivel_id' => $grupoDestino->nivel_id,
                    'grado_id' => $grupoDestino->grado_id,
                    'generacion_id' => $grupoDestino->generacion_id,
                    'semestre_id' => $grupoDestino->semestre_id,
                    'orden' => $origen->orden,
                    'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
                    'asignacion_origen_id' => $origen->id,
                ]);
                $creadas++;

                if (!$this->copiar_horarios) {
                    continue;
                }

                foreach ($origen->horarios as $horarioOrigen) {
                    $ocupada = Horario::query()
                        ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                        ->where('grupo_id', $grupoDestino->id)
                        ->where('dia_id', $horarioOrigen->dia_id)
                        ->where('hora_id', $horarioOrigen->hora_id)
                        ->exists();

                    if ($ocupada) {
                        continue;
                    }

                    Horario::query()->create([
                        'nivel_id' => $grupoDestino->nivel_id,
                        'grado_id' => $grupoDestino->grado_id,
                        'generacion_id' => $grupoDestino->generacion_id,
                        'semestre_id' => $grupoDestino->semestre_id,
                        'grupo_id' => $grupoDestino->id,
                        'hora_id' => $horarioOrigen->hora_id,
                        'dia_id' => $horarioOrigen->dia_id,
                        'asignacion_materia_id' => $nueva->id,
                        'taller_sesion_id' => null,
                        'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    ]);
                    $horariosCopiados++;
                }
            }
        });

        $this->dispatch('swal', [
            'title' => 'Preparación del ciclo terminada',
            'text' => "Nuevas: {$creadas}. Omitidas: {$omitidas}. Horarios copiados: {$horariosCopiados}. Revisa y confirma las cargas.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function resolverGrupoDestino(AsignacionMateriaModel $origen): ?Grupo
    {
        $origen->loadMissing('grupo');
        $grupoOrigen = $origen->grupo;

        if (!$grupoOrigen) {
            return null;
        }

        $idsVigentes = Inscripcion::query()
            ->visiblesEnListas()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $grupoOrigen->grado_id)
            ->whereNotNull('grupo_id')
            ->when($grupoOrigen->semestre_id, fn($q) => $q->where('semestre_id', $grupoOrigen->semestre_id))
            ->pluck('grupo_id')
            ->unique();

        return Grupo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $grupoOrigen->grado_id)
            ->where('asignacion_grupo_id', $grupoOrigen->asignacion_grupo_id)
            ->when(
                $grupoOrigen->semestre_id,
                fn($q) => $q->where('semestre_id', $grupoOrigen->semestre_id),
                fn($q) => $q->whereNull('semestre_id')
            )
            ->when($idsVigentes->isNotEmpty(), fn($q) => $q->whereIn('id', $idsVigentes))
            ->orderByDesc('generacion_id')
            ->first()
            ?? Grupo::query()->find($grupoOrigen->id);
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'buscar',
            'filtro_generacion',
            'filtro_estado',
            'filtro_grado',
            'filtro_semestre',
            'filtro_grupo',
            'filtro_horario',
            'filtro_profesor',
        ]);

        $this->resetPage('materiasPage');
    }

    public function limpiarFormularioDespuesDeGuardar(): void
    {
        $grupo = $this->grupo_id;
        $this->reset(['materia_id', 'profesor_id']);
        $this->grupo_id = $grupo;
        $this->resetValidation(['grupo_id', 'materia_id', 'profesor_id']);
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['grupo_id', 'materia_id', 'profesor_id']);
        $this->resetValidation(['grupo_id', 'materia_id', 'profesor_id']);
    }

    public function ordenarMateriasPorGrupoJs($grupoId, $ids): void
    {
        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $index => $id) {
            AsignacionMateriaModel::query()
                ->whereKey($id)
                ->where('grupo_id', $grupoId)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->update(['orden' => $index + 1]);
        }
    }

    private function autorizarAdministracion(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede confirmar, cerrar, archivar, eliminar o copiar cargas.');
    }

    public function render()
    {
        return view('livewire.accion.asignacion-materia');
    }
}
