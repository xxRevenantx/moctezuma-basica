<?php

namespace App\Livewire\Accion;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Facades\Excel;

class Matricula extends Component
{
    use WithPagination;

    public string $slug_nivel = '';

    public ?Nivel $nivel = null;
    public Collection $niveles;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public Collection $generaciones;
    public Collection $semestres;
    public Collection $grupos;
    public Collection $grados;

    public string $search = '';

    public int $total = 0;
    public int $hombres = 0;
    public int $mujeres = 0;

    public ?string $generacionGrupoLabel = null;

    public bool $selectPage = false;
    public array $selected = [];

    public ?int $nuevo_grado_id = null;
    public ?int $nuevo_semestre_id = null;
    public ?int $nuevo_grupo_id = null;

    public Collection $nuevosSemestres;
    public Collection $nuevosGrupos;

    public int $perPage = 10;

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderBy('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->semestres = collect();
        $this->grupos = collect();
        $this->nuevosSemestres = collect();
        $this->nuevosGrupos = collect();

        $this->recalcularResumen();
    }

    public function esBachillerato(): bool
    {
        return (int) $this->nivel?->id === 4;
    }

    protected function baseGrupoQuery(): Builder
    {
        return Grupo::query()
            ->where('nivel_id', $this->nivel->id);
    }

    protected function loadSemestres(): Collection
    {
        if (!$this->esBachillerato() || !$this->grado_id) {
            return collect();
        }

        return Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero']);
    }

    protected function loadSemestresDestino(): Collection
    {
        if (!$this->esBachillerato() || !$this->nuevo_grado_id) {
            return collect();
        }

        return Semestre::query()
            ->where('grado_id', $this->nuevo_grado_id)
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero']);
    }

    protected function loadGrupos(): Collection
    {
        if (!$this->grado_id || !$this->generacion_id) {
            return collect();
        }

        $query = $this->baseGrupoQuery()
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato()) {
            if (!$this->semestre_id) {
                return collect();
            }

            $query->where('semestre_id', $this->semestre_id);
        } else {
            $query->whereNull('semestre_id');
        }

