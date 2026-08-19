<?php

namespace App\Livewire;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Semestre;
use App\Services\ContextoEscolarService;
use App\Services\ListaAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ListasGenerales extends Component
{
    public Collection $niveles;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public Collection $parciales;

    public ?int $nivel_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public ?int $ciclo_escolar_id = null;

    /** @var array<int, int|string> */
    public array $alumnos_seleccionados = [];

    public string $buscar_alumno = '';
    public string $modo_descarga = 'seleccionados';
    public string $tipo_descarga = 'formatos';
    public string $opcion_descarga = 'personalizadores';
    public bool $mostrar_motivo = false;

    public function mount(): void
    {
        abort_unless((bool) (auth()->user()?->is_admin ?? false), 403);

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = Parcial::query()
            ->orderBy('id')
            ->get(['id', 'parcial', 'descripcion']);

        $ciclo = CicloEscolar::query()
            ->where('es_actual', true)
            ->first()
            ?? CicloEscolar::query()
                ->orderByDesc('inicio_anio')
                ->orderByDesc('fin_anio')
                ->first();

        $this->ciclo_escolar_id = $ciclo?->id;
    }

    public function updatedNivelId(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->mostrar_motivo = false;

        if ($this->nivel_id) {
            $this->cargarGeneraciones();
            if ($this->esFormatoGlobal()) {
                $this->cargarGrados();
            }
        } else {
            $this->tipo_descarga = 'formatos';
            if (!in_array($this->opcion_descarga, ['personalizadores', 'etiquetas'], true)) {
                $this->opcion_descarga = 'personalizadores';
            }
            if (!in_array($this->modo_descarga, ['seleccionados', 'todos_activos'], true)) {
                $this->modo_descarga = 'seleccionados';
            }
        }

        if (!$this->esFormatoGlobal()) {
            $this->limpiarSeleccionAlumnos();
        }
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();

        $this->cargarGrados();

        if (!$this->esFormatoGlobal()) {
            $this->limpiarSeleccionAlumnos();
        }
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = collect();
        $this->grupos = collect();

        $this->cargarSemestres();
        $this->cargarGrupos();

        if (!$this->esFormatoGlobal()) {
            $this->limpiarSeleccionAlumnos();
        }
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->grupos = collect();
        $this->cargarGrupos();

        if (!$this->esFormatoGlobal()) {
            $this->limpiarSeleccionAlumnos();
        }
    }

    public function updatedGrupoId(): void
    {
        if (!$this->esFormatoGlobal()) {
            $this->limpiarSeleccionAlumnos();
        }
    }

    public function updatedTipoDescarga(): void
    {
        if (!$this->nivel_id) {
            $this->tipo_descarga = 'formatos';
        }

        if (($this->esBachillerato() || $this->esSecundaria()) && in_array($this->tipo_descarga, ['evaluacion', 'asistencia'], true)) {
            $this->tipo_descarga = 'grupo';
        }

        $opciones = $this->opcionesDescarga();
        if (!array_key_exists($this->opcion_descarga, $opciones)) {
            $this->opcion_descarga = array_key_first($opciones) ?? '';
        }

        if (!$this->esFormatoGlobal()) {
            if ($this->modo_descarga === 'todos_activos') {
                $this->modo_descarga = 'grupo';
            }
            $this->limpiarSeleccionAlumnos();
        }

        if ($this->tipo_descarga !== 'grupo') {
            $this->mostrar_motivo = false;
        }
    }

    public function updatedOpcionDescarga(): void
    {
        if (!$this->nivel_id && !in_array($this->opcion_descarga, ['personalizadores', 'etiquetas'], true)) {
            $this->opcion_descarga = 'personalizadores';
        }

        if ($this->esFormatoGlobal()) {
            if (!$this->nivel_id && !in_array($this->modo_descarga, ['seleccionados', 'todos_activos'], true)) {
                $this->modo_descarga = 'seleccionados';
            }
            return;
        }

        if ($this->modo_descarga === 'todos_activos') {
            $this->modo_descarga = 'grupo';
        }
        $this->limpiarSeleccionAlumnos();
    }

    public function updatedModoDescarga(): void
    {
        $permitidos = $this->modosDescarga();
        if (!array_key_exists($this->modo_descarga, $permitidos)) {
            $this->modo_descarga = array_key_first($permitidos) ?? 'grupo';
        }

        $this->mostrar_motivo = false;

        if ($this->modo_descarga === 'todos_activos') {
            $this->nivel_id = null;
            $this->generacion_id = null;
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
            $this->generaciones = collect();
            $this->grados = collect();
            $this->semestres = collect();
            $this->grupos = collect();
            $this->tipo_descarga = 'formatos';
            if (!in_array($this->opcion_descarga, ['personalizadores', 'etiquetas'], true)) {
                $this->opcion_descarga = 'personalizadores';
            }
        }

        if ($this->modo_descarga === 'nivel') {
            $this->generacion_id = null;
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->semestres = collect();
            $this->grupos = collect();
        }

        if ($this->esFormatoGlobal() && $this->modo_descarga === 'seleccionados' && $this->nivel_id) {
            if ($this->generaciones->isEmpty()) {
                $this->cargarGeneraciones();
            }

            $this->cargarGrados();
            $this->cargarSemestres();
            $this->cargarGrupos();
        }

        if ($this->esFormatoGlobal() && $this->modo_descarga === 'grupo' && !$this->generacion_id) {
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
            $this->grados = collect();
            $this->semestres = collect();
            $this->grupos = collect();
        }

        if (!$this->esFormatoGlobal()) {
            $this->limpiarSeleccionAlumnos();
        }
    }

    public function updatedAlumnosSeleccionados(mixed $value = null, mixed $key = null): void
    {
        if ($this->modo_descarga !== 'seleccionados') {
            $this->alumnos_seleccionados = [];
            return;
        }

        $this->alumnos_seleccionados = $this->idsAlumnosSeleccionadosValidos;
    }

    public function seleccionarNivelRapido(?int $nivelId): void
    {
        if ($nivelId !== null && !$this->niveles->contains('id', $nivelId)) {
            return;
        }

        $this->nivel_id = $nivelId;
        $this->updatedNivelId();
    }

    public function limpiarFiltros(): void
    {
        $this->nivel_id = null;
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->generaciones = collect();
        $this->grados = collect();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->modo_descarga = 'seleccionados';
        $this->tipo_descarga = 'formatos';
        $this->opcion_descarga = 'personalizadores';
        $this->mostrar_motivo = false;
        $this->buscar_alumno = '';
        $this->alumnos_seleccionados = [];
        $this->resetErrorBag();
    }

    public function seleccionarTodos(): void
    {
        $ids = $this->alumnosDisponibles
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if ($this->esFormatoGlobal()) {
            $this->alumnos_seleccionados = collect($this->alumnos_seleccionados)
                ->map(fn ($id): int => (int) $id)
                ->merge($ids)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        } else {
            $this->alumnos_seleccionados = $ids;
        }
    }

    public function limpiarSeleccion(): void
    {
        $this->alumnos_seleccionados = [];
    }

    public function quitarAlumnoSeleccionado(int $alumnoId): void
    {
        $this->alumnos_seleccionados = collect($this->alumnos_seleccionados)
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $alumnoId)
            ->unique()
            ->values()
            ->all();
    }

    private function limpiarSeleccionAlumnos(): void
    {
        $this->alumnos_seleccionados = [];
        $this->buscar_alumno = '';
        $this->resetErrorBag('alumnos_seleccionados');
    }

    private function cargarGeneraciones(): void
    {
        if (!$this->nivel_id || !$this->ciclo_escolar_id) {
            $this->generaciones = collect();
            return;
        }

        $this->generaciones = app(ContextoEscolarService::class)->generaciones(
            nivelId: $this->nivel_id,
            cicloEscolarId: $this->ciclo_escolar_id,
        );
    }

    private function cargarGrados(): void
    {
        if (!$this->nivel_id || !$this->ciclo_escolar_id) {
            $this->grados = collect();
            return;
        }

        if (!$this->esFormatoGlobal() && !$this->generacion_id) {
            $this->grados = collect();
            return;
        }

        $this->grados = app(ContextoEscolarService::class)->grados(
            nivelId: $this->nivel_id,
            cicloEscolarId: $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
        );
    }

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato() || !$this->nivel_id || !$this->ciclo_escolar_id || !$this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = app(ContextoEscolarService::class)->semestres(
            nivelId: $this->nivel_id,
            cicloEscolarId: $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
        );
    }

    private function cargarGrupos(): void
    {
        if (
            !$this->nivel_id
            || !$this->ciclo_escolar_id
            || !$this->grado_id
            || ($this->esBachillerato() && !$this->semestre_id)
        ) {
            $this->grupos = collect();
            return;
        }

        if (!$this->esFormatoGlobal() && !$this->generacion_id) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = app(ContextoEscolarService::class)->grupos(
            nivelId: $this->nivel_id,
            cicloEscolarId: $this->ciclo_escolar_id,
            generacionId: $this->generacion_id,
            gradoId: $this->grado_id,
            semestreId: $this->semestre_id,
            bachillerato: $this->esBachillerato(),
        );
    }

    public function tiposDescarga(): array
    {
        if (!$this->nivel_id) {
            return ['formatos' => 'Formatos'];
        }

        $tipos = [
            'evaluacion' => 'Lista de evaluación',
            'asistencia' => 'Lista de asistencia',
            'grupo' => 'Lista de grupo',
            'formatos' => 'Formatos',
        ];

        if ($this->esBachillerato() || $this->esSecundaria()) {
            unset($tipos['evaluacion'], $tipos['asistencia']);
        }

        return $tipos;
    }

    public function opcionesDescarga(): array
    {
        if (!$this->nivel_id && $this->tipo_descarga === 'formatos') {
            return [
                'personalizadores' => 'Personalizadores',
                'etiquetas' => 'Etiquetas',
            ];
        }

        if ($this->esBachillerato() && $this->tipo_descarga !== 'formatos') {
            return $this->parciales
                ->mapWithKeys(fn ($parcial): array => [
                    'parcial_' . $parcial->id => $this->textoParcial($parcial),
                ])
                ->toArray();
        }

        return match ($this->tipo_descarga) {
            'evaluacion', 'asistencia', 'grupo' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],
            'formatos' => [
                'sece' => 'SECE',
                'sece_interna' => 'SECE interna',
                'personalizadores' => 'Personalizadores',
                'etiquetas' => 'Etiquetas',
            ],
            default => [],
        };
    }

    public function modosDescarga(): array
    {
        if ($this->esFormatoGlobal()) {
            $modos = [
                'seleccionados' => 'Alumnos seleccionados',
            ];

            if ($this->nivel_id) {
                $modos['grupo'] = 'Grupo completo';
                $modos['nivel'] = 'Nivel completo';
            }

            $modos['todos_activos'] = 'Todos los alumnos activos';

            return $modos;
        }

        return [
            'grupo' => 'Grupo seleccionado',
            'seleccionados' => 'Alumnos seleccionados',
            'nivel' => 'Todas las listas del nivel',
        ];
    }

    public function esFormatoGlobal(): bool
    {
        return $this->tipo_descarga === 'formatos'
            && in_array($this->opcion_descarga, ['personalizadores', 'etiquetas'], true);
    }

    public function esBachillerato(): bool
    {
        return (int) ($this->nivelSeleccionado?->id ?? 0) === 4
            || $this->nivelSeleccionado?->slug === 'bachillerato';
    }

    public function esSecundaria(): bool
    {
        return (int) ($this->nivelSeleccionado?->id ?? 0) === 3
            || $this->nivelSeleccionado?->slug === 'secundaria';
    }

    #[Computed]
    public function nivelSeleccionado(): ?Nivel
    {
        if (!$this->nivel_id) {
            return null;
        }

        return $this->niveles->firstWhere('id', $this->nivel_id);
    }

    #[Computed]
    public function generacionSeleccionada(): ?Generacion
    {
        return $this->generacion_id
            ? $this->generaciones->firstWhere('id', $this->generacion_id)
            : null;
    }

    #[Computed]
    public function gradoSeleccionado(): ?Grado
    {
        return $this->grado_id
            ? $this->grados->firstWhere('id', $this->grado_id)
            : null;
    }

    #[Computed]
    public function semestreSeleccionado(): ?Semestre
    {
        return $this->semestre_id
            ? $this->semestres->firstWhere('id', $this->semestre_id)
            : null;
    }

    #[Computed]
    public function grupoSeleccionado(): ?Grupo
    {
        return $this->grupo_id
            ? $this->grupos->firstWhere('id', $this->grupo_id)
            : null;
    }

    private function consultaGlobalAlumnos(): Builder
    {
        return Inscripcion::query()
            ->visiblesEnListas()
            ->with([
                'nivel:id,nombre,slug',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,nivel_id,grado_id,generacion_id,semestre_id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
            ]);
    }

    #[Computed]
    public function alumnosDisponibles(): Collection
    {
        if ($this->modo_descarga !== 'seleccionados') {
            return collect();
        }

        if ($this->esFormatoGlobal()) {
            $query = $this->consultaGlobalAlumnos()
                ->when($this->nivel_id, fn (Builder $q) => $q->where('nivel_id', $this->nivel_id))
                ->when($this->generacion_id, fn (Builder $q) => $q->where('generacion_id', $this->generacion_id))
                ->when($this->grado_id, fn (Builder $q) => $q->where('grado_id', $this->grado_id))
                ->when($this->semestre_id, fn (Builder $q) => $q->where('semestre_id', $this->semestre_id))
                ->when($this->grupo_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_id));

            $busqueda = trim($this->buscar_alumno);
            if ($busqueda !== '') {
                $terminos = preg_split('/\s+/', $busqueda) ?: [];

                foreach ($terminos as $termino) {
                    $termino = trim((string) $termino);
                    if ($termino === '') {
                        continue;
                    }

                    $like = '%' . $termino . '%';
                    $query->where(function (Builder $q) use ($like): void {
                        $q->where('matricula', 'like', $like)
                            ->orWhere('curp', 'like', $like)
                            ->orWhere('nombre', 'like', $like)
                            ->orWhere('apellido_paterno', 'like', $like)
                            ->orWhere('apellido_materno', 'like', $like);
                    });
                }
            }

            return $query
                ->orderBy('nivel_id')
                ->orderBy('grado_id')
                ->orderBy('grupo_id')
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre')
                ->get();
        }

        if (!$this->contextoGrupoCompleto()) {
            return collect();
        }

        $grupo = $this->grupoSeleccionado;
        if (!$grupo || !$this->nivelSeleccionado) {
            return collect();
        }

        $alumnos = app(ListaAcademicaService::class)
            ->alumnosPorContexto(
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                grupoIds: [(int) $grupo->id],
                fechaCorte: now(),
                nivelId: (int) $this->nivel_id,
                gradoId: (int) $this->grado_id,
                generacionId: (int) $this->generacion_id,
                semestreId: $this->esBachillerato() ? (int) $this->semestre_id : null,
                usarHistorialCiclo: true,
                incluirNoActivos: false,
                usarActualComoRespaldo: true,
                incluirTodaGeneracionBachillerato: $this->esBachillerato(),
            )
            ->filter(fn ($alumno): bool => $alumno->visibleEnListas());

        $busqueda = mb_strtolower(trim($this->buscar_alumno));
        if ($busqueda !== '') {
            $alumnos = $alumnos->filter(function ($alumno) use ($busqueda): bool {
                $texto = mb_strtolower(implode(' ', [
                    (string) ($alumno->matricula ?? ''),
                    (string) ($alumno->curp ?? ''),
                    (string) ($alumno->nombre ?? ''),
                    (string) ($alumno->apellido_paterno ?? ''),
                    (string) ($alumno->apellido_materno ?? ''),
                ]));

                return str_contains($texto, $busqueda);
            });
        }

        return $alumnos
            ->unique(fn ($alumno): int => (int) $alumno->id)
            ->sortBy(fn ($alumno): string => mb_strtolower(trim(
                (string) $alumno->apellido_paterno . ' ' .
                (string) $alumno->apellido_materno . ' ' .
                (string) $alumno->nombre
            )))
            ->values();
    }

    #[Computed]
    public function idsAlumnosSeleccionadosValidos(): array
    {
        $ids = collect($this->alumnos_seleccionados)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        if ($this->esFormatoGlobal()) {
            return Inscripcion::query()
                ->visiblesEnListas()
                ->whereIn('id', $ids)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        }

        $disponibles = $this->alumnosDisponibles
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);

        return $ids
            ->filter(fn (int $id): bool => $disponibles->contains($id))
            ->values()
            ->all();
    }

    #[Computed]
    public function alumnosSeleccionadosDetalle(): Collection
    {
        $ids = $this->idsAlumnosSeleccionadosValidos;
        if ($ids === []) {
            return collect();
        }

        return $this->consultaGlobalAlumnos()
            ->whereIn('id', $ids)
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    #[Computed]
    public function resumenSeleccionPorNivel(): Collection
    {
        return $this->alumnosSeleccionadosDetalle
            ->groupBy(fn ($alumno): string => (string) ($alumno->nivel?->nombre ?? 'Sin nivel'))
            ->map(fn (Collection $alumnos, string $nivel): array => [
                'nivel' => $nivel,
                'total' => $alumnos->count(),
            ])
            ->values();
    }

    #[Computed]
    public function totalAlumnosDisponibles(): int
    {
        return $this->alumnosDisponibles->count();
    }

    #[Computed]
    public function totalAlumnosSeleccionados(): int
    {
        return count($this->idsAlumnosSeleccionadosValidos);
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (!$this->tipo_descarga || !$this->opcion_descarga || !$this->ciclo_escolar_id) {
            return false;
        }

        if ($this->esFormatoGlobal()) {
            return match ($this->modo_descarga) {
                'seleccionados' => $this->totalAlumnosSeleccionados > 0,
                'grupo' => $this->nivel_id !== null && $this->contextoGrupoCompleto(),
                'nivel' => $this->nivel_id !== null,
                'todos_activos' => true,
                default => false,
            };
        }

        if (!$this->nivel_id) {
            return false;
        }

        if ($this->modo_descarga === 'nivel') {
            return true;
        }

        if (!$this->contextoGrupoCompleto()) {
            return false;
        }

        return $this->modo_descarga !== 'seleccionados' || $this->totalAlumnosSeleccionados > 0;
    }

    private function contextoGrupoCompleto(): bool
    {
        if (!$this->generacion_id || !$this->grado_id || !$this->grupo_id) {
            return false;
        }

        return !$this->esBachillerato() || (bool) $this->semestre_id;
    }

    #[Computed]
    public function urlPdf(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        if ($this->esFormatoGlobal()) {
            return route('listas-generales.formatos.pdf', [
                'modo_descarga' => $this->modo_descarga,
                'ciclo_escolar_id' => $this->ciclo_escolar_id,
                'nivel_id' => $this->nivel_id,
                'generacion_id' => $this->generacion_id,
                'grado_id' => $this->grado_id,
                'semestre_id' => $this->semestre_id,
                'grupo_id' => $this->grupo_id,
                'alumnos' => $this->modo_descarga === 'seleccionados'
                    ? implode(',', $this->idsAlumnosSeleccionadosValidos)
                    : null,
                'opcion_descarga' => $this->opcion_descarga,
            ]);
        }

        return route('accion.generales.listas.pdf', [
            'slug_nivel' => $this->nivelSeleccionado?->slug,
            'modo_descarga' => $this->modo_descarga,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'generacion_id' => in_array($this->modo_descarga, ['grupo', 'seleccionados'], true) ? $this->generacion_id : null,
            'grado_id' => in_array($this->modo_descarga, ['grupo', 'seleccionados'], true) ? $this->grado_id : null,
            'semestre_id' => in_array($this->modo_descarga, ['grupo', 'seleccionados'], true) ? $this->semestre_id : null,
            'grupo_id' => in_array($this->modo_descarga, ['grupo', 'seleccionados'], true) ? $this->grupo_id : null,
            'alumnos' => $this->modo_descarga === 'seleccionados'
                ? implode(',', $this->idsAlumnosSeleccionadosValidos)
                : null,
            'tipo_descarga' => $this->tipo_descarga,
            'opcion_descarga' => $this->opcion_descarga,
            'mostrar_motivo' => $this->tipo_descarga === 'grupo' && $this->mostrar_motivo ? 1 : 0,
        ]);
    }

    #[Computed]
    public function textoBotonDescarga(): string
    {
        if ($this->modo_descarga === 'seleccionados') {
            return 'Descargar PDF (' . $this->totalAlumnosSeleccionados . ' alumnos)';
        }

        return match ($this->modo_descarga) {
            'nivel' => 'Descargar PDF del nivel',
            'todos_activos' => 'Descargar PDF institucional',
            default => 'Descargar PDF',
        };
    }

    #[Computed]
    public function mensajeEstadoDescarga(): string
    {
        if ($this->esFormatoGlobal()) {
            return match ($this->modo_descarga) {
                'seleccionados' => $this->totalAlumnosSeleccionados > 0
                    ? 'Listo para generar el documento con alumnos de uno o varios niveles.'
                    : 'Selecciona al menos un alumno activo. Puedes mezclar niveles sin perder la selección.',
                'grupo' => $this->contextoGrupoCompleto()
                    ? 'Se incluirán todos los alumnos activos del grupo seleccionado.'
                    : ($this->esBachillerato()
                        ? 'Selecciona generación, grado, semestre y grupo.'
                        : 'Selecciona generación, grado y grupo.'),
                'nivel' => $this->nivel_id
                    ? 'Se incluirán todos los alumnos activos del nivel seleccionado.'
                    : 'Selecciona un nivel.',
                'todos_activos' => 'Se incluirán todos los alumnos activos de Preescolar, Primaria, Secundaria y Bachillerato.',
                default => 'Completa los filtros para continuar.',
            };
        }

        if (!$this->nivel_id) {
            return 'Selecciona un nivel para habilitar los documentos académicos exclusivos de ese nivel.';
        }

        if ($this->modo_descarga === 'nivel') {
            return 'Se generarán todas las listas disponibles del nivel seleccionado.';
        }

        if (!$this->contextoGrupoCompleto()) {
            return $this->esBachillerato()
                ? 'Selecciona generación, grado, semestre y grupo.'
                : 'Selecciona generación, grado y grupo.';
        }

        if ($this->modo_descarga === 'seleccionados' && $this->totalAlumnosSeleccionados === 0) {
            return 'Selecciona al menos un alumno activo del grupo.';
        }

        return $this->modo_descarga === 'seleccionados'
            ? 'Se incluirán únicamente los alumnos marcados.'
            : 'Se incluirán todos los alumnos activos del grupo seleccionado.';
    }

    public function nombreAlumno($alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno?->apellido_paterno,
            $alumno?->apellido_materno,
            $alumno?->nombre,
        ])));
    }

    public function textoContextoAlumno($alumno): string
    {
        $partes = [
            $alumno?->nivel?->nombre,
            $alumno?->grado?->nombre ? $alumno->grado->nombre . '°' : null,
            $this->textoGrupo($alumno?->grupo),
        ];

        if ($alumno?->nivel?->slug === 'bachillerato' && $alumno?->semestre) {
            $partes[] = 'Sem. ' . ($alumno->semestre->numero ?? $alumno->semestre->id);
        }

        if ($alumno?->generacion) {
            $partes[] = 'Gen. ' . $alumno->generacion->anio_ingreso . '-' . $alumno->generacion->anio_egreso;
        }

        return implode(' · ', array_values(array_filter($partes)));
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre
            ? 'Grupo ' . $grupo->asignacionGrupo->nombre
            : 'Sin grupo';
    }

    public function textoParcial($parcial): string
    {
        if (!empty($parcial?->parcial)) {
            return mb_strtoupper((string) $parcial->parcial);
        }

        if (!empty($parcial?->descripcion)) {
            return mb_strtoupper((string) $parcial->descripcion);
        }

        return 'PARCIAL ' . ($parcial?->id ?? '');
    }

    public function etiquetaOpcionDescarga(): string
    {
        if ($this->tipo_descarga === 'formatos') {
            return 'Formato';
        }

        return $this->esBachillerato() ? 'Parcial' : 'Periodo';
    }

    public function render()
    {
        return view('livewire.listas-generales');
    }
}
