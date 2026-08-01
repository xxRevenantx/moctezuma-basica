<?php

namespace App\Livewire\Inscripcion;

use App\Exports\Inscripciones\InscripcionesExport;
use App\Exports\Inscripciones\PlantillaInscripcionesExport;
use App\Imports\Inscripciones\InscripcionesImport;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\Tutor;
use App\Rules\CurpMexicana;
use App\Services\AsignacionEscolarService;
use App\Services\CurpLocalLookupService;
use App\Services\CurpService;
use App\Services\GestionAcademicaService;
use App\Services\GestionResponsablesAlumnoService;
use App\Services\ImagenPersonalService;
use App\Services\MatriculaAlumnoService;
use App\Services\ObservacionInscripcionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class CrearInscripcion extends Component
{
    use WithFileUploads;

    public bool $consultandoCurp = false;
    public ?string $curpError = null;
    public ?string $curpAdvertencia = null;
    public ?string $curpSuccess = null;
    public ?string $ultimaCurpConsultada = null;

    public string $curpEstado = 'inicial';
    public string $curpMensaje = 'Escribe una CURP para comprobar si ya está registrada.';
    public bool $curpLocalValidada = false;
    public bool $curpExisteLocal = false;
    public ?string $ultimaCurpValidadaLocal = null;
    public ?array $alumnoExistente = null;
    public array $curpDiferencias = [];
    public ?string $direccionTutorAdvertencia = null;
    public bool $guardandoInscripcion = false;
    #[Locked]
    public bool $puedeGestionarResponsablesSensibles = false;

    public string $curp = '';
    public string $matricula = '';

    public bool $matriculaEditadaManual = false;
    public ?string $folio = null;

    public string $nombre = '';
    public string $apellido_paterno = '';
    public ?string $apellido_materno = null;
    public ?string $fecha_nacimiento = null;
    public ?string $genero = null;

    public ?string $fecha_inscripcion = null;
    public ?int $ciclo_id = null;
    public ?int $ciclo_escolar_id = null;
    public string $tipo_ingreso = 'nuevo_ingreso';
    public string $estado_inscripcion = 'inscrito';
    public ?string $motivo_captura_historica = null;
    public ?string $generacionAutomaticaLabel = null;
    public ?string $asignacionAdvertencia = null;

    public ?string $fecha_baja = null;
    public ?string $motivo_baja = null;
    public ?string $observaciones_baja = null;
    public ?string $observaciones = null;

    public ?string $pais_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $lugar_nacimiento = null;

    public ?string $calle = null;
    public ?string $numero_exterior = null;
    public ?string $numero_interior = null;
    public ?string $colonia = null;
    public ?string $codigo_postal = null;
    public ?string $municipio = null;
    public ?string $estado_residencia = null;
    public ?string $ciudad_residencia = null;

    public $foto = null;

    /** @var array<int, array<string, mixed>> */
    public array $responsables = [];
    public string $buscarTutor = '';
    public array $resultadosTutores = [];
    public bool $mostrarNuevoTutor = false;
    public ?string $responsablesMensaje = null;
    public array $nuevoTutor = [
        'sin_curp' => false,
        'curp' => '',
        'identificador_alternativo' => '',
        'motivo_sin_curp' => '',
        'nombre' => '',
        'apellido_paterno' => '',
        'apellido_materno' => '',
        'genero' => '',
        'fecha_nacimiento' => '',
        'telefono_celular' => '',
        'telefono_casa' => '',
        'correo_electronico' => '',
        'calle' => '',
        'numero' => '',
        'colonia' => '',
        'codigo_postal' => '',
        'municipio' => '',
        'estado' => '',
        'ciudad' => '',
    ];

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public bool $esBachillerato = false;

    public Collection $niveles;
    public Collection $gradosOptions;
    public Collection $generacionesOptions;
    public Collection $semestresOptions;
    public array $gruposOptions = [];
    public Collection $ciclosOptions;
    public Collection $cicloEscolaresOptions;

    public $archivoAlumnos = null;
    public array $erroresImportacionAlumnos = [];
    public ?string $mensajeImportacionAlumnos = null;
    public ?string $errorImportacionAlumnos = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.crear'), 403);
        $this->puedeGestionarResponsablesSensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');

        $this->niveles = $this->loadNiveles();
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];
        $this->ciclosOptions = $this->loadCiclos();
        $this->cicloEscolaresOptions = $this->loadCicloEscolares();
        $this->ciclo_escolar_id = $this->cicloEscolaresOptions->firstWhere('es_actual', true)?->id
            ?: $this->cicloEscolaresOptions->first()?->id;

        $this->fecha_inscripcion = now()->toDateString();
        $this->ciclo_escolar_id = $this->ciclo_escolar_id ?: $this->cicloEscolaresOptions->first()?->id;
        $this->fecha_baja = null;
        $this->motivo_baja = null;
        $this->observaciones_baja = null;

        $this->matricula = '';
    }

    protected function rules(): array
    {
        $gradoRules = [
            Rule::requiredIf(!$this->esBachillerato),
            'nullable',
            'integer',
            Rule::exists('grados', 'id'),
        ];

        $semestreRules = [
            Rule::requiredIf($this->esBachillerato),
            'nullable',
            'integer',
            Rule::exists('semestres', 'id'),
        ];

        return [
            'curp' => [
                'required',
                'string',
                'size:18',
                'regex:/^[A-Z0-9]+$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $resultado = app(CurpLocalLookupService::class)->validarFormato((string) $value);

                    if (! $resultado['valida']) {
                        $fail($resultado['mensaje']);
                    }
                },
            ],
            'matricula' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9\-]+$/i',
                Rule::unique('inscripciones', 'matricula'),
            ],
            'folio' => [
                'nullable',
                'string',
                'max:50',
            ],

            'nombre' => [
                'required',
                'string',
                'max:255',
            ],
            'apellido_paterno' => [
                'required',
                'string',
                'max:255',
            ],
            'apellido_materno' => [
                'nullable',
                'string',
                'max:255',
            ],
            'fecha_nacimiento' => [
                'required',
                'date',
            ],
            'genero' => [
                'required',
                'string',
                Rule::in(['H', 'M']),
            ],

            'fecha_inscripcion' => [
                'required',
                'date',
            ],
            'ciclo_escolar_id' => [
                'required',
                'integer',
                Rule::exists('ciclo_escolares', 'id'),
            ],
            'ciclo_id' => [
                'required',
                'integer',
                Rule::exists('ciclos', 'id'),
            ],
            'tipo_ingreso' => [
                'required',
                Rule::in(['nuevo_ingreso', 'traslado', 'captura_historica']),
            ],
            'estado_inscripcion' => [
                'required',
                Rule::in(['preinscrito', 'inscrito']),
            ],
            'motivo_captura_historica' => [
                Rule::requiredIf($this->tipo_ingreso === 'captura_historica'),
                'nullable',
                'string',
                'min:10',
                'max:500',
            ],

            'fecha_baja' => [
                'nullable',
                'date',
            ],
            'motivo_baja' => [
                'nullable',
                'string',
                'max:255',
            ],
            'observaciones_baja' => [
                'nullable',
                'string',
                'max:255',
            ],
            'observaciones' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (app(ObservacionInscripcionService::class)->excedeLimite($value)) {
                        $fail('Las observaciones no deben superar 5,000 caracteres.');
                    }
                },
            ],

            'pais_nacimiento' => [
                'nullable',
                'string',
                'max:150',
            ],
            'estado_nacimiento' => [
                'nullable',
                'string',
                'max:150',
            ],
            'lugar_nacimiento' => [
                'nullable',
                'string',
                'max:150',
            ],

            'calle' => [
                'nullable',
                'string',
                'max:255',
            ],
            'numero_exterior' => [
                'nullable',
                'string',
                'max:50',
            ],
            'numero_interior' => [
                'nullable',
                'string',
                'max:50',
            ],
            'colonia' => [
                'nullable',
                'string',
                'max:255',
            ],
            'codigo_postal' => [
                'nullable',
                'regex:/^[0-9]{5}$/',
            ],
            'municipio' => [
                'nullable',
                'string',
                'max:255',
            ],
            'estado_residencia' => [
                'nullable',
                'string',
                'max:255',
            ],
            'ciudad_residencia' => [
                'nullable',
                'string',
                'max:255',
            ],

            'nivel_id' => [
                'required',
                'integer',
                Rule::exists('niveles', 'id'),
            ],
            'grado_id' => $gradoRules,
            'generacion_id' => [
                'required',
                'integer',
                Rule::exists('generaciones', 'id'),
            ],
            'semestre_id' => $semestreRules,
            'grupo_id' => [
                'required',
                'integer',
                Rule::exists('grupos', 'id'),
            ],

            'responsables' => ['array'],
            'responsables.*.tutor_id' => ['required', 'integer', Rule::exists('tutores', 'id')],
            'responsables.*.parentesco' => ['required', Rule::in(GestionResponsablesAlumnoService::PARENTESCOS)],
            'responsables.*.estado_tutela' => ['required', Rule::in(GestionResponsablesAlumnoService::ESTADOS_TUTELA)],
            'responsables.*.es_principal' => ['boolean'],
            'responsables.*.es_tutor_legal' => ['boolean'],
            'responsables.*.vive_con_alumno' => ['boolean'],
            'responsables.*.recibe_avisos' => ['boolean'],
            'responsables.*.recibe_calificaciones' => ['boolean'],
            'responsables.*.contacto_emergencia' => ['boolean'],
            'responsables.*.autorizado_recoger' => ['boolean'],
            'responsables.*.responsable_economico' => ['boolean'],
            'responsables.*.observaciones' => ['nullable', 'string', 'max:1000'],
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],

            'archivoAlumnos' => [
                'nullable',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'curp.required' => 'La CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex' => 'La CURP solo debe contener letras y números.',

            'matricula.required' => 'La matrícula es obligatoria.',
            'matricula.unique' => 'La matrícula generada ya existe.',

            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',

            'fecha_inscripcion.required' => 'La fecha de inscripción es obligatoria.',
            'ciclo_escolar_id.required' => 'Selecciona un ciclo escolar.',
            'ciclo_id.required' => 'Selecciona el momento de ingreso.',
            'tipo_ingreso.required' => 'Selecciona el tipo de ingreso.',
            'estado_inscripcion.required' => 'Selecciona si el alumno quedará preinscrito o inscrito.',
            'motivo_captura_historica.required' => 'Explica por qué se realizará una captura histórica.',
            'motivo_captura_historica.min' => 'El motivo de captura histórica debe tener al menos 10 caracteres.',
            'motivo_captura_historica.max' => 'El motivo de captura histórica no debe superar 500 caracteres.',

            'fecha_baja.date' => 'La fecha de baja no es válida.',
            'motivo_baja.string' => 'El motivo de baja no es válido.',
            'motivo_baja.max' => 'El motivo de baja no debe superar 255 caracteres.',
            'observaciones_baja.string' => 'Las observaciones de baja no son válidas.',
            'observaciones_baja.max' => 'Las observaciones de baja no deben superar 255 caracteres.',
            'observaciones.string' => 'Las observaciones no tienen un formato válido.',

            'nivel_id.required' => 'Selecciona un nivel.',
            'grado_id.required' => 'Selecciona un grado.',
            'generacion_id.required' => 'Selecciona una generación.',
            'semestre_id.required' => 'Selecciona un semestre.',
            'grupo_id.required' => 'Selecciona un grupo.',

            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'responsables.*.tutor_id.exists' => 'Uno de los responsables seleccionados no es válido.',

            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe exceder 5MB.',

            'archivoAlumnos.file' => 'Selecciona un archivo válido.',
            'archivoAlumnos.mimes' => 'El archivo debe ser Excel o CSV.',
            'archivoAlumnos.max' => 'El archivo no debe superar 10MB.',
        ];
    }

    private function titleCaseNombre(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');

        $lowerWords = ['De', 'Del', 'La', 'Las', 'Los', 'Y', 'E', 'San', 'Santa', 'Van', 'Von'];

        foreach ($lowerWords as $word) {
            $value = preg_replace('/\b' . preg_quote($word, '/') . '\b/u', mb_strtolower($word, 'UTF-8'), $value) ?? $value;
        }

        $value = preg_replace_callback('/^(de|del|la|las|los|y|e|san|santa|van|von)\b/iu', function ($match) {
            return mb_convert_case(mb_strtolower($match[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }, $value) ?? $value;

        return $value;
    }

    protected function sanitizeStrings(): void
    {
        $requiredStringFields = [
            'curp',
            'matricula',
            'nombre',
            'apellido_paterno',
        ];

        $nullableStringFields = [
            'folio',
            'apellido_materno',
            'motivo_baja',
            'observaciones_baja',
            'motivo_captura_historica',
            'pais_nacimiento',
            'estado_nacimiento',
            'lugar_nacimiento',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'codigo_postal',
            'municipio',
            'estado_residencia',
            'ciudad_residencia',
        ];

        foreach ($requiredStringFields as $field) {
            $value = $this->{$field} ?? '';
            $value = is_string($value) ? $value : '';
            $value = preg_replace('/\s+/u', ' ', trim($value));
            $this->{$field} = $value;
        }

        foreach ($nullableStringFields as $field) {
            $value = $this->{$field} ?? null;

            if (is_string($value)) {
                $value = preg_replace('/\s+/u', ' ', trim($value));
                $this->{$field} = $value === '' ? null : $value;
            }
        }

        if ($this->curp !== '') {
            $this->curp = mb_strtoupper($this->curp);
        }

        if ($this->matricula !== '') {
            $this->matricula = mb_strtoupper($this->matricula);
        }
    }

    protected function nivelCodeBySlug(?string $slug): string
    {
        return match ($slug) {
            'preescolar' => 'PREES',
            'primaria' => 'PRIM',
            'secundaria' => 'SEC',
            'bachillerato' => 'BACHI',
            default => 'NIV',
        };
    }

    protected function anioInicioCiclo(): string
    {
        if ($this->generacion_id) {
            $generacion = Generacion::query()->find($this->generacion_id);

            if ($generacion?->anio_ingreso) {
                return (string) $generacion->anio_ingreso;
            }
        }

        return (string) now()->year;
    }

    protected function generarMatriculaConSlug(string $slug): ?string
    {
        $curpLimpia = mb_strtoupper(trim($this->curp));

        if ($curpLimpia === '') {
            return null;
        }

        if (!preg_match('/^[A-Z0-9]+$/', $curpLimpia)) {
            return null;
        }

        $anio = $this->anioInicioCiclo();
        $nivel = $this->nivelCodeBySlug($slug);

        // Se toman los primeros 4 caracteres disponibles.
        // Si tiene menos de 4, se completa con X para no romper la matrícula.
        $curp4 = mb_substr($curpLimpia, 0, 4);
        $curp4 = str_pad($curp4, 4, 'X');

        for ($i = 0; $i < 50; $i++) {
            $consecutivo = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$nivel}{$curp4}{$consecutivo}";

            if (!Inscripcion::query()->where('matricula', $matricula)->exists()) {
                return $matricula;
            }
        }

        for ($i = 0; $i < 50; $i++) {
            $consecutivo = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$nivel}{$curp4}{$consecutivo}";

            if (!Inscripcion::query()->where('matricula', $matricula)->exists()) {
                return $matricula;
            }
        }

        return null;
    }

    protected function refrescarMatriculaSiPosible(): void
    {
        /*
            Si la matrícula fue capturada manualmente,
            no se vuelve a generar para no borrar el dato escrito.
        */
        if ($this->matriculaEditadaManual && trim($this->matricula) !== '') {
            return;
        }

        // La matrícula se calcula únicamente después de confirmar que la CURP
        // tiene formato válido y no existe en la base local.
        if (! $this->curpLocalValidada || $this->curpExisteLocal || $this->curpEstado !== 'disponible') {
            $this->matricula = '';
            return;
        }

        if (!$this->nivel_id) {
            $this->matricula = '';
            return;
        }

        $nivel = $this->niveles->firstWhere('id', $this->nivel_id) ?: Nivel::query()->find($this->nivel_id);
        $slug = $nivel?->slug;

        if (!$slug) {
            $this->matricula = '';
            return;
        }

        $matricula = $this->generarMatriculaConSlug($slug);

        if ($matricula) {
            $this->matricula = $matricula;
            $this->resetValidation('matricula');
            return;
        }

        $this->matricula = '';
    }

    public function updatedMatricula($value): void
    {
        $this->matriculaEditadaManual = true;

        $this->matricula = mb_strtoupper(
            preg_replace('/\s+/', '', trim((string) $value))
        );

        $this->validateOnly('matricula');
    }

    public function updatedCurp(string $value): void
    {
        $servicio = app(CurpLocalLookupService::class);
        $this->curp = $servicio->normalizar($value);

        $this->limpiarResultadoCurp(false);
        $resultado = $servicio->validarFormato($this->curp);
        $this->curpEstado = $resultado['estado'];
        $this->curpMensaje = $resultado['mensaje'];

        if (! $resultado['valida'] && mb_strlen($this->curp) < 16) {
            $this->matricula = '';
            $this->resetValidation('curp');
            return;
        }

        // Algunos registros históricos contienen identificadores heredados de
        // 16 o 17 caracteres. También se buscan de forma exacta para impedir
        // que se intente crear otro alumno con el mismo identificador.
        $this->validarCurpLocal();
    }

    public function validarCurpLocal(): void
    {
        $servicio = app(CurpLocalLookupService::class);
        $this->curp = $servicio->normalizar($this->curp);
        $resultado = $servicio->validarFormato($this->curp);
        $longitud = mb_strlen($this->curp);

        if (! $resultado['valida'] && $longitud < 16) {
            $this->curpEstado = $resultado['estado'];
            $this->curpMensaje = $resultado['mensaje'];
            $this->curpLocalValidada = false;
            return;
        }

        if ($this->ultimaCurpValidadaLocal === $this->curp && $this->curpLocalValidada) {
            return;
        }

        $this->curpEstado = 'consultando';
        $this->curpMensaje = 'Buscando CURP en la base de datos…';
        $this->curpLocalValidada = false;

        try {
            $this->alumnoExistente = $servicio->buscar($this->curp);
            $this->ultimaCurpValidadaLocal = $this->curp;
            $this->curpExisteLocal = $this->alumnoExistente !== null;

            if ($this->curpExisteLocal) {
                $this->curpLocalValidada = true;
                $this->curpEstado = 'encontrada';
                $this->curpMensaje = $resultado['valida']
                    ? 'Esta CURP ya pertenece a un alumno registrado.'
                    : 'Este identificador histórico ya pertenece a un alumno registrado.';
                $this->matricula = '';
                $this->matriculaEditadaManual = false;
                $this->addError('curp', 'El identificador ya está registrado. Utiliza la acción administrativa indicada en la tarjeta.');
                return;
            }

            if (! $resultado['valida']) {
                $this->curpEstado = $resultado['estado'];
                $this->curpMensaje = $resultado['mensaje'];
                $this->curpLocalValidada = false;
                $this->curpExisteLocal = false;
                $this->matricula = '';
                return;
            }

            $this->curpLocalValidada = true;
            $this->curpEstado = 'disponible';
            $this->curpMensaje = 'La CURP no existe en la base de datos. Puedes consultar sus datos externos.';
            $this->resetValidation('curp');
            $this->refrescarMatriculaSiPosible();
        } catch (\Throwable) {
            $this->curpEstado = 'error';
            $this->curpMensaje = 'No fue posible validar la CURP localmente. Intenta nuevamente.';
            $this->curpLocalValidada = false;
            $this->curpExisteLocal = false;
            $this->alumnoExistente = null;
            $this->matricula = '';
        }
    }

    public function limpiarResultadoCurp(bool $limpiarCampo = false): void
    {
        if ($limpiarCampo) {
            $this->curp = '';
            $this->matricula = '';
            $this->matriculaEditadaManual = false;
        }

        $this->curpError = null;
        $this->curpAdvertencia = null;
        $this->curpSuccess = null;
        $this->ultimaCurpConsultada = null;
        $this->ultimaCurpValidadaLocal = null;
        $this->curpLocalValidada = false;
        $this->curpExisteLocal = false;
        $this->alumnoExistente = null;
        $this->curpDiferencias = [];
        $this->resetValidation('curp');

        if ($limpiarCampo || $this->curp === '') {
            $this->curpEstado = 'inicial';
            $this->curpMensaje = 'Escribe una CURP para comprobar si ya está registrada.';
        }
    }

    public function consultarCurp(): void
    {
        $local = app(CurpLocalLookupService::class);
        $this->curp = $local->normalizar($this->curp);
        $resultado = $local->validarFormato($this->curp);

        $this->curpError = null;
        $this->curpAdvertencia = null;
        $this->curpSuccess = null;

        if (! $resultado['valida']) {
            $this->curpEstado = $resultado['estado'];
            $this->curpMensaje = $resultado['mensaje'];
            return;
        }

        if (! $this->curpLocalValidada || $this->ultimaCurpValidadaLocal !== $this->curp) {
            $this->validarCurpLocal();
        }

        if (! $this->curpLocalValidada || $this->curpExisteLocal) {
            $this->curpAdvertencia = $this->curpExisteLocal
                ? 'La consulta externa fue bloqueada porque la CURP ya existe localmente.'
                : 'Espera a que termine la validación local antes de consultar el servicio externo.';
            return;
        }

        if ($this->ultimaCurpConsultada === $this->curp && $this->curpSuccess) {
            return;
        }

        $this->ultimaCurpConsultada = $this->curp;
        $this->consultandoCurp = true;

        try {
            $payload = app(CurpService::class)->obtenerDatosPorCurp($this->curp);
        } catch (\Throwable) {
            $payload = [
                'error' => true,
                'message' => 'No se pudo consultar el servicio externo.',
                'tipo_error' => 'inesperado',
            ];
        } finally {
            $this->consultandoCurp = false;
        }

        if (($payload['error'] ?? true) === true) {
            $this->curpAdvertencia = (string) ($payload['message']
                ?? 'No se pudieron obtener los datos. Puedes continuar capturándolos manualmente.');
            return;
        }

        $datosAntes = [
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
        ];

        $this->llenarDatosDesdePayloadCurp($payload);
        $this->sanitizeStrings();
        $this->refrescarMatriculaSiPosible();

        $habiaDatosCapturados = collect($datosAntes)->filter(fn ($valor) => filled($valor))->isNotEmpty();
        $this->curpSuccess = $habiaDatosCapturados
            ? 'Se obtuvieron datos externos. Revisa cualquier diferencia antes de guardar.'
            : 'Los datos externos se cargaron correctamente.';

        $this->resetValidation([
            'curp',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'pais_nacimiento',
            'estado_nacimiento',
            'lugar_nacimiento',
        ]);
    }

    #[On('limpiar-curp-success')]
    public function ocultarCurpSuccess(): void
    {
        $this->curpSuccess = null;
    }

    protected function llenarDatosDesdePayloadCurp(array $payload): void
    {
        $datos = $payload['datos'] ?? null;

        if (! is_array($datos)) {
            $solicitante = data_get($payload, 'response.Solicitante');

            if (! is_array($solicitante)) {
                $this->curpAdvertencia = 'No se pudieron obtener los datos de la CURP. Puedes continuar llenando los datos manualmente.';
                return;
            }

            $datos = [
                'nombre' => data_get($solicitante, 'Nombres'),
                'apellido_paterno' => data_get($solicitante, 'ApellidoPaterno'),
                'apellido_materno' => data_get($solicitante, 'ApellidoMaterno'),
                'fecha_nacimiento' => data_get($solicitante, 'FechaNacimiento'),
                'genero' => data_get($solicitante, 'ClaveSexo'),
                'pais_nacimiento' => data_get($solicitante, 'Nacionalidad'),
                'estado_nacimiento' => data_get($solicitante, 'EntidadNacimiento'),
                'lugar_nacimiento' => data_get($solicitante, 'EntidadNacimiento'),
            ];
        }

        $this->curpDiferencias = [];

        $campos = [
            'nombre' => 'Nombre(s)',
            'apellido_paterno' => 'Apellido paterno',
            'apellido_materno' => 'Apellido materno',
            'fecha_nacimiento' => 'Fecha de nacimiento',
            'genero' => 'Género',
            'pais_nacimiento' => 'País de nacimiento',
            'estado_nacimiento' => 'Estado de nacimiento',
            'lugar_nacimiento' => 'Lugar de nacimiento',
        ];

        foreach ($campos as $campo => $etiqueta) {
            $nuevo = $datos[$campo] ?? null;

            if (blank($nuevo)) {
                continue;
            }

            $nuevo = match ($campo) {
                'nombre', 'apellido_paterno', 'apellido_materno' => $this->titleCaseNombre((string) $nuevo),
                'genero' => mb_strtoupper(trim((string) $nuevo)),
                default => trim((string) $nuevo),
            };

            if ($campo === 'genero' && ! in_array($nuevo, ['H', 'M'], true)) {
                continue;
            }

            $actual = $this->{$campo};

            if (blank($actual)) {
                $this->{$campo} = $nuevo;
                continue;
            }

            $actualComparar = mb_strtoupper(trim((string) $actual));
            $nuevoComparar = mb_strtoupper(trim((string) $nuevo));

            if ($actualComparar !== $nuevoComparar) {
                $this->curpDiferencias[] = [
                    'campo' => $etiqueta,
                    'capturado' => (string) $actual,
                    'servicio' => (string) $nuevo,
                ];
            }
        }
    }

    public function updatedBuscarTutor(): void
    {
        $this->buscarTutores();
    }

    public function buscarTutores(): void
    {
        $termino = trim($this->buscarTutor);

        if (mb_strlen($termino) < 2) {
            $this->resultadosTutores = [];
            return;
        }

        $yaSeleccionados = collect($this->responsables)
            ->pluck('tutor_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->resultadosTutores = Tutor::query()
            ->activos()
            ->whereNotIn('id', $yaSeleccionados ?: [0])
            ->where(function ($query) use ($termino): void {
                $like = '%' . $termino . '%';
                $query->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('curp', 'like', '%' . mb_strtoupper($termino) . '%')
                    ->orWhere('identificador_alternativo', 'like', $like)
                    ->orWhere('telefono_celular', 'like', $like)
                    ->orWhere('telefono_casa', 'like', $like)
                    ->orWhere('correo_electronico', 'like', $like);
            })
            ->withCount('relacionesActivas')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(8)
            ->get()
            ->map(fn (Tutor $tutor): array => [
                'id' => $tutor->id,
                'nombre' => $tutor->nombre_completo,
                'curp' => $tutor->identidad_protegida,
                'telefono' => $tutor->telefono_celular ?: $tutor->telefono_casa,
                'correo' => $tutor->correo_electronico,
                'relaciones' => (int) $tutor->relaciones_activas_count,
            ])
            ->all();
    }

    public function agregarResponsable(int $tutorId): void
    {
        if (collect($this->responsables)->contains(fn (array $item): bool => (int) $item['tutor_id'] === $tutorId)) {
            $this->responsablesMensaje = 'Ese responsable ya fue agregado al alumno.';
            return;
        }

        $tutor = Tutor::query()->activos()->findOrFail($tutorId);
        $primero = $this->responsables === [];
        $this->responsables[] = [
            'tutor_id' => $tutor->id,
            'nombre' => $tutor->nombre_completo,
            'curp' => $tutor->identidad_protegida,
            'telefono' => $tutor->telefono_celular ?: $tutor->telefono_casa,
            'correo' => $tutor->correo_electronico,
            'parentesco' => 'OTRO',
            'es_principal' => $primero,
            'es_tutor_legal' => false,
            'estado_tutela' => 'no_aplica',
            'vive_con_alumno' => false,
            'recibe_avisos' => true,
            'recibe_calificaciones' => true,
            'contacto_emergencia' => false,
            'autorizado_recoger' => false,
            'responsable_economico' => false,
            'observaciones' => null,
        ];

        $this->buscarTutor = '';
        $this->resultadosTutores = [];
        $this->responsablesMensaje = 'Responsable agregado. Configura su parentesco y funciones.';
        $this->resetValidation('responsables');
    }

    public function quitarResponsable(int $indice): void
    {
        if (! isset($this->responsables[$indice])) {
            return;
        }

        $eraPrincipal = (bool) ($this->responsables[$indice]['es_principal'] ?? false);
        array_splice($this->responsables, $indice, 1);

        if ($eraPrincipal && $this->responsables !== []) {
            $this->responsables[0]['es_principal'] = true;
        }

        $this->responsables = array_values($this->responsables);
        $this->resetValidation('responsables');
    }

    public function establecerPrincipalBorrador(int $indice): void
    {
        if (! isset($this->responsables[$indice])) {
            return;
        }

        foreach ($this->responsables as $i => $responsable) {
            $this->responsables[$i]['es_principal'] = $i === $indice;
        }
    }

    public function usarDomicilioResponsable(int $indice, string $modo = 'vacios'): void
    {
        $responsable = $this->responsables[$indice] ?? null;

        if (! $responsable) {
            return;
        }

        $tutor = Tutor::query()->find((int) $responsable['tutor_id']);

        if (! $tutor) {
            return;
        }

        $mapeo = [
            'calle' => $tutor->calle,
            'numero_exterior' => $tutor->numero,
            'colonia' => $tutor->colonia,
            'codigo_postal' => $tutor->codigo_postal,
            'municipio' => $tutor->municipio,
            'estado_residencia' => $tutor->estado,
            'ciudad_residencia' => $tutor->ciudad,
        ];

        $copiados = 0;
        $conservados = 0;

        foreach ($mapeo as $campo => $valor) {
            if (blank($valor)) {
                continue;
            }

            if ($modo === 'reemplazar' || blank($this->{$campo})) {
                $this->{$campo} = $valor;
                $copiados++;
            } else {
                $conservados++;
            }
        }

        $this->responsables[$indice]['vive_con_alumno'] = true;
        $this->direccionTutorAdvertencia = "Se copiaron {$copiados} datos del domicilio. Se conservaron {$conservados} campos ya capturados.";
    }

    public function updatedNuevoTutor(mixed $value, string $key): void
    {
        if ($key === 'sin_curp') {
            if ((bool) $value) {
                $this->nuevoTutor['curp'] = '';
            } else {
                $this->nuevoTutor['identificador_alternativo'] = '';
                $this->nuevoTutor['motivo_sin_curp'] = '';
            }

            $this->resetValidation([
                'nuevoTutor.curp',
                'nuevoTutor.identificador_alternativo',
                'nuevoTutor.motivo_sin_curp',
            ]);
            return;
        }

        if ($key !== 'curp') {
            return;
        }

        $this->nuevoTutor['curp'] = mb_strtoupper(
            preg_replace('/[^A-Z0-9]/i', '', (string) $value) ?: ''
        );

        if (($this->nuevoTutor['sin_curp'] ?? false) || blank($this->nuevoTutor['curp'])) {
            $this->resetValidation('nuevoTutor.curp');
            return;
        }

        $this->validateOnly('nuevoTutor.curp', [
            'nuevoTutor.curp' => ['required', 'string', 'size:18', new CurpMexicana(), 'unique:tutores,curp'],
        ]);
    }

    public function crearTutorBorrador(): void
    {
        $this->normalizarNuevoTutor();
        $sinCurp = (bool) ($this->nuevoTutor['sin_curp'] ?? false);

        $this->validate([
            'nuevoTutor.sin_curp' => ['boolean'],
            'nuevoTutor.curp' => [Rule::requiredIf(! $sinCurp), 'nullable', 'string', 'size:18', new CurpMexicana(), 'unique:tutores,curp'],
            'nuevoTutor.identificador_alternativo' => [Rule::requiredIf($sinCurp), 'nullable', 'string', 'max:80', 'unique:tutores,identificador_alternativo'],
            'nuevoTutor.motivo_sin_curp' => [Rule::requiredIf($sinCurp), 'nullable', 'string', 'min:5', 'max:255'],
            'nuevoTutor.nombre' => ['required', 'string', 'max:255'],
            'nuevoTutor.apellido_paterno' => ['required', 'string', 'max:255'],
            'nuevoTutor.apellido_materno' => ['nullable', 'string', 'max:255'],
            'nuevoTutor.genero' => ['nullable', Rule::in(['M', 'F', 'O'])],
            'nuevoTutor.fecha_nacimiento' => ['nullable', 'date'],
            'nuevoTutor.telefono_celular' => ['nullable', 'string', 'max:20'],
            'nuevoTutor.telefono_casa' => ['nullable', 'string', 'max:20'],
            'nuevoTutor.correo_electronico' => ['nullable', 'email', 'max:255'],
            'nuevoTutor.codigo_postal' => ['nullable', 'regex:/^[0-9]{5}$/'],
        ]);

        if (blank($this->nuevoTutor['telefono_celular']) && blank($this->nuevoTutor['telefono_casa']) && blank($this->nuevoTutor['correo_electronico'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'nuevoTutor.telefono_celular' => 'Captura al menos teléfono celular, teléfono de casa o correo.',
            ]);
        }

        $tutor = DB::transaction(function (): Tutor {
            return Tutor::query()->create([
                'curp' => blank($this->nuevoTutor['curp']) ? null : $this->nuevoTutor['curp'],
                'identificador_alternativo' => blank($this->nuevoTutor['identificador_alternativo']) ? null : $this->nuevoTutor['identificador_alternativo'],
                'motivo_sin_curp' => blank($this->nuevoTutor['motivo_sin_curp']) ? null : $this->nuevoTutor['motivo_sin_curp'],
                'parentesco' => 'NO ESPECIFICADO',
                'nombre' => $this->nuevoTutor['nombre'],
                'apellido_paterno' => $this->nuevoTutor['apellido_paterno'],
                'apellido_materno' => blank($this->nuevoTutor['apellido_materno']) ? null : $this->nuevoTutor['apellido_materno'],
                'genero' => blank($this->nuevoTutor['genero']) ? null : $this->nuevoTutor['genero'],
                'fecha_nacimiento' => blank($this->nuevoTutor['fecha_nacimiento']) ? null : $this->nuevoTutor['fecha_nacimiento'],
                'telefono_celular' => blank($this->nuevoTutor['telefono_celular']) ? null : $this->nuevoTutor['telefono_celular'],
                'telefono_casa' => blank($this->nuevoTutor['telefono_casa']) ? null : $this->nuevoTutor['telefono_casa'],
                'correo_electronico' => blank($this->nuevoTutor['correo_electronico']) ? null : $this->nuevoTutor['correo_electronico'],
                'calle' => blank($this->nuevoTutor['calle']) ? null : $this->nuevoTutor['calle'],
                'numero' => blank($this->nuevoTutor['numero']) ? null : $this->nuevoTutor['numero'],
                'colonia' => blank($this->nuevoTutor['colonia']) ? null : $this->nuevoTutor['colonia'],
                'codigo_postal' => blank($this->nuevoTutor['codigo_postal']) ? null : $this->nuevoTutor['codigo_postal'],
                'municipio' => blank($this->nuevoTutor['municipio']) ? null : $this->nuevoTutor['municipio'],
                'estado' => blank($this->nuevoTutor['estado']) ? null : $this->nuevoTutor['estado'],
                'ciudad' => blank($this->nuevoTutor['ciudad']) ? null : $this->nuevoTutor['ciudad'],
                'activo' => true,
            ]);
        });

        $this->dispatch('tutorRegistered', tutor: $tutor->id);
        $this->resetNuevoTutor();
        $this->mostrarNuevoTutor = false;
        $this->agregarResponsable($tutor->id);
    }

    public function resetNuevoTutor(): void
    {
        $this->nuevoTutor = [
            'sin_curp' => false,
            'curp' => '',
            'identificador_alternativo' => '',
            'motivo_sin_curp' => '',
            'nombre' => '',
            'apellido_paterno' => '',
            'apellido_materno' => '',
            'genero' => '',
            'fecha_nacimiento' => '',
            'telefono_celular' => '',
            'telefono_casa' => '',
            'correo_electronico' => '',
            'calle' => '',
            'numero' => '',
            'colonia' => '',
            'codigo_postal' => '',
            'municipio' => '',
            'estado' => '',
            'ciudad' => '',
        ];
        $this->resetValidation('nuevoTutor');
    }

    private function normalizarNuevoTutor(): void
    {
        foreach ($this->nuevoTutor as $campo => $valor) {
            if (is_string($valor)) {
                $this->nuevoTutor[$campo] = trim($valor);
            }
        }

        $this->nuevoTutor['curp'] = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->nuevoTutor['curp']) ?: '');
        $this->nuevoTutor['identificador_alternativo'] = mb_strtoupper((string) $this->nuevoTutor['identificador_alternativo']);

        if ($this->nuevoTutor['sin_curp'] ?? false) {
            $this->nuevoTutor['curp'] = '';
        } else {
            $this->nuevoTutor['identificador_alternativo'] = '';
            $this->nuevoTutor['motivo_sin_curp'] = '';
        }

        $this->nuevoTutor['nombre'] = $this->titleCaseNombre($this->nuevoTutor['nombre']);
        $this->nuevoTutor['apellido_paterno'] = $this->titleCaseNombre($this->nuevoTutor['apellido_paterno']);
        $this->nuevoTutor['apellido_materno'] = $this->titleCaseNombre($this->nuevoTutor['apellido_materno']);
    }

    private function validarResponsablesAntesDeGuardar(): void
    {
        $this->puedeGestionarResponsablesSensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');

        if (! $this->puedeGestionarResponsablesSensibles) {
            foreach ($this->responsables as $indice => $responsable) {
                $this->responsables[$indice]['es_tutor_legal'] = false;
                $this->responsables[$indice]['estado_tutela'] = 'no_aplica';
                $this->responsables[$indice]['recibe_calificaciones'] = false;
                $this->responsables[$indice]['autorizado_recoger'] = false;
            }
        }

        $servicio = app(GestionResponsablesAlumnoService::class);
        $normalizados = $servicio->normalizarLista($this->responsables);
        $this->responsables = array_map(function (array $item): array {
            $actual = collect($this->responsables)->firstWhere('tutor_id', $item['tutor_id']) ?: [];
            return [...$actual, ...$item];
        }, $normalizados);

        $esMenor = $servicio->esMenor($this->fecha_nacimiento, $this->fecha_inscripcion);

        if ($esMenor && $this->responsables === []) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'responsables' => 'Un alumno menor de edad debe tener al menos un responsable.',
            ]);
        }

        if ($this->responsables !== [] && collect($this->responsables)->where('es_principal', true)->count() !== 1) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'responsables' => 'Selecciona exactamente un contacto principal.',
            ]);
        }
    }

    protected function baseGrupoQuery()
    {
        $query = Grupo::query();

        if (Schema::hasColumn('grupos', 'deleted_at')) {
            $query->whereNull('grupos.deleted_at');
        }

        return $query;
    }

    protected function loadNiveles(): Collection
    {
        return Nivel::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug', 'color']);
    }

    protected function loadGrados(): Collection
    {
        if (!$this->nivel_id || $this->esBachillerato) {
            return collect();
        }

        return Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);
    }

    protected function loadSemestres(): Collection
    {
        if (!$this->esBachillerato || !$this->nivel_id) {
            return collect();
        }

        return Semestre::query()
            ->whereHas('grado', fn ($query) => $query->where('nivel_id', $this->nivel_id))
            ->with('grado:id,nivel_id,nombre,orden')
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global']);
    }

    protected function loadGruposOptionsFromGrupos(): array
    {
        if (!$this->ciclo_escolar_id || !$this->nivel_id || !$this->generacion_id || !$this->grado_id) {
            return [];
        }

        return app(AsignacionEscolarService::class)
            ->gruposCompatibles(
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                nivelId: (int) $this->nivel_id,
                generacionId: (int) $this->generacion_id,
                gradoId: (int) $this->grado_id,
                semestreId: $this->semestre_id ? (int) $this->semestre_id : null,
            )
            ->values()
            ->all();
    }

    protected function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return 'Sin grupo';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    protected function loadCiclos(): Collection
    {
        return Ciclo::query()
            ->orderBy('id', 'asc')
            ->get(['id', 'ciclo']);
    }

    protected function loadCicloEscolares(): Collection
    {
        return CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);
    }

    public function updatedCicloEscolarId($value): void
    {
        $this->ciclo_escolar_id = $value ? (int) $value : null;
        $this->reiniciarAsignacionDependiente(false);
        $this->normalizarTipoIngresoPorCiclo();
    }

    public function updatedCicloId($value): void
    {
        $this->ciclo_id = $value ? (int) $value : null;

        if ($this->esBachillerato) {
            $this->proponerSemestreBachillerato();
        }
    }

    public function updatedTipoIngreso(string $value): void
    {
        $this->tipo_ingreso = $value;

        if ($value !== 'captura_historica') {
            $this->motivo_captura_historica = null;
            $this->resetValidation('motivo_captura_historica');
        }

        if ($this->esBachillerato) {
            $this->proponerSemestreBachillerato();
        } elseif ($this->grado_id) {
            $this->resolverGeneracionAutomatica();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;
        $this->reiniciarAsignacionDependiente(true);

        $nivel = $this->nivel_id
            ? $this->niveles->firstWhere('id', $this->nivel_id)
                ?: Nivel::query()->find($this->nivel_id)
            : null;

        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        if (!$nivel) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if ($this->esBachillerato) {
            $this->semestresOptions = $this->loadSemestres();
            $this->proponerSemestreBachillerato();
        } else {
            $this->gradosOptions = $this->loadGrados();
        }

        $this->refrescarMatriculaSiPosible();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value ? (int) $value : null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->generacionesOptions = collect();
        $this->gruposOptions = [];
        $this->generacionAutomaticaLabel = null;
        $this->asignacionAdvertencia = null;
        $this->resetValidation(['grado_id', 'generacion_id', 'grupo_id']);

        if (!$this->esBachillerato && $this->grado_id) {
            $this->resolverGeneracionAutomatica();
        }

        $this->refrescarMatriculaSiPosible();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;
        $this->generacion_id = null;
        $this->grupo_id = null;
        $this->generacionesOptions = collect();
        $this->gruposOptions = [];
        $this->generacionAutomaticaLabel = null;
        $this->asignacionAdvertencia = null;
        $this->resetValidation(['semestre_id', 'generacion_id', 'grupo_id']);

        if ($this->esBachillerato && $this->semestre_id) {
            $semestre = Semestre::query()->find($this->semestre_id);
            $this->grado_id = $semestre?->grado_id ? (int) $semestre->grado_id : null;
            $this->resolverGeneracionAutomatica();
        }

        $this->refrescarMatriculaSiPosible();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;
        $this->resetValidation('grupo_id');
        $this->refrescarMatriculaSiPosible();
    }

    private function reiniciarAsignacionDependiente(bool $conservarNivel): void
    {
        if (!$conservarNivel) {
            $this->nivel_id = null;
            $this->esBachillerato = false;
        }

        $this->grado_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];
        $this->generacionAutomaticaLabel = null;
        $this->asignacionAdvertencia = null;
        $this->resetValidation([
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_id',
        ]);
    }

    private function normalizarTipoIngresoPorCiclo(): void
    {
        if (!$this->ciclo_escolar_id) {
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);

        if ($ciclo?->cerrado_at && $this->tipo_ingreso !== 'captura_historica') {
            $this->tipo_ingreso = 'captura_historica';
            $this->asignacionAdvertencia = 'El ciclo seleccionado está cerrado. El registro se realizará como captura histórica.';
        }
    }

    private function proponerSemestreBachillerato(): void
    {
        if (!$this->esBachillerato || !$this->ciclo_escolar_id) {
            return;
        }

        $this->semestresOptions = $this->loadSemestres();

        if ($this->tipo_ingreso === 'nuevo_ingreso') {
            $numero = (int) $this->ciclo_id === 1 ? 1 : 2;
            $semestre = $this->semestresOptions->firstWhere('numero', $numero);

            $this->semestre_id = $semestre?->id ? (int) $semestre->id : null;
            $this->grado_id = $semestre?->grado_id ? (int) $semestre->grado_id : null;
        }

        if ($this->semestre_id) {
            $this->resolverGeneracionAutomatica();
        }
    }

    private function resolverGeneracionAutomatica(): void
    {
        $this->generacion_id = null;
        $this->generacionesOptions = collect();
        $this->grupo_id = null;
        $this->gruposOptions = [];
        $this->generacionAutomaticaLabel = null;
        $this->asignacionAdvertencia = null;

        if (!$this->ciclo_escolar_id || !$this->nivel_id || !$this->grado_id) {
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);
        $nivel = Nivel::query()->find($this->nivel_id);
        $grado = Grado::query()->find($this->grado_id);
        $semestre = $this->semestre_id
            ? Semestre::query()->find($this->semestre_id)
            : null;

        if (!$ciclo || !$nivel || !$grado) {
            return;
        }

        $servicio = app(AsignacionEscolarService::class);
        $this->generacionAutomaticaLabel = $servicio->etiquetaGeneracionEsperada(
            $ciclo,
            $nivel,
            $grado,
            $semestre,
        );

        $generacion = $servicio->resolverGeneracion(
            $ciclo,
            $nivel,
            $grado,
            $semestre,
            null,
            $this->tipo_ingreso,
        );

        if (!$generacion) {
            $this->asignacionAdvertencia = 'No existe la generación ' . $this->generacionAutomaticaLabel
                . ' para esta combinación. Créala desde Grupos o prepara el ciclo escolar.';
            return;
        }

        $this->generacion_id = (int) $generacion->id;
        $this->generacionesOptions = collect([$generacion]);
        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();

        if (empty($this->gruposOptions)) {
            $this->asignacionAdvertencia = 'No existe un grupo activo compatible con el ciclo, nivel, grado y generación calculados.';
        }
    }

    public function updated($property): void
    {
        $this->sanitizeStrings();

        if (
            $property === 'foto'
            || $property === 'curp'
            || $property === 'archivoAlumnos'
            || $property === 'buscarTutor'
            || $property === 'mostrarNuevoTutor'
            || str_starts_with($property, 'responsables.')
            || str_starts_with($property, 'nuevoTutor.')
        ) {
            return;
        }

        if (
            in_array($property, [
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
                'grupo_id',
                'ciclo_escolar_id',
                'ciclo_id',
                'tipo_ingreso',
                'estado_inscripcion',
                'motivo_captura_historica',
            ], true)
        ) {
            return;
        }

        $this->validateOnly($property);
    }

    public function quitarFotoTemporal(): void
    {
        $this->reset('foto');
        $this->dispatch('foto-limpiada');
    }

    protected function validarRelacionAcademica(array &$data): bool
    {
        try {
            $grupo = app(AsignacionEscolarService::class)->validarAsignacion($data);
            $data['grado_id'] = (int) $grupo->grado_id;
            $data['semestre_id'] = $grupo->semestre_id ? (int) $grupo->semestre_id : null;

            return true;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            foreach ($exception->errors() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->addError($campo, $mensaje);
                }
            }

            return false;
        }
    }

    public function guardar(
        ImagenPersonalService $imagenes,
        ObservacionInscripcionService $observacionesService,
        MatriculaAlumnoService $matriculas,
        GestionAcademicaService $gestionAcademica,
        GestionResponsablesAlumnoService $responsablesService,
        \App\Services\HistorialCicloEscolarService $historialCiclos,
    ): void
    {
        if ($this->guardandoInscripcion) {
            return;
        }

        $this->guardandoInscripcion = true;
        $this->sanitizeStrings();
        $this->observaciones = $observacionesService->sanitizar($this->observaciones);

        $resultadoCurp = app(CurpLocalLookupService::class)->validarFormato($this->curp);

        if (! $resultadoCurp['valida']) {
            $this->guardandoInscripcion = false;
            $this->addError('curp', $resultadoCurp['mensaje']);
            $this->dispatch('inscripcion-ir-paso', paso: 1);
            return;
        }

        $this->validarCurpLocal();

        if (! $this->curpLocalValidada || $this->curpExisteLocal) {
            $this->guardandoInscripcion = false;
            $this->addError('curp', $this->curpExisteLocal
                ? 'La CURP ya está registrada. No se creó una inscripción duplicada.'
                : 'No fue posible confirmar la CURP en la base local.');
            $this->dispatch('inscripcion-ir-paso', paso: 1);
            return;
        }

        if (!$this->matriculaEditadaManual || trim($this->matricula) === '') {
            $this->refrescarMatriculaSiPosible();
        }

        $this->normalizarTipoIngresoPorCiclo();

        try {
            $data = $this->validate();
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->guardandoInscripcion = false;
            $this->dispatch('inscripcion-ir-primer-error', campos: array_keys($exception->errors()));
            throw $exception;
        }

        if ($data['tipo_ingreso'] === 'captura_historica') {
            abort_unless(auth()->user()?->canAccess('academico.editar'), 403);
        }

        try {
            $this->validarResponsablesAntesDeGuardar();
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->guardandoInscripcion = false;
            foreach ($exception->errors() as $campo => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    $this->addError($campo, $mensaje);
                }
            }
            $this->dispatch('inscripcion-ir-paso', paso: 3);
            return;
        }

        if (!$this->validarRelacionAcademica($data)) {
            $this->guardandoInscripcion = false;
            $this->dispatch('inscripcion-ir-paso', paso: 2);
            return;
        }

        $fotoPath = null;

        try {
            if ($this->foto) {
                $fotoPath = $imagenes->guardar($this->foto, 'inscripciones/fotos', 1200, false);
            }

            DB::transaction(function () use ($data, $fotoPath, $observacionesService, $matriculas, $gestionAcademica, $responsablesService, $historialCiclos) {
                $curpDuplicada = Inscripcion::withTrashed()
                    ->where('curp', $data['curp'])
                    ->lockForUpdate()
                    ->exists();

                if ($curpDuplicada) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'curp' => 'La CURP fue registrada por otro usuario antes de guardar. Recarga el formulario.',
                    ]);
                }

                $matriculaDuplicada = Inscripcion::withTrashed()
                    ->where('matricula', $data['matricula'])
                    ->lockForUpdate()
                    ->exists();

                if ($matriculaDuplicada) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'matricula' => 'La matrícula ya fue utilizada. Genera una nueva e intenta nuevamente.',
                    ]);
                }

                $inscripcion = Inscripcion::query()->create([
                    'curp' => $data['curp'],
                    'matricula' => $data['matricula'],
                    'folio' => $data['folio'] ?? null,

                    'nombre' => $data['nombre'],
                    'apellido_paterno' => $data['apellido_paterno'],
                    'apellido_materno' => $data['apellido_materno'] ?? null,
                    'fecha_nacimiento' => $data['fecha_nacimiento'],
                    'genero' => $data['genero'],

                    'fecha_inscripcion' => $data['fecha_inscripcion'],
                    'ciclo_id' => (int) $data['ciclo_id'],
                    'ciclo_escolar_id' => (int) $data['ciclo_escolar_id'],

                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'observaciones_baja' => null,

                    'pais_nacimiento' => $data['pais_nacimiento'] ?? null,
                    'estado_nacimiento' => $data['estado_nacimiento'] ?? null,
                    'lugar_nacimiento' => $data['lugar_nacimiento'] ?? null,

                    'calle' => $data['calle'] ?? null,
                    'numero_exterior' => $data['numero_exterior'] ?? null,
                    'numero_interior' => $data['numero_interior'] ?? null,
                    'colonia' => $data['colonia'] ?? null,
                    'codigo_postal' => $data['codigo_postal'] ?? null,
                    'municipio' => $data['municipio'] ?? null,
                    'estado_residencia' => $data['estado_residencia'] ?? null,
                    'ciudad_residencia' => $data['ciudad_residencia'] ?? null,

                    'nivel_id' => (int) $data['nivel_id'],
                    'grado_id' => (int) $data['grado_id'],
                    'generacion_id' => (int) $data['generacion_id'],
                    'semestre_id' => ! empty($data['semestre_id']) ? (int) $data['semestre_id'] : null,
                    'grupo_id' => (int) $data['grupo_id'],

                    'foto_path' => $fotoPath,
                    'tutor_id' => collect($this->responsables)->firstWhere('es_principal', true)['tutor_id'] ?? null,
                    'activo' => $data['estado_inscripcion'] === 'inscrito',
                    'estatus' => $data['estado_inscripcion'] === 'inscrito' ? 'activo' : 'preinscrito',
                    'motivo_estatus' => $data['tipo_ingreso'] === 'captura_historica'
                        ? $data['motivo_captura_historica']
                        : null,
                    'tipo_ultimo_ingreso' => $data['tipo_ingreso'],
                    'fecha_ultimo_ingreso' => $data['fecha_inscripcion'],
                    'usuario_acceso_activo' => $data['estado_inscripcion'] === 'inscrito',
                    'fecha_estatus' => $data['fecha_inscripcion'],
                ]);

                $responsablesService->sincronizar(
                    $inscripcion,
                    $this->responsables,
                    auth()->id(),
                    $data['fecha_inscripcion'],
                );

                $observacionesService->guardar(
                    inscripcion: $inscripcion,
                    cicloEscolarId: (int) $data['ciclo_escolar_id'],
                    contenido: $data['observaciones'] ?? null,
                    origen: 'registro',
                    usuarioId: auth()->id(),
                );

                if ($data['estado_inscripcion'] === 'preinscrito') {
                    $historialCiclos->registrarPreinscripcion(
                        $inscripcion,
                        auth()->id(),
                        $data['fecha_inscripcion'],
                    );
                }

                if ($data['estado_inscripcion'] === 'inscrito') {
                    $matriculas->asegurarVigente(
                        $inscripcion,
                        'inscripcion',
                        auth()->id(),
                        $data['fecha_inscripcion'],
                    );

                    $gestionAcademica->registrarInscripcionInicial(
                        $inscripcion,
                        'Inscripción activa registrada en el ciclo ' . (string) $data['ciclo_escolar_id'] . '.',
                        auth()->id(),
                        $data['fecha_inscripcion'],
                    );
                }
            });
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if ($fotoPath) {
                $imagenes->eliminarRuta($fotoPath);
            }

            $this->guardandoInscripcion = false;
            $this->dispatch('inscripcion-ir-primer-error', campos: array_keys($exception->errors()));
            throw $exception;
        } catch (QueryException $exception) {
            if ($fotoPath) {
                $imagenes->eliminarRuta($fotoPath);
            }

            $this->guardandoInscripcion = false;

            if ($this->esViolacionDeUnicidad($exception)) {
                $campo = str_contains(mb_strtolower($exception->getMessage()), 'curp')
                    ? 'curp'
                    : 'matricula';

                $this->addError(
                    $campo,
                    $campo === 'curp'
                        ? 'La CURP fue registrada por otro usuario antes de guardar. No se creó un duplicado.'
                        : 'La matrícula fue utilizada por otro usuario antes de guardar. Genera una nueva e intenta otra vez.'
                );
                $this->dispatch('inscripcion-ir-paso', paso: 1);
                return;
            }

            report($exception);
            $this->addError('formulario', 'No fue posible guardar la inscripción. No se realizaron cambios parciales.');
            return;
        } catch (\Throwable $exception) {
            if ($fotoPath) {
                $imagenes->eliminarRuta($fotoPath);
            }

            $this->guardandoInscripcion = false;
            report($exception);
            $this->addError('formulario', 'No fue posible guardar la inscripción. No se realizaron cambios parciales.');
            return;
        }

        $this->guardandoInscripcion = false;
        $this->dispatch('swal', [
            'title' => '¡Inscripción creada correctamente!',
            'text' => $data['matricula'] . ' · ' . $data['nombre'] . ' ' . $data['apellido_paterno'],
            'icon' => 'success',
            'position' => 'top-end',
        ]);
        $this->dispatch('inscripcion-guardada');

        $this->cancelar(true);
        $this->dispatch('refreshInscripciones');
    }

    public function descargarPlantillaAlumnos()
    {
        return Excel::download(
            new PlantillaInscripcionesExport(),
            'PLANTILLA_IMPORTAR_ALUMNOS.xlsx'
        );
    }

    public function exportarAlumnos()
    {
        return Excel::download(
            new InscripcionesExport(),
            'ALUMNOS_REGISTRADOS.xlsx'
        );
    }

    public function importarAlumnos(): void
    {
        $this->reset([
            'erroresImportacionAlumnos',
            'mensajeImportacionAlumnos',
            'errorImportacionAlumnos',
        ]);

        $this->validate([
            'archivoAlumnos' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:10240',
            ],
        ], [
            'archivoAlumnos.required' => 'Selecciona un archivo para importar.',
            'archivoAlumnos.file' => 'Selecciona un archivo válido.',
            'archivoAlumnos.mimes' => 'El archivo debe ser Excel o CSV.',
            'archivoAlumnos.max' => 'El archivo no debe superar 10MB.',
        ]);

        try {
            $import = new InscripcionesImport();

            Excel::import($import, $this->archivoAlumnos);

            $this->mensajeImportacionAlumnos = "Importación terminada. Creados: {$import->creados}. Actualizados: {$import->actualizados}.";

            $this->reset('archivoAlumnos');

            $this->dispatch('swal', [
                'title' => 'Importación terminada',
                'text' => $this->mensajeImportacionAlumnos,
                'icon' => 'success',
                'position' => 'top-end',
            ]);

            $this->dispatch('refreshInscripciones');
        } catch (ExcelValidationException $e) {
            $errores = [];

            foreach ($e->failures() as $failure) {
                $errores[] = [
                    'fila' => $failure->row(),
                    'campo' => $failure->attribute(),
                    'errores' => $failure->errors(),
                    'valor' => $failure->values()[$failure->attribute()] ?? null,
                ];
            }

            $this->erroresImportacionAlumnos = $errores;
            $this->errorImportacionAlumnos = 'El archivo contiene errores. Revisa las filas marcadas.';
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorImportacionAlumnos = collect($e->errors())
                ->flatten()
                ->first() ?: 'La importación contiene una identidad duplicada o incompatible.';
        } catch (\Throwable $e) {
            report($e);
            $this->errorImportacionAlumnos = 'No se pudo importar el archivo. No se realizaron cambios parciales.';
        }
    }

    public function limpiarArchivoAlumnos(): void
    {
        $this->reset([
            'archivoAlumnos',
            'erroresImportacionAlumnos',
            'mensajeImportacionAlumnos',
            'errorImportacionAlumnos',
        ]);

        $this->resetValidation('archivoAlumnos');
    }

    private function recargarOpcionesAsignacionEscolar(): void
    {
        $this->niveles = $this->loadNiveles();

        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        if (!$this->nivel_id) {
            $this->esBachillerato = false;
            return;
        }

        $nivel = $this->niveles->firstWhere('id', $this->nivel_id)
            ?: Nivel::query()->find($this->nivel_id);

        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        if ($this->esBachillerato) {
            $this->resolverGeneracionAutomatica();

            if ($this->generacion_id) {
                $this->semestresOptions = $this->loadSemestres();
            }

            if ($this->generacion_id && $this->semestre_id) {
                $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
            }

            return;
        }

        $this->gradosOptions = $this->loadGrados();

        if ($this->grado_id) {
            $this->resolverGeneracionAutomatica();
        }

        if ($this->grado_id && $this->generacion_id) {
            $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
        }
    }

    public function cancelar(bool $conservarAsignacionEscolar = false): void
    {
        /*
            Se guarda temporalmente la asignación escolar
            para conservarla después de registrar un alumno.
        */
        $asignacionEscolar = [
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'esBachillerato' => $this->esBachillerato,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
        ];

        $camposParaLimpiar = [
            'curp',
            'matricula',
            'folio',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'fecha_inscripcion',
            'ciclo_escolar_id',
            'ciclo_id',
            'tipo_ingreso',
            'estado_inscripcion',
            'motivo_captura_historica',
            'generacionAutomaticaLabel',
            'asignacionAdvertencia',
            'matriculaEditadaManual',

            'fecha_baja',
            'motivo_baja',
            'observaciones_baja',
            'observaciones',

            'pais_nacimiento',
            'estado_nacimiento',
            'lugar_nacimiento',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'codigo_postal',
            'municipio',
            'estado_residencia',
            'ciudad_residencia',

            'responsables',
            'buscarTutor',
            'resultadosTutores',
            'mostrarNuevoTutor',
            'responsablesMensaje',
            'nuevoTutor',
            'foto',
            'consultandoCurp',
            'curpError',
            'curpAdvertencia',
            'curpSuccess',
            'ultimaCurpConsultada',
            'curpEstado',
            'curpMensaje',
            'curpLocalValidada',
            'curpExisteLocal',
            'ultimaCurpValidadaLocal',
            'alumnoExistente',
            'curpDiferencias',
            'direccionTutorAdvertencia',
            'guardandoInscripcion',

            'archivoAlumnos',
            'erroresImportacionAlumnos',
            'mensajeImportacionAlumnos',
            'errorImportacionAlumnos',
        ];

        /*
            Si se presiona Cancelar manualmente, sí se limpia todo.
            Si viene después de Guardar, se conserva nivel, grado,
            generación, semestre y grupo.
        */
        if (!$conservarAsignacionEscolar) {
            $camposParaLimpiar = array_merge($camposParaLimpiar, [
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
                'grupo_id',
                'esBachillerato',
            ]);
        }

        $this->reset($camposParaLimpiar);
        $this->resetValidation();

        if ($conservarAsignacionEscolar) {
            $this->nivel_id = $asignacionEscolar['nivel_id'];
            $this->grado_id = $asignacionEscolar['grado_id'];
            $this->generacion_id = $asignacionEscolar['generacion_id'];
            $this->semestre_id = $asignacionEscolar['semestre_id'];
            $this->grupo_id = $asignacionEscolar['grupo_id'];
            $this->esBachillerato = $asignacionEscolar['esBachillerato'];
            $this->ciclo_escolar_id = $asignacionEscolar['ciclo_escolar_id'];
        }

        $this->recargarOpcionesAsignacionEscolar();

        $this->ciclosOptions = $this->loadCiclos();
        $this->cicloEscolaresOptions = $this->loadCicloEscolares();

        if (! $this->ciclo_escolar_id) {
            $this->ciclo_escolar_id = $this->cicloEscolaresOptions->firstWhere('es_actual', true)?->id
                ?: $this->cicloEscolaresOptions->first()?->id;
        }

        $this->fecha_inscripcion = now()->toDateString();
        $this->tipo_ingreso = 'nuevo_ingreso';
        $this->estado_inscripcion = 'inscrito';
        $this->normalizarTipoIngresoPorCiclo();
        $this->fecha_baja = null;
        $this->motivo_baja = null;
        $this->observaciones_baja = null;
        $this->matricula = '';

        $this->dispatch('foto-limpiada');
        $this->dispatch('reset-observaciones-editor', editor: 'observaciones-inscripcion-crear', contenido: '');
    }

    private function camposObligatoriosPendientes(): array
    {
        $esMenor = app(GestionResponsablesAlumnoService::class)
            ->esMenor($this->fecha_nacimiento, $this->fecha_inscripcion);
        $responsablesCompletos = ! $esMenor || (
            $this->responsables !== []
            && collect($this->responsables)->where('es_principal', true)->count() === 1
        );

        $campos = [
            'curp' => $this->curpLocalValidada && ! $this->curpExisteLocal,
            'matricula' => filled($this->matricula),
            'nombre' => filled($this->nombre),
            'apellido_paterno' => filled($this->apellido_paterno),
            'fecha_nacimiento' => filled($this->fecha_nacimiento),
            'genero' => filled($this->genero),
            'fecha_inscripcion' => filled($this->fecha_inscripcion),
            'ciclo_escolar_id' => filled($this->ciclo_escolar_id),
            'ciclo_id' => filled($this->ciclo_id),
            'tipo_ingreso' => filled($this->tipo_ingreso),
            'estado_inscripcion' => filled($this->estado_inscripcion),
            'motivo_captura_historica' => $this->tipo_ingreso !== 'captura_historica'
                || mb_strlen(trim((string) $this->motivo_captura_historica)) >= 10,
            'nivel_id' => filled($this->nivel_id),
            'ubicacion' => $this->esBachillerato ? filled($this->semestre_id) : filled($this->grado_id),
            'generacion_id' => filled($this->generacion_id),
            'grupo_id' => filled($this->grupo_id),
            'responsables' => $responsablesCompletos,
        ];

        return array_keys(array_filter($campos, fn (bool $completo) => ! $completo));
    }

    private function resumenInscripcion(): array
    {
        $nivel = $this->nivel_id ? $this->niveles->firstWhere('id', $this->nivel_id) : null;
        $grado = $this->grado_id ? $this->gradosOptions->firstWhere('id', $this->grado_id) : null;
        $semestre = $this->semestre_id ? $this->semestresOptions->firstWhere('id', $this->semestre_id) : null;
        $generacion = $this->generacion_id ? $this->generacionesOptions->firstWhere('id', $this->generacion_id) : null;
        $grupoSeleccionado = collect($this->gruposOptions)->firstWhere('id', $this->grupo_id);
        $ciclo = $this->ciclo_escolar_id
            ? $this->cicloEscolaresOptions->firstWhere('id', $this->ciclo_escolar_id)
            : null;
        $pendientes = $this->camposObligatoriosPendientes();

        return [
            'nombre' => trim(implode(' ', array_filter([$this->nombre, $this->apellido_paterno, $this->apellido_materno]))) ?: 'Alumno sin nombre',
            'curp' => $this->curp ?: 'Sin CURP',
            'matricula' => $this->matricula ?: 'Pendiente',
            'ciclo' => $ciclo ? $ciclo->inicio_anio . '-' . $ciclo->fin_anio : 'Pendiente',
            'nivel' => $nivel?->nombre ?: 'Pendiente',
            'ubicacion' => $semestre ? $semestre->numero . '° semestre' : ($grado?->nombre ?: 'Pendiente'),
            'generacion' => $generacion?->etiqueta ?: 'Pendiente',
            'grupo' => data_get($grupoSeleccionado, 'label')
                ?: data_get($grupoSeleccionado, 'clave')
                ?: 'Pendiente',
            'estado' => $this->estado_inscripcion === 'preinscrito' ? 'Preinscrito' : 'Inscrito y activo',
            'pendientes' => count($pendientes),
            'listo' => count($pendientes) === 0 && $this->curpEstado === 'disponible' && ! $this->curpExisteLocal,
        ];
    }

    private function esViolacionDeUnicidad(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($driverCode, [1062, 1555, 2067], true);
    }

    private function textoBotonGuardar(): string
    {
        return match (true) {
            $this->estado_inscripcion === 'preinscrito' => 'Guardar como preinscrito',
            $this->tipo_ingreso === 'captura_historica' => 'Registrar captura histórica',
            $this->tipo_ingreso === 'traslado' => 'Registrar ingreso por traslado',
            default => 'Registrar alumno e inscripción',
        };
    }

    public function render()
    {
        $resumen = $this->resumenInscripcion();

        return view('livewire.inscripcion.crear-inscripcion', [
            'niveles' => $this->niveles,
            'grados' => $this->gradosOptions,
            'generaciones' => $this->generacionesOptions,
            'semestres' => $this->semestresOptions,
            'grupos' => $this->gruposOptions,
            'esBachillerato' => $this->esBachillerato,
            'ciclos' => $this->ciclosOptions,
            'cicloEscolares' => $this->cicloEscolaresOptions,
            'resumenInscripcion' => $resumen,
            'puedeConsultarCurpExterna' => $this->curpLocalValidada
                && ! $this->curpExisteLocal
                && $this->curpEstado === 'disponible'
                && ! $this->consultandoCurp,
            'textoBotonGuardar' => $this->textoBotonGuardar(),
        ]);
    }
}