        return $query
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'generacion_id', 'grado_id', 'semestre_id']);
    }

    protected function loadGruposDestino(): Collection
    {
        if (!$this->nivel || !$this->generacion_id || !$this->nuevo_grado_id) {
            return collect();
        }

        $query = $this->baseGrupoQuery()
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->nuevo_grado_id);

        if ($this->esBachillerato()) {
            if (!$this->nuevo_semestre_id) {
                return collect();
            }

            $query->where('semestre_id', $this->nuevo_semestre_id);
        } else {
            $query->whereNull('semestre_id');
        }

        return $query
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'nivel_id', 'generacion_id', 'grado_id', 'semestre_id']);
    }

    protected function consultaInscripcionesBase(): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo:id,nombre',
                'semestre:id,numero',
                'generacion:id,anio_ingreso,anio_egreso',
            ])
            ->where('nivel_id', $this->nivel->id);

        if ($this->generacion_id) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if ($this->grado_id) {
            $query->where('grado_id', $this->grado_id);
        }

        if ($this->grupo_id) {
            $query->where('grupo_id', $this->grupo_id);
        }

        if ($this->esBachillerato()) {
            if ($this->semestre_id) {
                $query->where('semestre_id', $this->semestre_id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($this->search !== '') {
            $busqueda = trim($this->search);

            $query->where(function ($q) use ($busqueda) {
                $q->where('matricula', 'like', "%{$busqueda}%")
                    ->orWhere('curp', 'like', "%{$busqueda}%")
                    ->orWhere('folio', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_materno', 'like', "%{$busqueda}%");
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    protected function consultaPersonal(): Collection
    {
        if (
            !$this->grado_id ||
            !$this->grupo_id ||
            !$this->generacion_id ||
            ($this->esBachillerato() && !$this->semestre_id)
        ) {
            return collect();
        }

        return PersonaNivel::query()
            ->with([
                'persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero',
                'nivel:id,nombre',
                'detalles' => function ($query) {
                    $query->with([
                        'grado:id,nombre',
                        'grupo:id,nombre',
                    ]);
                },
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('detalles', function ($query) {
                $query->where('grado_id', $this->grado_id)
                    ->where('grupo_id', $this->grupo_id);
            })
            ->get();
    }

    protected function recalcularResumen(): void
    {
        $base = $this->consultaInscripcionesBase();

        $this->total = (clone $base)->count();
        $this->hombres = (clone $base)->where('genero', 'H')->count();
        $this->mujeres = (clone $base)->where('genero', 'M')->count();

        $this->actualizarGeneracionLabel();
    }

    protected function actualizarGeneracionLabel(): void
    {
        $this->generacionGrupoLabel = null;

        if (!$this->generacion_id) {
            return;
        }

        $generacion = $this->generaciones->firstWhere('id', $this->generacion_id);

        if ($generacion) {
            $this->generacionGrupoLabel = $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso;
        }
    }

    protected function limpiarSeleccion(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    protected function limpiarDestinoCambio(): void
    {
        $this->nuevo_grado_id = null;
        $this->nuevo_semestre_id = null;
        $this->nuevo_grupo_id = null;
        $this->nuevosSemestres = collect();
        $this->nuevosGrupos = collect();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;

        $this->grupo_id = null;
        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();

        $this->grupos = $this->loadGrupos();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value ? (int) $value : null;

        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();

        $this->semestres = collect();
        $this->grupos = collect();

        if ($this->esBachillerato()) {
            $this->semestres = $this->loadSemestres();
        } else {
            $this->grupos = $this->loadGrupos();
        }

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;

        $this->grupo_id = null;

        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();

        $this->grupos = $this->loadGrupos();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;

        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSearch(): void
    {
        $this->limpiarSeleccion();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedNuevoGradoId($value): void
    {
        $this->nuevo_grado_id = $value ? (int) $value : null;
        $this->nuevo_semestre_id = null;
        $this->nuevo_grupo_id = null;

        $this->resetValidation([
            'nuevo_grado_id',
            'nuevo_semestre_id',
            'nuevo_grupo_id',
        ]);

        $this->nuevosSemestres = $this->esBachillerato()
            ? $this->loadSemestresDestino()
            : collect();

        $this->nuevosGrupos = $this->esBachillerato()
            ? collect()
            : $this->loadGruposDestino();
    }

    public function updatedNuevoSemestreId($value): void
    {
        $this->nuevo_semestre_id = $value ? (int) $value : null;
        $this->nuevo_grupo_id = null;

        $this->resetValidation([
            'nuevo_semestre_id',
            'nuevo_grupo_id',
        ]);

        $this->nuevosGrupos = $this->loadGruposDestino();
    }

    public function updatedNuevoGrupoId($value): void
    {
        $this->nuevo_grupo_id = $value ? (int) $value : null;

        $this->resetValidation('nuevo_grupo_id');
    }

    public function updatedSelectPage($value): void
    {
        if ((bool) $value) {
            $this->selected = $this->consultaInscripcionesBase()
                ->paginate($this->perPage, ['*'], 'page', $this->getPage())
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->all();
        } else {
            $this->selected = [];
        }
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function clearFilters(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'semestre_id',
            'grupo_id',
            'search',
            'selectPage',
            'selected',
            'nuevo_grado_id',
            'nuevo_semestre_id',
            'nuevo_grupo_id',
        ]);

        $this->semestres = collect();
        $this->grupos = collect();
        $this->nuevosSemestres = collect();
        $this->nuevosGrupos = collect();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function eliminar(int $id): void
    {
        $inscripcion = Inscripcion::query()->findOrFail($id);
        $inscripcion->delete();

        $this->selected = array_values(array_filter(
            $this->selected,
            fn($item) => (int) $item !== $id
        ));

        $this->selectPage = false;
        $this->recalcularResumen();

        $this->dispatch('swal', [
            'title' => 'Registro eliminado correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function aplicarCambiarGrado(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('swal', [
                'title' => 'Selecciona al menos un alumno.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        if (!$this->generacion_id) {
            $this->addError('nuevo_grado_id', 'Primero filtra una generación.');

            return;
        }

        if ($this->esBachillerato()) {
            $this->aplicarCambiarBachillerato();

            return;
        }

        $this->validate([
            'nuevo_grado_id' => ['required', 'integer', 'exists:grados,id'],
            'nuevo_grupo_id' => ['required', 'integer', 'exists:grupos,id'],
        ], [
            'nuevo_grado_id.required' => 'Selecciona el grado destino.',
            'nuevo_grupo_id.required' => 'Selecciona el grupo destino.',
        ]);

        $gradoValido = Grado::query()
            ->where('id', $this->nuevo_grado_id)
            ->where('nivel_id', $this->nivel->id)
            ->exists();

        if (!$gradoValido) {
            $this->addError('nuevo_grado_id', 'El grado destino no pertenece al nivel actual.');

            return;
        }

        $grupoDestino = Grupo::query()
            ->where('id', $this->nuevo_grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->nuevo_grado_id)
            ->whereNull('semestre_id')
            ->first(['id', 'nombre', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id']);

        if (!$grupoDestino) {
            $this->addError('nuevo_grupo_id', 'El grupo destino no pertenece al nivel, generación y grado seleccionados.');

            return;
        }

        $ids = collect($this->selected)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $alumnosValidos = Inscripcion::query()
            ->whereIn('id', $ids)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->whereNull('semestre_id')
            ->pluck('id');

        if ($alumnosValidos->count() !== $ids->count()) {
            $this->dispatch('swal', [
                'title' => 'Hay alumnos seleccionados que no pertenecen al nivel o generación filtrada.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        DB::transaction(function () use ($alumnosValidos, $grupoDestino) {
            Inscripcion::query()
                ->whereIn('id', $alumnosValidos)
                ->update([
                    'grado_id' => (int) $this->nuevo_grado_id,
                    'grupo_id' => (int) $grupoDestino->id,
                    'semestre_id' => null,
                ]);
        });

        $totalActualizados = $alumnosValidos->count();

        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();
        $this->recalcularResumen();

        $this->dispatch('swal', [
            'title' => "{$totalActualizados} alumno(s) actualizados al grado y grupo destino.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    protected function aplicarCambiarBachillerato(): void
    {
        $this->validate([
            'nuevo_grado_id' => ['required', 'integer', 'exists:grados,id'],
            'nuevo_semestre_id' => ['required', 'integer', 'exists:semestres,id'],
            'nuevo_grupo_id' => ['required', 'integer', 'exists:grupos,id'],
        ], [
            'nuevo_grado_id.required' => 'Selecciona el grado destino.',
            'nuevo_semestre_id.required' => 'Selecciona el semestre destino.',
            'nuevo_grupo_id.required' => 'Selecciona el grupo destino.',
        ]);

        $gradoValido = Grado::query()
            ->where('id', $this->nuevo_grado_id)
            ->where('nivel_id', $this->nivel->id)
            ->exists();

        if (!$gradoValido) {
            $this->addError('nuevo_grado_id', 'El grado destino no pertenece a bachillerato.');

            return;
        }

        $semestreValido = Semestre::query()
            ->where('id', $this->nuevo_semestre_id)
            ->where('grado_id', $this->nuevo_grado_id)
            ->exists();

        if (!$semestreValido) {
            $this->addError('nuevo_semestre_id', 'El semestre destino no pertenece al grado seleccionado.');

            return;
        }

        $grupoDestino = Grupo::query()
            ->where('id', $this->nuevo_grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->nuevo_grado_id)
            ->where('semestre_id', $this->nuevo_semestre_id)
            ->first(['id', 'nombre', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id']);

        if (!$grupoDestino) {
            $this->addError('nuevo_grupo_id', 'El grupo destino no pertenece al nivel, generación, grado y semestre seleccionados.');

            return;
        }

        $ids = collect($this->selected)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $alumnosValidos = Inscripcion::query()
            ->whereIn('id', $ids)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->whereNotNull('semestre_id')
            ->pluck('id');

        if ($alumnosValidos->count() !== $ids->count()) {
            $this->dispatch('swal', [
                'title' => 'Hay alumnos seleccionados que no pertenecen al nivel o generación filtrada.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        DB::transaction(function () use ($alumnosValidos, $grupoDestino) {
            Inscripcion::query()
                ->whereIn('id', $alumnosValidos)
                ->update([
                    'grado_id' => (int) $this->nuevo_grado_id,
                    'semestre_id' => (int) $this->nuevo_semestre_id,
                    'grupo_id' => (int) $grupoDestino->id,
                ]);
        });

        $totalActualizados = $alumnosValidos->count();

        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();
        $this->recalcularResumen();

        $this->dispatch('swal', [
            'title' => "{$totalActualizados} alumno(s) actualizados al grado, semestre y grupo destino.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function exportarMatricula()
    {
        $rows = $this->consultaInscripcionesBase()->get();

        $esBachillerato = $this->esBachillerato();

        return Excel::download(
            new class ($rows, $esBachillerato) implements FromCollection, WithHeadings, WithMapping {
            public function __construct(
            protected Collection $rows,
            protected bool $esBachillerato
            ) {}

            public function collection()
            {
                return $this->rows;
            }

            public function headings(): array
            {
                $headings = [
                'Matrícula',
                'Folio',
                'Apellido paterno',
                'Apellido materno',
                'Nombre(s)',
                'CURP',
                'Género',
                'Generación',
                'Grado',
                ];

                if ($this->esBachillerato) {
                    $headings[] = 'Semestre';
                }

                $headings[] = 'Grupo';

                return $headings;
            }

            public function map($row): array
            {
                $data = [
                    $row->matricula,
                    $row->folio,
                    $row->apellido_paterno,
                    $row->apellido_materno,
                    $row->nombre,
                    $row->curp,
                    $row->genero,
                    $row->generacion ? ($row->generacion->anio_ingreso . ' - ' . $row->generacion->anio_egreso) : null,
                    $row->grado?->nombre,
                ];

                if ($this->esBachillerato) {
                    $data[] = $row->semestre?->numero;
                }

                $data[] = $row->grupo?->nombre;

                return $data;
            }
            },
            'matricula.xlsx'
        );
    }

    public function restaurarFiltrosMatricula(array $filtros): void
    {
        // Se restauran los filtros en el mismo orden de dependencia de los selects.
        $this->generacion_id = !empty($filtros['generacion_id'])
            ? (int) $filtros['generacion_id']
            : null;

        $this->grado_id = !empty($filtros['grado_id'])
            ? (int) $filtros['grado_id']
            : null;

        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->limpiarSeleccion();
        $this->limpiarDestinoCambio();

        // Primero se reconstruyen los semestres si el nivel es bachillerato.
        $this->semestres = $this->esBachillerato()
            ? $this->loadSemestres()
            : collect();

        if ($this->esBachillerato()) {
            $semestreId = !empty($filtros['semestre_id'])
                ? (int) $filtros['semestre_id']
                : null;

            $semestreExiste = $semestreId
                ? $this->semestres->contains('id', $semestreId)
                : false;

            $this->semestre_id = $semestreExiste ? $semestreId : null;
        }

        // Después se reconstruyen los grupos con generación, grado y semestre ya definidos.
        $this->grupos = $this->loadGrupos();

        $grupoId = !empty($filtros['grupo_id'])
            ? (int) $filtros['grupo_id']
            : null;

        $grupoExiste = $grupoId
            ? $this->grupos->contains('id', $grupoId)
            : false;

        $this->grupo_id = $grupoExiste ? $grupoId : null;

        $this->search = isset($filtros['search'])
            ? trim((string) $filtros['search'])
            : '';

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function render()
    {
        $rows = $this->consultaInscripcionesBase()->paginate($this->perPage);
        $personal = $this->consultaPersonal();

        return view('livewire.accion.matricula', [
            'rows' => $rows,
            'personal' => $personal,
            'esBachillerato' => $this->esBachillerato(),
            'nuevosSemestres' => $this->nuevosSemestres,
            'nuevosGrupos' => $this->nuevosGrupos,
        ]);
    }
}
