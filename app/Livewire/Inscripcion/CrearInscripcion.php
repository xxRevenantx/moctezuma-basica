<?php

namespace App\Livewire\Inscripcion;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\CurpService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearInscripcion extends Component
{
    use WithFileUploads;

    // =========================
    // CURP API flags
    // =========================
    public bool $consultandoCurp = false;
    public ?string $curpError = null;
    public ?string $ultimaCurpConsultada = null;

    // =========================
    // Campos del formulario
    // =========================
    public string $curp = '';
    public string $matricula = '';
    public ?string $folio = null;

    public string $nombre = '';
    public string $apellido_paterno = '';
    public ?string $apellido_materno = null;
    public ?string $fecha_nacimiento = null; // Y-m-d
    public ?string $genero = null; // H | M

    // Nacimiento (opcionales)
    public ?string $pais_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $lugar_nacimiento = null;

    // Domicilio (opcionales)
    public ?string $calle = null;
    public ?string $numero_exterior = null;
    public ?string $numero_interior = null;
    public ?string $colonia = null;
    public ?string $codigo_postal = null;
    public ?string $municipio = null;
    public ?string $estado_residencia = null;
    public ?string $ciudad_residencia = null;

    public $foto = null;

    // =========================
    // Selects académicos
    // =========================
    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null; // ✅ solo Bachillerato
    public ?int $grupo_id = null;

    public bool $esBachillerato = false;

    // =========================
    // Catálogos (derivados de grupos)
    // =========================
    public Collection $niveles;
    public Collection $gradosOptions;
    public Collection $generacionesOptions;
    public Collection $semestresOptions;
    public array $gruposOptions = [];

    public function mount(): void
    {
        $this->niveles = $this->loadNivelesFromGrupos();

        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        $this->matricula = '';
    }

    // ==========================================================
    // MATRÍCULA (autogenerada cuando CURP es correcta + nivel slug)
    // Formato: {ANIO}{NIVELCODE}{CURP4}{NN} (fallback a NNNN)
    // ==========================================================
    protected function nivelCodeBySlug(?string $slug): string
    {
        return match ($slug) {
            'preescolar'   => 'PREES',
            'primaria'     => 'PRIM',
            'secundaria'   => 'SEC',
            'bachillerato' => 'BACHI',
            default        => 'NIV',
        };
    }

    protected function anioInicioCiclo(): string
    {
        // Ideal: Generacion->anio_ingreso
        if ($this->generacion_id) {
            $gen = Generacion::query()->find($this->generacion_id);
            if ($gen?->anio_ingreso) return (string) $gen->anio_ingreso;
        }

        // Fallback: año actual
        return (string) now()->year;
    }

    protected function generarMatriculaConSlug(string $slug): ?string
    {
        if (strlen($this->curp) !== 18) return null;
        if (!preg_match('/^[A-Z0-9]{18}$/', $this->curp)) return null;

        $anio  = $this->anioInicioCiclo();
        $nivel = $this->nivelCodeBySlug($slug);
        $curp4 = strtoupper(substr($this->curp, 0, 4));

        // intentos con 2 dígitos
        for ($i = 0; $i < 50; $i++) {
            $nn = str_pad((string) random_int(0, 99), 2, '0', STR_PAD_LEFT);
            $mat = "{$anio}{$nivel}{$curp4}{$nn}";

            if (!Inscripcion::query()->where('matricula', $mat)->exists()) {
                return $mat;
            }
        }


        // fallback con 4 dígitos
        for ($i = 0; $i < 50; $i++) {
            $nnnn = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $mat = "{$anio}{$nivel}{$curp4}{$nnnn}";

            if (!Inscripcion::query()->where('matricula', $mat)->exists()) {
                return $mat;
            }
        }

        return null;
    }

    protected function refrescarMatriculaSiPosible(): void
    {
        // Requisito: CURP válida + nivel seleccionado
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

        $mat = $this->generarMatriculaConSlug($slug);

        if ($mat) {
            $this->matricula = $mat;
            $this->resetValidation('matricula');
        } else {
            // si no se pudo generar por colisiones extremas
            $this->matricula = '';
        }
    }

