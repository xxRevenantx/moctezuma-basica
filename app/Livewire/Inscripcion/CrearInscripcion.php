<?php

namespace App\Livewire\Inscripcion;

use App\Models\Ciclo;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\Tutor;
use App\Services\CurpService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearInscripcion extends Component
{
    use WithFileUploads;

    public bool $consultandoCurp = false;
    public ?string $curpError = null;
    public ?string $curpAdvertencia = null;
    public ?string $curpSuccess = null;
    public ?string $ultimaCurpConsultada = null;

    public string $curp = '';
    public string $matricula = '';
    public ?string $folio = null;

    public string $nombre = '';
    public string $apellido_paterno = '';
    public ?string $apellido_materno = null;
    public ?string $fecha_nacimiento = null;
    public ?string $genero = null;

    public ?string $fecha_inscripcion = null;
    public ?int $ciclo_id = null;

    public ?string $fecha_baja = null;
    public ?string $motivo_baja = null;
    public ?string $observaciones_baja = null;

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

    public ?int $tutor_id = null;
    public bool $copiar_direccion_tutor = false;

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
    public Collection $tutores;

    public function mount(): void
    {
        $this->niveles = $this->loadNivelesFromGrupos();
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];
        $this->ciclosOptions = $this->loadCiclos();
        $this->tutores = $this->loadTutores();

        $this->fecha_inscripcion = now()->toDateString();
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
                'max:18',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('inscripciones', 'curp'),
            ],
            'matricula' => [
                'required',
                'string',
                'max:50',
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
            'ciclo_id' => [
                'required',
                'integer',
                Rule::exists('ciclos', 'id'),
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

            'tutor_id' => [
                'nullable',
                'integer',
                Rule::exists('tutors', 'id'),
            ],
            'foto' => [
                'nullable',
                'image',
                'max:2048',
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'curp.required' => 'La CURP es obligatoria.',
            'curp.max' => 'La CURP no debe tener más de 18 caracteres.',
            'curp.regex' => 'La CURP solo debe contener letras y números.',
            'curp.unique' => 'Ya existe una inscripción con esta CURP.',

            'matricula.required' => 'La matrícula es obligatoria.',
            'matricula.unique' => 'La matrícula generada ya existe.',

            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',

            'fecha_inscripcion.required' => 'La fecha de inscripción es obligatoria.',
            'ciclo_id.required' => 'Selecciona un ciclo.',

            'fecha_baja.date' => 'La fecha de baja no es válida.',
            'motivo_baja.string' => 'El motivo de baja no es válido.',
            'motivo_baja.max' => 'El motivo de baja no debe superar 255 caracteres.',
            'observaciones_baja.string' => 'Las observaciones de baja no son válidas.',
            'observaciones_baja.max' => 'Las observaciones de baja no deben superar 255 caracteres.',

            'nivel_id.required' => 'Selecciona un nivel.',
            'grado_id.required' => 'Selecciona un grado.',
            'generacion_id.required' => 'Selecciona una generación.',
            'semestre_id.required' => 'Selecciona un semestre.',
            'grupo_id.required' => 'Selecciona un grupo.',

            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'tutor_id.exists' => 'El tutor seleccionado no es válido.',
            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe exceder 2MB.',
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

    public function updatedCurp(string $value): void
    {
        $this->curp = mb_strtoupper(trim($value));
        $this->curpError = null;
        $this->curpAdvertencia = null;
        $this->curpSuccess = null;

        $this->limpiarDatosCurpSiIncompleta();

        if ($this->curp === '') {
            $this->ultimaCurpConsultada = null;
            $this->matricula = '';
            return;
        }

        if (!preg_match('/^[A-Z0-9]+$/', $this->curp)) {
            $this->curpError = 'La CURP solo debe contener letras y números.';
            $this->matricula = '';
            return;
        }

        if (strlen($this->curp) < 18) {
            $this->ultimaCurpConsultada = null;

            $this->curpAdvertencia = 'La CURP tiene menos de 18 caracteres. No se consultó en RENAPO, pero puedes continuar con la inscripción llenando los datos manualmente.';

            $this->refrescarMatriculaSiPosible();
            $this->resetValidation('curp');

            return;
        }

        if (strlen($this->curp) > 18) {
            $this->curpError = 'La CURP no debe tener más de 18 caracteres.';
            $this->matricula = '';
            return;
        }

        if ($this->ultimaCurpConsultada === $this->curp) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        $this->consultarCurp();
    }

    public function consultarCurp(): void
    {
        $this->curp = mb_strtoupper(trim($this->curp));
        $this->curpError = null;
        $this->curpAdvertencia = null;
        $this->curpSuccess = null;

        if ($this->curp === '') {
            $this->curpError = 'La CURP es obligatoria.';
            $this->matricula = '';
            return;
        }

        if (!preg_match('/^[A-Z0-9]+$/', $this->curp)) {
            $this->curpError = 'La CURP solo debe contener letras y números.';
            $this->matricula = '';
            return;
        }

        if (strlen($this->curp) < 18) {
            $this->curpAdvertencia = 'La CURP tiene menos de 18 caracteres. No se consultó en RENAPO, pero puedes continuar con la inscripción llenando los datos manualmente.';

            $this->ultimaCurpConsultada = null;
            $this->refrescarMatriculaSiPosible();
            $this->resetValidation('curp');

            return;
        }

        if (strlen($this->curp) > 18) {
            $this->curpError = 'La CURP no debe tener más de 18 caracteres.';
            $this->matricula = '';
            return;
        }

        $this->ultimaCurpConsultada = $this->curp;
        $this->consultandoCurp = true;

        /** @var CurpService $curpService */
        $curpService = app(CurpService::class);

        try {
            $payload = $curpService->obtenerDatosPorCurp($this->curp);
        } catch (\Throwable $e) {
            $this->consultandoCurp = false;

            $this->curpAdvertencia = 'No se pudo consultar la CURP en RENAPO. Puedes continuar con la inscripción llenando los datos manualmente.';

            $this->refrescarMatriculaSiPosible();
            $this->resetValidation('curp');

            return;
        }

        $this->consultandoCurp = false;

        if (($payload['error'] ?? true) === true) {
            $this->curpAdvertencia = 'La CURP no existe en RENAPO o no se pudieron obtener sus datos. Puedes continuar con la inscripción llenando los datos manualmente.';

            $this->refrescarMatriculaSiPosible();
            $this->resetValidation('curp');

            return;
        }

        $this->llenarDatosDesdePayloadCurp($payload);

        $this->sanitizeStrings();
        $this->refrescarMatriculaSiPosible();

        $this->curpSuccess = 'La CURP se cargó correctamente y se encuentra registrada en RENAPO.';
        $this->dispatch('ocultar-curp-success');

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

    protected function limpiarDatosCurpSiIncompleta(): void
    {
        if (strlen($this->curp) === 18) {
            return;
        }

        $this->curpError = null;
    }

    protected function llenarDatosDesdePayloadCurp(array $payload): void
    {
        $datos = $payload['datos'] ?? null;

        if (is_array($datos)) {
            $this->nombre = $this->titleCaseNombre($datos['nombre'] ?? $this->nombre);
            $this->apellido_paterno = $this->titleCaseNombre($datos['apellido_paterno'] ?? $this->apellido_paterno);

            $apellidoMaterno = $datos['apellido_materno'] ?? null;
            $this->apellido_materno = $apellidoMaterno ? $this->titleCaseNombre($apellidoMaterno) : null;

            if (!empty($datos['fecha_nacimiento'])) {
                $this->fecha_nacimiento = $datos['fecha_nacimiento'];
            }

            $genero = mb_strtoupper((string) ($datos['genero'] ?? $datos['clave_sexo'] ?? ''));

            if (in_array($genero, ['H', 'M'], true)) {
                $this->genero = $genero;
            }

            $this->pais_nacimiento = $datos['pais_nacimiento'] ?? $this->pais_nacimiento;
            $this->estado_nacimiento = $datos['estado_nacimiento'] ?? $this->estado_nacimiento;
            $this->lugar_nacimiento = $datos['lugar_nacimiento'] ?? $this->lugar_nacimiento;

            return;
        }

        $solicitante = data_get($payload, 'response.Solicitante');

        if (!$solicitante || !is_array($solicitante)) {
            $this->curpAdvertencia = 'No se pudieron obtener los datos de la CURP. Puedes continuar llenando los datos manualmente.';
            return;
        }

        $this->nombre = $this->titleCaseNombre((string) data_get($solicitante, 'Nombres', $this->nombre));
        $this->apellido_paterno = $this->titleCaseNombre((string) data_get($solicitante, 'ApellidoPaterno', $this->apellido_paterno));

        $apellidoMaterno = data_get($solicitante, 'ApellidoMaterno');
        $this->apellido_materno = $apellidoMaterno ? $this->titleCaseNombre((string) $apellidoMaterno) : null;

        $fechaApi = data_get($solicitante, 'FechaNacimiento');

        if (!empty($fechaApi)) {
            $this->fecha_nacimiento = $fechaApi;
        }

        $sexo = mb_strtoupper((string) data_get($solicitante, 'ClaveSexo', ''));

        if (in_array($sexo, ['H', 'M'], true)) {
            $this->genero = $sexo;
        }

        $this->pais_nacimiento = data_get($solicitante, 'Nacionalidad') ?: $this->pais_nacimiento;
        $this->estado_nacimiento = data_get($solicitante, 'EntidadNacimiento') ?: $this->estado_nacimiento;
        $this->lugar_nacimiento = data_get($solicitante, 'EntidadNacimiento') ?: $this->lugar_nacimiento;
    }

    protected function loadTutores(): Collection
    {
        return Tutor::query()
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->get([
                'id',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'calle',
                'numero',
                'colonia',
                'codigo_postal',
                'municipio',
                'estado',
                'ciudad',
            ]);
    }

    protected function llenarDireccionDesdeTutor(): void
    {
        if (!$this->tutor_id) {
            return;
        }

        $tutor = Tutor::query()->find($this->tutor_id);

        if (!$tutor) {
            return;
        }

        $this->calle = $tutor->calle ?: $this->calle;
        $this->numero_exterior = $tutor->numero ?: $this->numero_exterior;
        $this->colonia = $tutor->colonia ?: $this->colonia;
        $this->codigo_postal = $tutor->codigo_postal ?: $this->codigo_postal;
        $this->municipio = $tutor->municipio ?: $this->municipio;
        $this->estado_residencia = $tutor->estado ?: $this->estado_residencia;
        $this->ciudad_residencia = $tutor->ciudad ?: $this->ciudad_residencia;

        $this->sanitizeStrings();
    }

    public function updatedTutorId($value): void
    {
        $this->tutor_id = $value ? (int) $value : null;

        if (!$this->tutor_id) {
            $this->copiar_direccion_tutor = false;
            return;
        }

        if ($this->copiar_direccion_tutor) {
            $this->llenarDireccionDesdeTutor();
        }
    }

    public function updatedCopiarDireccionTutor($value): void
    {
        $this->copiar_direccion_tutor = (bool) $value;

        if ($this->copiar_direccion_tutor && $this->tutor_id) {
            $this->llenarDireccionDesdeTutor();
        }
    }

    protected function baseGrupoQuery()
    {
        return Grupo::query()->whereNull('deleted_at');
    }

    protected function loadNivelesFromGrupos(): Collection
    {
        $nivelIds = $this->baseGrupoQuery()
            ->select('nivel_id')
            ->distinct()
            ->pluck('nivel_id')
            ->filter()
            ->values();

        if ($nivelIds->isEmpty()) {
            return collect();
        }

        return Nivel::query()
            ->whereIn('id', $nivelIds)
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug', 'color']);
    }

    protected function loadGradosFromGrupos(): Collection
    {
        if (!$this->nivel_id || $this->esBachillerato) {
            return collect();
        }

        $gradoIds = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->select('grado_id')
            ->distinct()
            ->pluck('grado_id')
            ->filter()
            ->values();

        if ($gradoIds->isEmpty()) {
            return collect();
        }

        return Grado::query()
            ->whereIn('id', $gradoIds)
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);
    }

    protected function loadGeneracionesFromGrupos(): Collection
    {
        if (!$this->nivel_id) {
            return collect();
        }

        $query = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id);

        if (!$this->esBachillerato) {
            if (!$this->grado_id) {
                return collect();
            }

            $query->where('grado_id', $this->grado_id)
                ->whereNull('semestre_id');
        }

        $generacionIds = $query
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
            ->where('nivel_id', $this->nivel_id)
            ->where('status', true)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);
    }

    protected function loadSemestresFromGrupos(): Collection
    {
        if (!$this->esBachillerato || !$this->nivel_id || !$this->generacion_id) {
            return collect();
        }

        $semestreIds = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
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
            ->with('grado:id,nombre')
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero']);
    }

    protected function loadGruposOptionsFromGrupos(): array
    {
        if (!$this->nivel_id || !$this->generacion_id) {
            return [];
        }

        $query = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato) {
            if (!$this->semestre_id) {
                return [];
            }

            $query->where('semestre_id', $this->semestre_id);
        } else {
            if (!$this->grado_id) {
                return [];
            }

            $query->where('grado_id', $this->grado_id)
                ->whereNull('semestre_id');
        }

        $grupos = $query
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'grado:id,nombre',
                'semestre:id,numero',
            ])
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'nombre']);

        return $grupos->map(function ($grupo) {
            $generacion = $grupo->generacion
                ? "{$grupo->generacion->anio_ingreso}-{$grupo->generacion->anio_egreso}"
                : 'Sin generación';

            $partes = [];

            $partes[] = $grupo->nombre;

            if ($grupo->grado) {
                $partes[] = $grupo->grado->nombre;
            }

            if ($grupo->semestre) {
                $partes[] = 'Semestre ' . $grupo->semestre->numero;
            }

            $partes[] = $generacion;

            return [
                'id' => $grupo->id,
                'label' => implode(' · ', $partes),
            ];
        })->toArray();
    }

    protected function loadCiclos(): Collection
    {
        return Ciclo::query()
            ->orderBy('id', 'asc')
            ->get(['id', 'ciclo']);
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;

        $this->grado_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation([
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_id',
        ]);

        $nivel = $this->nivel_id
            ? $this->niveles->firstWhere('id', $this->nivel_id)
            : null;

        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        if (!$this->nivel_id) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if ($this->esBachillerato) {
            $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
        } else {
            $this->gradosOptions = $this->loadGradosFromGrupos();
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
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation([
            'generacion_id',
            'semestre_id',
            'grupo_id',
        ]);

        if ($this->esBachillerato || !$this->nivel_id || !$this->grado_id) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
        $this->refrescarMatriculaSiPosible();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;

        $this->semestre_id = null;
        $this->grupo_id = null;

        if ($this->esBachillerato) {
            $this->grado_id = null;
        }

        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation([
            'semestre_id',
            'grupo_id',
        ]);

        if (!$this->nivel_id || !$this->generacion_id) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if ($this->esBachillerato) {
            $this->semestresOptions = $this->loadSemestresFromGrupos();
        } else {
            if ($this->grado_id) {
                $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
            }
        }

        $this->refrescarMatriculaSiPosible();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;

        $this->grupo_id = null;
        $this->grado_id = null;

        $this->resetValidation(['grupo_id']);

        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();

        $this->refrescarMatriculaSiPosible();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;

        if ($this->esBachillerato && $this->grupo_id) {
            $grupo = $this->baseGrupoQuery()
                ->where('id', $this->grupo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->first(['id', 'grado_id']);

            $this->grado_id = $grupo?->grado_id ? (int) $grupo->grado_id : null;
        }

        $this->refrescarMatriculaSiPosible();
    }

    public function updated($property): void
    {
        $this->sanitizeStrings();

        if ($property === 'foto' || $property === 'curp') {
            return;
        }

        if (
            in_array($property, [
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
                'grupo_id',
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
        $nivel = Nivel::query()->find((int) $data['nivel_id']);
        $esBachillerato = $nivel?->slug === 'bachillerato';

        $generacionValida = Generacion::query()
            ->where('id', (int) $data['generacion_id'])
            ->where('nivel_id', (int) $data['nivel_id'])
            ->where('status', true)
            ->exists();

        if (!$generacionValida) {
            $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado o está inactiva.');
            return false;
        }

        $grupoQuery = $this->baseGrupoQuery()
            ->where('id', (int) $data['grupo_id'])
            ->where('nivel_id', (int) $data['nivel_id'])
            ->where('generacion_id', (int) $data['generacion_id']);

        if ($esBachillerato) {
            if (empty($data['semestre_id'])) {
                $this->addError('semestre_id', 'Selecciona un semestre.');
                return false;
            }

            $grupo = $grupoQuery
                ->where('semestre_id', (int) $data['semestre_id'])
                ->first(['id', 'grado_id', 'semestre_id']);

            if (!$grupo) {
                $this->addError('grupo_id', 'El grupo no corresponde al nivel, generación y semestre seleccionados.');
                return false;
            }

            $semestreValido = Semestre::query()
                ->where('id', (int) $data['semestre_id'])
                ->where('grado_id', (int) $grupo->grado_id)
                ->exists();

            if (!$semestreValido) {
                $this->addError('semestre_id', 'El semestre no corresponde al grado interno del grupo seleccionado.');
                return false;
            }

            $data['grado_id'] = (int) $grupo->grado_id;

            return true;
        }

        if (empty($data['grado_id'])) {
            $this->addError('grado_id', 'Selecciona un grado.');
            return false;
        }

        $grupo = $grupoQuery
            ->where('grado_id', (int) $data['grado_id'])
            ->whereNull('semestre_id')
            ->first(['id', 'grado_id']);

        if (!$grupo) {
            $this->addError('grupo_id', 'El grupo no corresponde al nivel, grado y generación seleccionados.');
            return false;
        }

        return true;
    }

    public function guardar(): void
    {
        $this->sanitizeStrings();

        if ($this->curp !== '' && strlen($this->curp) < 18) {
            $this->curpAdvertencia = 'La CURP tiene menos de 18 caracteres. La inscripción se guardará con datos capturados manualmente.';
        }

        $this->refrescarMatriculaSiPosible();

        $data = $this->validate();

        if (!$this->validarRelacionAcademica($data)) {
            return;
        }

        $fotoPath = null;

        if ($this->foto) {
            $fotoPath = $this->foto->store('inscripciones/fotos', 'public');
        }

        Inscripcion::query()->create([
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
            'semestre_id' => !empty($data['semestre_id']) ? (int) $data['semestre_id'] : null,
            'grupo_id' => (int) $data['grupo_id'],

            'foto_path' => $fotoPath,
            'tutor_id' => $data['tutor_id'] ?? null,
            'activo' => true,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->cancelar();
        $this->dispatch('refreshInscripciones');
    }

    public function cancelar(): void
    {
        $this->reset([
            'curp',
            'matricula',
            'folio',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'fecha_inscripcion',
            'ciclo_id',

            'fecha_baja',
            'motivo_baja',
            'observaciones_baja',

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
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_id',
            'tutor_id',
            'copiar_direccion_tutor',
            'foto',
            'consultandoCurp',
            'curpError',
            'curpAdvertencia',
            'curpSuccess',
            'ultimaCurpConsultada',
        ]);

        $this->resetValidation();

        $this->niveles = $this->loadNivelesFromGrupos();
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];
        $this->ciclosOptions = $this->loadCiclos();
        $this->tutores = $this->loadTutores();

        $this->esBachillerato = false;
        $this->fecha_inscripcion = now()->toDateString();
        $this->fecha_baja = null;
        $this->motivo_baja = null;
        $this->observaciones_baja = null;
        $this->matricula = '';

        $this->dispatch('foto-limpiada');
    }

    public function render()
    {
        return view('livewire.inscripcion.crear-inscripcion', [
            'niveles' => $this->niveles,
            'grados' => $this->gradosOptions,
            'generaciones' => $this->generacionesOptions,
            'semestres' => $this->semestresOptions,
            'grupos' => $this->gruposOptions,
            'esBachillerato' => $this->esBachillerato,
            'ciclos' => $this->ciclosOptions,
            'tutores' => $this->tutores,
        ]);
    }
}
