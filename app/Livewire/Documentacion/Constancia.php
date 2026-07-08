<?php

namespace App\Livewire\Documentacion;

use App\Models\Constancia as ConstanciaModelo;
use App\Models\ConstanciaPlantilla;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\GroqDocumentoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class Constancia extends Component
{
    use WithPagination;

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

    public bool $incluir_calificaciones = false;

    public string $contenido_html = '';

    public bool $mostrar_modal_plantilla = false;

    public bool $editando_plantilla = false;

    public ?int $plantilla_editando_id = null;

    public string $nueva_clave = '';

    public string $nuevo_titulo = '';

    public string $nuevo_contenido_html = '';

    public string $nuevas_variables = '';

    public bool $nuevo_activo = true;

    public string $instruccion_ia = '';

    public string $buscar_constancia = '';

    public bool $mostrar_modal_editar_constancia = false;

    public ?int $constancia_editando_id = null;

    public ?string $editar_fecha_expedicion = null;

    public ?string $editar_dirigido_a = null;

    public string $editar_contenido_generado_html = '';

    public bool $editar_primer_periodo = false;

    public bool $editar_segundo_periodo = false;

    public bool $editar_tercer_periodo = false;

    protected $queryString = [
        'query' => ['except' => ''],
        'buscar_constancia' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->fecha_expedicion = now()->format('Y-m-d');

        $this->cargarCatalogos();
        $this->cargarPrimeraPlantilla();
    }

    private function variablesBase(): array
    {
        return [
            '@sexo',
            '@nombre',
            '@alumno',
            '@curp',
            '@matricula',
            '@grado',
            '@nivel',
            '@nivel_minuscula',
            '@grupo',
            '@generacion',
            '@ciclo',
            '@cct',
            '@descripcion',
            '@fecha',
            '@dirigido',
        ];
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
            ->where('clave', '!=', 'baja-traslado')
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

        if (!$this->esConstanciaEstudiosSeleccionada()) {
            $this->incluir_calificaciones = false;
            $this->primer_periodo = false;
            $this->segundo_periodo = false;
            $this->tercer_periodo = false;
        }

        // Evita conservar un egresado seleccionado al cambiar a una constancia
        // que únicamente admite alumnos activos.
        if ($this->selectedAlumno !== null) {
            $this->limpiarAlumno();
        }
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

        // Se agregan las variables base para que siempre estén disponibles.
        $this->plantilla_variables = collect($plantilla->variables ?? [])
            ->merge($this->variablesBase())
            ->unique()
            ->values()
            ->toArray();

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
        $this->nuevas_variables = implode("\n", $this->variablesBase());
        $this->nuevo_activo = true;
        $this->instruccion_ia = '';

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
        $this->instruccion_ia = '';

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
        $this->instruccion_ia = '';

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
            'nueva_clave.regex' => 'La clave solo puede llevar minúsculas, números, guion medio y guion bajo.',
            'nueva_clave.unique' => 'Ya existe una plantilla con esa clave. Por favor elige otra.',
            'nuevo_titulo.required' => 'El título es obligatorio.',
            'nuevo_titulo.string' => 'El título debe ser una cadena de texto.',
            'nuevo_titulo.max' => 'El título no puede tener más de 255 caracteres.',
            'nuevo_contenido_html.required' => 'El contenido HTML es obligatorio.',
            'nuevo_contenido_html.string' => 'El contenido HTML debe ser una cadena de texto.',
            'nuevas_variables.string' => 'Las variables deben ser una cadena de texto.',
            'editar_contenido_generado_html.required' => 'El contenido de la constancia es obligatorio.',
            'instruccion_ia.max' => 'La instrucción para GroqCloud no puede superar los 2500 caracteres.',
        ];
    }

    public function redactarPlantillaConIA(string $accion): void
    {
        $accion = mb_strtolower(trim($accion));

        if (!in_array($accion, ['generar', 'mejorar', 'corregir'], true)) {
            $this->dispatch('notificar', tipo: 'error', mensaje: 'La acción de GroqCloud no es válida.');

            return;
        }

        $this->validate([
            'nuevo_titulo' => ['nullable', 'string', 'max:255'],
            'nuevo_contenido_html' => ['nullable', 'string', 'max:20000'],
            'nuevas_variables' => ['nullable', 'string', 'max:5000'],
            'instruccion_ia' => ['nullable', 'string', 'max:2500'],
        ]);

        if ($accion === 'generar' && blank(strip_tags($this->instruccion_ia))) {
            $this->addError(
                'instruccion_ia',
                'Describe qué constancia deseas generar. Por ejemplo: constancia de estudios para trámite de beca.'
            );

            return;
        }

        if (
            in_array($accion, ['mejorar', 'corregir'], true)
            && blank(strip_tags($this->nuevo_contenido_html))
        ) {
            $this->addError('nuevo_contenido_html', 'Primero escribe o genera contenido para poder procesarlo.');

            return;
        }

        $variables = collect(preg_split('/\r\n|\r|\n/', $this->nuevas_variables))
            ->map(fn($variable) => trim((string) $variable))
            ->filter()
            ->merge($this->variablesBase())
            ->unique()
            ->values()
            ->all();

        try {
            $html = app(GroqDocumentoService::class)->redactar(
                tipoDocumento: 'constancia escolar',
                accion: $accion,
                titulo: $this->nuevo_titulo,
                instruccion: $this->instruccion_ia,
                contenidoActual: $this->nuevo_contenido_html,
                variablesPermitidas: $variables,
            );

            $this->nuevo_contenido_html = $html;
            $this->resetValidation(['nuevo_contenido_html', 'instruccion_ia']);

            $this->dispatch('actualizar-editor-plantilla', contenido: $html);
            $this->dispatch(
                'notificar',
                tipo: 'success',
                mensaje: match ($accion) {
                    'generar' => 'GroqCloud generó una propuesta. Revísala antes de guardar.',
                    'mejorar' => 'GroqCloud mejoró la redacción. Revísala antes de guardar.',
                    'corregir' => 'GroqCloud corrigió el contenido. Revísalo antes de guardar.',
                }
            );
        } catch (Throwable $exception) {
            Log::warning('No se pudo procesar la plantilla de constancia con GroqCloud.', [
                'accion' => $accion,
                'plantilla_id' => $this->plantilla_editando_id,
                'modelo' => config('groq.model'),
                'error' => $exception->getMessage(),
            ]);

            $this->dispatch(
                'notificar',
                tipo: 'error',
                mensaje: $exception->getMessage()
            );
        }
    }

    public function guardarPlantillaSistema(): void
    {
        $plantillaId = $this->plantilla_editando_id;

        $this->validate([
            'nueva_clave' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9_-]+$/',
                'unique:constancia_plantillas,clave,' . $plantillaId,
            ],
            'nuevo_titulo' => ['required', 'string', 'max:255'],
            'nuevo_contenido_html' => ['required', 'string'],
            'nuevas_variables' => ['nullable', 'string'],
            'nuevo_activo' => ['boolean'],
        ]);

        $variables = collect(preg_split('/\r\n|\r|\n/', $this->nuevas_variables))
            ->map(fn($variable) => trim($variable))
            ->filter()
            ->merge($this->variablesBase())
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

        $consulta = Inscripcion::query()
            ->with([
                'nivel:id,nombre,cct',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ]);

        $this->aplicarFiltroAlumnosDisponibles($consulta);

        $this->alumnos = $consulta
            ->where(function (Builder $consulta) use ($texto) {
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

        if ($this->incluir_calificaciones && !$this->hayPeriodosSeleccionados()) {
            $this->addError('periodos_calificaciones', 'Selecciona al menos un periodo para incluir calificaciones.');
            $this->dispatch('cerrar-ventana-constancia-vacia');
            return;
        }

        if ($this->modo_descarga === 'alumno') {
            $this->validate([
                'selectedAlumno' => ['required'],
            ]);

            // Solo la constancia individual se guarda en el historial.
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
            $this->dispatch('abrir-constancia-nueva-ventana', url: null);
            $this->dispatch('notificar', tipo: 'error', mensaje: 'No se encontraron alumnos para generar constancias.');
            return;
        }

        session()->put('constancias_masivas_payload', [
            'alumno_ids' => $alumnos->pluck('id')->values()->toArray(),
            'plantilla_id' => $this->plantilla_id,
            'contenido_html' => $this->contenido_html,
            'fecha_expedicion' => $this->fecha_expedicion ?: now()->format('Y-m-d'),
            'dirigido_a' => $this->dirigido_a,
            'modo_descarga' => $this->modo_descarga,
            'periodos_calificaciones' => $this->periodosSeleccionados(),
        ]);

        $this->dispatch('abrir-constancia-nueva-ventana', url: route('misrutas.constancias.masivas.pdf'));
    }

    private function obtenerAlumnosParaDescarga()
    {
        $consulta = Inscripcion::query()
            ->with([
                'nivel.director',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ]);

        $this->aplicarFiltroAlumnosDisponibles($consulta);

        return $consulta
            ->when($this->modo_descarga === 'nivel', function (Builder $consulta) {
                $consulta->where('nivel_id', $this->nivel_id);
            })
            ->when($this->modo_descarga === 'grado', function (Builder $consulta) {
                $consulta->where('nivel_id', $this->nivel_id)
                    ->where('grado_id', $this->grado_id);
            })
            ->when($this->modo_descarga === 'grupo', function (Builder $consulta) {
                $consulta->where('nivel_id', $this->nivel_id)
                    ->when($this->grado_id, function (Builder $query) {
                        $query->where('grado_id', $this->grado_id);
                    })
                    ->where('grupo_id', $this->grupo_id);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    private function aplicarFiltroAlumnosDisponibles(Builder $consulta): Builder
    {
        $estatusSalida = [
            'baja_temporal',
            'baja_definitiva',
            'trasladado',
            'traslado',
            'suspendido',
            'inactivo',
            'archivado',
        ];

        if ($this->esConstanciaTerminoSeleccionada()) {
            return $consulta->where(function (Builder $query) use ($estatusSalida) {
                $query->where('estatus', 'egresado')
                    ->orWhere(function (Builder $activos) use ($estatusSalida) {
                        $activos->where('activo', true)
                            ->whereNotIn('estatus', $estatusSalida);
                    });
            });
        }

        return $consulta
            ->where('activo', true)
            ->whereNotIn('estatus', $estatusSalida);
    }

    private function crearConstanciaIndividual(int $inscripcionId): ConstanciaModelo
    {
        $consulta = Inscripcion::query()
            ->with([
                'nivel.director',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'ciclo:id,ciclo',
            ])
            ->whereKey($inscripcionId);

        $this->aplicarFiltroAlumnosDisponibles($consulta);

        $alumno = $consulta->firstOrFail();

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

        $sexo = strtoupper((string) ($alumno->sexo ?? $alumno->genero ?? ''));

        return [
            'id' => $alumno->id,
            'nombre_completo' => trim(($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? '')),
            'curp' => $alumno->curp ?? '',
            'matricula' => $alumno->matricula ?? '',
            'grado' => $alumno->grado?->nombre ?? '',
            'nivel' => $alumno->nivel?->nombre ?? '',
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? '',
            'generacion' => $generacion,
            'ciclo' => $alumno->ciclo?->ciclo ?? '',
            'cct' => $alumno->nivel?->cct ?? '',
            'sexo_original' => $sexo,
        ];
    }

    private function reemplazarVariablesConAlumno(string $contenido, array $alumno): string
    {
        $sexoOriginal = strtoupper((string) ($alumno['sexo_original'] ?? ''));

        $esMasculino = str_contains($sexoOriginal, 'MASCULINO') || $sexoOriginal === 'H' || $sexoOriginal === 'HOMBRE';
        $sexo = $esMasculino ? 'Que el alumno:' : 'Que la alumna:';
        $descripcion = $esMasculino ? 'regularmente inscrito' : 'regularmente inscrita';

        $variables = [
            '@sexo' => $sexo,
            '@nombre' => $alumno['nombre_completo'] ?? '',
            '@alumno' => $alumno['nombre_completo'] ?? '',
            '@curp' => $alumno['curp'] ?? '',
            '@matricula' => $alumno['matricula'] ?? '',
            '@grado' => $alumno['grado'] ?? '',
            '@nivel_minuscula' => Str::lower(trim((string) ($alumno['nivel'] ?? ''))),
            '@nivel' => $alumno['nivel'] ?? '',
            '@grupo' => $alumno['grupo'] ?? '',
            '@generacion' => $alumno['generacion'] ?? '',
            '@ciclo' => $alumno['ciclo'] ?? '',
            '@cct' => $alumno['cct'] ?? '',
            '@descripcion' => $descripcion,
            '@fecha' => Carbon::parse($this->fecha_expedicion ?: now())->translatedFormat('d \d\e F \d\e Y'),
            '@dirigido' => $this->dirigido_a ?: 'A QUIEN CORRESPONDA',
        ];

        return str_replace(array_keys($variables), array_values($variables), $contenido);
    }

    private function periodosSeleccionados(): array
    {
        return [
            'incluir_calificaciones' => $this->incluir_calificaciones && $this->esConstanciaEstudiosSeleccionada(),
            'primer_periodo' => $this->incluir_calificaciones && $this->primer_periodo,
            'segundo_periodo' => $this->incluir_calificaciones && $this->segundo_periodo,
            'tercer_periodo' => $this->incluir_calificaciones && $this->tercer_periodo,
        ];
    }

    private function hayPeriodosSeleccionados(): bool
    {
        return $this->primer_periodo || $this->segundo_periodo || $this->tercer_periodo;
    }

    public function esConstanciaEstudiosSeleccionada(): bool
    {
        $identificador = Str::lower(trim($this->tipo_constancia . ' ' . $this->plantilla_titulo));

        return Str::contains($identificador, 'estudio')
            && !Str::contains($identificador, ['baja', 'traslado', 'conducta', 'relaciones']);
    }

    public function esConstanciaTerminoSeleccionada(): bool
    {
        $identificador = Str::lower(Str::ascii(trim(
            $this->tipo_constancia . ' ' . $this->plantilla_titulo
        )));

        return Str::contains($identificador, 'estudio')
            && Str::contains($identificador, ['termino', 'terminacion', 'conclusion', 'egreso']);
    }

    private function generarFolio(): string
    {
        $siguiente = (ConstanciaModelo::query()->max('id') ?? 0) + 1;

        return 'CONST-' . now()->format('Y') . '-' . Str::padLeft((string) $siguiente, 5, '0');
    }

    public function updatedBuscarConstancia(): void
    {
        $this->resetPage('constanciasPage');
    }

    public function abrirEditarConstancia(int $constanciaId): void
    {
        $constancia = ConstanciaModelo::query()->findOrFail($constanciaId);
        abort_if(($constancia->estado_documento ?? 'emitida') === 'cancelada', 422, 'Una constancia cancelada no puede editarse.');
        $periodos = $constancia->periodos_calificaciones ?? [];

        $this->constancia_editando_id = $constancia->id;
        $this->editar_fecha_expedicion = $constancia->fecha_expedicion?->format('Y-m-d');
        $this->editar_dirigido_a = $constancia->dirigido_a;
        $this->editar_contenido_generado_html = $constancia->contenido_generado_html ?? '';

        $this->editar_primer_periodo = (bool) ($periodos['primer_periodo'] ?? false);
        $this->editar_segundo_periodo = (bool) ($periodos['segundo_periodo'] ?? false);
        $this->editar_tercer_periodo = (bool) ($periodos['tercer_periodo'] ?? false);

        $this->mostrar_modal_editar_constancia = true;

        $this->resetValidation([
            'editar_fecha_expedicion',
            'editar_dirigido_a',
            'editar_contenido_generado_html',
        ]);

        $this->dispatch('abrir-modal-editar-constancia', contenido: $this->editar_contenido_generado_html);
    }

    public function cerrarEditarConstancia(): void
    {
        $this->mostrar_modal_editar_constancia = false;
        $this->constancia_editando_id = null;
        $this->editar_fecha_expedicion = null;
        $this->editar_dirigido_a = null;
        $this->editar_contenido_generado_html = '';
        $this->editar_primer_periodo = false;
        $this->editar_segundo_periodo = false;
        $this->editar_tercer_periodo = false;

        $this->resetValidation([
            'editar_fecha_expedicion',
            'editar_dirigido_a',
            'editar_contenido_generado_html',
        ]);

        $this->dispatch('cerrar-modal-editar-constancia');
    }

    public function actualizarConstancia(): void
    {
        $this->validate([
            'constancia_editando_id' => ['required', 'exists:constancias,id'],
            'editar_fecha_expedicion' => ['nullable', 'date'],
            'editar_dirigido_a' => ['nullable', 'string', 'max:255'],
            'editar_contenido_generado_html' => ['required', 'string'],
        ]);

        $constancia = ConstanciaModelo::query()->with('documentoAlumno')->findOrFail($this->constancia_editando_id);
        abort_if(($constancia->estado_documento ?? 'emitida') === 'cancelada', 422, 'Una constancia cancelada no puede editarse.');

        if ($constancia->documentoAlumno) {
            $constancia->documentoAlumno->update([
                'es_actual' => false,
                'estado' => 'reemplazado',
            ]);
        }

        $constancia->update([
            'documento_alumno_id' => null,
            'fecha_expedicion' => $this->editar_fecha_expedicion ?: now()->format('Y-m-d'),
            'dirigido_a' => $this->editar_dirigido_a,
            'periodos_calificaciones' => [
                'primer_periodo' => $this->editar_primer_periodo,
                'segundo_periodo' => $this->editar_segundo_periodo,
                'tercer_periodo' => $this->editar_tercer_periodo,
            ],
            'contenido_generado_html' => $this->editar_contenido_generado_html,
        ]);

        $this->cerrarEditarConstancia();
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Constancia actualizada correctamente.');
    }

    public function eliminarConstanciaGenerada(int $constanciaId): void
    {
        $constancia = ConstanciaModelo::query()
            ->with('documentoAlumno')
            ->findOrFail($constanciaId);

        $constancia->update([
            'estado_documento' => 'cancelada',
            'cancelada_at' => now(),
            'cancelada_por' => auth()->id(),
        ]);

        if ($constancia->documentoAlumno) {
            $constancia->documentoAlumno->update([
                'estado' => 'cancelada',
                'validado_por' => auth()->id(),
                'validado_at' => now(),
            ]);
        }

        $this->resetPage('constanciasPage');
        $this->dispatch('notificar', tipo: 'success', mensaje: 'Constancia cancelada. Se conservó en el historial y continúa disponible para administración.');
    }

    public function abrirPdfConstancia(int $constanciaId): void
    {
        $constancia = ConstanciaModelo::query()->findOrFail($constanciaId);

        $this->dispatch('abrir-constancia-nueva-ventana', url: route('misrutas.constancias.pdf', $constancia));
    }

    public function render()
    {
        $buscar = trim($this->buscar_constancia);

        return view('livewire.documentacion.constancia', [
            'plantillas' => ConstanciaPlantilla::query()
                ->orderBy('titulo')
                ->get(),

            'plantillasActivas' => ConstanciaPlantilla::query()
                ->where('activo', true)
                ->where('clave', '!=', 'baja-traslado')
                ->orderBy('titulo')
                ->get(),

            'constanciasGeneradas' => ConstanciaModelo::query()
                ->with([
                    'alumno:id,nombre,apellido_paterno,apellido_materno,matricula,nivel_id,grado_id,grupo_id',
                    'alumno.nivel:id,nombre',
                    'alumno.grado:id,nombre',
                    'alumno.grupo:id,asignacion_grupo_id',
                    'alumno.grupo.asignacionGrupo:id,nombre',
                    'plantilla:id,titulo,clave',
                ])
                ->when($buscar !== '', function ($consulta) use ($buscar) {
                    $consulta->where(function ($query) use ($buscar) {
                        $query->where('folio', 'like', "%{$buscar}%")
                            ->orWhere('dirigido_a', 'like', "%{$buscar}%")
                            ->orWhereHas('alumno', function ($alumno) use ($buscar) {
                                $alumno->where('nombre', 'like', "%{$buscar}%")
                                    ->orWhere('apellido_paterno', 'like', "%{$buscar}%")
                                    ->orWhere('apellido_materno', 'like', "%{$buscar}%")
                                    ->orWhere('matricula', 'like', "%{$buscar}%");
                            });
                    });
                })
                ->latest()
                ->paginate(8, pageName: 'constanciasPage'),
        ]);
    }
}