    // =========================
    // CURP en tiempo real
    // =========================
    public function updatedCurp(string $value): void
    {
        $this->curp = strtoupper(trim($value));
        $this->curpError = null;

        // No generar si aún no está completa
        if (strlen($this->curp) !== 18) {
            $this->ultimaCurpConsultada = null;
            $this->matricula = ''; // ✅ limpia mientras escribe
            return;
        }

        if (!preg_match('/^[A-Z0-9]{18}$/', $this->curp)) {
            $this->curpError = 'Formato de CURP inválido.';
            $this->matricula = '';
            return;
        }

        if ($this->ultimaCurpConsultada === $this->curp) {
            // por si el usuario ya tenía nivel seleccionado y solo re-enfoca
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
            $this->matricula = '';
            return;
        }

        $this->consultandoCurp = false;

        if (!isset($payload['error']) || $payload['error'] === true) {
            $this->curpError = $payload['error_msg'] ?? $payload['message'] ?? 'CURP inválido o error de conexión.';
            $this->matricula = '';
            return;
        }

        $sol = data_get($payload, 'response.Solicitante');
        if (!$sol || !is_array($sol)) {
            $this->curpError = 'No se pudieron obtener los datos de la CURP.';
            $this->matricula = '';
            return;
        }

        // dd($sol);

        $this->nombre = (string) data_get($sol, 'Nombres', $this->nombre);
        $this->apellido_paterno = (string) data_get($sol, 'ApellidoPaterno', $this->apellido_paterno);
        $this->apellido_materno = data_get($sol, 'ApellidoMaterno') ?: null;

        $fechaApi = data_get($sol, 'FechaNacimiento');
        if (!empty($fechaApi)) {
            try {
                $this->fecha_nacimiento = $fechaApi;
            } catch (\Throwable $e) {
                // noop
            }
        }

        $sexo = strtoupper((string) data_get($sol, 'ClaveSexo', ''));
        if (in_array($sexo, ['H', 'M'], true)) {
            $this->genero = $sexo;
        }

        $this->pais_nacimiento = data_get($sol, 'Nacionalidad') ?: $this->pais_nacimiento;
        $this->estado_nacimiento = data_get($sol, 'EntidadNacimiento') ?: $this->estado_nacimiento;
        $this->lugar_nacimiento = data_get($sol, 'EntidadNacimiento') ?: $this->lugar_nacimiento;

        $this->sanitizeStrings();

        // ✅ AQUÍ: ya es CURP correcta → genera matrícula si nivel->slug existe
        $this->refrescarMatriculaSiPosible();

        $this->validateOnly('curp');
    }

    // =========================
    // Fuentes desde tabla grupos
    // =========================
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

        if ($nivelIds->isEmpty()) return collect();

