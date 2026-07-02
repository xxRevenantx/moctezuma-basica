<?php

namespace App\Livewire\Accion;

use App\Models\Generacion;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\GestionAcademicaService;
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

    public ?int $generacion_id = null;
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

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();

        $this->generacion_id = $this->generaciones->firstWhere('status', true)?->id
            ?: $this->generaciones->first()?->id;

        $this->fecha_movimiento = now()->toDateString();
        $this->fecha_reingreso = now()->toDateString();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['generacion_id', 'search', 'filtro_estatus'], true)) {
            $this->selected = [];
            $this->selectPage = false;
            $this->resetPage();
            $this->resetPage('inactivosPage');
        }
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
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();
        $this->resetPage('inactivosPage');
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

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $cantidad === 1 ? 'Movimiento registrado' : "{$cantidad} movimientos registrados",
            'text' => 'Los alumnos conservan su generación y ahora aparecen en estados no activos.',
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

        if ($alumno->estatus === 'egresado') {
            $this->addError('motivo_reingreso', 'Un alumno egresado no puede reincorporarse desde este módulo.');

            return;
        }

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
            'trasladado' => 'Traslado',
            'suspendido' => 'Suspendido',
            'inactivo' => 'Inactivo',
            'egresado' => 'Egresado',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            default => 'Activo',
        };
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre
            ?? $grupo?->nombre
            ?? '—';
    }

    private function baseQuery(): Builder
    {
        return Inscripcion::query()
            ->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $this->nivel->id)
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
                $query->where('activo', false)
                    ->orWhereIn('estatus', [
                        'baja_temporal',
                        'baja_definitiva',
                        'trasladado',
                        'suspendido',
                        'inactivo',
                        'egresado',
                    ]);
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

    public function render()
    {
        $activosQuery = $this->activosQuery();
        $inactivosQuery = $this->inactivosQuery();

        return view('livewire.accion.baja', [
            'activos' => $this->activos(),
            'inactivos' => $this->inactivos(),
            'generacionSeleccionada' => $this->generaciones->firstWhere('id', $this->generacion_id),
            'total' => (clone $activosQuery)->count(),
            'hombres' => (clone $activosQuery)->where('genero', 'Hombre')->count(),
            'mujeres' => (clone $activosQuery)->where('genero', 'Mujer')->count(),
            'totalBajas' => (clone $inactivosQuery)->count(),
        ]);
    }
}
