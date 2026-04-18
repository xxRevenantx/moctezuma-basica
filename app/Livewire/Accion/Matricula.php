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

    public ?string $gradoGeneracionLabel = null;

    public bool $selectPage = false;
    public array $selected = [];
    public ?int $nuevo_grado_id = null;

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

        $this->generaciones = $this->loadGeneraciones();
        $this->semestres = collect();
        $this->grupos = collect();
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

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

    protected function loadGeneraciones(): Collection
    {
        $generacionIds = $this->baseGrupoQuery()
            ->select('generacion_id')
            ->distinct()
            ->pluck('generacion_id')
            ->filter()
            ->values();

        if ($generacionIds->isEmpty()) {
            return collect();
        }

        return Generacion::query()
            ->whereIn('id', $generacionIds)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'anio_ingreso', 'anio_egreso']);
    }

    protected function loadSemestres(): Collection
    {
        if (!$this->esBachillerato() || !$this->generacion_id) {
            return collect();
        }

        $semestreIds = $this->baseGrupoQuery()
            ->where('generacion_id', $this->generacion_id)
            ->whereNotNull('semestre_id')
            ->select('semestre_id')
            ->distinct()
            ->pluck('semestre_id')
            ->filter()
            ->values();

        if ($semestreIds->isEmpty()) {
            return collect();
        }

        return Semestre::query()
            ->whereIn('id', $semestreIds)
            ->orderBy('numero')
            ->get(['id', 'numero']);
    }

    protected function loadGrupos(): Collection
    {
        if (!$this->generacion_id) {
            return collect();
        }

        $query = $this->baseGrupoQuery()
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato()) {
            if (!$this->semestre_id) {
                return collect();
            }

            $query->where('semestre_id', $this->semestre_id);
        }

        return $query
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'generacion_id', 'semestre_id']);
    }

    protected function consultaInscripcionesBase(): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'grado:id,nombre',
                'grupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where('nivel_id', $this->nivel->id);

        if ($this->generacion_id) {
            $query->where('generacion_id', $this->generacion_id);
        }

        if ($this->esBachillerato()) {
            if ($this->semestre_id) {
                $query->where('semestre_id', $this->semestre_id);
            }
        }

        if ($this->grupo_id) {
            $query->where('grupo_id', $this->grupo_id);
        }

        if (trim($this->search) !== '') {
            $buscar = trim($this->search);

            $query->where(function ($q) use ($buscar) {
                $q->where('matricula', 'like', "%{$buscar}%")
                    ->orWhere('curp', 'like', "%{$buscar}%")
                    ->orWhere('folio', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                    ->orWhere('apellido_materno', 'like', "%{$buscar}%");
            });
        }

        return $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    protected function consultaPersonal(): Collection
    {
        if (!$this->filtrosCompletos()) {
            return collect();
        }

        return PersonaNivel::query()
            ->with([
                'persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero',
                'nivel:id,nombre',
                'detalles' => function ($q) {
                    $q->with([
                        'grado:id,nombre',
                        'grupo:id,nombre',
                    ])->where('grupo_id', $this->grupo_id);
                },
            ])
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('detalles', function ($q) {
                $q->where('grupo_id', $this->grupo_id);
            })
            ->get();
    }

    protected function filtrosCompletos(): bool
    {
        if ($this->esBachillerato()) {
            return !empty($this->generacion_id) && !empty($this->semestre_id) && !empty($this->grupo_id);
        }

        return !empty($this->generacion_id) && !empty($this->grupo_id);
    }

    protected function recalcularResumen(): void
    {
        $query = $this->consultaInscripcionesBase();

        $this->total = (clone $query)->count();
        $this->hombres = (clone $query)->where('genero', 'H')->count();
        $this->mujeres = (clone $query)->where('genero', 'M')->count();

        $this->gradoGeneracionLabel = $this->obtenerLabelGradosGeneracion();
    }

    protected function obtenerLabelGradosGeneracion(): ?string
    {
        if (!$this->generacion_id) {
            return null;
        }

        $nombres = $this->baseGrupoQuery()
            ->where('generacion_id', $this->generacion_id)
            ->with('grado:id,nombre')
            ->get()
            ->pluck('grado.nombre')
            ->filter()
            ->unique()
            ->values();

        if ($nombres->isEmpty()) {
            return null;
        }

        return $nombres->implode(', ');
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;

        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->selected = [];
        $this->selectPage = false;

        $this->semestres = collect();
        $this->grupos = collect();

        if ($this->esBachillerato() && $this->generacion_id) {
            $this->semestres = $this->loadSemestres();
        }

        if (!$this->esBachillerato() && $this->generacion_id) {
            $this->grupos = $this->loadGrupos();
        }

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;

        $this->grupo_id = null;
        $this->selected = [];
        $this->selectPage = false;

        $this->grupos = $this->loadGrupos();

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;

        $this->selected = [];
        $this->selectPage = false;

        $this->resetPage();
        $this->recalcularResumen();
    }

    public function updatedSearch(): void
    {
        $this->selected = [];
        $this->selectPage = false;

        $this->resetPage();
        $this->recalcularResumen();
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
            'semestre_id',
            'grupo_id',
            'search',
            'selectPage',
            'selected',
            'nuevo_grado_id',
        ]);

        $this->semestres = collect();
        $this->grupos = collect();

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
        if ($this->esBachillerato()) {
            $this->dispatch('swal', [
                'title' => 'En bachillerato este cambio masivo se debe controlar con semestre y grupo.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->validate([
            'nuevo_grado_id' => ['required', 'integer', 'exists:grados,id'],
        ], [
            'nuevo_grado_id.required' => 'Selecciona un grado.',
        ]);

        if (empty($this->selected)) {
            $this->dispatch('swal', [
                'title' => 'Selecciona al menos un alumno.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $gradoValido = Grado::query()
            ->where('id', $this->nuevo_grado_id)
            ->where('nivel_id', $this->nivel->id)
            ->exists();

        if (!$gradoValido) {
            $this->addError('nuevo_grado_id', 'El grado no pertenece al nivel.');
            return;
        }

        Inscripcion::query()
            ->whereIn('id', $this->selected)
            ->where('nivel_id', $this->nivel->id)
            ->update([
                'grado_id' => $this->nuevo_grado_id,
            ]);

        $this->selected = [];
        $this->selectPage = false;
        $this->nuevo_grado_id = null;

        $this->recalcularResumen();

        $this->dispatch('swal', [
            'title' => 'Grado actualizado correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function exportarMatricula()
    {
        $rows = $this->consultaInscripcionesBase()->get();

        $esBachillerato = $this->esBachillerato();

        return Excel::download(
            new class($rows, $esBachillerato) implements FromCollection, WithHeadings, WithMapping {
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

    public function render()
    {
        $rows = $this->consultaInscripcionesBase()->paginate($this->perPage);
        $personal = $this->consultaPersonal();

        return view('livewire.accion.matricula', [
            'rows' => $rows,
            'personal' => $personal,
            'esBachillerato' => $this->esBachillerato(),
        ]);
    }
}
