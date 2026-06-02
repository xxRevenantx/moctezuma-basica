<?php

namespace App\Livewire\Documentacion;

use App\Models\Constancia as ConstanciaModelo;
use App\Models\ConstanciaPlantilla;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

class Constancia extends Component
{
    public string $query = '';

    public array $alumnos = [];

    public int $selectedIndex = 0;

    public ?array $selectedAlumno = null;

    public string $modo_descarga = 'alumno';

    public ?int $nivel_id = null;

    public ?int $grado_id = null;

    public ?int $grupo_id = null;

    public array $niveles = [];

    public array $grados = [];

    public array $grupos = [];

    public ?int $plantilla_id = null;

    public string $tipo_constancia = '';

    public string $plantilla_titulo = '';

    public array $plantilla_variables = [];

    public ?string $fecha_expedicion = null;

    public ?string $dirigido_a = null;

    public bool $primer_periodo = false;

    public bool $segundo_periodo = false;

    public bool $tercer_periodo = false;

    public string $contenido_html = '';

    public bool $mostrar_modal_plantilla = false;

    public bool $editando_plantilla = false;

    public ?int $plantilla_editando_id = null;

    public string $nueva_clave = '';

    public string $nuevo_titulo = '';

    public string $nuevo_contenido_html = '';

    public string $nuevas_variables = '';

    public bool $nuevo_activo = true;

    public function mount(): void
    {
        $this->fecha_expedicion = now()->format('Y-m-d');

        $this->cargarCatalogos();

        $this->cargarPrimeraPlantilla();
    }

