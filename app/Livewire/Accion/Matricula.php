<?php

namespace App\Livewire\Accion;

use App\Exports\MatriculaExport;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\MovimientoAlumno;
use App\Models\Semestre;
use App\Models\TrayectoriaAcademica;
use App\Services\TrayectoriaAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
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
    public Collection $cicloEscolares;
    public Collection $ciclos;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $estatus = 'todos';
    public string $search = '';
    public bool $mostrar_archivados = false;

    public int $total = 0;
    public int $hombres = 0;
    public int $mujeres = 0;
    public int $bajas = 0;

    public bool $selectPage = false;
    public array $selected = [];

    public ?int $nueva_generacion_id = null;
    public ?int $nuevo_grado_id = null;
    public ?int $nuevo_semestre_id = null;
    public ?int $nuevo_grupo_id = null;
    public Collection $nuevosSemestres;
    public Collection $nuevosGrupos;
    public ?string $motivo_correccion = null;

    public bool $modalHistorial = false;
    public ?int $historialAlumnoId = null;

    public int $perPage = 20;

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);
        $this->ciclos = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);

        $this->ciclo_escolar_id = $this->cicloEscolares->firstWhere('es_actual', true)?->id
            ?: $this->cicloEscolares->first()?->id;
        $this->ciclo_id = $this->ciclos->first()?->id;

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status']);

        $this->semestres = collect();
        $this->grupos = collect();
        $this->nuevosSemestres = collect();
        $this->nuevosGrupos = collect();

        $this->recalcularResumen();
    }

    public function esBachillerato(): bool
    {
        $texto = mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? ''));

        return str_contains($texto, 'bachillerato');
    }

    public function updatedCicloEscolarId($value): void
    {
        $this->ciclo_escolar_id = filled($value) ? (int) $value : null;
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedCicloId($value): void
    {
        $this->ciclo_id = filled($value) ? (int) $value : null;
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = filled($value) ? (int) $value : null;
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos(
            $this->generacion_id,
            $this->grado_id,
            $this->semestre_id
        );
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = filled($value) ? (int) $value : null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->esBachillerato()
            ? collect()
            : $this->cargarGrupos($this->generacion_id, $this->grado_id, null);
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = filled($value) ? (int) $value : null;
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos(
            $this->generacion_id,
            $this->grado_id,
            $this->semestre_id
        );
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = filled($value) ? (int) $value : null;
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedEstatus(): void
    {
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedMostrarArchivados(): void
    {
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSearch(): void
    {
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedNuevaGeneracionId($value): void
    {
        $this->nueva_generacion_id = filled($value) ? (int) $value : null;
        $this->nuevo_grupo_id = null;
        $this->nuevosGrupos = $this->cargarGrupos(
            $this->nueva_generacion_id,
            $this->nuevo_grado_id,
            $this->nuevo_semestre_id
        );
    }

    public function updatedNuevoGradoId($value): void
    {
        $this->nuevo_grado_id = filled($value) ? (int) $value : null;
        $this->nuevo_semestre_id = null;
        $this->nuevo_grupo_id = null;
        $this->nuevosSemestres = $this->cargarSemestres($this->nuevo_grado_id);
        $this->nuevosGrupos = $this->esBachillerato()
            ? collect()
            : $this->cargarGrupos($this->nueva_generacion_id, $this->nuevo_grado_id, null);
    }

    public function updatedNuevoSemestreId($value): void
    {
        $this->nuevo_semestre_id = filled($value) ? (int) $value : null;
        $this->nuevo_grupo_id = null;
        $this->nuevosGrupos = $this->cargarGrupos(
            $this->nueva_generacion_id,
            $this->nuevo_grado_id,
            $this->nuevo_semestre_id
        );
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selected = $value
            ? $this->filas()->pluck('id')->map(fn($id) => (string) $id)->all()
            : [];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function clearFilters(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->estatus = 'todos';
        $this->search = '';
        $this->mostrar_archivados = false;
        $this->semestres = collect();
        $this->grupos = collect();
        $this->limpiarSeleccion();
        $this->limpiarCorreccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function aplicarCorreccion(): void
    {
        if (empty($this->selected)) {
            $this->addError('selected', 'Selecciona al menos un alumno.');
            return;
        }

        $reglas = [
            'ciclo_escolar_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'nueva_generacion_id' => ['required', 'exists:generaciones,id'],
            'nuevo_grado_id' => ['required', 'exists:grados,id'],
            'nuevo_grupo_id' => ['required', 'exists:grupos,id'],
            'motivo_correccion' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->esBachillerato()) {
            $reglas['nuevo_semestre_id'] = ['required', 'exists:semestres,id'];
        }

        $this->validate($reglas);

        $grupo = Grupo::query()
            ->whereKey($this->nuevo_grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->nueva_generacion_id)
            ->where('grado_id', $this->nuevo_grado_id)
            ->when(
                $this->esBachillerato(),
                fn(Builder $query) => $query->where('semestre_id', $this->nuevo_semestre_id),
                fn(Builder $query) => $query->whereNull('semestre_id')
            )
            ->first();

        if (!$grupo) {
            $this->addError('nuevo_grupo_id', 'El grupo no corresponde a la generación, grado y semestre seleccionados.');
            return;
        }

        $total = app(TrayectoriaAcademicaService::class)->corregirAsignacion(
            $this->selected,
            (int) $this->ciclo_escolar_id,
            (int) $this->ciclo_id,
            [
                'nivel_id' => $this->nivel->id,
                'grado_id' => (int) $this->nuevo_grado_id,
                'generacion_id' => (int) $this->nueva_generacion_id,
                'grupo_id' => (int) $this->nuevo_grupo_id,
                'semestre_id' => $this->esBachillerato() ? (int) $this->nuevo_semestre_id : null,
            ],
            auth()->id(),
            $this->motivo_correccion ?: 'Corrección administrativa desde el módulo de matrícula.',
            now()
        );

        $this->limpiarSeleccion();
        $this->limpiarCorreccion();
        $this->recalcularResumen();

        $this->dispatch('swal', [
            'title' => "Se corrigieron {$total} trayectoria(s).",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function archivar(int $inscripcionId): void
    {
        $alumno = Inscripcion::query()->findOrFail($inscripcionId);
        $trayectoria = $this->trayectoriaContextoAlumno($inscripcionId);

        $alumno->delete();
        $this->registrarMovimientoArchivo($alumno, $trayectoria, 'archivado');

        $this->dispatch('swal', [
            'title' => 'Alumno archivado. Su historial se conserva.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->recalcularResumen();
    }

    public function restaurar(int $inscripcionId): void
    {
        $alumno = Inscripcion::withTrashed()->findOrFail($inscripcionId);
        $trayectoria = $this->trayectoriaContextoAlumno($inscripcionId);

        $alumno->restore();
        $this->registrarMovimientoArchivo($alumno, $trayectoria, 'restaurado');

        $this->dispatch('swal', [
            'title' => 'Alumno restaurado del archivo.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->recalcularResumen();
    }

    public function abrirHistorial(int $inscripcionId): void
    {
        $this->historialAlumnoId = $inscripcionId;
        $this->modalHistorial = true;
    }

    public function cerrarHistorial(): void
    {
        $this->modalHistorial = false;
        $this->historialAlumnoId = null;
    }

    public function exportarMatricula()
    {
        $rows = $this->hidratarFilas($this->consultaInscripcionesBase()->get());
        $ciclo = $this->cicloEscolares->firstWhere('id', $this->ciclo_escolar_id);
        $corte = $this->ciclos->firstWhere('id', $this->ciclo_id);

        return Excel::download(
            new MatriculaExport(
                rows: $rows,
                nivelNombre: $this->nivel?->nombre ?? '—',
                generacionNombre: $this->nombreGeneracion($this->generacion_id),
                gradoNombre: $this->grados->firstWhere('id', $this->grado_id)?->nombre ?? 'Todos',
                semestreNombre: $this->semestres->firstWhere('id', $this->semestre_id)
                    ? 'Semestre ' . $this->semestres->firstWhere('id', $this->semestre_id)->numero
                    : 'Todos',
                grupoNombre: $this->textoGrupo($this->grupos->firstWhere('id', $this->grupo_id)),
                search: $this->search,
                esBachillerato: $this->esBachillerato(),
                cicloEscolarNombre: $ciclo ? "{$ciclo->inicio_anio}-{$ciclo->fin_anio}" : '—',
                corteNombre: $corte?->ciclo ?? '—',
                estatusNombre: $this->estatus === 'todos' ? 'Todos' : $this->etiquetaEstatus($this->estatus)
            ),
            'matricula_historica_' . now()->format('Y_m_d_H_i_s') . '.xlsx'
        );
    }

    public function exportarPdf()
    {
        return redirect()->route('misrutas.matricula.historial.pdf', array_filter([
            'slug_nivel' => $this->slug_nivel,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'ciclo_id' => $this->ciclo_id,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'estatus' => $this->estatus,
            'search' => $this->search,
            'mostrar_archivados' => $this->mostrar_archivados ? 1 : null,
        ], fn($value) => $value !== null && $value !== ''));
    }

    public function restaurarFiltrosMatricula(array $filtros): void
    {
        foreach (['ciclo_escolar_id', 'ciclo_id', 'generacion_id', 'grado_id', 'semestre_id', 'grupo_id'] as $campo) {
            $this->{$campo} = filled($filtros[$campo] ?? null) ? (int) $filtros[$campo] : null;
        }

        $this->estatus = (string) ($filtros['estatus'] ?? 'todos');
        $this->search = trim((string) ($filtros['search'] ?? ''));
        $this->mostrar_archivados = (bool) ($filtros['mostrar_archivados'] ?? false);
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->cargarGrupos($this->generacion_id, $this->grado_id, $this->semestre_id);
        $this->limpiarSeleccion();
        $this->resetPage();
        $this->recalcularResumen();
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return 'Todos';
        }

        return $grupo->asignacionGrupo?->nombre
            ?? $grupo->grupo
            ?? $grupo->nombre
            ?? 'Sin grupo';
    }

    public function etiquetaEstatus(string $estatus): string
    {
        return match ($estatus) {
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'traslado' => 'Traslado',
            'reingreso' => 'Reingreso',
            'egresado' => 'Egresado',
            'no_promovido' => 'No promovido',
            'promovido' => 'Promovido',
            'archivado' => 'Archivado',
            default => 'Activo',
        };
    }

    private function filas(): LengthAwarePaginator
    {
        $paginator = $this->consultaInscripcionesBase()->paginate($this->perPage);
        $paginator->setCollection($this->hidratarFilas($paginator->getCollection()));

        return $paginator;
    }

    private function consultaInscripcionesBase(): Builder
    {
        $query = $this->mostrar_archivados
            ? Inscripcion::withTrashed()
            : Inscripcion::query();

        $query->with([
            'trayectoriasAcademicas' => function (Builder $trayectorias) {
                $this->aplicarFiltrosTrayectoria($trayectorias)
                    ->with([
                        'nivel:id,nombre,slug',
                        'grado:id,nombre,orden',
                        'generacion:id,anio_ingreso,anio_egreso',
                        'grupo.asignacionGrupo:id,nombre',
                        'semestre:id,numero',
                        'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                        'ciclo:id,ciclo',
                    ])
                    ->orderByDesc('numero_estancia')
                    ->orderByDesc('id');
            },
            'matriculasAlumno:id,inscripcion_id,nivel_id,matricula,fecha_asignacion,fecha_fin,vigente',
        ]);

        $query->whereHas('trayectoriasAcademicas', function (Builder $trayectorias) {
            $this->aplicarFiltrosTrayectoria($trayectorias);
        });

        $termino = preg_replace('/\s+/', ' ', trim($this->search));

        if (filled($termino)) {
            $buscar = "%{$termino}%";
            $query->where(function (Builder $subquery) use ($buscar) {
                $subquery->where('matricula', 'like', $buscar)
                    ->orWhere('folio', 'like', $buscar)
                    ->orWhere('curp', 'like', $buscar)
                    ->orWhere('nombre', 'like', $buscar)
                    ->orWhere('apellido_paterno', 'like', $buscar)
                    ->orWhere('apellido_materno', 'like', $buscar)
                    ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$buscar])
                    ->orWhereHas('matriculasAlumno', fn(Builder $matriculas) => $matriculas->where('matricula', 'like', $buscar));
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    private function aplicarFiltrosTrayectoria(Builder $query): Builder
    {
        return $query
            ->when($this->ciclo_escolar_id, fn(Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->ciclo_id, fn(Builder $q) => $q->where('ciclo_id', $this->ciclo_id))
            ->where('nivel_id', $this->nivel->id)
            ->where('vigente_en_corte', true)
            ->when($this->generacion_id, fn(Builder $q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id, fn(Builder $q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id, fn(Builder $q) => $q->where('grupo_id', $this->grupo_id))
            ->when(
                $this->esBachillerato() && $this->semestre_id,
                fn(Builder $q) => $q->where('semestre_id', $this->semestre_id)
            )
            ->when(
                !$this->esBachillerato(),
                fn(Builder $q) => $q->whereNull('semestre_id')
            )
            ->when(
                $this->estatus !== 'todos',
                fn(Builder $q) => $q->where('estatus', $this->estatus)
            );
    }

    private function hidratarFilas(Collection $alumnos): Collection
    {
        return $alumnos->map(function (Inscripcion $alumno) {
            $trayectoria = $alumno->trayectoriasAcademicas->first();

            if (!$trayectoria) {
                return $alumno;
            }

            $matricula = $alumno->matriculasAlumno
                ->firstWhere('nivel_id', $trayectoria->nivel_id)?->matricula
                ?: $alumno->matricula;

            $alumno->setRelation('trayectoriaContexto', $trayectoria);
            $alumno->setRelation('nivel', $trayectoria->nivel);
            $alumno->setRelation('grado', $trayectoria->grado);
            $alumno->setRelation('generacion', $trayectoria->generacion);
            $alumno->setRelation('grupo', $trayectoria->grupo);
            $alumno->setRelation('semestre', $trayectoria->semestre);

            $alumno->setAttribute('matricula_contexto', $matricula);
            $alumno->setAttribute('estatus_historial', $trayectoria->estatus);
            $alumno->setAttribute('fecha_baja_contexto', $trayectoria->fecha_baja);
            $alumno->setAttribute('motivo_baja_contexto', $trayectoria->motivo_baja);
            $alumno->setAttribute('datos_reconstruidos', $trayectoria->datos_reconstruidos);
            $alumno->setAttribute('trayectoria_id', $trayectoria->id);

            return $alumno;
        });
    }

    private function recalcularResumen(): void
    {
        if (!$this->ciclo_escolar_id || !$this->ciclo_id) {
            $this->total = $this->hombres = $this->mujeres = $this->bajas = 0;
            return;
        }

        $base = $this->consultaInscripcionesBase();
        $this->total = (clone $base)->count();
        $this->hombres = (clone $base)->where('genero', 'H')->count();
        $this->mujeres = (clone $base)->where('genero', 'M')->count();

        $this->bajas = Inscripcion::withTrashed()
            ->whereHas('trayectoriasAcademicas', function (Builder $query) {
                $query->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('ciclo_id', $this->ciclo_id)
                    ->where('nivel_id', $this->nivel->id)
                    ->where('vigente_en_corte', true)
                    ->whereIn('estatus', ['baja_temporal', 'baja_definitiva', 'traslado']);
            })
            ->count();
    }

    private function cargarSemestres(?int $gradoId): Collection
    {
        if (!$this->esBachillerato() || !$gradoId) {
            return collect();
        }

        return Semestre::query()
            ->where('grado_id', $gradoId)
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero']);
    }

    private function cargarGrupos(?int $generacionId, ?int $gradoId, ?int $semestreId): Collection
    {
        if (!$generacionId || !$gradoId) {
            return collect();
        }

        if ($this->esBachillerato() && !$semestreId) {
            return collect();
        }

        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $generacionId)
            ->where('grado_id', $gradoId)
            ->when(
                $this->esBachillerato(),
                fn(Builder $query) => $query->where('semestre_id', $semestreId),
                fn(Builder $query) => $query->whereNull('semestre_id')
            )
            ->orderBy('asignacion_grupo_id')
            ->orderBy('id')
            ->get();
    }

    private function nombreGeneracion(?int $id): string
    {
        $generacion = $this->generaciones->firstWhere('id', $id);

        return $generacion
            ? "{$generacion->anio_ingreso}-{$generacion->anio_egreso}"
            : 'Todas';
    }

    private function trayectoriaContextoAlumno(int $inscripcionId): ?TrayectoriaAcademica
    {
        return TrayectoriaAcademica::query()
            ->where('inscripcion_id', $inscripcionId)
            ->when($this->ciclo_escolar_id, fn (Builder $query) => $query->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->ciclo_id, fn (Builder $query) => $query->where('ciclo_id', $this->ciclo_id))
            ->where('nivel_id', $this->nivel->id)
            ->where('vigente_en_corte', true)
            ->latest('numero_estancia')
            ->latest('id')
            ->first();
    }

    private function registrarMovimientoArchivo(
        Inscripcion $alumno,
        ?TrayectoriaAcademica $trayectoria,
        string $tipo
    ): void {
        MovimientoAlumno::query()->create([
            'inscripcion_id' => $alumno->id,
            'trayectoria_academica_id' => $trayectoria?->id,
            'ciclo_escolar_id' => $trayectoria?->ciclo_escolar_id ?: $this->ciclo_escolar_id,
            'ciclo_id' => $trayectoria?->ciclo_id ?: $this->ciclo_id,
            'trayectoria_origen_id' => $trayectoria?->id,
            'tipo' => $tipo,
            'fecha' => now()->toDateString(),
            'motivo' => $tipo === 'archivado'
                ? 'Registro archivado sin eliminar su historial académico.'
                : 'Registro restaurado desde el archivo.',
            'observaciones' => 'Movimiento realizado desde el módulo de matrícula.',
            'estado_anterior' => ['archivado' => $tipo === 'archivado' ? false : true],
            'estado_nuevo' => ['archivado' => $tipo === 'archivado'],
            'registrado_por' => auth()->id(),
        ]);
    }

    private function limpiarSeleccion(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    private function limpiarCorreccion(): void
    {
        $this->nueva_generacion_id = null;
        $this->nuevo_grado_id = null;
        $this->nuevo_semestre_id = null;
        $this->nuevo_grupo_id = null;
        $this->motivo_correccion = null;
        $this->nuevosSemestres = collect();
        $this->nuevosGrupos = collect();
    }

    public function render()
    {
        $historialAlumno = null;

        if ($this->historialAlumnoId) {
            $historialAlumno = Inscripcion::withTrashed()
                ->with([
                    'trayectoriasAcademicas' => fn($query) => $query
                        ->with(['cicloEscolar', 'ciclo', 'nivel', 'grado', 'generacion', 'grupo.asignacionGrupo', 'semestre'])
                        ->orderByDesc('ciclo_escolar_id')
                        ->orderByDesc('ciclo_id')
                        ->orderByDesc('numero_estancia'),
                    'movimientos' => fn($query) => $query
                        ->with(['cicloEscolar', 'ciclo', 'usuario'])
                        ->orderByDesc('fecha')
                        ->orderByDesc('id'),
                    'matriculasAlumno.nivel',
                ])
                ->find($this->historialAlumnoId);
        }

        return view('livewire.accion.matricula', [
            'rows' => $this->filas(),
            'esBachillerato' => $this->esBachillerato(),
            'historialAlumno' => $historialAlumno,
            'cicloSeleccionado' => $this->cicloEscolares->firstWhere('id', $this->ciclo_escolar_id),
            'corteSeleccionado' => $this->ciclos->firstWhere('id', $this->ciclo_id),
        ]);
    }
}
