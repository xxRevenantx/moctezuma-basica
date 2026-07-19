<?php

namespace App\Livewire\Accion;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\ObservacionInscripcion;
use App\Models\Semestre;
use App\Models\Tutor;
use App\Services\CurpService;
use App\Services\ExpedienteDigitalService;
use App\Services\GestionAcademicaService;
use App\Services\ImagenPersonalService;
use App\Services\ObservacionInscripcionService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarMatricula extends Component
{
    use WithFileUploads;

    public string $slug_nivel = '';
    public ?int $InscripcionId = null;

    public string $curp = '';
    public string $matricula = '';
    public ?string $folio = null;
    public string $nombre = '';
    public string $apellido_paterno = '';
    public ?string $apellido_materno = null;
    public ?string $fecha_nacimiento = null;
    public ?string $genero = null;
    public ?string $fecha_ingreso_plantel = null;
    public ?int $ciclo_id = null;

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
    public ?string $foto_actual = null;
    public ?string $foto_actual_url = null;
    public bool $foto_actual_existe = false;

    public ?int $tutor_id = null;
    public bool $copiar_direccion_tutor = false;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $generacion_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $estatus = 'activo';
    public ?string $fecha_estatus = null;
    public string $motivo_cambio = '';
    public bool $confirmar_cambio_academico = false;

    public ?string $observaciones = null;
    public ?int $observacion_ciclo_escolar_id = null;

    /** @var array<int, string|null> */
    public array $observacionesPorCiclo = [];

    public Collection $niveles;
    public Collection $gradosOptions;
    public Collection $generacionesOptions;
    public Collection $semestresOptions;
    public array $gruposOptions = [];
    public Collection $ciclosOptions;
    public Collection $ciclosEscolaresObservacion;
    public Collection $tutores;

    public bool $consultandoCurp = false;
    public ?string $curpError = null;
    public ?string $curpSuccess = null;

    public function mount(string $slug_nivel, Inscripcion $inscripcion): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.editar'), 403);

        $this->slug_nivel = $slug_nivel;
        $this->niveles = $this->loadNivelesFromGrupos();
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];
        $this->ciclosOptions = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);
        $this->ciclosEscolaresObservacion = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->tutores = Tutor::query()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $this->cargar($inscripcion);
    }

    private function cargar(Inscripcion $alumno): void
    {
        $this->InscripcionId = $alumno->id;

        foreach ([
            'curp',
            'matricula',
            'folio',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
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
            'tutor_id',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_id',
            'ciclo_id',
            'estatus',
        ] as $campo) {
            $this->{$campo} = $alumno->{$campo};
        }

        $this->fecha_nacimiento = optional($alumno->fecha_nacimiento)->format('Y-m-d');
        $this->fecha_ingreso_plantel = optional($alumno->fecha_inscripcion)->format('Y-m-d');
        $this->fecha_estatus = optional($alumno->fecha_estatus)->format('Y-m-d') ?: now()->toDateString();
        $this->foto_actual = $alumno->foto_path;
        $this->foto_actual_existe = $alumno->foto_existe;
        $this->foto_actual_url = $alumno->foto_url;

        $this->observacion_ciclo_escolar_id = $this->ciclosEscolaresObservacion
            ->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolaresObservacion->first()?->id;
        $this->cargarObservacionCiclo();

        $this->recargarOpcionesAsignacionEscolar();
    }

    public function updatingObservacionCicloEscolarId($value): void
    {
        if (! $this->observacion_ciclo_escolar_id) {
            return;
        }

        $this->observacionesPorCiclo[(int) $this->observacion_ciclo_escolar_id] =
            app(ObservacionInscripcionService::class)->sanitizar($this->observaciones);
    }

    public function updatedObservacionCicloEscolarId($value): void
    {
        $this->observacion_ciclo_escolar_id = $value ? (int) $value : null;
        $this->resetValidation(['observacion_ciclo_escolar_id', 'observaciones']);
        $this->cargarObservacionCiclo(true);
    }

    private function cargarObservacionCiclo(bool $actualizarEditor = false): void
    {
        if (! $this->InscripcionId || ! $this->observacion_ciclo_escolar_id) {
            $this->observaciones = null;
        } elseif (array_key_exists((int) $this->observacion_ciclo_escolar_id, $this->observacionesPorCiclo)) {
            $this->observaciones = $this->observacionesPorCiclo[(int) $this->observacion_ciclo_escolar_id];
        } else {
            $this->observaciones = app(ObservacionInscripcionService::class)->sanitizar(
                ObservacionInscripcion::query()
                    ->where('inscripcion_id', $this->InscripcionId)
                    ->where('ciclo_escolar_id', $this->observacion_ciclo_escolar_id)
                    ->value('contenido')
            );

            $this->observacionesPorCiclo[(int) $this->observacion_ciclo_escolar_id] = $this->observaciones;
        }

        if ($actualizarEditor) {
            $this->dispatch(
                'reset-observaciones-editor',
                editor: 'observaciones-inscripcion-editar',
                contenido: $this->observaciones ?? '',
            );
        }
    }

    public function esBachillerato(): bool
    {
        $nivel = $this->niveles->firstWhere('id', $this->nivel_id)
            ?: Nivel::query()->find($this->nivel_id);

        return $nivel?->slug === 'bachillerato'
            || str_contains(mb_strtolower((string) ($nivel?->nombre ?? '')), 'bachillerato');
    }

    protected function baseGrupoQuery()
    {
        $query = Grupo::query();

        if (Schema::hasColumn('grupos', 'deleted_at')) {
            $query->whereNull('grupos.deleted_at');
        }

        return $query;
    }

    protected function loadNivelesFromGrupos(): Collection
    {
        $ids = $this->baseGrupoQuery()
            ->select('nivel_id')
            ->distinct()
            ->pluck('nivel_id')
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug', 'color']);
        }

        return Nivel::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug', 'color']);
    }

    protected function loadGradosFromGrupos(): Collection
    {
        if (! $this->nivel_id || $this->esBachillerato()) {
            return collect();
        }

        $ids = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->select('grado_id')
            ->distinct()
            ->pluck('grado_id')
            ->filter()
            ->values();

        return Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->when($ids->isNotEmpty(), fn ($query) => $query->whereIn('id', $ids))
            ->orderBy('orden')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);
    }

    protected function loadGeneracionesFromGrupos(): Collection
    {
        if (! $this->nivel_id) {
            return collect();
        }

        $query = $this->baseGrupoQuery()->where('nivel_id', $this->nivel_id);

        if (! $this->esBachillerato()) {
            if (! $this->grado_id) {
                return collect();
            }

            $query->where('grado_id', $this->grado_id)->whereNull('semestre_id');
        }

        $ids = $query->select('generacion_id')->distinct()->pluck('generacion_id')->filter()->values();

        if ($this->generacion_id) {
            $ids->push($this->generacion_id);
            $ids = $ids->unique()->values();
        }

        if ($ids->isEmpty()) {
            return collect();
        }

        return Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->whereIn('id', $ids)
            ->where(function ($query): void {
                $query->where('status', true);

                if ($this->generacion_id) {
                    $query->orWhere('generaciones.id', $this->generacion_id);
                }
            })
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'nombre', 'status']);
    }

    protected function loadSemestresFromGrupos(): Collection
    {
        if (! $this->esBachillerato() || ! $this->nivel_id || ! $this->generacion_id) {
            return collect();
        }

        $ids = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->whereNotNull('semestre_id')
            ->select('semestre_id')
            ->distinct()
            ->pluck('semestre_id')
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Semestre::query()
            ->whereIn('id', $ids)
            ->with('grado:id,nombre')
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero']);
    }

    protected function loadGruposOptionsFromGrupos(): array
    {
        if (! $this->nivel_id || ! $this->generacion_id) {
            return [];
        }

        $query = $this->baseGrupoQuery()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id);

        if ($this->esBachillerato()) {
            if (! $this->semestre_id) {
                return [];
            }

            $query->where('semestre_id', $this->semestre_id);
        } else {
            if (! $this->grado_id) {
                return [];
            }

            $query->where('grado_id', $this->grado_id)->whereNull('semestre_id');
        }

        return $query
            ->with([
                'asignacionGrupo:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso,nombre',
                'grado:id,nombre',
                'semestre:id,numero',
            ])
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->orderBy('grupos.grado_id')
            ->orderBy('grupos.semestre_id')
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get()
            ->map(function (Grupo $grupo): array {
                $generacion = $grupo->generacion?->etiqueta ?? 'Sin generación';
                $partes = [$grupo->asignacionGrupo?->nombre ?? 'Sin grupo'];

                if ($grupo->grado) {
                    $partes[] = $grupo->grado->nombre;
                }

                if ($grupo->semestre) {
                    $partes[] = 'Semestre ' . $grupo->semestre->numero;
                }

                $partes[] = $generacion;

                return [
                    'id' => (int) $grupo->id,
                    'grado_id' => $grupo->grado_id ? (int) $grupo->grado_id : null,
                    'semestre_id' => $grupo->semestre_id ? (int) $grupo->semestre_id : null,
                    'label' => implode(' · ', array_filter($partes)),
                ];
            })
            ->toArray();
    }

    private function recargarOpcionesAsignacionEscolar(): void
    {
        $this->gradosOptions = collect();
        $this->generacionesOptions = collect();
        $this->semestresOptions = collect();
        $this->gruposOptions = [];

        if (! $this->nivel_id) {
            return;
        }

        if ($this->esBachillerato()) {
            $this->generacionesOptions = $this->loadGeneracionesFromGrupos();

            if ($this->generacion_id) {
                $this->semestresOptions = $this->loadSemestresFromGrupos();
            }

            if ($this->generacion_id && $this->semestre_id) {
                $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
            }

            return;
        }

        $this->gradosOptions = $this->loadGradosFromGrupos();

        if ($this->grado_id) {
            $this->generacionesOptions = $this->loadGeneracionesFromGrupos();
        }

        if ($this->grado_id && $this->generacion_id) {
            $this->gruposOptions = $this->loadGruposOptionsFromGrupos();
        }
    }

    public function updatedNivelId($value): void
    {
        $this->nivel_id = $value ? (int) $value : null;
        $this->grado_id = null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->resetValidation(['nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'grupo_id']);
        $this->recargarOpcionesAsignacionEscolar();
    }

    public function updatedGradoId($value): void
    {
        $this->grado_id = $value ? (int) $value : null;
        $this->generacion_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->resetValidation(['grado_id', 'generacion_id', 'semestre_id', 'grupo_id']);
        $this->recargarOpcionesAsignacionEscolar();
    }

    public function updatedGeneracionId($value): void
    {
        $this->generacion_id = $value ? (int) $value : null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        if ($this->esBachillerato()) {
            $this->grado_id = null;
        }

        $this->resetValidation(['generacion_id', 'semestre_id', 'grupo_id']);
        $this->recargarOpcionesAsignacionEscolar();
    }

    public function updatedSemestreId($value): void
    {
        $this->semestre_id = $value ? (int) $value : null;
        $this->grupo_id = null;

        if ($this->esBachillerato()) {
            $this->grado_id = null;
        }

        $this->resetValidation(['semestre_id', 'grupo_id']);
        $this->recargarOpcionesAsignacionEscolar();
    }

    public function updatedGrupoId($value): void
    {
        $this->grupo_id = $value ? (int) $value : null;

        if ($this->esBachillerato() && $this->grupo_id) {
            $grupo = $this->baseGrupoQuery()
                ->whereKey($this->grupo_id)
                ->where('nivel_id', $this->nivel_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->first(['id', 'grado_id']);

            $this->grado_id = $grupo?->grado_id ? (int) $grupo->grado_id : null;
        }
    }

    public function updatedTutorId($value): void
    {
        $this->tutor_id = $value ? (int) $value : null;

        if ($this->copiar_direccion_tutor && $this->tutor_id) {
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

    private function llenarDireccionDesdeTutor(): void
    {
        $tutor = Tutor::query()->find($this->tutor_id);

        if (! $tutor) {
            return;
        }

        $this->calle = $tutor->calle;
        $this->numero_exterior = $tutor->numero_exterior ?? $tutor->numero ?? null;
        $this->numero_interior = $tutor->numero_interior;
        $this->colonia = $tutor->colonia;
        $this->codigo_postal = $tutor->codigo_postal;
        $this->municipio = $tutor->municipio;
        $this->estado_residencia = $tutor->estado_residencia ?? $tutor->estado ?? null;
        $this->ciudad_residencia = $tutor->ciudad_residencia ?? $tutor->ciudad ?? null;
    }

    public function consultarCurp(CurpService $service): void
    {
        $this->consultandoCurp = true;
        $this->curpError = null;
        $this->curpSuccess = null;

        try {
            $respuesta = $service->obtenerDatosPorCurp($this->curp);
        } finally {
            $this->consultandoCurp = false;
        }

        if ($respuesta['error'] ?? true) {
            $this->curpError = $respuesta['message'] ?? 'No fue posible consultar la CURP.';
            return;
        }

        $datos = $respuesta['datos'] ?? [];
        $this->curp = mb_strtoupper($datos['curp'] ?: $this->curp);
        $this->nombre = $datos['nombre'] ?: $this->nombre;
        $this->apellido_paterno = $datos['apellido_paterno'] ?: $this->apellido_paterno;
        $this->apellido_materno = $datos['apellido_materno'] ?: $this->apellido_materno;
        $this->genero = in_array($datos['genero'] ?? null, ['H', 'M'], true)
            ? $datos['genero']
            : $this->genero;

        if (! empty($datos['fecha_nacimiento'])) {
            try {
                $this->fecha_nacimiento = Carbon::parse($datos['fecha_nacimiento'])->format('Y-m-d');
            } catch (\Throwable) {
                // Conserva la fecha capturada si el servicio devuelve un formato inesperado.
            }
        }

        $this->pais_nacimiento = $datos['pais_nacimiento'] ?: $this->pais_nacimiento;
        $this->estado_nacimiento = $datos['estado_nacimiento'] ?: $this->estado_nacimiento;
        $this->lugar_nacimiento = $datos['lugar_nacimiento'] ?: $this->lugar_nacimiento;
        $this->curpSuccess = 'Los datos de la CURP se actualizaron en el formulario.';
    }

    public function quitarFotoTemporal(): void
    {
        $this->foto = null;
        $this->resetValidation('foto');
        $this->dispatch('foto-limpiada');
    }

    private function sanitizar(): void
    {
        $this->curp = mb_strtoupper(trim($this->curp));
        $this->matricula = mb_strtoupper(trim($this->matricula));
        $this->folio = filled($this->folio) ? trim((string) $this->folio) : null;
        $this->nombre = trim($this->nombre);
        $this->apellido_paterno = trim($this->apellido_paterno);
        $this->apellido_materno = filled($this->apellido_materno) ? trim((string) $this->apellido_materno) : null;

        foreach ([
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
        ] as $campo) {
            $this->{$campo} = filled($this->{$campo}) ? trim((string) $this->{$campo}) : null;
        }
    }

    protected function rules(): array
    {
        return [
            'curp' => [
                'required',
                'string',
                'size:18',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique('inscripciones', 'curp')->ignore($this->InscripcionId),
            ],
            'matricula' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9\-]+$/i',
                Rule::unique('inscripciones', 'matricula')->ignore($this->InscripcionId),
            ],
            'folio' => ['nullable', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'genero' => ['required', Rule::in(['H', 'M'])],
            'fecha_ingreso_plantel' => ['required', 'date'],
            'ciclo_id' => ['required', 'integer', Rule::exists('ciclos', 'id')],
            'observacion_ciclo_escolar_id' => [
                'required',
                'integer',
                Rule::exists('ciclo_escolares', 'id'),
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

            'nivel_id' => ['required', 'integer', Rule::exists('niveles', 'id')],
            'grado_id' => [Rule::requiredIf(! $this->esBachillerato()), 'nullable', 'integer', Rule::exists('grados', 'id')],
            'generacion_id' => ['required', 'integer', Rule::exists('generaciones', 'id')],
            'semestre_id' => [Rule::requiredIf($this->esBachillerato()), 'nullable', 'integer', Rule::exists('semestres', 'id')],
            'grupo_id' => ['required', 'integer', Rule::exists('grupos', 'id')],

            'estatus' => ['required', Rule::in(GestionAcademicaService::ESTATUS)],
            'fecha_estatus' => ['required', 'date'],
            'motivo_cambio' => ['nullable', 'string', 'max:1000'],
            'confirmar_cambio_academico' => ['boolean'],

            'pais_nacimiento' => ['nullable', 'string', 'max:150'],
            'estado_nacimiento' => ['nullable', 'string', 'max:150'],
            'lugar_nacimiento' => ['nullable', 'string', 'max:150'],
            'calle' => ['nullable', 'string', 'max:255'],
            'numero_exterior' => ['nullable', 'string', 'max:50'],
            'numero_interior' => ['nullable', 'string', 'max:50'],
            'colonia' => ['nullable', 'string', 'max:150'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],
            'municipio' => ['nullable', 'string', 'max:150'],
            'estado_residencia' => ['nullable', 'string', 'max:150'],
            'ciudad_residencia' => ['nullable', 'string', 'max:150'],
            'tutor_id' => ['nullable', 'integer', Rule::exists('tutores', 'id')],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    private function validarRelacionAcademica(array &$data, Inscripcion $alumno): bool
    {
        $nivel = Nivel::query()->find((int) $data['nivel_id']);
        $esBachillerato = $nivel?->slug === 'bachillerato';

        $generacion = Generacion::query()->find((int) $data['generacion_id']);

        if (! $generacion || (int) $generacion->nivel_id !== (int) $data['nivel_id']) {
            $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado.');
            return false;
        }

        if (! $generacion->status && (int) $alumno->generacion_id !== (int) $generacion->id) {
            $this->addError('generacion_id', 'No puedes reasignar al alumno a una generación inactiva.');
            return false;
        }

        $grupoQuery = $this->baseGrupoQuery()
            ->whereKey((int) $data['grupo_id'])
            ->where('nivel_id', (int) $data['nivel_id'])
            ->where('generacion_id', (int) $data['generacion_id']);

        if ($esBachillerato) {
            $grupo = $grupoQuery
                ->where('semestre_id', (int) $data['semestre_id'])
                ->first(['id', 'grado_id', 'semestre_id']);

            if (! $grupo) {
                $this->addError('grupo_id', 'El grupo no corresponde al nivel, generación y semestre seleccionados.');
                return false;
            }

            $semestreValido = Semestre::query()
                ->whereKey((int) $data['semestre_id'])
                ->where('grado_id', (int) $grupo->grado_id)
                ->exists();

            if (! $semestreValido) {
                $this->addError('semestre_id', 'El semestre no corresponde al grado interno del grupo seleccionado.');
                return false;
            }

            $data['grado_id'] = (int) $grupo->grado_id;
            $this->grado_id = (int) $grupo->grado_id;

            return true;
        }

        $grupo = $grupoQuery
            ->where('grado_id', (int) $data['grado_id'])
            ->whereNull('semestre_id')
            ->first(['id']);

        if (! $grupo) {
            $this->addError('grupo_id', 'El grupo no corresponde al nivel, grado y generación seleccionados.');
            return false;
        }

        $data['semestre_id'] = null;
        $this->semestre_id = null;

        return true;
    }

    public function actualizarInscripcion(
        GestionAcademicaService $service,
        ImagenPersonalService $imagenes,
        ObservacionInscripcionService $observacionesService,
    ) {
        $this->sanitizar();
        $this->observaciones = $observacionesService->sanitizar($this->observaciones);

        if ($this->observacion_ciclo_escolar_id) {
            $this->observacionesPorCiclo[(int) $this->observacion_ciclo_escolar_id] = $this->observaciones;
        }

        foreach ($this->observacionesPorCiclo as $cicloId => $contenido) {
            if ($contenido !== null && ! is_string($contenido)) {
                $this->addError('observaciones', 'El contenido de las observaciones no tiene un formato válido.');

                return null;
            }

            if (! $this->ciclosEscolaresObservacion->contains('id', (int) $cicloId)) {
                $this->addError('observacion_ciclo_escolar_id', 'El ciclo escolar de una observación ya no está disponible.');

                return null;
            }

            $contenido = $observacionesService->sanitizar($contenido);
            $this->observacionesPorCiclo[(int) $cicloId] = $contenido;

            if ($observacionesService->excedeLimite($contenido)) {
                $ciclo = $this->ciclosEscolaresObservacion->firstWhere('id', (int) $cicloId);
                $nombreCiclo = $ciclo ? $ciclo->inicio_anio.'-'.$ciclo->fin_anio : (string) $cicloId;
                $this->addError('observaciones', "Las observaciones del ciclo {$nombreCiclo} superan 5,000 caracteres.");

                return null;
            }
        }

        $data = $this->validate();
        $alumno = Inscripcion::withTrashed()->findOrFail($this->InscripcionId);

        if (! $this->validarRelacionAcademica($data, $alumno)) {
            return null;
        }

        $cambioAcademico = (int) $alumno->nivel_id !== (int) $data['nivel_id']
            || (int) $alumno->grado_id !== (int) $data['grado_id']
            || (int) $alumno->generacion_id !== (int) $data['generacion_id']
            || (int) $alumno->grupo_id !== (int) $data['grupo_id']
            || (int) ($alumno->semestre_id ?? 0) !== (int) ($data['semestre_id'] ?? 0);

        $cambioEstatus = ($alumno->estatus ?? 'activo') !== $data['estatus'];

        if (($cambioAcademico || $cambioEstatus) && mb_strlen(trim($data['motivo_cambio'] ?? '')) < 5) {
            $this->addError('motivo_cambio', 'Indica el motivo del cambio académico o de estatus.');
            return null;
        }

        if ($cambioAcademico && ! $this->confirmar_cambio_academico) {
            $this->addError('confirmar_cambio_academico', 'Confirma que deseas reemplazar la asignación académica actual.');
            return null;
        }

        DB::transaction(function () use ($alumno, $service, $imagenes, $observacionesService, $data, $cambioAcademico, $cambioEstatus): void {
            $fotoPath = $alumno->foto_path;

            if ($this->foto) {
                $fotoPath = $imagenes->guardar($this->foto, 'inscripciones/fotos', 1200, false);
                $imagenes->eliminarRuta($alumno->foto_path);
            }

            $alumno->update([
                'curp' => $data['curp'],
                'matricula' => $data['matricula'],
                'folio' => $data['folio'] ?? null,
                'nombre' => $data['nombre'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'] ?? null,
                'fecha_nacimiento' => $data['fecha_nacimiento'],
                'genero' => $data['genero'],
                'fecha_inscripcion' => $data['fecha_ingreso_plantel'],
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
                'tutor_id' => $data['tutor_id'] ?? null,
                'foto_path' => $fotoPath,
            ]);

            foreach ($this->observacionesPorCiclo as $cicloId => $contenido) {
                $observacionesService->guardar(
                    inscripcion: $alumno,
                    cicloEscolarId: (int) $cicloId,
                    contenido: $contenido,
                    origen: 'edicion',
                    usuarioId: auth()->id(),
                );
            }

            if ($cambioAcademico) {
                $service->cambiarAsignacion($alumno, [
                    'nivel_id' => (int) $data['nivel_id'],
                    'grado_id' => (int) $data['grado_id'],
                    'generacion_id' => (int) $data['generacion_id'],
                    'semestre_id' => ! empty($data['semestre_id']) ? (int) $data['semestre_id'] : null,
                    'grupo_id' => (int) $data['grupo_id'],
                    'matricula' => $data['matricula'],
                ], trim((string) $data['motivo_cambio']), auth()->id());
            }

            if ($cambioEstatus) {
                $service->cambiarEstatus(
                    $alumno,
                    $data['estatus'],
                    trim((string) $data['motivo_cambio']),
                    auth()->id(),
                    $data['fecha_estatus']
                );
            }
        });

        session()->flash('success', 'Matrícula actualizada correctamente.');

        return redirect()->route('submodulos.accion', [
            'slug_nivel' => $this->slug_nivel,
            'accion' => 'matricula',
        ]);
    }

    public function render()
    {
        $resumenDocumental = null;

        if ($this->InscripcionId && auth()->user()?->is_admin) {
            $alumnoDocumental = Inscripcion::query()
                ->with([
                    'nivel:id,nombre,slug,color',
                    'documentos.tipoDocumento:id,nombre,slug,es_general,requiere_nivel,orden',
                    'documentos.nivel:id,nombre,slug,color',
                ])
                ->find($this->InscripcionId);

            if ($alumnoDocumental) {
                $resumenDocumental = app(ExpedienteDigitalService::class)->resumen($alumnoDocumental);
            }
        }

        return view('livewire.accion.editar-matricula', [
            'niveles' => $this->niveles,
            'grados' => $this->gradosOptions,
            'generaciones' => $this->generacionesOptions,
            'semestres' => $this->semestresOptions,
            'grupos' => $this->gruposOptions,
            'ciclos' => $this->ciclosOptions,
            'ciclosEscolaresObservacion' => $this->ciclosEscolaresObservacion,
            'tutores' => $this->tutores,
            'esBachillerato' => $this->esBachillerato(),
            'resumenDocumental' => $resumenDocumental,
        ]);
    }
}