    public function cargarCatalogos(): void
    {
        $this->niveles = Nivel::query()
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get()
            ->toArray();

        $this->grados = Grado::query()
            ->select('id', 'nivel_id', 'nombre', 'orden')
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->toArray();

        $this->grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->select('id', 'nivel_id', 'grado_id', 'asignacion_grupo_id')
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderBy('id')
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nivel_id' => $grupo->nivel_id,
                    'grado_id' => $grupo->grado_id,
                    'nombre' => $grupo->asignacionGrupo?->nombre ?? 'Sin grupo',
                ];
            })
            ->toArray();
    }

    public function cargarPrimeraPlantilla(): void
    {
        $plantilla = ConstanciaPlantilla::query()
            ->where('activo', true)
            ->orderBy('titulo')
            ->first();

        if (!$plantilla) {
            $this->limpiarPlantillaSeleccionada();

            return;
        }

        $this->tipo_constancia = $plantilla->clave;

        $this->cargarPlantilla();
    }

    public function updatedTipoConstancia(): void
    {
        $this->cargarPlantilla();
    }

    public function cargarPlantilla(): void
    {
        $plantilla = ConstanciaPlantilla::query()
            ->where('clave', $this->tipo_constancia)
            ->where('activo', true)
            ->first();

        if (!$plantilla) {
            $this->limpiarPlantillaSeleccionada();

            return;
        }

        $this->plantilla_id = $plantilla->id;
        $this->plantilla_titulo = $plantilla->titulo;
        $this->plantilla_variables = $plantilla->variables ?? [];
        $this->contenido_html = $plantilla->contenido_html;

        $this->dispatch('actualizar-editor-constancia', contenido: $this->contenido_html);
    }

    public function limpiarPlantillaSeleccionada(): void
    {
        $this->plantilla_id = null;
        $this->tipo_constancia = '';
        $this->plantilla_titulo = '';
        $this->plantilla_variables = [];
        $this->contenido_html = '';

        $this->dispatch('actualizar-editor-constancia', contenido: '');
    }

    public function abrirFormularioPlantilla(): void
    {
        $this->mostrar_modal_plantilla = true;
        $this->editando_plantilla = false;
        $this->plantilla_editando_id = null;

        $this->nueva_clave = '';
        $this->nuevo_titulo = '';
        $this->nuevo_contenido_html = '';
        $this->nuevas_variables = "@nombre\n@curp\n@matricula\n@grado\n@nivel\n@grupo\n@generacion\n@ciclo\n@cct\n@sexo\n@descripcion\n@fecha\n@dirigido";
        $this->nuevo_activo = true;

        $this->resetValidation([
            'nueva_clave',
            'nuevo_titulo',
            'nuevo_contenido_html',
            'nuevas_variables',
            'nuevo_activo',
        ]);

        $this->dispatch('abrir-modal-plantilla', contenido: '');
    }

    public function editarPlantilla(int $plantillaId): void
    {
        $plantilla = ConstanciaPlantilla::query()->findOrFail($plantillaId);

        $this->mostrar_modal_plantilla = true;
        $this->editando_plantilla = true;
        $this->plantilla_editando_id = $plantilla->id;

        $this->nueva_clave = $plantilla->clave;
        $this->nuevo_titulo = $plantilla->titulo;
        $this->nuevo_contenido_html = $plantilla->contenido_html;
        $this->nuevas_variables = implode("\n", $plantilla->variables ?? []);
        $this->nuevo_activo = (bool) $plantilla->activo;

        $this->resetValidation([
            'nueva_clave',
            'nuevo_titulo',
            'nuevo_contenido_html',
            'nuevas_variables',
            'nuevo_activo',
        ]);

        $this->dispatch('abrir-modal-plantilla', contenido: $this->nuevo_contenido_html);
    }

    public function cerrarFormularioPlantilla(): void
    {
        $this->mostrar_modal_plantilla = false;
        $this->editando_plantilla = false;
        $this->plantilla_editando_id = null;

        $this->resetValidation([
            'nueva_clave',
            'nuevo_titulo',
            'nuevo_contenido_html',
            'nuevas_variables',
            'nuevo_activo',
        ]);

        $this->dispatch('cerrar-modal-plantilla');
    }

    protected function messages(): array
    {
        return [
            'nueva_clave.required' => 'La clave es obligatoria.',
            'nueva_clave.string' => 'La clave debe ser una cadena de texto.',
            'nueva_clave.max' => 'La clave no puede tener más de 100 caracteres.',
            'nueva_clave.regex' => 'La clave solo puede llevar minúsculas, números y guion bajo.',
            'nueva_clave.unique' => 'Ya existe una plantilla con esa clave. Por favor elige otra.',
            'nuevo_titulo.required' => 'El título es obligatorio.',
            'nuevo_titulo.string' => 'El título debe ser una cadena de texto.',
            'nuevo_titulo.max' => 'El título no puede tener más de 255 caracteres.',
            'nuevo_contenido_html.required' => 'El contenido HTML es obligatorio.',
            'nuevo_contenido_html.string' => 'El contenido HTML debe ser una cadena de texto.',

            'nuevas_variables.string' => 'Las variables deben ser una cadena de texto.',
        ];
    }

    public function guardarPlantillaSistema(): void
    {
        $plantillaId = $this->plantilla_editando_id;

        $this->validate([
            'nueva_clave' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_]+$/',
                'unique:constancia_plantillas,clave,' . $plantillaId,
            ],
            'nuevo_titulo' => ['required', 'string', 'max:255'],
            'nuevo_contenido_html' => ['required', 'string'],
            'nuevas_variables' => ['nullable', 'string'],
            'nuevo_activo' => ['boolean'],
        ], [
            'nueva_clave.regex' => 'La clave solo puede llevar minúsculas, números y guion bajo.',
            'nuevas_variables.string' => 'Las variables deben ser una cadena de texto.',
        ]);

        $variables = collect(preg_split('/\r\n|\r|\n/', $this->nuevas_variables))
            ->map(fn($variable) => trim($variable))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $plantilla = ConstanciaPlantilla::query()->updateOrCreate(
            ['id' => $this->plantilla_editando_id],
            [
                'clave' => $this->nueva_clave,
                'titulo' => $this->nuevo_titulo,
                'contenido_html' => $this->nuevo_contenido_html,
                'variables' => $variables,
                'activo' => $this->nuevo_activo,
            ]
        );

        $this->tipo_constancia = $plantilla->clave;

        $this->cargarPlantilla();

        $this->mostrar_modal_plantilla = false;
        $this->editando_plantilla = false;
        $this->plantilla_editando_id = null;

        $this->dispatch('cerrar-modal-plantilla');
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Plantilla guardada correctamente.');
    }

    public function cambiarEstadoPlantilla(int $plantillaId): void
    {
        $plantilla = ConstanciaPlantilla::query()->findOrFail($plantillaId);

        $plantilla->update([
            'activo' => !$plantilla->activo,
        ]);

        if ($this->plantilla_id === $plantilla->id && !$plantilla->activo) {
            $this->cargarPrimeraPlantilla();
        }

        if (!$this->plantilla_id) {
            $this->cargarPrimeraPlantilla();
        }

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Estado actualizado correctamente.');
    }

    public function eliminarPlantilla(int $plantillaId): void
    {
        $plantilla = ConstanciaPlantilla::query()
            ->withCount('constancias')
            ->findOrFail($plantillaId);

        if ($plantilla->constancias_count > 0) {
            $plantilla->update([
                'activo' => false,
            ]);

            if ($this->plantilla_id === $plantilla->id) {
                $this->cargarPrimeraPlantilla();
            }

            $this->dispatch('notificar', tipo: 'warning', mensaje: 'La plantilla tiene constancias generadas, por seguridad solo fue desactivada.');

            return;
        }

        $plantilla->delete();

        if ($this->plantilla_id === $plantillaId) {
            $this->cargarPrimeraPlantilla();
        }

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Plantilla eliminada correctamente.');
    }

    public function guardarCambiosContenidoActual(): void
    {
        $this->validate([
            'plantilla_id' => ['required', 'exists:constancia_plantillas,id'],
            'contenido_html' => ['required', 'string'],
        ]);

        ConstanciaPlantilla::query()
            ->where('id', $this->plantilla_id)
            ->update([
                'contenido_html' => $this->contenido_html,
            ]);

        $this->dispatch('notificar', tipo: 'success', mensaje: 'Contenido guardado correctamente.');
    }

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
                'nivel:id,nombre,cct',
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

    public function selectAlumno(int $index): void
    {
        if (!isset($this->alumnos[$index])) {
            return;
        }

        $this->selectedAlumno = $this->alumnos[$index];

        $this->query = $this->selectedAlumno['nombre_completo'] . ' - ' . $this->selectedAlumno['nivel'];

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
    }

    public function updatedModoDescarga(): void
    {
        if ($this->modo_descarga !== 'alumno') {
            $this->limpiarAlumno();
        }

        if ($this->modo_descarga === 'alumno') {
            $this->nivel_id = null;
            $this->grado_id = null;
            $this->grupo_id = null;
        }
    }

    public function updatedNivelId(): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
    }

    public function descargarConstancia(): void
    {
        $this->validate([
            'plantilla_id' => ['required', 'exists:constancia_plantillas,id'],
            'fecha_expedicion' => ['nullable', 'date'],
            'contenido_html' => ['required', 'string'],
            'modo_descarga' => ['required', 'in:alumno,nivel,grado,grupo'],
        ]);

        if ($this->modo_descarga === 'alumno') {
            $this->validate([
                'selectedAlumno' => ['required'],
            ]);

            // Solo en descarga individual se guarda historial en la base de datos.
            $constancia = $this->crearConstanciaIndividual((int) $this->selectedAlumno['id']);

            $url = route('misrutas.constancias.pdf', $constancia);

            $this->dispatch('abrir-constancia-nueva-ventana', url: $url);

            return;
        }

        if ($this->modo_descarga === 'nivel') {
            $this->validate([
                'nivel_id' => ['required', 'exists:niveles,id'],
            ]);
        }

        if ($this->modo_descarga === 'grado') {
            $this->validate([
                'nivel_id' => ['required', 'exists:niveles,id'],
                'grado_id' => ['required', 'exists:grados,id'],
            ]);
        }

        if ($this->modo_descarga === 'grupo') {
            $this->validate([
                'nivel_id' => ['required', 'exists:niveles,id'],
                'grupo_id' => ['required', 'exists:grupos,id'],
            ]);
        }

        $alumnos = $this->obtenerAlumnosParaDescarga();

        if ($alumnos->isEmpty()) {
            $this->dispatch('notificar', tipo: 'error', mensaje: 'No se encontraron alumnos para generar constancias.');

            return;
        }

        // En descargas masivas no se guarda nada en la tabla constancias.
        session()->put('constancias_zip_payload', [
            'alumno_ids' => $alumnos->pluck('id')->values()->toArray(),
            'plantilla_id' => $this->plantilla_id,
            'plantilla_titulo' => $this->plantilla_titulo,
            'contenido_html' => $this->contenido_html,
            'fecha_expedicion' => $this->fecha_expedicion ?: now()->format('Y-m-d'),
            'dirigido_a' => $this->dirigido_a,
            'modo_descarga' => $this->modo_descarga,
            'periodos_calificaciones' => $this->periodosSeleccionados(),
        ]);

        $url = route('misrutas.constancias.zip');

        $this->dispatch('abrir-constancia-nueva-ventana', url: $url);
    }

    private function obtenerAlumnosParaDescarga()
    {
        return Inscripcion::query()
            ->with([
                'nivel:id,nombre,cct',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ])
            ->where('activo', true)
            ->when($this->modo_descarga === 'nivel', function ($consulta) {
                $consulta->where('nivel_id', $this->nivel_id);
            })
            ->when($this->modo_descarga === 'grado', function ($consulta) {
                $consulta->where('nivel_id', $this->nivel_id)
                    ->where('grado_id', $this->grado_id);
            })
            ->when($this->modo_descarga === 'grupo', function ($consulta) {
                $consulta->where('nivel_id', $this->nivel_id)
                    ->when($this->grado_id, function ($query) {
                        $query->where('grado_id', $this->grado_id);
                    })
                    ->where('grupo_id', $this->grupo_id);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    private function crearConstanciaIndividual(int $inscripcionId): ConstanciaModelo
    {
        $alumno = Inscripcion::query()
            ->with([
                'nivel:id,nombre,cct',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ])
            ->findOrFail($inscripcionId);

        $alumnoArray = $this->formatearAlumno($alumno);

        $contenidoGenerado = $this->reemplazarVariablesConAlumno($this->contenido_html, $alumnoArray);

        return ConstanciaModelo::create([
            'inscripcion_id' => $alumno->id,
            'constancia_plantilla_id' => $this->plantilla_id,
            'folio' => $this->generarFolio(),
            'fecha_expedicion' => $this->fecha_expedicion ?: now()->format('Y-m-d'),
            'dirigido_a' => $this->dirigido_a,
            'modo_descarga' => 'alumno',
            'periodos_calificaciones' => $this->periodosSeleccionados(),
            'contenido_generado_html' => $contenidoGenerado,
        ]);
    }

    private function formatearAlumno(Inscripcion $alumno): array
    {
        $generacion = '';

        if ($alumno->generacion) {
            $generacion = trim(($alumno->generacion->anio_ingreso ?? '') . '-' . ($alumno->generacion->anio_egreso ?? ''));
        }

        return [
            'id' => $alumno->id,
            'nombre_completo' => trim(($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? '')),
            'curp' => $alumno->curp,
            'matricula' => $alumno->matricula,
            'genero' => $alumno->genero,
            'nivel' => $alumno->nivel?->nombre,
            'cct' => $alumno->nivel?->cct,
            'grado' => $alumno->grado?->nombre,
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre,
            'generacion' => $generacion,
            'ciclo' => $alumno->ciclo?->ciclo,
        ];
    }

    private function reemplazarVariablesConAlumno(string $contenido, array $alumno): string
    {
        $genero = mb_strtolower((string) ($alumno['genero'] ?? ''));

        $esMujer = in_array($genero, ['f', 'femenino', 'mujer', 'femenina']);

        $sexo = $esMujer ? 'La alumna' : 'El alumno';

        $descripcion = $esMujer
            ? 'se encuentra inscrita'
            : 'se encuentra inscrito';

        $variables = [
            '@alumno' => $alumno['nombre_completo'] ?? '',
            '@nombre' => $alumno['nombre_completo'] ?? '',
            '@curp' => $alumno['curp'] ?? '',
            '@matricula' => $alumno['matricula'] ?? '',
            '@grado' => $alumno['grado'] ?? '',
            '@nivel' => $alumno['nivel'] ?? '',
            '@grupo' => $alumno['grupo'] ?? '',
            '@generacion' => $alumno['generacion'] ?? '',
            '@ciclo' => $alumno['ciclo'] ?? '',
            '@cct' => $alumno['cct'] ?? '',
            '@sexo' => $sexo,
            '@descripcion' => $descripcion,
            '@fecha' => Carbon::parse($this->fecha_expedicion ?: now())->translatedFormat('d \d\e F \d\e Y'),
            '@dirigido' => $this->dirigido_a ?: 'A QUIEN CORRESPONDA',
        ];

        return str_replace(array_keys($variables), array_values($variables), $contenido);
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
        $siguiente = (ConstanciaModelo::query()->max('id') ?? 0) + 1;

        return 'CONST-' . now()->format('Y') . '-' . Str::padLeft((string) $siguiente, 5, '0');
    }

    public function render()
    {
        return view('livewire.documentacion.constancia', [
            'plantillas' => ConstanciaPlantilla::query()
                ->orderBy('titulo')
                ->get(),

            'plantillasActivas' => ConstanciaPlantilla::query()
                ->where('activo', true)
                ->orderBy('titulo')
                ->get(),
        ]);
    }
}
