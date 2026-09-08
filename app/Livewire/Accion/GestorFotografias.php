<?php

namespace App\Livewire\Accion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\ImagenPersonalService;
use App\Services\SystemAuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class GestorFotografias extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestres;
    public Collection $grupos;

    public ?int $ciclo_escolar_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $search = '';
    public string $estado_foto = 'sin_foto';
    public bool $incluir_no_vigentes = false;
    public int $perPage = 24;

    /** @var array<int, mixed> */
    public array $fotosIndividuales = [];

    public $fotoEditor = null;

    /** @var array<int, mixed> */
    public array $archivosMasivos = [];

    /** @var array<int, int|null> índice archivo => inscripción */
    public array $asignacionesMasivas = [];

    /** @var array<int, bool> inscripción => eliminar */
    public array $eliminaciones = [];

    /** @var array<int, string> */
    public array $erroresArchivos = [];

    /** @var array<int, string> */
    public array $coincidenciaArchivo = [];

    public bool $guardando = false;

    protected $paginationTheme = 'tailwind';

    public function mount(?string $slug_nivel = null): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->slug_nivel = $slug_nivel && $this->niveles->contains('slug', $slug_nivel)
            ? $slug_nivel
            : (string) ($this->niveles->first()?->slug ?? '');

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);

        $this->ciclo_escolar_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;

        $this->cargarContextoNivel();
    }

    public function seleccionarNivel(string $slug): void
    {
        abort_unless($this->niveles->contains('slug', $slug), 404);
        $this->slug_nivel = $slug;
        $this->reset(['generacion_id', 'grado_id', 'semestre_id', 'grupo_id']);
        $this->limpiarPendientes();
        $this->cargarContextoNivel();
        $this->resetPage();
    }

    private function cargarContextoNivel(): void
    {
        $this->nivel = Nivel::query()->where('slug', $this->slug_nivel)->firstOrFail();
        $this->generaciones = $this->cargarGeneraciones();
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
        $this->semestres = collect();
        $this->grupos = collect();
    }

    public function esBachillerato(): bool
    {
        return str_contains(
            mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')),
            'bachillerato'
        );
    }

    private function cicloEsActual(): bool
    {
        if (! $this->ciclo_escolar_id) {
            return false;
        }

        return (bool) $this->ciclosEscolares
            ->firstWhere('id', (int) $this->ciclo_escolar_id)?->es_actual;
    }

    private function cargarGeneraciones(): Collection
    {
        if (! $this->nivel) {
            return collect();
        }

        $cicloId = (int) $this->ciclo_escolar_id;

        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($cicloId > 0, fn (Builder $query) => $query->where(function (Builder $q) use ($cicloId): void {
                $q->whereHas('inscripcionCiclos', fn (Builder $h) => $h
                    ->where('ciclo_escolar_id', $cicloId)
                    ->where('nivel_id', $this->nivel->id))
                    ->orWhereHas('grupos', fn (Builder $g) => $g
                        ->withTrashed()
                        ->where('ciclo_escolar_id', $cicloId)
                        ->where('nivel_id', $this->nivel->id));
            }))
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

    private function cargarGrupos(): Collection
    {
        if (! $this->nivel || ! $this->generacion_id || ! $this->grado_id || ! $this->ciclo_escolar_id) {
            return collect();
        }

        return Grupo::withTrashed()
            ->with('asignacionGrupo')
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato(),
                fn (Builder $q) => $q->where('semestre_id', $this->semestre_id),
                fn (Builder $q) => $q->whereNull('semestre_id')
            )
            ->get()
            ->sortBy(fn ($grupo) => $grupo->asignacionGrupo?->nombre ?? $grupo->grupo ?? $grupo->nombre ?? $grupo->id)
            ->values();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->reset(['generacion_id', 'grado_id', 'semestre_id', 'grupo_id']);
        $this->generaciones = $this->cargarGeneraciones();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos();
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestres = $this->cargarSemestres($this->grado_id);
        $this->grupos = $this->esBachillerato() ? collect() : $this->cargarGrupos();
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->grupos = $this->cargarGrupos();
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedGrupoId(): void
    {
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedEstadoFoto(): void
    {
        $this->resetPage();
    }

    public function updatedIncluirNoVigentes(): void
    {
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = in_array((int) $this->perPage, [12, 24, 48, 96], true) ? (int) $this->perPage : 24;
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['generacion_id', 'grado_id', 'semestre_id', 'grupo_id', 'search', 'incluir_no_vigentes']);
        $this->estado_foto = 'sin_foto';
        $this->semestres = collect();
        $this->grupos = collect();
        $this->limpiarPendientes();
        $this->resetPage();
    }

    public function updatedFotosIndividuales($archivo, $alumnoId): void
    {
        $id = (int) $alumnoId;

        try {
            $this->validateOnly("fotosIndividuales.{$alumnoId}", [
                "fotosIndividuales.{$alumnoId}" => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ]);
            unset($this->eliminaciones[$id]);
        } catch (Throwable $e) {
            unset($this->fotosIndividuales[$id]);
            throw $e;
        }
    }

    public function asignarFotoEditor(int $alumnoId): void
    {
        $this->validate([
            'fotoEditor' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->fotosIndividuales[$alumnoId] = $this->fotoEditor;
        $this->fotoEditor = null;
        unset($this->eliminaciones[$alumnoId]);
        $this->resetValidation('fotoEditor');
    }

    public function quitarFotoPendiente(int $alumnoId): void
    {
        unset($this->fotosIndividuales[$alumnoId]);
        $this->resetValidation("fotosIndividuales.{$alumnoId}");
    }

    public function alternarEliminar(int $alumnoId): void
    {
        $alumno = Inscripcion::withTrashed()->findOrFail($alumnoId);

        if (! $alumno->foto_path) {
            return;
        }

        unset($this->fotosIndividuales[$alumnoId]);
        $nuevoEstado = ! ($this->eliminaciones[$alumnoId] ?? false);
        $this->eliminaciones[$alumnoId] = $nuevoEstado;

        if ($nuevoEstado) {
            foreach ($this->asignacionesMasivas as $index => $asignado) {
                if ((int) $asignado === $alumnoId) {
                    $this->asignacionesMasivas[$index] = null;
                }
            }
        }
    }

    public function updatedAsignacionesMasivas($valor, $index): void
    {
        $alumnoId = (int) $valor;
        if ($alumnoId > 0) {
            unset($this->eliminaciones[$alumnoId]);
        }
    }

    public function updatedArchivosMasivos(): void
    {
        $this->procesarArchivosMasivos();
    }

    private function procesarArchivosMasivos(): void
    {
        $this->asignacionesMasivas = [];
        $this->erroresArchivos = [];
        $this->coincidenciaArchivo = [];

        $alumnos = $this->queryBase(ignorarEstadoFoto: true, ignorarBusqueda: true)->get([
            'inscripciones.id',
            'inscripciones.matricula',
            'inscripciones.curp',
            'inscripciones.nombre',
            'inscripciones.apellido_paterno',
            'inscripciones.apellido_materno',
        ]);

        $porMatricula = $alumnos->filter(fn ($a) => filled($a->matricula))
            ->keyBy(fn ($a) => $this->normalizarIdentificador((string) $a->matricula));
        $porCurp = $alumnos->filter(fn ($a) => filled($a->curp))
            ->keyBy(fn ($a) => $this->normalizarIdentificador((string) $a->curp));
        $porId = $alumnos->keyBy(fn ($a) => (string) $a->id);

        foreach ($this->archivosMasivos as $index => $archivo) {
            try {
                validator(['archivo' => $archivo], [
                    'archivo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                ])->validate();
            } catch (Throwable) {
                $this->erroresArchivos[$index] = 'Archivo inválido. Usa JPG, JPEG, PNG o WebP de hasta 5 MB.';
                $this->asignacionesMasivas[$index] = null;
                continue;
            }

            $base = pathinfo((string) $archivo->getClientOriginalName(), PATHINFO_FILENAME);
            $clave = $this->normalizarIdentificador($base);
            $alumno = null;
            $tipo = null;

            if ($clave !== '' && $porMatricula->has($clave)) {
                $alumno = $porMatricula->get($clave);
                $tipo = 'Matrícula';
            } elseif ($clave !== '' && $porCurp->has($clave)) {
                $alumno = $porCurp->get($clave);
                $tipo = 'CURP';
            } elseif (ctype_digit($clave) && $porId->has($clave)) {
                $alumno = $porId->get($clave);
                $tipo = 'ID';
            }

            $this->asignacionesMasivas[$index] = $alumno?->id;
            if ($alumno) {
                unset($this->eliminaciones[(int) $alumno->id]);
            }
            $this->coincidenciaArchivo[$index] = $alumno
                ? "{$tipo}: {$this->nombreCompleto($alumno)}"
                : 'Sin coincidencia automática';
        }
    }

    private function normalizarIdentificador(string $valor): string
    {
        return preg_replace('/[^A-Z0-9]/', '', Str::upper(trim($valor))) ?? '';
    }

    public function eliminarArchivoMasivo(int $index): void
    {
        if (! array_key_exists($index, $this->archivosMasivos)) {
            return;
        }

        unset($this->archivosMasivos[$index]);
        $this->archivosMasivos = array_values($this->archivosMasivos);
        $this->procesarArchivosMasivos();
    }

    public function limpiarPendientes(): void
    {
        $this->fotosIndividuales = [];
        $this->fotoEditor = null;
        $this->archivosMasivos = [];
        $this->asignacionesMasivas = [];
        $this->eliminaciones = [];
        $this->erroresArchivos = [];
        $this->coincidenciaArchivo = [];
        $this->resetValidation();
    }

    public function getPendientesCountProperty(): int
    {
        $individuales = collect($this->fotosIndividuales)->filter()->keys()->map(fn ($id) => (int) $id);
        $masivos = collect($this->asignacionesMasivas)->filter()->map(fn ($id) => (int) $id);
        $eliminados = collect($this->eliminaciones)->filter()->keys()->map(fn ($id) => (int) $id);

        return $individuales->merge($masivos)->merge($eliminados)->unique()->count();
    }

    public function indiceMasivoParaAlumno(int $alumnoId): ?int
    {
        foreach ($this->asignacionesMasivas as $index => $asignado) {
            if ((int) $asignado === $alumnoId) {
                return (int) $index;
            }
        }

        return null;
    }

    public function guardarCambios(ImagenPersonalService $imagenes, SystemAuditService $auditoria): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->guardando = true;

        try {
            $duplicados = collect($this->asignacionesMasivas)
                ->filter()
                ->countBy(fn ($id) => (int) $id)
                ->filter(fn ($cantidad) => $cantidad > 1)
                ->keys();

            if ($duplicados->isNotEmpty()) {
                $this->addError('archivosMasivos', 'Hay dos o más fotografías masivas asignadas al mismo alumno. Corrige las asignaciones antes de guardar.');
                return;
            }

            foreach ($this->fotosIndividuales as $id => $archivo) {
                if (! $archivo) {
                    continue;
                }
                validator(['foto' => $archivo], [
                    'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                ])->validate();
            }

            foreach ($this->archivosMasivos as $index => $archivo) {
                if (! $archivo || ! ($this->asignacionesMasivas[$index] ?? null)) {
                    continue;
                }
                validator(['foto' => $archivo], [
                    'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                ])->validate();
            }

            $cambios = [];

            foreach ($this->eliminaciones as $alumnoId => $eliminar) {
                if (! $eliminar) {
                    continue;
                }
                $cambios[(int) $alumnoId] = ['tipo' => 'eliminar', 'archivo' => null];
            }

            foreach ($this->asignacionesMasivas as $index => $alumnoId) {
                if (! $alumnoId || ! isset($this->archivosMasivos[$index])) {
                    continue;
                }
                $cambios[(int) $alumnoId] = ['tipo' => 'masiva', 'archivo' => $this->archivosMasivos[$index]];
            }

            // La selección/editado individual tiene prioridad frente a una asignación masiva.
            foreach ($this->fotosIndividuales as $alumnoId => $archivo) {
                if (! $archivo) {
                    continue;
                }
                $cambios[(int) $alumnoId] = ['tipo' => 'individual', 'archivo' => $archivo];
            }

            if ($cambios === []) {
                $this->dispatch('swal', [
                    'title' => 'Sin cambios pendientes',
                    'text' => 'Selecciona, arrastra, reemplaza o elimina alguna fotografía antes de guardar.',
                    'icon' => 'info',
                    'position' => 'center',
                ]);
                return;
            }

            $guardadas = 0;
            $eliminadas = 0;
            $nuevasRutas = [];
            $rutasAnteriores = [];
            $auditoriasPendientes = [];

            // Primero se generan los nuevos archivos. Si alguno falla, todavía no
            // se ha modificado la base de datos ni se ha borrado ninguna foto actual.
            try {
                foreach ($cambios as $alumnoId => &$cambio) {
                    if ($cambio['tipo'] === 'eliminar') {
                        continue;
                    }

                    $cambio['nueva_ruta'] = $imagenes->guardarFotografiaAlumno($cambio['archivo']);
                    $nuevasRutas[] = $cambio['nueva_ruta'];
                }
                unset($cambio);

                DB::transaction(function () use ($cambios, &$guardadas, &$eliminadas, &$rutasAnteriores, &$auditoriasPendientes): void {
                    $alumnos = Inscripcion::withTrashed()->whereIn('id', array_keys($cambios))->get()->keyBy('id');

                    foreach ($cambios as $alumnoId => $cambio) {
                        /** @var Inscripcion|null $alumno */
                        $alumno = $alumnos->get($alumnoId);
                        if (! $alumno) {
                            continue;
                        }

                        $fotoAnterior = $alumno->foto_path;

                        if ($cambio['tipo'] === 'eliminar') {
                            $alumno->update(['foto_path' => null]);
                            $eliminadas++;

                            if (filled($fotoAnterior)) {
                                $rutasAnteriores[] = $fotoAnterior;
                            }

                            $auditoriasPendientes[] = [
                                'action' => 'fotografia_alumno_eliminada',
                                'metadata' => [
                                    'inscripcion_id' => $alumno->id,
                                    'matricula' => $alumno->matricula,
                                    'foto_anterior' => $fotoAnterior,
                                ],
                            ];
                            continue;
                        }

                        $nuevaRuta = (string) $cambio['nueva_ruta'];
                        $alumno->update(['foto_path' => $nuevaRuta]);
                        $guardadas++;

                        if (filled($fotoAnterior) && $fotoAnterior !== $nuevaRuta) {
                            $rutasAnteriores[] = $fotoAnterior;
                        }

                        $auditoriasPendientes[] = [
                            'action' => 'fotografia_alumno_actualizada',
                            'metadata' => [
                                'inscripcion_id' => $alumno->id,
                                'matricula' => $alumno->matricula,
                                'origen' => $cambio['tipo'],
                                'foto_anterior' => $fotoAnterior,
                                'foto_nueva' => $nuevaRuta,
                                'medida' => '2.5 x 3 cm',
                                'pixeles' => '295 x 354',
                            ],
                        ];
                    }
                });
            } catch (Throwable $e) {
                foreach ($nuevasRutas as $rutaNueva) {
                    $imagenes->eliminarRuta($rutaNueva);
                }
                throw $e;
            }

            // Los archivos anteriores se eliminan solo después de confirmar la BD.
            foreach (array_values(array_unique($rutasAnteriores)) as $rutaAnterior) {
                $imagenes->eliminarRuta($rutaAnterior);
            }

            foreach ($auditoriasPendientes as $registro) {
                $auditoria->record($registro['action'], 'alumnos', $registro['metadata']);
            }

            $this->limpiarPendientes();
            $this->dispatch('swal', [
                'title' => 'Fotografías actualizadas',
                'text' => "Se guardaron {$guardadas} fotografía(s) y se eliminaron {$eliminadas}.",
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('swal', [
                'title' => 'No se pudieron guardar las fotografías',
                'text' => $e->getMessage(),
                'icon' => 'error',
                'position' => 'center',
            ]);
        } finally {
            $this->guardando = false;
        }
    }

    private function queryBase(bool $ignorarEstadoFoto = false, bool $ignorarBusqueda = false): Builder
    {
        $cicloId = (int) $this->ciclo_escolar_id;
        $esActual = $this->cicloEsActual();

        $query = ($this->incluir_no_vigentes || ! $esActual
            ? Inscripcion::withTrashed()
            : Inscripcion::query())
            ->with([
                'ciclosEscolaresHistorial' => fn ($historial) => $historial
                    ->where('ciclo_escolar_id', $cicloId)
                    ->with([
                        'generacion',
                        'grado',
                        'semestre',
                        'grupo' => fn ($grupo) => $grupo->withTrashed()->with('asignacionGrupo'),
                    ]),
            ])
            ->whereHas('ciclosEscolaresHistorial', function (Builder $historial) use ($cicloId, $esActual): void {
                $historial
                    ->where('ciclo_escolar_id', $cicloId)
                    ->where('nivel_id', $this->nivel->id)
                    ->when($this->generacion_id, fn (Builder $q) => $q->where('generacion_id', $this->generacion_id))
                    ->when($this->grado_id, fn (Builder $q) => $q->where('grado_id', $this->grado_id))
                    ->when($this->semestre_id, fn (Builder $q) => $q->where('semestre_id', $this->semestre_id))
                    ->when($this->grupo_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_id));

                if ($esActual && ! $this->incluir_no_vigentes) {
                    $historial->where('estado', 'en_curso')->where('estatus_actual_ciclo', 'activo');
                    return;
                }

                $historial
                    ->where('estado', '!=', 'anulado')
                    ->whereIn('estatus_ingreso', ['activo', 'reingreso', 'no_promovido'])
                    ->where(function (Builder $resultado): void {
                        $resultado->whereNull('resultado_final')->orWhere('resultado_final', '!=', 'no_iniciado');
                    });
            });

        if ($esActual && ! $this->incluir_no_vigentes) {
            $query->visiblesEnListas();
        }

        if (! $ignorarBusqueda && trim($this->search) !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('matricula', 'like', $term)
                    ->orWhere('curp', 'like', $term)
                    ->orWhere('folio', 'like', $term)
                    ->orWhere('nombre', 'like', $term)
                    ->orWhere('apellido_paterno', 'like', $term)
                    ->orWhere('apellido_materno', 'like', $term);
            });
        }

        if (! $ignorarEstadoFoto) {
            match ($this->estado_foto) {
                'sin_foto' => $query->where(function (Builder $q): void {
                    $q->whereNull('foto_path')->orWhere('foto_path', '');
                }),
                'con_foto', 'archivo_faltante' => $query->whereNotNull('foto_path')->where('foto_path', '!=', ''),
                default => null,
            };
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    private function alumnosPaginados(): LengthAwarePaginator
    {
        if (! in_array($this->estado_foto, ['con_foto', 'archivo_faltante'], true)) {
            return $this->queryBase()->paginate($this->perPage);
        }

        $todos = $this->queryBase()->get()
            ->filter(fn (Inscripcion $a) => $this->estado_foto === 'con_foto' ? $a->foto_existe : ! $a->foto_existe)
            ->values();
        $pagina = max(1, $this->getPage());
        $items = $todos->forPage($pagina, $this->perPage)->values();

        return new Paginator(
            $items,
            $todos->count(),
            $this->perPage,
            $pagina,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    private function resumen(): array
    {
        $base = $this->queryBase(ignorarEstadoFoto: true);
        $alumnos = (clone $base)->get(['inscripciones.id', 'inscripciones.foto_path']);
        $conRuta = $alumnos->filter(fn ($a) => filled($a->foto_path));
        $faltantes = $conRuta->filter(fn ($a) => ! $a->foto_existe)->count();
        $conFotoReal = $conRuta->count() - $faltantes;
        $total = $alumnos->count();

        return [
            'total' => $total,
            'con_foto' => $conFotoReal,
            'sin_foto' => max(0, $total - $conRuta->count()),
            'faltantes' => $faltantes,
            'porcentaje' => $total > 0 ? (int) round(($conFotoReal / $total) * 100) : 0,
        ];
    }

    public function nombreCompleto($alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno->nombre ?? null,
            $alumno->apellido_paterno ?? null,
            $alumno->apellido_materno ?? null,
        ])));
    }

    public function etiquetaGrupo($historial): string
    {
        if (! $historial) {
            return 'Sin ubicación académica';
        }

        $grado = $historial->grado?->nombre ?? 'Sin grado';
        $grupo = $historial->grupo?->asignacionGrupo?->nombre
            ?? $historial->grupo?->grupo
            ?? $historial->grupo?->nombre
            ?? 'Sin grupo';

        if ($this->esBachillerato()) {
            $semestre = $historial->semestre?->numero
                ? "Sem. {$historial->semestre->numero}"
                : ($historial->semestre?->nombre ?? 'Sin semestre');
            return "{$grado} · {$semestre} · Grupo {$grupo}";
        }

        return "{$grado} · Grupo {$grupo}";
    }

    public function render()
    {
        $alumnos = $this->alumnosPaginados();
        $resumen = $this->resumen();
        $alumnosAsignables = $this->queryBase(ignorarEstadoFoto: true, ignorarBusqueda: true)
            ->get(['inscripciones.id', 'inscripciones.matricula', 'inscripciones.curp', 'inscripciones.nombre', 'inscripciones.apellido_paterno', 'inscripciones.apellido_materno']);

        return view('livewire.accion.gestor-fotografias', compact('alumnos', 'resumen', 'alumnosAsignables'));
    }
}
