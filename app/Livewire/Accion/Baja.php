<?php

namespace App\Livewire\Accion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Inscripcion;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Services\GestionAcademicaService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class Baja extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $niveles;
    public Collection $generaciones;
    public Collection $ciclosEscolares;

    public ?int $generacion_id = null;
    public ?int $ciclo_escolar_id = null;
    public string $search = '';
    public string $filtro_estatus = '';

    public array $selected = [];
    public bool $selectPage = false;

    public string $tipo_movimiento = 'baja_definitiva';
    public string $motivo = '';
    public string $observaciones = '';
    public string $fecha_movimiento = '';

    public string $motivo_reingreso = '';
    public string $fecha_reingreso = '';

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
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);

        $cicloPredeterminado = $this->ciclosEscolares->firstWhere('es_actual', true)
            ?? $this->ciclosEscolares->first();

        $this->ciclo_escolar_id = $cicloPredeterminado?->id;
        $this->generaciones = collect();
        $this->cargarGeneraciones(false);

        $this->fecha_movimiento = now()->toDateString();
        $this->fecha_reingreso = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['generacion_id', 'search', 'filtro_estatus'], true)) {
            $this->reiniciarSeleccionYPaginacion();
        }
    }

    public function updatedCicloEscolarId($value): void
    {
        $this->ciclo_escolar_id = filled($value) ? (int) $value : null;
        $this->cargarGeneraciones(false);
        $this->reiniciarSeleccionYPaginacion();
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selected = $value
            ? $this->activos()->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->reiniciarSeleccionYPaginacion();
    }

    public function limpiarFiltros(): void
    {
        $this->search = '';
        $this->filtro_estatus = '';
        $this->reiniciarSeleccionYPaginacion();
    }

    public function aplicarMovimiento(): void
    {
        $datos = $this->validate([
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:inscripciones,id'],
            'tipo_movimiento' => ['required', 'in:baja_temporal,baja_definitiva,trasladado,suspendido,inactivo'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'fecha_movimiento' => ['required', 'date'],
        ], [
            'generacion_id.required' => 'Selecciona una generación.',
            'selected.required' => 'Selecciona al menos un alumno.',
            'selected.min' => 'Selecciona al menos un alumno.',
            'motivo.required' => 'Escribe el motivo del movimiento.',
            'motivo.min' => 'El motivo debe contener al menos 5 caracteres.',
            'fecha_movimiento.required' => 'Selecciona la fecha del movimiento.',
        ]);

        $alumnos = $this->activosQuery()
            ->whereIn('inscripciones.id', array_map('intval', $datos['selected']))
            ->get();

        if ($alumnos->isEmpty()) {
            $this->addError('selected', 'Los alumnos seleccionados ya no están disponibles como activos.');

            return;
        }

        $service = app(GestionAcademicaService::class);
        $observaciones = filled($datos['observaciones'] ?? null)
            ? trim((string) $datos['observaciones'])
            : null;

        foreach ($alumnos as $alumno) {
            $actualizado = $service->cambiarEstatus(
                $alumno,
                $datos['tipo_movimiento'],
                trim($datos['motivo']),
                auth()->id(),
                $datos['fecha_movimiento']
            );

            $actualizado->forceFill([
                'observaciones_baja' => $observaciones,
            ])->save();
        }

        $cantidad = $alumnos->count();

        $this->selected = [];
        $this->selectPage = false;
        $this->tipo_movimiento = 'baja_definitiva';
        $this->motivo = '';
        $this->observaciones = '';
        $this->fecha_movimiento = now()->toDateString();
        $this->resetPage();
        $this->resetPage('inactivosPage');
        $this->resetPage('historialPage');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $cantidad === 1 ? 'Movimiento registrado' : "{$cantidad} movimientos registrados",
            'text' => 'Los alumnos conservan su generación y ahora aparecen en bajas o movimientos administrativos.',
            'position' => 'top-end',
        ]);
    }

    public function reactivarAlumno(int $inscripcionId): void
    {
        $datos = $this->validate([
            'fecha_reingreso' => ['required', 'date'],
            'motivo_reingreso' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'fecha_reingreso.required' => 'Selecciona la fecha de reincorporación.',
            'motivo_reingreso.required' => 'Escribe el motivo de la reincorporación.',
            'motivo_reingreso.min' => 'El motivo debe contener al menos 5 caracteres.',
        ]);

        $alumno = $this->inactivosQuery()->whereKey($inscripcionId)->firstOrFail();

        $actualizado = app(GestionAcademicaService::class)->cambiarEstatus(
            $alumno,
            'reingreso',
            trim($datos['motivo_reingreso']),
            auth()->id(),
            $datos['fecha_reingreso']
        );

        $actualizado->forceFill([
            'observaciones_baja' => null,
        ])->save();

        $this->motivo_reingreso = '';
        $this->fecha_reingreso = now()->toDateString();
        $this->resetPage();
        $this->resetPage('inactivosPage');
        $this->resetPage('historialPage');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Reincorporación registrada',
            'text' => 'El alumno conserva su generación original y vuelve a la matrícula activa.',
            'position' => 'top-end',
        ]);
    }

    public function etiquetaEstatus(?string $estatus): string
    {
        return match ($estatus) {
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'traslado', 'trasladado' => 'Traslado',
            'suspendido' => 'Suspendido',
            'inactivo' => 'Inactivo',
            'egresado' => 'Egresado',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            'preinscrito' => 'Preinscrito',
            default => 'Activo',
        };
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre
            ?? $grupo?->nombre
            ?? '—';
    }

    public function nombreCompleto(Inscripcion $alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ], fn ($valor) => filled($valor)))) ?: '—';
    }

    public function iniciales(Inscripcion $alumno): string
    {
        $partes = array_values(array_filter([
            $alumno->nombre,
            $alumno->apellido_paterno,
        ], fn ($valor) => filled($valor)));

        return collect($partes)
            ->map(fn ($valor) => mb_strtoupper(mb_substr(trim((string) $valor), 0, 1)))
            ->take(2)
            ->implode('') ?: 'A';
    }

    public function claseEstatus(?string $estatus): string
    {
        return match ($estatus) {
            'activo' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300',
            'preinscrito' => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300',
            'reingreso' => 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300',
            'no_promovido' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300',
            'traslado', 'trasladado' => 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300',
            'suspendido' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/40 dark:bg-orange-950/30 dark:text-orange-300',
            'egresado' => 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900/40 dark:bg-violet-950/30 dark:text-violet-300',
            'inactivo' => 'border-slate-200 bg-slate-100 text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300',
            default => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300',
        };
    }

    public function fechaMovimientoTexto(Inscripcion $alumno): string
    {
        $fecha = $alumno->fecha_estatus ?: $alumno->fecha_baja;

        if (!$fecha) {
            return '—';
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y');
        } catch (\Throwable) {
            return '—';
        }
    }

    private function cargarGeneraciones(bool $conservarSeleccion = true): void
    {
        $seleccionAnterior = $conservarSeleccion ? $this->generacion_id : null;
        $ciclo = $this->ciclo_escolar_id
            ? $this->ciclosEscolares->firstWhere('id', (int) $this->ciclo_escolar_id)
            : null;

        $consulta = Generacion::query()
            ->whereNull('deleted_at')
            ->where('nivel_id', $this->nivel?->id);

        if ($ciclo) {
            $consulta->where(function (Builder $query) use ($ciclo): void {
                $query->where(function (Builder $porRango) use ($ciclo): void {
                    $porRango
                        ->where('anio_ingreso', '<=', (int) $ciclo->inicio_anio)
                        ->where('anio_egreso', '>=', (int) $ciclo->fin_anio);
                })->orWhereHas('inscripciones', function (Builder $inscripciones) use ($ciclo): void {
                    $inscripciones->where('ciclo_escolar_id', (int) $ciclo->id);
                });
            });
        }

        $this->generaciones = $consulta
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();

        if ($seleccionAnterior && $this->generaciones->contains('id', (int) $seleccionAnterior)) {
            $this->generacion_id = (int) $seleccionAnterior;

            return;
        }

        $this->generacion_id = $this->generaciones->firstWhere('status', true)?->id
            ?: $this->generaciones->first()?->id;
    }

    private function reiniciarSeleccionYPaginacion(): void
    {
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();
        $this->resetPage('inactivosPage');
        $this->resetPage('historialPage');
    }

    private function baseQuery(): Builder
    {
        return Inscripcion::query()
            ->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $this->nivel?->id)
            ->when($this->ciclo_escolar_id, fn (Builder $query) => $query->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->generacion_id, fn (Builder $query) => $query->where('generacion_id', $this->generacion_id))
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%' . trim($this->search) . '%';

                $query->where(function (Builder $search) use ($term): void {
                    $search->where('matricula', 'like', $term)
                        ->orWhere('curp', 'like', $term)
                        ->orWhere('nombre', 'like', $term)
                        ->orWhere('apellido_paterno', 'like', $term)
                        ->orWhere('apellido_materno', 'like', $term);
                });
            });
    }

    private function activosQuery(): Builder
    {
        return $this->baseQuery()
            ->where('activo', true)
            ->when($this->filtro_estatus !== '', fn (Builder $query) => $query->where('estatus', $this->filtro_estatus));
    }

    private function inactivosQuery(): Builder
    {
        return $this->baseQuery()
            ->where(function (Builder $query): void {
                $query->whereIn('estatus', Inscripcion::ESTATUS_BAJA_ADMINISTRATIVA)
                    ->orWhere(function (Builder $legado): void {
                        $legado->where('activo', false)
                            ->where(function (Builder $sinEstatus): void {
                                $sinEstatus->whereNull('estatus')->orWhere('estatus', '');
                            });
                    });
            });
    }

    private function activos(): LengthAwarePaginator
    {
        return $this->activosQuery()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(15);
    }

    private function inactivos(): LengthAwarePaginator
    {
        return $this->inactivosQuery()
            ->orderByDesc('fecha_estatus')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(15, ['*'], 'inactivosPage');
    }

    private function historialMovimientos(): LengthAwarePaginator
    {
        return MovimientoAlumno::query()
            ->with(['inscripcion.generacion', 'cicloEscolar', 'usuario'])
            ->whereHas('inscripcion', fn (Builder $query) => $query->where('nivel_id', $this->nivel?->id))
            ->when($this->ciclo_escolar_id, fn (Builder $query) => $query->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->generacion_id, function (Builder $query): void {
                $query->whereHas('inscripcion', fn (Builder $alumno) => $alumno->where('generacion_id', $this->generacion_id));
            })
            ->when(trim($this->search) !== '', function (Builder $query): void {
                $term = '%' . trim($this->search) . '%';
                $query->whereHas('inscripcion', function (Builder $alumno) use ($term): void {
                    $alumno->where('matricula', 'like', $term)
                        ->orWhere('nombre', 'like', $term)
                        ->orWhere('apellido_paterno', 'like', $term)
                        ->orWhere('apellido_materno', 'like', $term);
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(12, ['*'], 'historialPage');
    }

    public function render()
    {
        $activosQuery = $this->activosQuery();
        $inactivosQuery = $this->inactivosQuery();

        return view('livewire.accion.baja', [
            'activos' => $this->activos(),
            'inactivos' => $this->inactivos(),
            'generacionSeleccionada' => $this->generaciones->firstWhere('id', $this->generacion_id),
            'total' => (clone $activosQuery)->count(),
            'hombres' => (clone $activosQuery)->whereIn('genero', ['H', 'Hombre'])->count(),
            'mujeres' => (clone $activosQuery)->whereIn('genero', ['M', 'Mujer'])->count(),
            'totalBajas' => (clone $inactivosQuery)->count(),
            'historialMovimientos' => $this->historialMovimientos(),
        ]);
    }
}
