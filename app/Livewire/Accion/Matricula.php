<?php

namespace App\Livewire\Accion;

use App\Exports\MatriculaExport;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\GestionAcademicaService;
use App\Services\AlumnosNoVigentesService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class Matricula extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $generacionesDestino;
    public Collection $grados;
    public Collection $semestres;
    public Collection $semestresDestino;
    public Collection $grupos;
    public Collection $gruposDestino;

    public ?int $ciclo_escolar_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
    public string $estatus = 'todos';
    public string $search = '';
    public bool $mostrar_archivados = false;
    public int $perPage = 20;

    public array $selected = [];
    public bool $selectPage = false;
    public ?int $destino_ciclo_escolar_id = null;
    public ?int $destino_generacion_id = null;
    public ?int $destino_grado_id = null;
    public ?int $destino_semestre_id = null;
    public ?int $destino_grupo_id = null;
    public string $motivo_cambio = '';

    public bool $modalBitacora = false;
    public ?int $alumnoBitacoraId = null;

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
        $this->semestres = collect();
        $this->semestresDestino = collect();
        $this->grupos = collect();
        $this->gruposDestino = collect();

        $alumnoId = request()->integer('alumno');
        $buscar = trim((string) request('buscar', ''));

        if ($alumnoId > 0) {
            $buscar = (string) Inscripcion::query()->whereKey($alumnoId)->value('matricula');
        }

        if ($buscar !== '') {
            $this->search = $buscar;
        }
    }

    public function esBachillerato(): bool
    {
        return str_contains(
            mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')),
            'bachillerato'
        );
    }

    private function cargarGeneraciones(?int $cicloEscolarId = null): Collection
    {
        $esActual = $this->cicloEsActual($cicloEscolarId);

        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($cicloEscolarId, function (Builder $query) use ($cicloEscolarId, $esActual): void {
                $query->where(function (Builder $contexto) use ($cicloEscolarId, $esActual): void {
                    $contexto
                        ->whereHas('inscripcionCiclos', fn (Builder $historial) => $historial
                            ->where('ciclo_escolar_id', $cicloEscolarId)
                            ->where('nivel_id', $this->nivel->id))
                        ->orWhereHas('grupos', function (Builder $grupos) use ($cicloEscolarId, $esActual): void {
                            $grupos->withTrashed()
                                ->where('ciclo_escolar_id', $cicloEscolarId)
                                ->where('nivel_id', $this->nivel->id)
                                ->when($esActual, fn (Builder $grupo) => $grupo->where('estado', 'activo'));
                        });
                });
            })
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function cargarSemestres(?int $gradoId): Collection
    {
        return $gradoId
            ? Semestre::query()->where('grado_id', $gradoId)->orderBy('numero')->get()
            : collect();
    }

    private function cargarGrupos(?int $generacionId, ?int $gradoId, ?int $semestreId, ?int $cicloEscolarId = null): Collection
    {
        if (! $generacionId || ! $gradoId) {
            return collect();
        }

        $cicloEscolarId ??= $this->ciclo_escolar_id;
        $esActual = $this->cicloEsActual($cicloEscolarId);
        $query = $esActual ? Grupo::query() : Grupo::withTrashed();

        return $query
            ->with('asignacionGrupo')
            ->withCount(['inscripcionCiclos as alumnos_contexto_count' => function (Builder $alumnos) use ($cicloEscolarId, $esActual): void {
                $alumnos->where('ciclo_escolar_id', $cicloEscolarId);

                if ($esActual) {
                    $alumnos
                        ->where('estado', 'en_curso')
                        ->where('estatus_actual_ciclo', 'activo')
                        ->whereHas('inscripcion', fn (Builder $inscripcion) => $inscripcion->visiblesEnListas());

                    return;
                }

                $alumnos
                    ->where('estado', '!=', 'anulado')
                    ->whereIn('estatus_ingreso', ['activo', 'reingreso', 'no_promovido'])
                    ->where(function (Builder $resultado): void {
                        $resultado
                            ->whereNull('resultado_final')
                            ->orWhere('resultado_final', '!=', 'no_iniciado');
                    });
            }])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->when($esActual, fn (Builder $grupo) => $grupo->where('estado', 'activo'))
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $generacionId)
            ->where('grado_id', $gradoId)
            ->when(
                $this->esBachillerato(),
                fn (Builder $grupo) => $grupo->where('semestre_id', $semestreId),
                fn (Builder $grupo) => $grupo->whereNull('semestre_id')
            )
            ->get()
            ->sortBy(fn ($grupo) => $grupo->asignacionGrupo?->nombre ?? $grupo->id)
            ->values();
    }

    private function cicloEsActual(?int $cicloEscolarId): bool
    {
        if (! $cicloEscolarId) {
            return false;
        }

        return (bool) $this->ciclosEscolares
            ->firstWhere('id', (int) $cicloEscolarId)?->es_actual;
    }

    public function getEsCicloActualProperty(): bool
    {
        return $this->cicloEsActual($this->ciclo_escolar_id);
    }

    public function getOpcionesEstatusProperty(): array
    {
        return array_values(array_unique([
            ...GestionAcademicaService::ESTATUS,
            'promovido',
            'promovido_nivel',
            'grado_concluido',
        ]));
    }

    private function aplicarFiltroEstatusContexto(Builder $query, string $estatus): void
    {
        match ($estatus) {
            'activo' => $query
                ->where('estado', 'en_curso')
                ->whereIn('estatus_actual_ciclo', ['activo', 'reingreso', 'no_promovido']),
            'promovido' => $query->whereIn('resultado_final', ['promovido', 'promovido_grado']),
            'promovido_nivel' => $query->where('resultado_final', 'promovido_nivel'),
            'egresado' => $query->where(function (Builder $estado): void {
                $estado->where('resultado_final', 'egresado')
                    ->orWhere('estatus_actual_ciclo', 'egresado');
            }),
            'trasladado' => $query->where(function (Builder $estado): void {
                $estado->whereIn('resultado_final', ['traslado', 'trasladado'])
                    ->orWhereIn('estatus_actual_ciclo', ['traslado', 'trasladado']);
            }),
            default => $query->where(function (Builder $estado) use ($estatus): void {
                $estado->where('resultado_final', $estatus)
                    ->orWhere('estatus_actual_ciclo', $estatus);
            }),
        };
    }

    private function contarPorEstatusContexto(Builder $query, string $grupo): int
    {
        $cicloEscolarId = (int) $this->ciclo_escolar_id;

        return $query->whereHas('ciclosEscolaresHistorial', function (Builder $historial) use ($cicloEscolarId, $grupo): void {
            $historial
                ->where('ciclo_escolar_id', $cicloEscolarId)
                ->where('nivel_id', $this->nivel->id);

            if ($grupo === 'bajas') {
                $historial->where(function (Builder $estado): void {
                    $estado->whereIn('resultado_final', ['baja_temporal', 'baja_definitiva', 'traslado', 'trasladado', 'suspendido', 'inactivo'])
                        ->orWhereIn('estatus_actual_ciclo', ['baja_temporal', 'baja_definitiva', 'traslado', 'trasladado', 'suspendido', 'inactivo']);
                });

                return;
            }

            $this->aplicarFiltroEstatusContexto($historial, $grupo);
        })->count();
    }

    public function estatusContexto($contexto): string
    {
        if (! $contexto) {
            return 'inactivo';
        }

        if ($contexto->estado === 'en_curso') {
            return (string) ($contexto->estatus_actual_ciclo ?: 'activo');
        }

        return (string) ($contexto->resultado_final ?: $contexto->estatus_actual_ciclo ?: 'inactivo');
    }

    public function updatedCicloEscolarId(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'destino_generacion_id',
            'destino_grado_id',
            'destino_semestre_id',
            'destino_grupo_id',
        ]);
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->semestres = collect();
        $this->semestresDestino = collect();
        $this->grupos = collect();
        $this->gruposDestino = collect();
        $this->filtrosCambiaron();
    }

    public function updatedDestinoCicloEscolarId(): void
    {
        $this->reset([
            'destino_generacion_id',
            'destino_grado_id',
            'destino_semestre_id',
            'destino_grupo_id',
        ]);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->filtrosCambiaron();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->esBachillerato()
            ? collect()
            : $this->cargarGrupos($this->generacion_id, $this->grado_id, null);
        $this->filtrosCambiaron();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->filtrosCambiaron();
    }

    public function updatedGrupoId(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedEstatus(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedMostrarArchivados(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedSearch(): void
    {
        $this->filtrosCambiaron();
    }

    public function updatedDestinoGeneracionId(): void
    {
        $this->destino_grupo_id = null;
        $this->gruposDestino = $this->cargarGrupos(
            $this->destino_generacion_id,
            $this->destino_grado_id,
            $this->destino_semestre_id,
            $this->destino_ciclo_escolar_id
        );
    }

    public function updatedDestinoGradoId(): void
    {
        $this->destino_semestre_id = null;
        $this->destino_grupo_id = null;
        $this->semestresDestino = $this->cargarSemestres($this->destino_grado_id);
        $this->gruposDestino = $this->esBachillerato()
            ? collect()
            : $this->cargarGrupos($this->destino_generacion_id, $this->destino_grado_id, null, $this->destino_ciclo_escolar_id);
    }

    public function updatedDestinoSemestreId(): void
    {
        $this->destino_grupo_id = null;
        $this->gruposDestino = $this->cargarGrupos(
            $this->destino_generacion_id,
            $this->destino_grado_id,
            $this->destino_semestre_id,
            $this->destino_ciclo_escolar_id
        );
    }

    private function filtrosCambiaron(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();
    }

    public function updatedSelectPage(bool $value): void
    {
        if (! $value) {
            $this->selected = [];
            return;
        }

        $this->selected = $this->query()
            ->forPage($this->getPage(), $this->perPage)
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->all();
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    private function query(): Builder
    {
        $query = Inscripcion::query();
        $cicloEscolarId = (int) $this->ciclo_escolar_id;
        $esActual = $this->cicloEsActual($this->ciclo_escolar_id);

        return $query
            ->with([
                'ciclosEscolaresHistorial' => fn ($historial) => $historial
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->with([
                        'cicloEscolar',
                        'generacion',
                        'grado',
                        'semestre',
                        'grupo' => fn ($grupo) => $grupo->withTrashed()->with('asignacionGrupo'),
                    ]),
            ])
            ->when($esActual, fn (Builder $alumnos) => $alumnos->visiblesEnListas())
            ->whereHas('ciclosEscolaresHistorial', function (Builder $historial) use ($cicloEscolarId, $esActual): void {
                $historial
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('nivel_id', $this->nivel->id)
                    ->when($this->generacion_id, fn (Builder $q) => $q->where('generacion_id', $this->generacion_id))
                    ->when($this->grado_id, fn (Builder $q) => $q->where('grado_id', $this->grado_id))
                    ->when($this->semestre_id, fn (Builder $q) => $q->where('semestre_id', $this->semestre_id))
                    ->when($this->grupo_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_id));

                if ($esActual) {
                    $historial
                        ->where('estado', 'en_curso')
                        ->where('estatus_actual_ciclo', 'activo');

                    return;
                }

                // En ciclos anteriores se conserva el padrón de quienes realmente
                // iniciaron ese ciclo, aunque hoy sean egresados o hayan causado baja.
                $historial
                    ->where('estado', '!=', 'anulado')
                    ->whereIn('estatus_ingreso', ['activo', 'reingreso', 'no_promovido'])
                    ->where(function (Builder $resultado): void {
                        $resultado
                            ->whereNull('resultado_final')
                            ->orWhere('resultado_final', '!=', 'no_iniciado');
                    });
            })
            ->when(trim($this->search) !== '', function (Builder $q) use ($cicloEscolarId): void {
                $term = '%' . trim($this->search) . '%';
                $q->where(function (Builder $busqueda) use ($term, $cicloEscolarId): void {
                    $busqueda->where('matricula', 'like', $term)
                        ->orWhere('curp', 'like', $term)
                        ->orWhere('folio', 'like', $term)
                        ->orWhere('nombre', 'like', $term)
                        ->orWhere('apellido_paterno', 'like', $term)
                        ->orWhere('apellido_materno', 'like', $term)
                        ->orWhereHas('ciclosEscolaresHistorial', fn (Builder $historial) => $historial
                            ->where('ciclo_escolar_id', $cicloEscolarId)
                            ->where('matricula', 'like', $term));
                });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    public function cambiarGeneracionSeleccionados(GestionAcademicaService $service): void
    {
        if (! $this->esCicloActual) {
            $this->dispatch('swal', [
                'title' => 'Consulta histórica de solo lectura',
                'text' => 'Para proteger el historial, cambia la asignación desde el ciclo escolar actual o mediante una corrección histórica autorizada.',
                'icon' => 'warning',
                'position' => 'center',
            ]);

            return;
        }

        $rules = [
            'destino_ciclo_escolar_id' => ['required', 'exists:ciclo_escolares,id'],
            'selected' => ['required', 'array', 'min:1'],
            'destino_generacion_id' => ['required', 'exists:generaciones,id'],
            'destino_grado_id' => ['required', 'exists:grados,id'],
            'destino_grupo_id' => ['required', 'exists:grupos,id'],
            'motivo_cambio' => ['required', 'string', 'min:5', 'max:1000'],
        ];

        if ($this->esBachillerato()) {
            $rules['destino_semestre_id'] = ['required', 'exists:semestres,id'];
        }

        $this->validate($rules);

        $total = 0;
        foreach (Inscripcion::withTrashed()->whereIn('id', $this->selected)->get() as $alumno) {
            $service->cambiarAsignacion($alumno, [
                'ciclo_escolar_id' => $this->destino_ciclo_escolar_id,
                'nivel_id' => $this->nivel->id,
                'generacion_id' => $this->destino_generacion_id,
                'grado_id' => $this->destino_grado_id,
                'semestre_id' => $this->esBachillerato() ? $this->destino_semestre_id : null,
                'grupo_id' => $this->destino_grupo_id,
                'matricula' => $alumno->matricula,
            ], $this->motivo_cambio, auth()->id());
            $total++;
        }

        $this->reset([
            'selected',
            'selectPage',
            'destino_generacion_id',
            'destino_grado_id',
            'destino_semestre_id',
            'destino_grupo_id',
            'motivo_cambio',
        ]);
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();

        $this->dispatch('swal', [
            'title' => 'Asignación actualizada',
            'text' => "Se modificaron {$total} alumno(s).",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function archivar(int $inscripcionId): void
    {
        $alumno = Inscripcion::query()->findOrFail($inscripcionId);
        $alumno->delete();

        $this->selected = array_values(array_filter(
            $this->selected,
            fn($id) => (int) $id !== $inscripcionId
        ));

        $this->dispatch('swal', [
            'title' => 'Alumno archivado',
            'text' => 'Su información permanece disponible al incluir expedientes archivados.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function restaurar(int $inscripcionId): void
    {
        $alumno = Inscripcion::withTrashed()->findOrFail($inscripcionId);
        $alumno->restore();

        $this->dispatch('swal', [
            'title' => 'Alumno restaurado',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function activarPreinscripcion(
        int $inscripcionId,
        string $motivo,
        GestionAcademicaService $service
    ): void {
        abort_unless(auth()->user()?->is_admin, 403);

        $motivo = trim($motivo);

        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500) {
            $this->dispatch('swal', [
                'title' => 'Motivo no válido',
                'text' => 'Escribe un motivo de activación de 5 a 500 caracteres.',
                'icon' => 'warning',
                'position' => 'center',
            ]);

            return;
        }

        $alumno = Inscripcion::query()->find($inscripcionId);

        if (! $alumno) {
            $this->dispatch('swal', [
                'title' => 'Alumno no disponible',
                'text' => 'El expediente no existe, está archivado o ya no se encuentra disponible.',
                'icon' => 'error',
                'position' => 'center',
            ]);

            return;
        }

        if (($alumno->estatus ?? 'activo') !== 'preinscrito') {
            $this->dispatch('swal', [
                'title' => 'La inscripción ya cambió',
                'text' => 'Solo los alumnos preinscritos pueden activarse desde este botón.',
                'icon' => 'info',
                'position' => 'center',
            ]);

            return;
        }

        try {
            $service->activarPreinscripcion(
                $alumno,
                $motivo,
                auth()->id()
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $mensaje = collect($exception->errors())->flatten()->first()
                ?: 'No fue posible activar la inscripción. Revisa la asignación académica.';

            $this->dispatch('swal', [
                'title' => 'No se pudo activar',
                'text' => $mensaje,
                'icon' => 'warning',
                'position' => 'center',
            ]);

            return;
        }

        $this->selected = array_values(array_filter(
            $this->selected,
            fn($id) => (int) $id !== $inscripcionId
        ));

        $this->dispatch('swal', [
            'title' => 'Inscripción activada',
            'text' => 'El alumno quedó inscrito y activo. También se habilitó su acceso y se registró el movimiento en la bitácora.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function abrirBitacora(int $id): void
    {
        $this->alumnoBitacoraId = $id;
        $this->modalBitacora = true;
    }

    public function cerrarBitacora(): void
    {
        $this->modalBitacora = false;
        $this->alumnoBitacoraId = null;
    }

    public function restaurarFiltrosMatricula(array $filtros): void
    {
        $pagina = max(1, (int) ($filtros['page'] ?? 1));

        $this->ciclo_escolar_id = filled($filtros['ciclo_escolar_id'] ?? null)
            ? (int) $filtros['ciclo_escolar_id']
            : ($this->ciclosEscolares->firstWhere('es_actual', true)?->id ?? $this->ciclosEscolares->first()?->id);
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);

        foreach (['generacion_id', 'grado_id', 'semestre_id', 'grupo_id'] as $campo) {
            $this->{$campo} = filled($filtros[$campo] ?? null) ? (int) $filtros[$campo] : null;
        }

        $this->estatus = (string) ($filtros['estatus'] ?? 'todos');
        $this->search = trim((string) ($filtros['search'] ?? ''));
        $this->mostrar_archivados = filter_var(
            $filtros['mostrar_archivados'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        $grupoSeleccionado = $this->grupo_id;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->grupo_id = $grupoSeleccionado
            && $this->grupos->contains(fn($grupo) => (int) $grupo->id === (int) $grupoSeleccionado)
            ? (int) $grupoSeleccionado
            : null;

        $this->selected = [];
        $this->selectPage = false;
        $this->setPage($pagina);
    }

    public function localizarAlumnoEnMatricula(int $inscripcionId): void
    {
        $alumno = Inscripcion::withTrashed()->findOrFail($inscripcionId);

        $this->ciclo_escolar_id = $alumno->ciclo_escolar_id ? (int) $alumno->ciclo_escolar_id : $this->ciclo_escolar_id;
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->generacion_id = $alumno->generacion_id ? (int) $alumno->generacion_id : null;
        $this->grado_id = $alumno->grado_id ? (int) $alumno->grado_id : null;
        $this->semestre_id = $alumno->semestre_id ? (int) $alumno->semestre_id : null;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->grupo_id = $alumno->grupo_id
            && $this->grupos->contains(fn($grupo) => (int) $grupo->id === (int) $alumno->grupo_id)
            ? (int) $alumno->grupo_id
            : null;
        $this->estatus = 'todos';
        $this->mostrar_archivados = $alumno->trashed();
        $this->search = $alumno->matricula
            ?: $alumno->curp
            ?: trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}");
        $this->selected = [];
        $this->selectPage = false;
        $this->setPage(1);
    }

    public function exportarExcel()
    {
        $rows = $this->query()->get();

        return Excel::download(
            new MatriculaExport($rows, $this->nivel->nombre, $this->esBachillerato()),
            'padron_' . $this->slug_nivel . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'search',
            'mostrar_archivados',
            'selected',
            'selectPage',
        ]);
        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->destino_ciclo_escolar_id = $this->ciclo_escolar_id;
        $this->generaciones = $this->cargarGeneraciones($this->ciclo_escolar_id);
        $this->generacionesDestino = $this->cargarGeneraciones($this->destino_ciclo_escolar_id);
        $this->estatus = 'todos';
        $this->semestres = collect();
        $this->grupos = collect();
        $this->resetPage();
    }

    public function textoGrupo($grupo): string
    {
        if (! $grupo) {
            return '—';
        }

        $nombre = $grupo->asignacionGrupo?->nombre
            ?? $grupo->grupo
            ?? $grupo->nombre
            ?? 'Sin grupo';

        if (isset($grupo->alumnos_contexto_count)) {
            return $nombre . ' · ' . number_format((int) $grupo->alumnos_contexto_count) . ' alumnos · cupo ilimitado';
        }

        if (isset($grupo->alumnos_activos_count)) {
            return $nombre . ' · ' . number_format((int) $grupo->alumnos_activos_count) . ' alumnos · cupo ilimitado';
        }

        return $nombre;
    }

    public function etiquetaEstatus(?string $estatus): string
    {
        return match ($estatus) {
            'preinscrito' => 'Preinscrito',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'trasladado' => 'Trasladado',
            'suspendido' => 'Suspendido',
            'egresado' => 'Egresado',
            'promovido', 'promovido_grado' => 'Promovido de grado',
            'promovido_nivel' => 'Promovido al siguiente nivel',
            'grado_concluido' => 'Grado concluido',
            'pendiente_reinscripcion' => 'Pendiente de reinscripción',
            'no_reinscrito' => 'No reinscrito',
            'inactivo' => 'Inactivo',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            default => ucfirst(str_replace('_', ' ', (string) $estatus)),
        };
    }

    public function render(AlumnosNoVigentesService $noVigentesService)
    {
        $alumnos = $this->query()->paginate($this->perPage);
        $resumenBase = (clone $this->query())->reorder();
        $contextos = (clone $resumenBase)->get()
            ->map(fn (Inscripcion $alumno) => $alumno->ciclosEscolaresHistorial->first())
            ->filter();

        $filtrosNoVigentes = [
            'categoria' => 'todos',
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'search' => $this->search,
        ];

        $resumen = [
            'total' => (clone $resumenBase)->count(),
            'hombres' => (clone $resumenBase)->where('genero', 'H')->count(),
            'mujeres' => (clone $resumenBase)->where('genero', 'M')->count(),
            'grupos' => $contextos->pluck('grupo_id')->filter()->unique()->count(),
            'no_vigentes' => $noVigentesService
                ->query($this->nivel, (int) $this->ciclo_escolar_id, $filtrosNoVigentes)
                ->count(),
        ];

        $bitacoraAlumno = $this->alumnoBitacoraId
            ? Inscripcion::withTrashed()
            ->with(['cambiosAcademicos.usuario', 'generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->find($this->alumnoBitacoraId)
            : null;

        return view('livewire.accion.matricula', compact('alumnos', 'resumen', 'bitacoraAlumno'));
    }
}
