<?php

namespace App\Livewire\Accion;

use App\Http\Controllers\FichaController;
use App\Models\FichaDescriptiva;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\CicloEscolar;
use App\Models\Periodos;
use App\Services\GroqFichaService;
use App\Services\GroqFichaGrupoService;
use App\Services\HtmlSanitizerService;
use App\Services\CicloNivelGateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

use App\Exports\FichaDescriptivaPlantillaImportacionExport;
use App\Imports\FichaDescriptivaImport;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class Ficha extends Component
{
    use WithPagination;
    use WithFileUploads;

    public function boot(): void
    {
        abort_unless(auth()->user()?->canAccess('fichas.capturar'), 403);
    }

    public string $descripcion = '';

    public bool $generando_descripcion = false;

    public string $observaciones_ia = '';

    public string $tipo_informe_grupo_ia = 'pedagogico';

    /** @var array<string, mixed> */
    public array $informe_grupo_ia = [];

    /** @var array<string, mixed> */
    public array $resumen_informe_grupo_ia = [];

    public ?string $informe_grupo_ia_generado_en = null;


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

    public ?bool $groq_disponible = null;
    public bool $groq_modelo_disponible = false;
    public string $groq_mensaje = 'Sin verificar';
    public string $groq_modelo = '';

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
        $this->limpiarInformeGrupoIa();

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
        $this->groq_modelo = (string) config('groq.model', 'openai/gpt-oss-20b');

        $nivel = Nivel::query()->where('slug', 'preescolar')->firstOrFail();
        $this->nivel_id = $nivel->id;

        $this->ciclo_escolar_id = CicloEscolar::query()
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

        if (
            in_array($property, [
                'generacion_id',
                'grado_id',
                'grupo_id',
                'ciclo_escolar_id',
                'periodo',
            ], true)
        ) {
            $this->limpiarInformeGrupoIa();
        }
    }

    public function cambiarPeriodo(int $periodo): void
    {
        if (!in_array($periodo, [1, 2, 3], true)) {
            return;
        }

        $this->periodo = $periodo;
        $this->resetPage();
        $this->limpiarInformeGrupoIa();
    }

    public function abrirModal(int $inscripcionId, string $campo): void
    {
        if (!array_key_exists($campo, $this->campos)) {
            return;
        }

        $this->inscripcion_id = $inscripcionId;
        $this->campo = $campo;
        $this->observaciones_ia = '';

        $this->descripcion = (string) FichaDescriptiva::query()
            ->where('inscripcion_id', $inscripcionId)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo', $this->periodo)
            ->where('campo', $campo)
            ->value('descripcion');

        $this->resetValidation();
        $this->modalAbierto = true;

        $this->dispatch('abrir-modal-ficha', contenido: $this->descripcion);
    }

    public function cerrarModal(): void
    {
        $this->modalAbierto = false;
        $this->inscripcion_id = null;
        $this->campo = '';
        $this->descripcion = '';
        $this->observaciones_ia = '';
        $this->generando_descripcion = false;

        $this->resetValidation();

        $this->dispatch('cerrar-modal-ficha');
    }

    public function verificarGroq(GroqFichaService $groq): void
    {
        $estado = $groq->estado();

        $this->groq_disponible = $estado['disponible'];
        $this->groq_modelo_disponible = $estado['modelo_disponible'];
        $this->groq_modelo = $estado['modelo'];
        $this->groq_mensaje = $estado['mensaje'];
    }

    public function generarDescripcionIA(GroqFichaService $groq): void
    {
        $this->validate([
            'inscripcion_id' => ['required', 'integer', 'exists:inscripciones,id'],
            'campo' => ['required', 'string', 'in:' . implode(',', array_keys($this->campos))],
            'observaciones_ia' => ['nullable', 'string', 'max:2500'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ], [
            'observaciones_ia.max' => 'Las observaciones no pueden superar los 2500 caracteres.',
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
        ]);

        $alumno = Inscripcion::query()
            ->with('grado:id,nombre')
            ->findOrFail($this->inscripcion_id);

        $contexto = (bool) config('groq.include_context', false)
            ? $this->obtenerContextoParaIA()
            : '';

        if (
            blank(strip_tags($this->observaciones_ia))
            && blank(strip_tags($this->descripcion))
            && blank($contexto)
        ) {
            $this->addError(
                'observaciones_ia',
                $this->campo === 'recomendaciones'
                ? 'Captura primero algún campo formativo o escribe observaciones para generar recomendaciones.'
                : 'Escribe algunas observaciones para generar la descripción.'
            );

            return;
        }

        try {
            $resultado = $groq->generarDescripcion(
                campo: $this->campos[$this->campo]['label'] ?? $this->campo,
                periodo: $this->periodoNombre(),
                grado: $alumno->grado?->nombre ?? 'Preescolar',
                referenciaAlumno: 'la persona estudiante',
                observaciones: $this->observaciones_ia,
                descripcionActual: $this->descripcion,
                contextoAdicional: $contexto,
                datosSensibles: [
                    $alumno->nombre ?? null,
                    $alumno->apellido_paterno ?? null,
                    $alumno->apellido_materno ?? null,
                    $alumno->curp ?? null,
                    $alumno->matricula ?? null,
                    $this->alumnoNombre($alumno),
                ]
            );

            $this->descripcion = $this->convertirTextoAHtml($resultado);
            $this->observaciones_ia = '';

            $this->verificarGroq($groq);

            $this->dispatch(
                'actualizar-editor-ficha',
                contenido: $this->descripcion
            );

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Descripción generada con GroqCloud',
                'text' => 'Revisa el texto y realiza los ajustes necesarios antes de guardarlo.',
                'position' => 'top',
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar la ficha con GroqCloud.', [
                'inscripcion_id' => $this->inscripcion_id,
                'campo' => $this->campo,
                'modelo' => $this->groq_modelo,
                'error' => $exception->getMessage(),
            ]);

            $this->verificarGroq($groq);

            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se pudo usar GroqCloud',
                'text' => $exception->getMessage(),
                'position' => 'top',
            ]);
        }
    }

    public function guardar(CicloNivelGateService $gate): void
    {
        $this->validate([
            'inscripcion_id' => ['required', 'integer', 'exists:inscripciones,id'],
            'campo' => ['required', 'string', 'in:' . implode(',', array_keys($this->campos))],
            'descripcion' => ['nullable', 'string', 'max:5000'],
        ], [
            'descripcion.max' => 'La descripción no puede superar los 5000 caracteres.',
        ]);

        $alumno = Inscripcion::query()->findOrFail($this->inscripcion_id);
        $gate->asegurar((int) $this->ciclo_escolar_id, (int) $alumno->nivel_id, 'fichas');
        $periodoOficialId = $this->resolverPeriodoOficialId((int) $alumno->nivel_id);

        if (!$periodoOficialId) {
            $this->addError('periodo', 'No existe un periodo oficial compatible con el ciclo, nivel y número de periodo seleccionados.');
            return;
        }

        $descripcionLimpia = app(HtmlSanitizerService::class)->sanitize($this->descripcion);

        FichaDescriptiva::query()->updateOrCreate(
            [
                'inscripcion_id' => $alumno->id,
                'ciclo_escolar_id' => $this->ciclo_escolar_id,
                'periodo' => $this->periodo,
                'periodo_id' => $periodoOficialId,
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

        $this->limpiarInformeGrupoIa();
        $this->cerrarModal();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Ficha guardada',
            'position' => 'top',
        ]);
    }

    public function generarInformeGrupoIa(GroqFichaGrupoService $groq): void
    {
        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grado_id' => ['required', 'integer', 'exists:grados,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'periodo' => ['required', 'integer', 'in:1,2,3'],
            'tipo_informe_grupo_ia' => ['required', 'in:pedagogico,direccion,consejo_tecnico,familias'],
        ], [
            'grado_id.required' => 'Selecciona un grado para generar el informe grupal.',
            'grupo_id.required' => 'Selecciona un grupo específico para generar el informe.',
            'tipo_informe_grupo_ia.in' => 'El tipo de informe seleccionado no es válido.',
        ]);

        $grupoValido = Grupo::query()
            ->whereKey($this->grupo_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->exists();

        if (!$grupoValido) {
            $this->addError('grupo_id', 'El grupo seleccionado no pertenece al grado indicado.');

            return;
        }

        try {
            $datos = $this->construirDatosAnonimosGrupoParaIa();
            $cobertura = $datos['cobertura'] ?? [];

            if ((int) ($cobertura['total_alumnos'] ?? 0) === 0) {
                throw new \RuntimeException('No hay alumnos activos en el grado y grupo seleccionados.');
            }

            if ((int) ($cobertura['fichas_capturadas'] ?? 0) === 0) {
                throw new \RuntimeException(
                    'Todavía no hay descripciones capturadas para este grupo y periodo.'
                );
            }

            $this->informe_grupo_ia = $groq->generarInforme(
                $datos,
                $this->tipo_informe_grupo_ia
            );

            $this->resumen_informe_grupo_ia = [
                'grado' => (string) data_get($datos, 'contexto.grado', 'Sin grado'),
                'grupo' => (string) data_get($datos, 'contexto.grupo', 'Sin grupo'),
                'periodo' => (string) data_get($datos, 'contexto.periodo', $this->periodoNombre()),
                'total_alumnos' => (int) ($cobertura['total_alumnos'] ?? 0),
                'fichas_capturadas' => (int) ($cobertura['fichas_capturadas'] ?? 0),
                'fichas_esperadas' => (int) ($cobertura['fichas_esperadas'] ?? 0),
                'porcentaje_cobertura' => (int) ($cobertura['porcentaje_cobertura'] ?? 0),
                'estado_captura' => (string) ($cobertura['estado_captura'] ?? 'parcial'),
                'modelo' => $groq->model(),
            ];

            $this->informe_grupo_ia_generado_en = Carbon::now()->format('d/m/Y H:i');

            $this->dispatch('swal', [
                'icon' => 'success',
                'title' => 'Informe descriptivo grupal generado',
                'text' => 'Revisa el contenido antes de utilizarlo en planeaciones o reuniones.',
                'position' => 'top',
            ]);
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar el informe grupal de fichas con GroqCloud.', [
                'nivel_id' => $this->nivel_id,
                'grado_id' => $this->grado_id,
                'grupo_id' => $this->grupo_id,
                'ciclo_escolar_id' => $this->ciclo_escolar_id,
                'periodo' => $this->periodo,
                'error' => $exception->getMessage(),
            ]);

            $this->limpiarInformeGrupoIa();

            $this->dispatch('swal', [
                'icon' => 'error',
                'title' => 'No se pudo generar el informe grupal',
                'text' => $exception->getMessage(),
                'position' => 'top',
            ]);
        }
    }

    public function limpiarInformeGrupoIa(): void
    {
        $this->informe_grupo_ia = [];
        $this->resumen_informe_grupo_ia = [];
        $this->informe_grupo_ia_generado_en = null;
    }

    public function clasePrioridadInformeGrupoIa(?string $prioridad): string
    {
        return match (mb_strtolower((string) $prioridad)) {
            'alta' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300',
            'baja' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300',
            default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300',
        };
    }

    /**
     * Construye el contexto del grupo sin nombres, matrículas, CURP ni identificadores individuales.
     *
     * @return array<string, mixed>
     */
    private function construirDatosAnonimosGrupoParaIa(): array
    {
        $alumnos = Inscripcion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->when($this->generacion_id, fn($q) => $q->where('generacion_id', $this->generacion_id))
            ->where('activo', true)
            ->orderBy('id')
            ->get([
                'id',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'curp',
                'matricula',
            ]);

        $ids = $alumnos->pluck('id');

        $fichas = FichaDescriptiva::query()
            ->whereIn('inscripcion_id', $ids)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo', $this->periodo)
            ->whereNotNull('descripcion')
            ->get(['inscripcion_id', 'campo', 'descripcion'])
            ->filter(fn(FichaDescriptiva $ficha) => filled(trim(strip_tags((string) $ficha->descripcion))));

        $identificadores = $alumnos->mapWithKeys(function (Inscripcion $alumno) {
            return [
                $alumno->id => array_values(array_filter([
                    trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno),
                    $alumno->nombre,
                    $alumno->apellido_paterno,
                    $alumno->apellido_materno,
                    $alumno->curp,
                    $alumno->matricula,
                ])),
            ];
        })->all();

        $maxFragmentosPorCampo = max(5, (int) config('groq.fichas_grupo.max_fragmentos_por_campo', 24));
        $maxCaracteresFragmento = max(200, (int) config('groq.fichas_grupo.max_caracteres_fragmento', 650));
        $presupuestoCaracteres = max(5000, (int) config('groq.fichas_grupo.max_caracteres_totales', 28000));

        $campos = [];
        $recomendacionesRegistradas = [];

        foreach ($this->campos as $clave => $campoInfo) {
            $fichasCampo = $fichas
                ->where('campo', $clave)
                ->values();

            $fragmentos = [];
            $vistos = [];

            foreach ($fichasCampo as $ficha) {
                if (count($fragmentos) >= $maxFragmentosPorCampo || $presupuestoCaracteres <= 0) {
                    break;
                }

                $texto = $this->anonimizarDescripcionGrupo(
                    (string) $ficha->descripcion,
                    $identificadores[$ficha->inscripcion_id] ?? [],
                    $maxCaracteresFragmento
                );

                if ($texto === '') {
                    continue;
                }

                $huella = mb_strtolower($texto);

                if (isset($vistos[$huella])) {
                    continue;
                }

                $texto = mb_substr($texto, 0, $presupuestoCaracteres);
                $presupuestoCaracteres -= mb_strlen($texto);
                $vistos[$huella] = true;
                $fragmentos[] = $texto;
            }

            $capturados = $fichasCampo
                ->pluck('inscripcion_id')
                ->unique()
                ->count();

            $totalAlumnos = $alumnos->count();

            if ($clave === 'recomendaciones') {
                $recomendacionesRegistradas = $fragmentos;

                continue;
            }

            $campos[] = [
                'clave' => $clave,
                'campo' => (string) ($campoInfo['label'] ?? $clave),
                'estudiantes_con_captura' => $capturados,
                'total_estudiantes' => $totalAlumnos,
                'porcentaje_cobertura' => $totalAlumnos > 0
                    ? (int) round(($capturados / $totalAlumnos) * 100)
                    : 0,
                'fragmentos_anonimos' => $fragmentos,
            ];
        }

        $totalAlumnos = $alumnos->count();
        $fichasEsperadas = $totalAlumnos * count($this->campos);
        $fichasCapturadas = $fichas
            ->map(fn(FichaDescriptiva $ficha) => $ficha->inscripcion_id . ':' . $ficha->campo)
            ->unique()
            ->count();
        $porcentajeCobertura = $fichasEsperadas > 0
            ? (int) round(($fichasCapturadas / $fichasEsperadas) * 100)
            : 0;

        $grado = Grado::query()->whereKey($this->grado_id)->value('nombre') ?? 'Sin grado';

        $grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);

        $grupoNombre = $grupo?->asignacionGrupo?->nombre
            ?? ($grupo?->nombre ?? 'Sin grupo');

        $generacion = $this->generacion_id
            ? Generacion::query()->whereKey($this->generacion_id)->first()
            : null;

        $ciclo = CicloEscolar::query()->whereKey($this->ciclo_escolar_id)->first();

        return [
            'contexto' => [
                'nivel' => 'Preescolar',
                'grado' => (string) $grado,
                'grupo' => (string) $grupoNombre,
                'generacion' => $generacion
                    ? trim($generacion->anio_ingreso . ' - ' . $generacion->anio_egreso)
                    : 'No especificada',
                'ciclo_escolar' => $ciclo
                    ? trim($ciclo->inicio_anio . ' - ' . $ciclo->fin_anio)
                    : 'No especificado',
                'periodo' => $this->periodoNombre(),
            ],
            'cobertura' => [
                'total_alumnos' => $totalAlumnos,
                'total_campos_por_alumno' => count($this->campos),
                'fichas_esperadas' => $fichasEsperadas,
                'fichas_capturadas' => $fichasCapturadas,
                'porcentaje_cobertura' => $porcentajeCobertura,
                'estado_captura' => match (true) {
                    $porcentajeCobertura >= 90 => 'completa',
                    $porcentajeCobertura >= 60 => 'parcial',
                    default => 'insuficiente',
                },
            ],
            'campos_formativos' => $campos,
            'recomendaciones_individuales_anonimizadas' => $recomendacionesRegistradas,
            'restricciones' => [
                'usar_solo_tendencias_grupales' => true,
                'no_individualizar_casos' => true,
                'no_emitir_diagnosticos' => true,
                'marcar_como_preliminar_si_cobertura_menor_a_90' => true,
            ],
        ];
    }

    /**
     * @param array<int, string> $identificadores
     */
    private function anonimizarDescripcionGrupo(
        string $descripcion,
        array $identificadores,
        int $limite
    ): string {
        $texto = html_entity_decode(strip_tags($descripcion), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', trim($texto)) ?? '';

        usort($identificadores, fn($a, $b) => mb_strlen((string) $b) <=> mb_strlen((string) $a));

        foreach ($identificadores as $identificador) {
            $identificador = trim((string) $identificador);

            if (mb_strlen($identificador) < 3) {
                continue;
            }

            $patron = '/(?<![\p{L}\p{N}])' . preg_quote($identificador, '/') . '(?![\p{L}\p{N}])/iu';
            $texto = preg_replace($patron, 'la persona estudiante', $texto) ?? $texto;
        }

        $patrones = [
            '/\b[A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]\d\b/iu',
            '/\b[\w.%+\-]+@[\w.\-]+\.[A-Z]{2,}\b/iu',
            '/\b(?:\+?52\s*)?(?:\d[\s\-()]*){10}\b/u',
            '/\b(?:matr[ií]cula|curp|folio)\s*[:#-]?\s*[A-Z0-9-]{4,}\b/iu',
        ];

        $texto = preg_replace($patrones, '[DATO OMITIDO]', $texto) ?? $texto;

        return mb_substr(trim($texto), 0, $limite);
    }

    public function getGeneracionesProperty()
    {
        return Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('status', true)
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
        return CicloEscolar::query()
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

    private function resolverPeriodoOficialId(int $nivelId): ?int
    {
        return Periodos::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $nivelId)
            ->whereHas('periodoBasica', fn ($query) => $query->where('periodo', $this->periodo))
            ->value('id');
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

    private function obtenerContextoParaIA(): string
    {
        if (!$this->inscripcion_id || $this->campo !== 'recomendaciones') {
            return '';
        }

        return FichaDescriptiva::query()
            ->where('inscripcion_id', $this->inscripcion_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo', $this->periodo)
            ->where('campo', '!=', 'recomendaciones')
            ->whereNotNull('descripcion')
            ->get(['campo', 'descripcion'])
            ->map(function (FichaDescriptiva $ficha) {
                $etiqueta = $this->campos[$ficha->campo]['label'] ?? $ficha->campo;
                $texto = trim(html_entity_decode(strip_tags((string) $ficha->descripcion)));

                return $texto !== '' ? $etiqueta . ': ' . $texto : null;
            })
            ->filter()
            ->implode("\n");
    }

    private function referenciaAlumno(Inscripcion $alumno): string
    {
        $valor = mb_strtoupper(trim((string) (
            $alumno->genero
            ?? $alumno->sexo
            ?? ''
        )));

        if (in_array($valor, ['M', 'MUJER', 'F', 'FEMENINO'], true)) {
            return 'la alumna';
        }

        if (in_array($valor, ['H', 'HOMBRE', 'MASCULINO'], true)) {
            return 'el alumno';
        }

        return 'el alumno o la alumna';
    }

    private function convertirTextoAHtml(string $texto): string
    {
        $parrafos = preg_split('/\R{2,}/u', trim($texto)) ?: [];

        return collect($parrafos)
            ->map(fn(string $parrafo) => '<p>' . e(trim($parrafo)) . '</p>')
            ->filter(fn(string $parrafo) => $parrafo !== '<p></p>')
            ->implode('');
    }

    public function render()
    {
        return view('livewire.accion.ficha', [
            'alumnos' => $this->alumnosPagina,
            'fichasResumen' => $this->fichasResumen,
        ]);
    }
}
