<?php

namespace App\Livewire\Accion;

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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarMatricula extends Component
{
    use WithFileUploads;

    public string $slug_nivel;
    public $InscripcionId;

    // CURP API
    public bool $consultandoCurp = false;
    public ?string $curpError = null;
    public ?string $ultimaCurpConsultada = null;

    // Formulario
    public string $curp = '';
    public string $matricula = '';
    public ?string $folio = null;

    public string $nombre = '';
    public string $apellido_paterno = '';
    public ?string $apellido_materno = null;
    public ?string $fecha_nacimiento = null;
    public ?string $genero = null;

    public ?string $fecha_inscripcion = null;
    public ?string $ciclo_id = null;

    // Nacimiento
    public ?string $pais_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $lugar_nacimiento = null;

    // Dirección
    public ?string $calle = null;
    public ?string $numero_exterior = null;
    public ?string $numero_interior = null;
    public ?string $colonia = null;
    public ?string $codigo_postal = null;
    public ?string $municipio = null;
    public ?string $estado_residencia = null;
    public ?string $ciudad_residencia = null;

    public $foto = null;
    public ?string $foto_actual = null;

    // Tutor
    public ?string $tutor_id = null;
    public bool $copiar_direccion_tutor = false;

    // Académico
    public ?string $nivel_id = null;
    public ?string $grado_id = null;
    public ?string $generacion_id = null;
    public ?string $semestre_id = null;
    public ?string $grupo_id = null;

    public bool $esBachillerato = false;

    // Catálogos
    public Collection $niveles;
    public Collection $gradosOptions;
    public Collection $generacionesOptions;
    public Collection $semestresOptions;
    public array $gruposOptions = [];
    public Collection $ciclosOptions;
    public Collection $tutores;

    public function mount(string $slug_nivel, Inscripcion $inscripcion): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->niveles = $this->loadNivelesFromGrupos();
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->ciclosOptions = $this->loadCiclos();
        $this->tutores = $this->loadTutores();

        $this->cargarInscripcion($inscripcion);
    }

    // Este método carga todo al entrar a la página
    protected function cargarInscripcion(Inscripcion $inscripcion): void
    {
        $this->InscripcionId = $inscripcion->id;

        $this->curp = (string) $inscripcion->curp;
        $this->matricula = (string) $inscripcion->matricula;
        $this->folio = $inscripcion->folio;

        $this->nombre = (string) $inscripcion->nombre;
        $this->apellido_paterno = (string) $inscripcion->apellido_paterno;
        $this->apellido_materno = $inscripcion->apellido_materno;
        $this->fecha_nacimiento = optional($inscripcion->fecha_nacimiento)->format('Y-m-d');
        $this->genero = $inscripcion->genero;

        $this->fecha_inscripcion = $inscripcion->fecha_inscripcion
            ? \Carbon\Carbon::parse($inscripcion->fecha_inscripcion)->format('Y-m-d')
            : null;

        $this->ciclo_id = $inscripcion->ciclo_id ? (string) $inscripcion->ciclo_id : null;

        $this->pais_nacimiento = $inscripcion->pais_nacimiento;
        $this->estado_nacimiento = $inscripcion->estado_nacimiento;
        $this->lugar_nacimiento = $inscripcion->lugar_nacimiento;

        $this->calle = $inscripcion->calle;
        $this->numero_exterior = $inscripcion->numero_exterior;
        $this->numero_interior = $inscripcion->numero_interior;
        $this->colonia = $inscripcion->colonia;
        $this->codigo_postal = $inscripcion->codigo_postal;
        $this->municipio = $inscripcion->municipio;
        $this->estado_residencia = $inscripcion->estado_residencia;
        $this->ciudad_residencia = $inscripcion->ciudad_residencia;

        $this->tutor_id = $inscripcion->tutor_id ? (string) $inscripcion->tutor_id : null;
        $this->copiar_direccion_tutor = false;

        $this->nivel_id = $inscripcion->nivel_id ? (string) $inscripcion->nivel_id : null;
        $this->grado_id = $inscripcion->grado_id ? (string) $inscripcion->grado_id : null;
        $this->generacion_id = $inscripcion->generacion_id ? (string) $inscripcion->generacion_id : null;
        $this->semestre_id = $inscripcion->semestre_id ? (string) $inscripcion->semestre_id : null;
        $this->grupo_id = $inscripcion->grupo_id ? (string) $inscripcion->grupo_id : null;

        $this->foto_actual = $inscripcion->foto_path;
        $this->foto = null;

        $nivel = $this->niveles->firstWhere('id', (int) $this->nivel_id);
        $this->esBachillerato = (bool) ($nivel && $nivel->slug === 'bachillerato');

        $this->gradosOptions = $this->loadGradosFromGrupos();
        $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
        $this->semestresOptions = $this->esBachillerato ? $this->loadSemestresFromGrupos() : collect();
        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();

        // Esto ayuda a que los selects queden marcados correctamente
        $this->grado_id = $inscripcion->grado_id ? (string) $inscripcion->grado_id : null;
        $this->generacion_id = $inscripcion->generacion_id ? (string) $inscripcion->generacion_id : null;
        $this->semestre_id = $inscripcion->semestre_id ? (string) $inscripcion->semestre_id : null;
        $this->grupo_id = $inscripcion->grupo_id ? (string) $inscripcion->grupo_id : null;

        $this->curpError = null;
        $this->ultimaCurpConsultada = $this->curp;
        $this->consultandoCurp = false;
        $this->resetValidation();
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

        foreach ($lowerWords as $w) {
            $value = preg_replace('/\b' . preg_quote($w, '/') . '\b/u', mb_strtolower($w, 'UTF-8'), $value) ?? $value;
        }

        $value = preg_replace_callback('/^(de|del|la|las|los|y|e|san|santa|van|von)\b/iu', function ($m) {
            return mb_convert_case(mb_strtolower($m[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }, $value) ?? $value;

        return $value;
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
            $gen = Generacion::query()->find((int) $this->generacion_id);

            if ($gen?->anio_ingreso) {
                return (string) $gen->anio_ingreso;
            }
        }

        return (string) now()->year;
    }

    protected function generarMatriculaConSlug(string $slug): ?string
    {
        if (strlen($this->curp) !== 18) {
            return null;
        }

        if (!preg_match('/^[A-Z0-9]{18}$/', $this->curp)) {
            return null;
        }

        $actual = $this->InscripcionId ? Inscripcion::find($this->InscripcionId) : null;
        if ($actual && $actual->curp === $this->curp && $actual->matricula) {
            return $actual->matricula;
        }

        $anio = $this->anioInicioCiclo();
        $nivel = $this->nivelCodeBySlug($slug);
        $curp4 = strtoupper(substr($this->curp, 0, 4));

        for ($i = 0; $i < 50; $i++) {
            $nn = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$nivel}{$curp4}{$nn}";

            $exists = Inscripcion::query()
                ->where('matricula', $matricula)
                ->where('id', '!=', $this->InscripcionId)
                ->exists();

            if (!$exists) {
                return $matricula;
            }
        }

        for ($i = 0; $i < 50; $i++) {
            $nnnn = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$nivel}{$curp4}{$nnnn}";

            $exists = Inscripcion::query()
                ->where('matricula', $matricula)
                ->where('id', '!=', $this->InscripcionId)
                ->exists();

            if (!$exists) {
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

        $nivel = $this->niveles->firstWhere('id', (int) $this->nivel_id);
        $slug = $nivel?->slug;

        if (!$slug) {
            $this->matricula = '';
            return;
        }

        $matricula = $this->generarMatriculaConSlug($slug);

        if ($matricula) {
            $this->matricula = $matricula;
            $this->resetValidation('matricula');
        }
    }

    public function updatedCurp(string $value): void
    {
        $this->curp = strtoupper(trim($value));
        $this->curpError = null;

        if (strlen($this->curp) !== 18) {
            $this->ultimaCurpConsultada = null;
            return;
        }

        if (!preg_match('/^[A-Z0-9]{18}$/', $this->curp)) {
            $this->curpError = 'Formato de CURP inválido.';
            return;
        }

        if ($this->ultimaCurpConsultada === $this->curp) {
            $this->refrescarMatriculaSiPosible();
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
            $this->curpError = 'Error al consultar la CURP. Intenta nuevamente.';
            return;
        }

        $this->consultandoCurp = false;

        if (!isset($payload['error']) || $payload['error'] === true) {
            $this->curpError = $payload['error_msg'] ?? $payload['message'] ?? 'CURP inválido o error de conexión.';
            return;
        }

        $sol = data_get($payload, 'response.Solicitante');

        if (!$sol || !is_array($sol)) {
            $this->curpError = 'No se pudieron obtener los datos de la CURP.';
            return;
        }

        $this->nombre = $this->titleCaseNombre((string) data_get($sol, 'Nombres', $this->nombre));
        $this->apellido_paterno = $this->titleCaseNombre((string) data_get($sol, 'ApellidoPaterno', $this->apellido_paterno));

        $apellidoMaterno = data_get($sol, 'ApellidoMaterno');
        $this->apellido_materno = $apellidoMaterno ? $this->titleCaseNombre((string) $apellidoMaterno) : null;

        $fechaApi = data_get($sol, 'FechaNacimiento');
        if (!empty($fechaApi)) {
            $this->fecha_nacimiento = $fechaApi;
        }

        $sexo = strtoupper((string) data_get($sol, 'ClaveSexo', ''));
        if (in_array($sexo, ['H', 'M'], true)) {
            $this->genero = $sexo;
        }

        $this->pais_nacimiento = data_get($sol, 'Nacionalidad') ?: $this->pais_nacimiento;
        $this->estado_nacimiento = data_get($sol, 'EntidadNacimiento') ?: $this->estado_nacimiento;
        $this->lugar_nacimiento = data_get($sol, 'EntidadNacimiento') ?: $this->lugar_nacimiento;

        $this->sanitizeStrings();
        $this->refrescarMatriculaSiPosible();
        $this->validateOnly('curp');
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

        $tutor = Tutor::find((int) $this->tutor_id);

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
        $this->tutor_id = $value !== '' ? (string) $value : null;

        if (!$this->tutor_id) {
            $this->copiar_direccion_tutor = false;
            return;
        }

        if ($this->copiar_direccion_tutor) {
            usleep(250000);
            $this->llenarDireccionDesdeTutor();
        }
    }

    public function updatedCopiarDireccionTutor($value): void
    {
        $this->copiar_direccion_tutor = (bool) $value;

        if (!$this->copiar_direccion_tutor || !$this->tutor_id) {
            return;
        }

        usleep(250000);
        $this->llenarDireccionDesdeTutor();
    }

    protected function baseGrupoQuery()
    {
        if (method_exists(Grupo::class, 'bootSoftDeletes')) {
            return Grupo::query()->withoutTrashed();
        }

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
        if (!$this->nivel_id) {
            return collect();
        }

        $gradoIds = $this->baseGrupoQuery()
            ->where('nivel_id', (int) $this->nivel_id)
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
            ->orderBy('orden')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);
    }

    protected function loadGeneracionesFromGrupos(): Collection
    {
        if (!$this->nivel_id || !$this->grado_id) {
            return collect();
        }

        $generacionIds = $this->baseGrupoQuery()
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('grado_id', (int) $this->grado_id)
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

    protected function loadSemestresFromGrupos(): Collection
    {
        if (!$this->esBachillerato) {
            return collect();
        }

        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) {
            return collect();
        }

        $semestreIds = $this->baseGrupoQuery()
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('grado_id', (int) $this->grado_id)
            ->where('generacion_id', (int) $this->generacion_id)
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

    protected function loadGruposOptionsFromGrupos(): array
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) {
            return [];
        }

        $query = $this->baseGrupoQuery()
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('grado_id', (int) $this->grado_id)
            ->where('generacion_id', (int) $this->generacion_id);

        if ($this->esBachillerato) {
            if (!$this->semestre_id) {
                return [];
            }

            $query->where('semestre_id', (int) $this->semestre_id);
        }

        $rows = $query
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->orderBy('semestre_id')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'nombre']);

        return $rows->map(function ($grupo) {
            $generacion = $grupo->generacion ? "{$grupo->generacion->anio_ingreso}–{$grupo->generacion->anio_egreso}" : null;
            $semestre = $grupo->semestre ? "Sem {$grupo->semestre->numero}" : null;

            $left = collect([$semestre, $grupo->nombre])->filter()->implode(' — ');
            $label = $generacion ? "{$left} ({$generacion})" : $left;

            return [
                'id' => (string) $grupo->id,
                'label' => $label,
            ];
        })->values()->all();
    }

    protected function loadCiclos(): Collection
    {
        return Ciclo::query()
            ->orderBy('id')
            ->get(['id', 'ciclo']);
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value !== '' ? (string) $value : null;

        $nivel = $this->niveles->firstWhere('id', (int) $this->nivel_id);
        $this->esBachillerato = (bool) ($nivel && $nivel->slug === 'bachillerato');

        $this->grado_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation(['grado_id', 'generacion_id', 'semestre_id', 'grupo_id']);

        if (!$this->nivel_id) {
            $this->matricula = '';
            return;
        }

        $this->gradosOptions = $this->loadGradosFromGrupos();
        $this->refrescarMatriculaSiPosible();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value !== '' ? (string) $value : null;

        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation(['generacion_id', 'semestre_id', 'grupo_id']);

        if (!$this->nivel_id || !$this->grado_id) {
            return;
        }

        $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value !== '' ? (string) $value : null;

        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation(['semestre_id', 'grupo_id']);

        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if ($this->esBachillerato) {
            $this->semestresOptions = $this->loadSemestresFromGrupos();
            $this->refrescarMatriculaSiPosible();
            return;
        }

        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
        $this->refrescarMatriculaSiPosible();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value !== '' ? (string) $value : null;

        $this->grupo_id = null;
        $this->resetValidation(['grupo_id']);

        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
    }

    public function quitarFotoTemporal(): void
    {
        $this->reset('foto');
        $this->dispatch('foto-limpiada');
    }

    protected function validarRelacionAcademica(array &$data): bool
    {
        $gradoValido = Grado::query()
            ->where('id', (int) $data['grado_id'])
            ->where('nivel_id', (int) $data['nivel_id'])
            ->exists();

        if (!$gradoValido) {
            $this->addError('grado_id', 'El grado no pertenece al nivel seleccionado.');
            return false;
        }

        $grupoQuery = Grupo::query()
            ->where('id', (int) $data['grupo_id'])
            ->where('nivel_id', (int) $data['nivel_id'])
            ->where('grado_id', (int) $data['grado_id'])
            ->where('generacion_id', (int) $data['generacion_id']);

        if ($this->esBachillerato) {
            $semestreValido = Grupo::query()
                ->where('nivel_id', (int) $data['nivel_id'])
                ->where('grado_id', (int) $data['grado_id'])
                ->where('generacion_id', (int) $data['generacion_id'])
                ->where('semestre_id', (int) $data['semestre_id'])
                ->exists();

            if (!$semestreValido) {
                $this->addError('semestre_id', 'El semestre no pertenece a la selección actual.');
                return false;
            }

            $grupoQuery->where('semestre_id', (int) $data['semestre_id']);
        } else {
            $data['semestre_id'] = null;
            $grupoQuery->whereNull('semestre_id');
        }

        $grupoValido = $grupoQuery->exists();

        if (!$grupoValido) {
            $this->addError('grupo_id', 'El grupo no pertenece a la selección actual.');
            return false;
        }

        return true;
    }

    protected function rules(): array
    {
        $rules = [
            'curp' => [
                'required',
                'string',
                'size:18',
                'regex:/^[A-Z0-9]{18}$/i',
                Rule::unique('inscripciones', 'curp')->ignore($this->InscripcionId),
            ],
            'matricula' => [
                'required',
                'string',
                'max:50',
                Rule::unique('inscripciones', 'matricula')->ignore($this->InscripcionId),
            ],
            'folio' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date'],
            'genero' => ['required', 'in:H,M'],
            'fecha_inscripcion' => ['required', 'date'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'pais_nacimiento' => ['nullable', 'string', 'max:255'],
            'estado_nacimiento' => ['nullable', 'string', 'max:255'],
            'lugar_nacimiento' => ['nullable', 'string', 'max:255'],
            'calle' => ['nullable', 'string', 'max:255'],
            'numero_exterior' => ['nullable', 'string', 'max:20'],
            'numero_interior' => ['nullable', 'string', 'max:20'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:10', 'regex:/^\d{5}$/'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado_residencia' => ['nullable', 'string', 'max:255'],
            'ciudad_residencia' => ['nullable', 'string', 'max:255'],
            'nivel_id' => ['required', 'exists:niveles,id'],
            'grado_id' => ['required', 'exists:grados,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grupo_id' => ['required', 'exists:grupos,id'],
            'tutor_id' => ['nullable', 'exists:tutores,id'],
            'copiar_direccion_tutor' => ['boolean'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];

        $rules['semestre_id'] = $this->esBachillerato
            ? ['required', 'exists:semestres,id']
            : ['nullable', 'exists:semestres,id'];

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'curp.required' => 'La CURP es obligatoria.',
            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex' => 'La CURP debe contener solo letras y números.',
            'curp.unique' => 'Esa CURP ya existe.',
            'matricula.required' => 'La matrícula es obligatoria.',
            'matricula.unique' => 'Esa matrícula ya existe.',
            'fecha_inscripcion.required' => 'La fecha de inscripción es obligatoria.',
            'ciclo_id.required' => 'Selecciona un ciclo.',
            'ciclo_id.exists' => 'El ciclo seleccionado no es válido.',
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',
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

    protected function sanitizeStrings(): void
    {
        $requiredStringFields = ['curp', 'matricula', 'nombre', 'apellido_paterno'];

        $nullableStringFields = [
            'folio',
            'apellido_materno',
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
                $this->{$field} = ($value === '') ? null : $value;
            }
        }

        if ($this->curp !== '') {
            $this->curp = strtoupper($this->curp);
        }

        if ($this->matricula !== '') {
            $this->matricula = strtoupper($this->matricula);
        }
    }

    public function updated($property): void
    {
        $this->sanitizeStrings();

        if (in_array($property, ['foto', 'curp', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id'], true)) {
            return;
        }

        $this->validateOnly($property);
    }

    public function actualizarInscripcion(): void
    {
        $this->sanitizeStrings();
        $this->refrescarMatriculaSiPosible();

        $data = $this->validate();

        if (!$this->validarRelacionAcademica($data)) {
            return;
        }

        $inscripcion = Inscripcion::findOrFail($this->InscripcionId);

        $fotoPath = $inscripcion->foto_path;

        if ($this->foto) {
            if ($fotoPath && Storage::disk('public')->exists($fotoPath)) {
                Storage::disk('public')->delete($fotoPath);
            }

            $fotoPath = $this->foto->store('inscripciones/fotos', 'public');
        }

        $inscripcion->update([
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
            'semestre_id' => $data['semestre_id'] ? (int) $data['semestre_id'] : null,
            'grupo_id' => (int) $data['grupo_id'],
            'foto_path' => $fotoPath,
            'tutor_id' => $data['tutor_id'] ? (int) $data['tutor_id'] : null,
        ]);

        // Limpiar solo la foto temporal
        $this->foto = null;

        // Recargar datos visibles por si hubo cambios en relaciones
        $inscripcion->refresh();
        $this->cargarInscripcion($inscripcion);

        // Aviso sin recargar página
        $this->dispatch('swal', [
            'title' => 'Inscripción actualizada correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('foto-limpiada');
    }

    public function cancelar()
    {
        return redirect()->route('submodulos.accion', [
            'slug_nivel' => $this->slug_nivel,
            'accion' => 'matricula',
        ]);
    }

    public function render()
    {
        return view('livewire.accion.editar-matricula', [
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
