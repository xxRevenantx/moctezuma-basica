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
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarMatricula extends Component
{
    use WithFileUploads;

    public string $slug_nivel;
    public ?int $InscripcionId = null;

    // CURP API
    public bool $consultandoCurp = false;
    public ?string $curpError = null;
    public ?string $ultimaCurpConsultada = null;

    // Datos personales
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
    public ?int $tutor_id = null;
    public bool $copiar_direccion_tutor = false;

    // Asignación escolar
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;
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

    // Este método carga la inscripción actual y prepara los selects sin romper su relación.
    protected function cargarInscripcion(Inscripcion $inscripcion): void
    {
        $this->InscripcionId = (int) $inscripcion->id;

        $this->curp = (string) $inscripcion->curp;
        $this->matricula = (string) $inscripcion->matricula;
        $this->folio = $inscripcion->folio;
        $this->nombre = (string) $inscripcion->nombre;
        $this->apellido_paterno = (string) $inscripcion->apellido_paterno;
        $this->apellido_materno = $inscripcion->apellido_materno;
        $this->fecha_nacimiento = $inscripcion->fecha_nacimiento
            ? Carbon::parse($inscripcion->fecha_nacimiento)->format('Y-m-d')
            : null;
        $this->genero = $inscripcion->genero;
        $this->fecha_inscripcion = $inscripcion->fecha_inscripcion
            ? Carbon::parse($inscripcion->fecha_inscripcion)->format('Y-m-d')
            : null;
        $this->ciclo_id = $inscripcion->ciclo_id ? (int) $inscripcion->ciclo_id : null;

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

        $this->tutor_id = $inscripcion->tutor_id ? (int) $inscripcion->tutor_id : null;
        $this->copiar_direccion_tutor = false;

        $this->nivel_id = $inscripcion->nivel_id ? (int) $inscripcion->nivel_id : null;
        $this->grado_id = $inscripcion->grado_id ? (int) $inscripcion->grado_id : null;
        $this->generacion_id = $inscripcion->generacion_id ? (int) $inscripcion->generacion_id : null;
        $this->semestre_id = $inscripcion->semestre_id ? (int) $inscripcion->semestre_id : null;
        $this->grupo_id = $inscripcion->grupo_id ? (int) $inscripcion->grupo_id : null;
        $this->foto_actual = $inscripcion->foto_path;
        $this->foto = null;

        $this->esBachillerato = $this->nivelEsBachillerato($this->nivel_id);

        $this->gradosOptions = $this->esBachillerato ? collect() : $this->loadGradosFromGrupos();
        $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
        $this->semestresOptions = $this->esBachillerato ? $this->loadSemestresFromGrupos() : collect();
        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();

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

        $palabrasMinusculas = ['De', 'Del', 'La', 'Las', 'Los', 'Y', 'E', 'San', 'Santa', 'Van', 'Von'];

        foreach ($palabrasMinusculas as $palabra) {
            $value = preg_replace('/\b' . preg_quote($palabra, '/') . '\b/u', mb_strtolower($palabra, 'UTF-8'), $value) ?? $value;
        }

        return preg_replace_callback('/^(de|del|la|las|los|y|e|san|santa|van|von)\b/iu', function ($m) {
            return mb_convert_case(mb_strtolower($m[0], 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        }, $value) ?? $value;
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
        if (strlen($this->curp) !== 18 || !preg_match('/^[A-Z0-9]{18}$/', $this->curp)) {
            return null;
        }

        $actual = $this->InscripcionId ? Inscripcion::query()->find($this->InscripcionId) : null;

        // Se conserva la matrícula si no cambió la CURP, el nivel ni la generación.
        // Si cambia la asignación escolar principal, se genera otra para evitar prefijos inconsistentes.
        if (
            $actual
            && $actual->curp === $this->curp
            && $actual->matricula
            && (int) $actual->nivel_id === (int) $this->nivel_id
            && (int) $actual->generacion_id === (int) $this->generacion_id
        ) {
            return $actual->matricula;
        }

        $anio = $this->anioInicioCiclo();
        $nivel = $this->nivelCodeBySlug($slug);
        $curp4 = strtoupper(substr($this->curp, 0, 4));

        for ($i = 0; $i < 50; $i++) {
            $nn = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$nivel}{$curp4}{$nn}";

            $existe = Inscripcion::query()
                ->where('matricula', $matricula)
                ->where('id', '!=', $this->InscripcionId)
                ->exists();

            if (!$existe) {
                return $matricula;
            }
        }

        for ($i = 0; $i < 50; $i++) {
            $nnnn = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $matricula = "{$anio}{$nivel}{$curp4}{$nnnn}";

            $existe = Inscripcion::query()
                ->where('matricula', $matricula)
                ->where('id', '!=', $this->InscripcionId)
                ->exists();

            if (!$existe) {
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

        $nivel = $this->niveles->firstWhere('id', $this->nivel_id);
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

        $solicitante = data_get($payload, 'response.Solicitante');

        if (!$solicitante || !is_array($solicitante)) {
            $this->curpError = 'No se pudieron obtener los datos de la CURP.';
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

        $sexo = strtoupper((string) data_get($solicitante, 'ClaveSexo', ''));
        if (in_array($sexo, ['H', 'M'], true)) {
            $this->genero = $sexo;
        }

        $this->pais_nacimiento = data_get($solicitante, 'Nacionalidad') ?: $this->pais_nacimiento;
        $this->estado_nacimiento = data_get($solicitante, 'EntidadNacimiento') ?: $this->estado_nacimiento;
        $this->lugar_nacimiento = data_get($solicitante, 'EntidadNacimiento') ?: $this->lugar_nacimiento;

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
            usleep(250000);
            $this->llenarDireccionDesdeTutor();
        }
    }

    public function updatedCopiarDireccionTutor($value): void
    {
        $this->copiar_direccion_tutor = (bool) $value;

        if ($this->copiar_direccion_tutor && $this->tutor_id) {
            usleep(250000);
            $this->llenarDireccionDesdeTutor();
        }
    }

    protected function baseGrupoQuery()
    {
        return Grupo::query()->whereNull('deleted_at');
    }

    protected function nivelEsBachillerato(?int $nivelId): bool
    {
        if (!$nivelId) {
            return false;
        }

        $nivel = $this->niveles->firstWhere('id', $nivelId) ?: Nivel::query()->find($nivelId);

        return (bool) ($nivel && $nivel->slug === 'bachillerato');
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
            ->whereNull('semestre_id')
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

        if ($this->esBachillerato) {
            $query->whereNotNull('semestre_id');
        } else {
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

        $rows = $query
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,numero',
                'grado:id,nombre',
            ])
            ->orderBy('semestre_id')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'nombre']);

        return $rows->map(function ($grupo) {
            $generacion = $grupo->generacion ? "{$grupo->generacion->anio_ingreso}–{$grupo->generacion->anio_egreso}" : null;
            $semestre = $grupo->semestre ? "Semestre {$grupo->semestre->numero}" : null;
            $grado = $grupo->grado ? "Grado {$grupo->grado->nombre}" : null;
            $partes = $this->esBachillerato
                ? collect([$semestre, "Grupo {$grupo->nombre}"])->filter()->implode(' — ')
                : collect([$grado, "Grupo {$grupo->nombre}"])->filter()->implode(' — ');

            return [
                'id' => (int) $grupo->id,
                'grado_id' => (int) $grupo->grado_id,
                'label' => $generacion ? "{$partes} ({$generacion})" : $partes,
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
        $this->nivel_id = $value ? (int) $value : null;
        $this->esBachillerato = $this->nivelEsBachillerato($this->nivel_id);

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

        $this->resetValidation(['generacion_id', 'semestre_id', 'grupo_id']);

        if (!$this->nivel_id || !$this->grado_id || $this->esBachillerato) {
            return;
        }

        $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->resetValidation(['semestre_id', 'grupo_id']);

        if (!$this->nivel_id || !$this->generacion_id) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if ($this->esBachillerato) {
            $this->grado_id = null;
            $this->semestresOptions = $this->loadSemestresFromGrupos();
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if (!$this->grado_id) {
            $this->refrescarMatriculaSiPosible();
            return;
        }

        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
        $this->refrescarMatriculaSiPosible();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;
        $this->grupo_id = null;
        $this->resetValidation(['grupo_id']);
        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;

        if ($this->esBachillerato && $this->grupo_id) {
            $grupo = Grupo::query()->find($this->grupo_id);
            $this->grado_id = $grupo?->grado_id ? (int) $grupo->grado_id : null;
        }

        $this->refrescarMatriculaSiPosible();
    }

    public function quitarFotoTemporal(): void
    {
        $this->reset('foto');
        $this->dispatch('foto-limpiada');
    }

    protected function validarRelacionAcademica(array &$data): bool
    {
        $nivelId = (int) $data['nivel_id'];
        $generacionId = (int) $data['generacion_id'];
        $grupoId = (int) $data['grupo_id'];

        $generacionValida = Generacion::query()
            ->where('id', $generacionId)
            ->where('nivel_id', $nivelId)
            ->exists();

        if (!$generacionValida) {
            $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado.');
            return false;
        }

        $grupoQuery = $this->baseGrupoQuery()
            ->where('id', $grupoId)
            ->where('nivel_id', $nivelId)
            ->where('generacion_id', $generacionId);

        if ($this->esBachillerato) {
            $semestreId = (int) ($data['semestre_id'] ?? 0);

            $grupo = (clone $grupoQuery)
                ->where('semestre_id', $semestreId)
                ->first(['id', 'grado_id', 'semestre_id']);

            if (!$grupo) {
                $this->addError('grupo_id', 'El grupo no pertenece al nivel, generación y semestre seleccionado.');
                return false;
            }

            $semestreValido = Semestre::query()
                ->where('id', $semestreId)
                ->where('grado_id', $grupo->grado_id)
                ->exists();

            if (!$semestreValido) {
                $this->addError('semestre_id', 'El semestre no corresponde al grado interno del grupo.');
                return false;
            }

            $data['grado_id'] = (int) $grupo->grado_id;
            $data['semestre_id'] = $semestreId;
            $this->grado_id = (int) $grupo->grado_id;

            return true;
        }

        $gradoId = (int) ($data['grado_id'] ?? 0);

        $gradoValido = Grado::query()
            ->where('id', $gradoId)
            ->where('nivel_id', $nivelId)
            ->exists();

        if (!$gradoValido) {
            $this->addError('grado_id', 'El grado no pertenece al nivel seleccionado.');
            return false;
        }

        $grupoValido = $grupoQuery
            ->where('grado_id', $gradoId)
            ->whereNull('semestre_id')
            ->exists();

        if (!$grupoValido) {
            $this->addError('grupo_id', 'El grupo no pertenece al nivel, grado y generación seleccionado.');
            return false;
        }

        $data['grado_id'] = $gradoId;
        $data['semestre_id'] = null;

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
            'ciclo_id' => ['required', 'integer', 'exists:ciclos,id'],
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
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'generacion_id' => ['required', 'integer', 'exists:generaciones,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'tutor_id' => ['nullable', 'integer', 'exists:tutores,id'],
            'copiar_direccion_tutor' => ['boolean'],
            'foto' => ['nullable', 'image', 'max:2048'],
        ];

        $rules['grado_id'] = $this->esBachillerato
            ? ['nullable', 'integer', 'exists:grados,id']
            : ['required', 'integer', 'exists:grados,id'];

        $rules['semestre_id'] = $this->esBachillerato
            ? ['required', 'integer', 'exists:semestres,id']
            : ['nullable', 'integer', 'exists:semestres,id'];

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
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',
            'fecha_inscripcion.required' => 'La fecha de inscripción es obligatoria.',
            'ciclo_id.required' => 'Selecciona un ciclo.',
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
                $this->{$field} = $value === '' ? null : $value;
            }
        }

        $this->curp = strtoupper($this->curp);
        $this->matricula = strtoupper($this->matricula);
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

        $inscripcion = Inscripcion::query()->findOrFail($this->InscripcionId);
        $fotoPath = $this->foto_actual;

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

        $this->foto = null;
        $inscripcion->refresh();
        $this->cargarInscripcion($inscripcion);

        $this->dispatch('swal', [
            'title' => 'Inscripción actualizada correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('foto-limpiada');
        $this->dispatch('refreshInscripciones');
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