        return Nivel::query()
            ->whereIn('id', $nivelIds)
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug', 'color']);
    }

    protected function loadGradosFromGrupos(): Collection
    {
        if (!$this->nivel_id) return collect();

        $gradoIds = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->select('grado_id')
            ->distinct()
            ->pluck('grado_id')
            ->filter()
            ->values();

        if ($gradoIds->isEmpty()) return collect();

        return Grado::query()
            ->whereIn('id', $gradoIds)
            ->orderBy('orden')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);
    }

    protected function loadGeneracionesFromGrupos(): Collection
    {
        if (!$this->nivel_id || !$this->grado_id) return collect();

        $generacionIds = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->select('generacion_id')
            ->distinct()
            ->pluck('generacion_id')
            ->filter()
            ->values();

        if ($generacionIds->isEmpty()) return collect();

        return Generacion::query()
            ->whereIn('id', $generacionIds)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);
    }

    protected function loadSemestresFromGrupos(): Collection
    {
        if (!$this->esBachillerato) return collect();
        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) return collect();

        $semestreIds = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->whereNotNull('semestre_id')
            ->select('semestre_id')
            ->distinct()
            ->pluck('semestre_id')
            ->filter()
            ->values();

        if ($semestreIds->isEmpty()) return collect();

        return Semestre::query()
            ->whereIn('id', $semestreIds)
            ->orderBy('numero')
            ->get(['id', 'numero']);
    }

    protected function loadGruposOptionsFromGrupos(): array
    {
        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) return [];

        $q = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato) {
            if (!$this->semestre_id) return [];
            $q->where('semestre_id', $this->semestre_id);
        }

        $rows = $q
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->orderBy('semestre_id')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'nombre']);

        return $rows->map(function ($g) {
            $gen = $g->generacion ? "{$g->generacion->anio_ingreso}–{$g->generacion->anio_egreso}" : null;
            $sem = $g->semestre ? "Sem {$g->semestre->numero}" : null;

            $left = collect([$sem, $g->nombre])->filter()->implode(' — ');
            $label = $gen ? "{$left} ({$gen})" : $left;

            return ['id' => $g->id, 'label' => $label];
        })->values()->all();
    }

    // =========================
    // Reactividad (selects)
    // =========================
    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;

        $nivel = $this->niveles->firstWhere('id', $this->nivel_id);
        $this->esBachillerato = $nivel && ($nivel->slug === 'bachillerato');

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

        // ✅ si ya hay CURP correcta, al elegir nivel se genera matrícula
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

        if (!$this->nivel_id || !$this->grado_id) return;

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

        if (!$this->nivel_id || !$this->grado_id || !$this->generacion_id) {
            // por si el año depende de generación
            $this->refrescarMatriculaSiPosible();
            return;
        }

        if ($this->esBachillerato) {
            $this->semestresOptions = $this->loadSemestresFromGrupos();
            $this->refrescarMatriculaSiPosible();
            return;
        }

        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();

        // por si el año depende de generación
        $this->refrescarMatriculaSiPosible();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;

        $this->grupo_id = null;
        $this->resetValidation(['grupo_id']);

        $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
    }

    // =========================
    // Validación
    // =========================
    protected function rules(): array
    {
        $rules = [
            'curp' => ['required', 'string', 'size:18', 'regex:/^[A-Z0-9]{18}$/i', Rule::unique('inscripciones', 'curp')],
            'matricula' => ['required', 'string', 'max:50', Rule::unique('inscripciones', 'matricula')],

            'folio' => ['nullable', 'string', 'max:50'],

            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date'],
            'genero' => ['required', 'in:H,M'],

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
            'matricula.required' => 'La matrícula es obligatoria.',
            'matricula.unique' => 'Esa matrícula ya existe.',
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
            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe exceder 2MB.',
        ];
    }

    // =========================
    // Sanitización
    // =========================
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
            $val = $this->{$field} ?? '';
            $val = is_string($val) ? $val : '';
            $val = preg_replace('/\s+/u', ' ', trim($val));
            $this->{$field} = $val;
        }

        foreach ($nullableStringFields as $field) {
            $val = $this->{$field} ?? null;
            if (is_string($val)) {
                $val = preg_replace('/\s+/u', ' ', trim($val));
                $this->{$field} = ($val === '') ? null : $val;
            }
        }

        if ($this->curp !== '') $this->curp = strtoupper($this->curp);
        if ($this->matricula !== '') $this->matricula = strtoupper($this->matricula);
    }

    public function updated($property): void
    {
        $this->sanitizeStrings();

        if ($property === 'foto') return;
        if ($property === 'curp') return;

        if (in_array($property, ['nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id'], true)) {
            return;
        }

        $this->validateOnly($property);
    }

    // =========================
    // Guardar
    // =========================
    public function guardar(): void
    {
        $this->sanitizeStrings();

        // ✅ Última verificación: si CURP es correcta y ya hay nivel, genera matrícula
        $this->refrescarMatriculaSiPosible();

        $data = $this->validate();

        $fotoPath = null;
        if ($this->foto) {
            $fotoPath = $this->foto->store('inscripciones/fotos', 'public');
        }

        Inscripcion::create([
            'curp' => $data['curp'],
            'matricula' => $data['matricula'],
            'folio' => $data['folio'] ?? null,

            'nombre' => $data['nombre'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'genero' => $data['genero'],

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
            'activo' => true,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->cancelar();
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
            'foto',
            'consultandoCurp',
            'curpError',
            'ultimaCurpConsultada',
        ]);

        $this->resetValidation();

        $this->niveles = $this->loadNivelesFromGrupos();
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];
        $this->esBachillerato = false;
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
        ]);
    }
}
