<?php

namespace App\Livewire\Accion;

use App\Http\Controllers\FichaController;
use App\Models\FichaDescriptiva;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\cicloEscolar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

use App\Exports\FichaDescriptivaPlantillaImportacionExport;
use App\Imports\FichaDescriptivaImport;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class Ficha extends Component
{
    use WithPagination;
    use WithFileUploads;


    protected string $paginationTheme = 'tailwind';

    public $archivo_fichas = null;

    public string $slug_nivel = 'preescolar';
    public ?string $slug_grado = null;

    public ?int $nivel_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $ciclo_escolar_id = null;

    public int $periodo = 1;
    public string $busqueda = '';
    public string $fecha_lugar = '';

    public bool $modalAbierto = false;
    public ?int $inscripcion_id = null;
    public string $campo = '';
    public string $descripcion = '';

    public array $campos = [];

    protected $queryString = [
        'generacion_id' => ['except' => null],
        'grado_id' => ['except' => null],
        'grupo_id' => ['except' => null],
        'periodo' => ['except' => 1],
        'busqueda' => ['except' => ''],
    ];

    public function descargarPlantillaImportacion()
    {
        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'periodo' => ['required', 'integer', 'in:1,2,3'],
        ], [
            'grado_id.required' => 'Selecciona un grado para descargar la plantilla.',
        ]);

        $nombreArchivo = 'plantilla_fichas_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(
            new FichaDescriptivaPlantillaImportacionExport(
                nivelId: $this->nivel_id,
                gradoId: $this->grado_id,
                grupoId: $this->grupo_id,
                generacionId: $this->generacion_id,
                cicloEscolarId: $this->ciclo_escolar_id,
                periodo: $this->periodo
            ),
            $nombreArchivo
        );
    }

    public function importarPlantillaFichas(): void
    {
        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'periodo' => ['required', 'integer', 'in:1,2,3'],
            'archivo_fichas' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'grado_id.required' => 'Selecciona el grado correspondiente a la plantilla.',
            'archivo_fichas.required' => 'Selecciona el archivo Excel de fichas.',
            'archivo_fichas.mimes' => 'El archivo debe ser Excel .xlsx o .xls.',
        ]);

        Excel::import(
            new FichaDescriptivaImport(
                nivelId: $this->nivel_id,
                gradoId: $this->grado_id,
                grupoId: $this->grupo_id,
                generacionId: $this->generacion_id,
                cicloEscolarId: $this->ciclo_escolar_id,
                periodo: $this->periodo
            ),
            $this->archivo_fichas
        );

        $this->archivo_fichas = null;
        $this->resetPage();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Fichas importadas correctamente',
            'position' => 'top',
        ]);
    }

    public function mount(string $slug_nivel = 'preescolar', ?string $slug_grado = null): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->slug_grado = $slug_grado;
        $this->campos = FichaController::CAMPOS;

        $nivel = Nivel::query()->where('slug', 'preescolar')->firstOrFail();
        $this->nivel_id = $nivel->id;

        $this->ciclo_escolar_id = cicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->value('id');

        $this->fecha_lugar = 'CD. ALTAMIRANO, GRO., A ' . mb_strtoupper(Carbon::now()->translatedFormat('d \\d\\e F \\d\\e\\l Y'));
    }

    public function updated($property): void
    {
        if (in_array($property, ['generacion_id', 'grado_id', 'grupo_id', 'periodo', 'busqueda'], true)) {
            $this->resetPage();
        }
    }

    public function cambiarPeriodo(int $periodo): void
    {
        if (!in_array($periodo, [1, 2, 3], true)) {
            return;
        }

        $this->periodo = $periodo;
        $this->resetPage();
    }

    public function abrirModal(int $inscripcionId, string $campo): void
    {
        if (!array_key_exists($campo, $this->campos)) {
            return;
        }

        $this->inscripcion_id = $inscripcionId;
        $this->campo = $campo;

        $this->descripcion = (string) FichaDescriptiva::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo', $this->periodo)
            ->where('campo', $campo)
            ->value('descripcion');

        $this->dispatch('abrir-modal-ficha', contenido: $this->descripcion ?? '');

        $this->resetValidation();
        $this->modalAbierto = true;
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->inscripcion_id = null;
        $this->campo = '';
        $this->descripcion = '';
        $this->resetValidation();

        $this->dispatch('cerrar-modal-ficha');
    }

    public function guardar(): void
    {
        $this->validate([
            'inscripcion_id' => ['required', 'integer', 'exists:inscripciones,id'],
            'campo' => ['required', 'string', 'in:' . implode(',', array_keys($this->campos))],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ], [
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
        ]);

        $alumno = Inscripcion::query()->findOrFail($this->inscripcion_id);

        $descripcionLimpia = trim(strip_tags(
            $this->descripcion,
            '<p><br><strong><b><em><i><u><s><strike><span><ul><ol><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><h5><h6><blockquote>'
        ));

        FichaDescriptiva::query()->updateOrCreate(
            [
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => $this->ciclo_escolar_id,
                'periodo' => $this->periodo,
                'campo' => $this->campo,
            ],
            [
                'nivel_id' => $alumno->nivel_id,
                'grado_id' => $alumno->grado_id,
                'grupo_id' => $alumno->grupo_id,
                'generacion_id' => $alumno->generacion_id,
                'descripcion' => $descripcionLimpia,
                'capturado_por' => Auth::id(),
                'fecha_captura' => now(),
            ]
        );

        $this->cerrarModal();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Ficha guardada',
            'position' => 'top',
        ]);
    }

    public function getGeneracionesProperty()
    {
        return Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);
    }

    public function getGradosProperty()
    {
        return Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('id')
            ->get(['id', 'nombre']);
    }

    public function getGruposProperty()
    {
        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $this->nivel_id)
            ->when($this->grado_id, fn($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->generacion_id, fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->orderBy('id')
            ->get();
    }

    public function getCiclosEscolaresProperty()
    {
        return cicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get();
    }

    public function getAlumnoModalProperty(): ?Inscripcion
    {
        if (!$this->inscripcion_id) {
            return null;
        }

        return Inscripcion::query()->find($this->inscripcion_id);
    }

    public function alumnoNombre(?Inscripcion $alumno = null): string
    {
        if (!$alumno) {
            return '';
        }

        return trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno);
    }

    public function periodoNombre(?int $periodo = null): string
    {
        return match ($periodo ?? $this->periodo) {
            1 => 'Primera Evaluación Diagnóstica',
            2 => 'Segunda Evaluación',
            3 => 'Tercera Evaluación',
            default => 'Evaluación',
        };
    }

    public function periodoCorto(int $periodo): string
    {
        return match ($periodo) {
            1 => 'Primer Periodo',
            2 => 'Segundo Periodo',
            3 => 'Tercer Periodo',
            default => 'Periodo ' . $periodo,
        };
    }

    public function campoCompleto(int $inscripcionId, string $campo): bool
    {
        return filled($this->fichasResumen[$inscripcionId][$campo] ?? null);
    }

    public function getFichasResumenProperty(): array
    {
        $ids = $this->alumnosPagina->pluck('id');

        return FichaDescriptiva::query()
            ->whereIn('inscripcion_id', $ids)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo', $this->periodo)
            ->get(['inscripcion_id', 'campo', 'descripcion'])
            ->groupBy('inscripcion_id')
            ->map(fn($items) => $items->pluck('descripcion', 'campo')->toArray())
            ->toArray();
    }

    public function getAlumnosPaginaProperty()
    {
        return $this->queryAlumnos()
            ->paginate(10);
    }

    private function queryAlumnos()
    {
        return Inscripcion::query()
            ->with(['nivel:id,nombre,slug', 'grado:id,nombre', 'grupo.asignacionGrupo:id,nombre', 'generacion:id,nivel_id,anio_ingreso,anio_egreso'])
            ->where('nivel_id', $this->nivel_id)
            ->when($this->generacion_id, fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id, fn($q) => $q->where('grado_id', $this->grado_id))
            ->when($this->grupo_id, fn($q) => $q->where('grupo_id', $this->grupo_id))
            ->when($this->busqueda !== '', function ($q) {
                $texto = '%' . Str::of($this->busqueda)->squish() . '%';
                $q->where(function ($sub) use ($texto) {
                    $sub->where('nombre', 'like', $texto)
                        ->orWhere('apellido_paterno', 'like', $texto)
                        ->orWhere('apellido_materno', 'like', $texto)
                        ->orWhere('curp', 'like', $texto)
                        ->orWhere('matricula', 'like', $texto);
                });
            })
            ->where('activo', true)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    public function getUrlExcelProperty(): string
    {
        return route('misrutas.fichas.excel', $this->parametrosDescarga());
    }

    public function getUrlPdfGrupoProperty(): string
    {
        return route('misrutas.fichas.grupo.pdf', $this->parametrosDescarga());
    }

    public function urlPdfAlumno(int $inscripcionId): string
    {
        return route('misrutas.fichas.alumno.pdf', array_merge($this->parametrosDescarga(), [
            'inscripcion' => $inscripcionId,
        ]));
    }

    private function parametrosDescarga(): array
    {
        return array_filter([
            'periodo' => $this->periodo,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'fecha_lugar' => $this->fecha_lugar,
        ], fn($value) => !blank($value));
    }

    public function render()
    {
        return view('livewire.accion.ficha', [
            'alumnos' => $this->alumnosPagina,
            'fichasResumen' => $this->fichasResumen,
        ]);
    }
}
