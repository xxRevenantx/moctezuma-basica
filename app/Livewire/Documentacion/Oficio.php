<?php

namespace App\Livewire\Documentacion;

use App\Models\Director;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Oficio as OficioModel;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Oficio extends Component
{
    use WithPagination;

    public string $query = '';

    public array $alumnos = [];

    public int $selectedIndex = 0;

    public ?array $selectedAlumno = null;

    public ?int $nivel_id = null;

    public ?int $director_id = null;

    public string $tipo_oficio = '';

    public string $folio = '';

    public string $seccion = 'ADMINISTRATIVA';

    public string $fecha_lugar = '';

    public string $asunto = '';

    public string $dirigido_1_nombre = '';

    public string $dirigido_1_cargo = '';

    public string $dirigido_1_lugar = '';

    public string $dirigido_2_nombre = '';

    public string $dirigido_2_cargo = '';

    public string $dirigido_2_lugar = '';

    public string $descripcion_html = '';

    public bool $primer_periodo = false;

    public bool $segundo_periodo = false;

    public bool $tercer_periodo = false;

    public string $buscar_oficio = '';

    public array $niveles = [];

    public array $directores = [];

    public function mount(): void
    {
        $this->fecha_lugar = 'Cd. Altamirano, Gro., a ' . now()->translatedFormat('j \d\e F \d\e Y');
        $this->folio = $this->generarFolio();

        $this->cargarCatalogos();
    }

    /**
     * Carga niveles y directores activos.
     */
    public function cargarCatalogos(): void
    {
        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'director_id')
            ->orderBy('nombre')
            ->get()
            ->toArray();

        $this->directores = Director::query()
            ->where('status', true)
            ->select('id', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno', 'cargo')
            ->orderBy('nombre')
            ->get()
            ->map(function ($director) {
                return [
                    'id' => $director->id,
                    'nombre_completo' => trim(
                        ($director->titulo ?? '') . ' ' .
                            ($director->nombre ?? '') . ' ' .
                            ($director->apellido_paterno ?? '') . ' ' .
                            ($director->apellido_materno ?? '')
                    ),
                    'cargo' => $director->cargo,
                ];
            })
            ->toArray();
    }

    /**
     * Busca alumnos con la misma lógica usada en constancias.
     */
    public function updatedQuery(): void
    {
        $this->buscarAlumnos();
    }

    public function buscarAlumnos(): void
    {
        $texto = trim($this->query);

        if (strlen($texto) < 2) {
            $this->alumnos = [];
            return;
        }

        $this->alumnos = Inscripcion::query()
            ->with([
                'nivel:id,nombre,cct,director_id',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ])
            ->where('activo', true)
            ->where(function ($consulta) use ($texto) {
                $consulta->where('nombre', 'like', "%{$texto}%")
                    ->orWhere('apellido_paterno', 'like', "%{$texto}%")
                    ->orWhere('apellido_materno', 'like', "%{$texto}%")
                    ->orWhere('curp', 'like', "%{$texto}%")
                    ->orWhere('matricula', 'like', "%{$texto}%")
                    ->orWhere('folio', 'like', "%{$texto}%");
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(10)
            ->get()
            ->map(fn($alumno) => $this->formatearAlumno($alumno))
            ->toArray();

        $this->selectedIndex = 0;
    }

    public function updatedTipoOficio(): void
    {
        if ($this->tipo_oficio === 'Alta') {
            $this->asunto = 'Alta por traslado.';
        }

        if ($this->tipo_oficio === 'Baja') {
            $this->asunto = 'Baja por traslado.';
        }
    }

    public function selectAlumno(int $index): void
    {
        if (!isset($this->alumnos[$index])) {
            return;
        }

        $this->selectedAlumno = $this->alumnos[$index];

        $this->query = $this->selectedAlumno['nombre_completo'] . ' - ' . $this->selectedAlumno['nivel'];
        $this->nivel_id = $this->selectedAlumno['nivel_id'];

        $nivelNombre = mb_strtolower($this->selectedAlumno['nivel'] ?? '');

        if (str_contains($nivelNombre, 'preescolar')) {
            $this->seccion = '';
        } else {
            $this->seccion = 'ADMINISTRATIVA';
        }

        $nivel = collect($this->niveles)->firstWhere('id', $this->nivel_id);

        if ($nivel && !empty($nivel['director_id'])) {
            $this->director_id = (int) $nivel['director_id'];
        }

        $this->alumnos = [];
        $this->selectedIndex = 0;
    }

    public function selectIndexUp(): void
    {
        if ($this->selectedIndex > 0) {
            $this->selectedIndex--;
        }
    }

    public function selectIndexDown(): void
    {
        if ($this->selectedIndex < count($this->alumnos) - 1) {
            $this->selectedIndex++;
        }
    }

    public function limpiarAlumno(): void
    {
        $this->query = '';
        $this->alumnos = [];
        $this->selectedIndex = 0;
        $this->selectedAlumno = null;
        $this->nivel_id = null;
    }

    public function updatedNivelId(): void
    {
        $nivel = collect($this->niveles)->firstWhere('id', (int) $this->nivel_id);

        if ($nivel && !empty($nivel['director_id'])) {
            $this->director_id = (int) $nivel['director_id'];
        }
    }

    /**
     * Guarda el oficio y abre el PDF en otra ventana.
     */
    public function guardarOficio(): void
    {
        $this->validate([
            'selectedAlumno' => ['required'],
            'nivel_id' => ['required', 'exists:niveles,id'],
            'director_id' => ['nullable', 'exists:directores,id'],
            'tipo_oficio' => ['required', 'in:Alta,Baja'],
            'folio' => ['required', 'string', 'max:255', 'unique:oficios,folio'],
            'seccion' => ['nullable', 'string', 'max:255'],
            'fecha_lugar' => ['nullable', 'string', 'max:255'],
            'asunto' => ['nullable', 'string', 'max:255'],
            'dirigido_1_nombre' => ['nullable', 'string', 'max:255'],
            'dirigido_1_cargo' => ['nullable', 'string', 'max:255'],
            'dirigido_1_lugar' => ['nullable', 'string', 'max:255'],
            'dirigido_2_nombre' => ['nullable', 'string', 'max:255'],
            'dirigido_2_cargo' => ['nullable', 'string', 'max:255'],
            'dirigido_2_lugar' => ['nullable', 'string', 'max:255'],
            'descripcion_html' => ['nullable', 'string'],
        ]);

        $oficio = OficioModel::create([
            'inscripcion_id' => $this->selectedAlumno['id'],
            'nivel_id' => $this->nivel_id,
            'director_id' => $this->director_id,
            'folio' => $this->folio,
            'tipo_oficio' => $this->tipo_oficio,
            'seccion' => $this->seccion,
            'fecha_lugar' => $this->fecha_lugar,
            'asunto' => $this->asunto,
            'dirigido_1_nombre' => $this->dirigido_1_nombre,
            'dirigido_1_cargo' => $this->dirigido_1_cargo,
            'dirigido_1_lugar' => $this->dirigido_1_lugar,
            'dirigido_2_nombre' => $this->dirigido_2_nombre,
            'dirigido_2_cargo' => $this->dirigido_2_cargo,
            'dirigido_2_lugar' => $this->dirigido_2_lugar,
            'periodos_calificaciones' => $this->periodosSeleccionados(),
            'descripcion_html' => $this->descripcion_html,
        ]);

        $this->resetFormulario();

        $this->dispatch('abrir-oficio-nueva-ventana', url: route('misrutas.oficios.pdf', $oficio));
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Oficio guardado correctamente.');
    }

    public function abrirPdfOficio(int $oficioId): void
    {
        $oficio = OficioModel::query()->findOrFail($oficioId);

        $this->dispatch('abrir-oficio-nueva-ventana', url: route('misrutas.oficios.pdf', $oficio));
    }

    public function eliminarOficio(int $oficioId): void
    {
        OficioModel::query()->findOrFail($oficioId)->delete();

        $this->resetPage('oficiosPage');
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Oficio eliminado correctamente.');
    }

    public function resetFormulario(): void
    {
        $this->query = '';
        $this->alumnos = [];
        $this->selectedIndex = 0;
        $this->selectedAlumno = null;

        $this->nivel_id = null;
        $this->director_id = null;
        $this->tipo_oficio = '';
        $this->folio = $this->generarFolio();
        $this->seccion = 'ADMINISTRATIVA';
        $this->fecha_lugar = 'Cd. Altamirano, Gro., a ' . now()->translatedFormat('j \d\e F \d\e Y');
        $this->asunto = '';

        $this->dirigido_1_nombre = '';
        $this->dirigido_1_cargo = '';
        $this->dirigido_1_lugar = '';

        $this->dirigido_2_nombre = '';
        $this->dirigido_2_cargo = '';
        $this->dirigido_2_lugar = '';

        $this->descripcion_html = '';

        $this->primer_periodo = false;
        $this->segundo_periodo = false;
        $this->tercer_periodo = false;

        $this->dispatch('limpiar-editor-oficio');
    }

    public function updatedBuscarOficio(): void
    {
        $this->resetPage('oficiosPage');
    }

    private function formatearAlumno(Inscripcion $alumno): array
    {
        $generacion = '';

        if ($alumno->generacion) {
            $generacion = trim(
                ($alumno->generacion->anio_ingreso ?? '') .
                    '-' .
                    ($alumno->generacion->anio_egreso ?? '')
            );
        }

        return [
            'id' => $alumno->id,
            'nombre_completo' => trim(
                ($alumno->nombre ?? '') . ' ' .
                    ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '')
            ),
            'curp' => $alumno->curp ?? '',
            'matricula' => $alumno->matricula ?? '',
            'nivel_id' => $alumno->nivel_id,
            'nivel' => $alumno->nivel?->nombre ?? '',
            'grado' => $alumno->grado?->nombre ?? '',
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? '',
            'generacion' => $generacion,
            'ciclo' => $alumno->ciclo?->ciclo ?? '',
        ];
    }

    private function periodosSeleccionados(): array
    {
        return [
            'primer_periodo' => $this->primer_periodo,
            'segundo_periodo' => $this->segundo_periodo,
            'tercer_periodo' => $this->tercer_periodo,
        ];
    }

    private function generarFolio(): string
    {
        $siguiente = (OficioModel::query()->max('id') ?? 0) + 1;

        return 'OF-' . now()->format('Y') . '-' . Str::padLeft((string) $siguiente, 5, '0');
    }

    public function render()
    {
        $buscar = trim($this->buscar_oficio);

        return view('livewire.documentacion.oficio', [
            'oficiosPorNivel' => OficioModel::query()
                ->with([
                    'alumno:id,nombre,apellido_paterno,apellido_materno,matricula,nivel_id,grado_id,grupo_id',
                    'alumno.nivel:id,nombre',
                    'alumno.grado:id,nombre',
                    'alumno.grupo:id,asignacion_grupo_id',
                    'alumno.grupo.asignacionGrupo:id,nombre',
                    'nivel:id,nombre',
                    'director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo',
                ])
                ->when($buscar !== '', function ($consulta) use ($buscar) {
                    $consulta->where(function ($query) use ($buscar) {
                        $query->where('folio', 'like', "%{$buscar}%")
                            ->orWhere('tipo_oficio', 'like', "%{$buscar}%")
                            ->orWhere('asunto', 'like', "%{$buscar}%")
                            ->orWhereHas('alumno', function ($alumno) use ($buscar) {
                                $alumno->where('nombre', 'like', "%{$buscar}%")
                                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                                    ->orWhere('matricula', 'like', "%{$buscar}%");
                            });
                    });
                })
                ->latest()
                ->get()
                ->groupBy(fn($oficio) => $oficio->nivel?->nombre ?? 'Sin nivel'),
        ]);
    }
}
