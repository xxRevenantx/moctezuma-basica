<?php

namespace App\Livewire\Tutor;

use App\Models\Inscripcion;
use App\Models\InscripcionTutor;
use App\Models\Tutor;
use App\Rules\CurpMexicana;
use App\Services\CurpService;
use App\Services\GestionResponsablesAlumnoService;
use App\Services\TutorCurpAutofillService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CrearTutor extends Component
{
    public bool $sin_curp = false;
    public ?string $curp = null;
    public ?string $identificador_alternativo = null;
    public ?string $motivo_sin_curp = null;
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;
    public ?string $genero = null;
    public ?string $fecha_nacimiento = null;
    public ?string $ciudad_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $municipio_nacimiento = null;
    public ?string $calle = null;
    public ?string $colonia = null;
    public ?string $ciudad = null;
    public ?string $municipio = null;
    public ?string $estado = null;
    public ?string $numero = null;
    public ?string $codigo_postal = null;
    public ?string $telefono_casa = null;
    public ?string $telefono_celular = null;
    public ?string $correo_electronico = null;

    // Consulta profesional de CURP, igual al flujo principal de inscripción.
    public bool $consultando_curp = false;
    public string $curp_estado = 'inicial';
    public string $curp_mensaje = 'Escribe una CURP para comprobar si ya está registrada.';
    public bool $curp_local_validada = false;
    public bool $curp_existe_local = false;
    public ?string $ultima_curp_validada_local = null;
    public ?string $ultima_curp_consultada = null;
    public ?string $curp_error = null;
    public ?string $curp_advertencia = null;
    public ?string $curp_exito = null;
    public ?array $tutor_existente = null;
    public ?array $alumno_con_misma_curp = null;
    public array $curp_datos_externos = [];
    public array $curp_diferencias = [];

    // Relación opcional con alumnos al registrar al responsable.
    public string $buscar_alumno = '';
    public bool $incluir_alumnos_historicos = false;
    public array $resultados_alumnos = [];
    public array $alumnos_relacionar = [];
    public bool $abrir_gestion_despues = true;

    #[Locked]
    public bool $puede_relaciones_sensibles = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.crear'), 403);
        $this->puede_relaciones_sensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');
    }

    protected function rules(): array
    {
        return [
            'sin_curp' => ['boolean'],
            'curp' => [Rule::requiredIf(! $this->sin_curp), 'nullable', 'string', 'size:18', new CurpMexicana(), 'unique:tutores,curp'],
            'identificador_alternativo' => [Rule::requiredIf($this->sin_curp), 'nullable', 'string', 'max:80', 'unique:tutores,identificador_alternativo'],
            'motivo_sin_curp' => [Rule::requiredIf($this->sin_curp), 'nullable', 'string', 'min:5', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'genero' => ['nullable', Rule::in(['M', 'F', 'O'])],
            'fecha_nacimiento' => ['nullable', 'date'],
            'ciudad_nacimiento' => ['nullable', 'string', 'max:255'],
            'estado_nacimiento' => ['nullable', 'string', 'max:255'],
            'municipio_nacimiento' => ['nullable', 'string', 'max:255'],
            'calle' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'codigo_postal' => ['nullable', 'regex:/^[0-9]{5}$/'],
            'telefono_casa' => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
            'alumnos_relacionar' => ['array'],
            'alumnos_relacionar.*.inscripcion_id' => ['required', 'integer', 'exists:inscripciones,id'],
            'alumnos_relacionar.*.parentesco' => ['required', Rule::in(GestionResponsablesAlumnoService::PARENTESCOS)],
            'alumnos_relacionar.*.estado_tutela' => ['required', Rule::in(GestionResponsablesAlumnoService::ESTADOS_TUTELA)],
            'alumnos_relacionar.*.fecha_inicio' => ['required', 'date'],
            'alumnos_relacionar.*.observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function updatedSinCurp(bool $sinCurp): void
    {
        if ($sinCurp) {
            $this->curp = null;
            $this->limpiarResultadoCurp();
        } else {
            $this->identificador_alternativo = null;
            $this->motivo_sin_curp = null;
        }

        $this->resetValidation(['curp', 'identificador_alternativo', 'motivo_sin_curp']);
    }

    public function updatedCurp(): void
    {
        $this->validarCurpLocal();
    }

    public function validarCurpLocal(): void
    {
        if ($this->sin_curp) {
            return;
        }

        $servicio = app(TutorCurpAutofillService::class);
        $resultado = $servicio->validarLocal($this->curp);
        $this->curp = $resultado['curp'];
        $this->curp_estado = $resultado['estado'];
        $this->curp_mensaje = $resultado['mensaje'];
        $this->curp_local_validada = (bool) $resultado['valida'];
        $this->curp_existe_local = $resultado['tutor_existente'] !== null;
        $this->tutor_existente = $resultado['tutor_existente'];
        $this->alumno_con_misma_curp = $resultado['alumno_existente'];
        $this->ultima_curp_validada_local = $this->curp;
        $this->curp_error = null;
        $this->curp_advertencia = null;
        $this->curp_exito = null;
        $this->curp_datos_externos = [];
        $this->curp_diferencias = [];
        $this->resetValidation('curp');

        if ($this->curp_existe_local) {
            $this->addError('curp', 'Esta CURP ya está registrada como responsable. Utiliza el registro existente.');
        }
    }

    public function consultarCurp(): void
    {
        if ($this->sin_curp) {
            return;
        }

        $autofill = app(TutorCurpAutofillService::class);
        $this->curp = $autofill->normalizar($this->curp);
        $this->curp_error = null;
        $this->curp_advertencia = null;
        $this->curp_exito = null;

        if (! $this->curp_local_validada || $this->ultima_curp_validada_local !== $this->curp) {
            $this->validarCurpLocal();
        }

        if (! $this->curp_local_validada || $this->curp_existe_local) {
            $this->curp_advertencia = $this->curp_existe_local
                ? 'La consulta externa se bloqueó porque el responsable ya existe localmente.'
                : 'Completa una CURP válida antes de consultar el servicio externo.';
            return;
        }

        if ($this->ultima_curp_consultada === $this->curp && $this->curp_datos_externos !== []) {
            return;
        }

        $this->consultando_curp = true;
        try {
            $payload = app(CurpService::class)->obtenerDatosPorCurp((string) $this->curp);
        } catch (\Throwable) {
            $payload = [
                'error' => true,
                'message' => 'No fue posible consultar el servicio externo. Puedes continuar manualmente.',
            ];
        } finally {
            $this->consultando_curp = false;
        }

        $this->ultima_curp_consultada = $this->curp;

        if (($payload['error'] ?? true) === true) {
            $this->curp_advertencia = (string) ($payload['message'] ?? 'No se encontraron datos para la CURP.');
            return;
        }

        $this->curp_datos_externos = $autofill->datosDesdePayload($payload);
        $this->aplicarDatosCurp(false);
    }

    public function aplicarDatosCurp(bool $reemplazar = false): void
    {
        if ($this->curp_datos_externos === []) {
            return;
        }

        $servicio = app(TutorCurpAutofillService::class);
        $actuales = [
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'genero' => $this->genero,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'estado_nacimiento' => $this->estado_nacimiento,
        ];
        $resultado = $servicio->aplicar($actuales, $this->curp_datos_externos, $reemplazar);

        foreach ($resultado['valores'] as $campo => $valor) {
            $this->{$campo} = $valor;
        }

        $this->curp_diferencias = $resultado['diferencias'];
        $this->curp_exito = $reemplazar
            ? "Se aplicaron {$resultado['aplicados']} datos del servicio, reemplazando los valores capturados."
            : "Se completaron {$resultado['aplicados']} campos vacíos y se conservaron {$resultado['conservados']} datos capturados.";
        $this->dispatch('tutor-curp-aplicada');
    }

    public function administrarTutorExistente(): void
    {
        $id = (int) ($this->tutor_existente['id'] ?? 0);
        if ($id <= 0) {
            return;
        }

        $this->dispatch('abrir-modal-alumnos-tutor');
        $this->dispatch('administrarAlumnosTutor', id: $id);
    }

    public function limpiarResultadoCurp(bool $limpiarCampo = false): void
    {
        if ($limpiarCampo) {
            $this->curp = '';
        }

        $this->curp_estado = 'inicial';
        $this->curp_mensaje = 'Escribe una CURP para comprobar si ya está registrada.';
        $this->curp_local_validada = false;
        $this->curp_existe_local = false;
        $this->ultima_curp_validada_local = null;
        $this->ultima_curp_consultada = null;
        $this->curp_error = null;
        $this->curp_advertencia = null;
        $this->curp_exito = null;
        $this->tutor_existente = null;
        $this->alumno_con_misma_curp = null;
        $this->curp_datos_externos = [];
        $this->curp_diferencias = [];
        $this->resetValidation('curp');
    }

    public function updatedBuscarAlumno(): void
    {
        $this->buscarAlumnos();
    }

    public function updatedIncluirAlumnosHistoricos(): void
    {
        $this->buscarAlumnos();
    }

    public function buscarAlumnos(): void
    {
        $termino = trim($this->buscar_alumno);
        if (mb_strlen($termino) < 2) {
            $this->resultados_alumnos = [];
            return;
        }

        $seleccionados = collect($this->alumnos_relacionar)
            ->pluck('inscripcion_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = $this->incluir_alumnos_historicos
            ? Inscripcion::withTrashed()
            : Inscripcion::query()->visiblesEnListas();

        $alumnos = $query
            ->with(['nivel:id,nombre', 'grado:id,nombre', 'semestre:id,numero', 'grupo:id,clave', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->whereNotIn('id', $seleccionados ?: [0])
            ->where(function (Builder $q) use ($termino): void {
                $like = '%' . $termino . '%';
                $q->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$like])
                    ->orWhere('curp', 'like', '%' . mb_strtoupper($termino) . '%')
                    ->orWhere('matricula', 'like', $like)
                    ->orWhere('folio', 'like', $like);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(12)
            ->get();

        $this->resultados_alumnos = $alumnos
            ->map(fn (Inscripcion $alumno): array => $this->alumnoParaVista($alumno))
            ->all();
    }

    public function seleccionarAlumno(int $inscripcionId): void
    {
        if (collect($this->alumnos_relacionar)->contains('inscripcion_id', $inscripcionId)) {
            return;
        }

        $alumno = Inscripcion::withTrashed()
            ->with(['nivel:id,nombre', 'grado:id,nombre', 'semestre:id,numero', 'grupo:id,clave', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->findOrFail($inscripcionId);

        $tienePrincipal = InscripcionTutor::query()
            ->where('inscripcion_id', $alumno->id)
            ->activas()
            ->where('es_principal', true)
            ->exists();

        $this->alumnos_relacionar[] = [
            ...$this->alumnoParaVista($alumno),
            'inscripcion_id' => (int) $alumno->id,
            'parentesco' => 'OTRO',
            'es_principal' => ! $tienePrincipal,
            'es_tutor_legal' => false,
            'estado_tutela' => 'no_aplica',
            'vive_con_alumno' => false,
            'recibe_avisos' => true,
            'recibe_calificaciones' => true,
            'contacto_emergencia' => false,
            'autorizado_recoger' => false,
            'responsable_economico' => false,
            'fecha_inicio' => now()->toDateString(),
            'observaciones' => '',
            'reemplazara_principal' => $tienePrincipal,
        ];

        $this->buscar_alumno = '';
        $this->resultados_alumnos = [];
    }

    public function quitarAlumno(int $indice): void
    {
        if (! isset($this->alumnos_relacionar[$indice])) {
            return;
        }

        unset($this->alumnos_relacionar[$indice]);
        $this->alumnos_relacionar = array_values($this->alumnos_relacionar);
    }

    public function guardar(GestionResponsablesAlumnoService $responsablesService): void
    {
        $this->normalizar();

        if (! $this->sin_curp) {
            $this->validarCurpLocal();
            if ($this->curp_existe_local) {
                throw ValidationException::withMessages([
                    'curp' => 'La CURP ya pertenece a un responsable. Selecciona el registro existente.',
                ]);
            }
        }

        $data = $this->validate();

        if (blank($data['telefono_celular']) && blank($data['telefono_casa']) && blank($data['correo_electronico'])) {
            throw ValidationException::withMessages([
                'telefono_celular' => 'Captura al menos teléfono celular, teléfono de casa o correo electrónico.',
            ]);
        }

        $tutor = DB::transaction(function () use ($data, $responsablesService): Tutor {
            $tutor = Tutor::query()->create([
                ...collect($data)->except(['alumnos_relacionar', 'sin_curp'])->all(),
                'curp' => blank($data['curp']) ? null : $data['curp'],
                'identificador_alternativo' => blank($data['identificador_alternativo']) ? null : $data['identificador_alternativo'],
                'motivo_sin_curp' => blank($data['motivo_sin_curp']) ? null : $data['motivo_sin_curp'],
                'parentesco' => 'NO ESPECIFICADO',
                'activo' => true,
            ]);

            foreach ($this->alumnos_relacionar as $relacion) {
                if (! $this->puede_relaciones_sensibles) {
                    $relacion['es_tutor_legal'] = false;
                    $relacion['estado_tutela'] = 'no_aplica';
                    $relacion['recibe_calificaciones'] = false;
                    $relacion['autorizado_recoger'] = false;
                }

                $alumno = Inscripcion::withTrashed()->findOrFail((int) $relacion['inscripcion_id']);
                $responsablesService->agregarOActualizar($alumno, $tutor, $relacion, auth()->id());
            }

            return $tutor;
        });

        $relaciones = count($this->alumnos_relacionar);
        $this->dispatch('swal', [
            'title' => 'Responsable creado correctamente',
            'text' => $relaciones > 0
                ? "Se relacionó con {$relaciones} alumno(s). Puedes revisar cada vínculo en el administrador."
                : 'El responsable quedó disponible para relacionarlo con uno o varios alumnos.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $abrirGestion = $this->abrir_gestion_despues;
        $this->dispatch('tutorRegistered', tutor: $tutor->id);
        $this->limpiar();
        $this->dispatch('refreshTutor');

        if ($abrirGestion) {
            $this->dispatch('abrir-modal-alumnos-tutor');
            $this->dispatch('administrarAlumnosTutor', id: $tutor->id);
        }
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->curp_estado = 'inicial';
        $this->curp_mensaje = 'Escribe una CURP para comprobar si ya está registrada.';
        $this->abrir_gestion_despues = true;
        $this->puede_relaciones_sensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');
        $this->resetValidation();
    }

    private function normalizar(): void
    {
        $this->curp = app(TutorCurpAutofillService::class)->normalizar($this->curp);
        $this->identificador_alternativo = mb_strtoupper(trim((string) $this->identificador_alternativo));

        if ($this->sin_curp) {
            $this->curp = null;
        } else {
            $this->identificador_alternativo = null;
            $this->motivo_sin_curp = null;
        }

        foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $campo) {
            $this->{$campo} = $this->titleCase((string) $this->{$campo});
        }

        foreach (['motivo_sin_curp', 'ciudad_nacimiento', 'estado_nacimiento', 'municipio_nacimiento', 'calle', 'colonia', 'ciudad', 'municipio', 'estado', 'numero', 'codigo_postal', 'telefono_casa', 'telefono_celular', 'correo_electronico'] as $campo) {
            $valor = trim((string) $this->{$campo});
            $this->{$campo} = $valor === '' ? null : $valor;
        }
    }

    private function alumnoParaVista(Inscripcion $alumno): array
    {
        $ubicacion = $alumno->semestre
            ? $alumno->semestre->numero . '° semestre'
            : ($alumno->grado?->nombre ?: 'Sin grado');
        $ciclo = $alumno->cicloEscolar
            ? $alumno->cicloEscolar->inicio_anio . '-' . $alumno->cicloEscolar->fin_anio
            : 'Sin ciclo';

        return [
            'id' => (int) $alumno->id,
            'nombre' => trim(collect([$alumno->nombre, $alumno->apellido_paterno, $alumno->apellido_materno])->filter()->join(' ')),
            'matricula' => $alumno->matricula ?: ($alumno->folio ?: 'Sin matrícula'),
            'curp' => $alumno->curp,
            'estatus' => $alumno->etiqueta_estatus,
            'activo_listas' => $alumno->visibleEnListas(),
            'ubicacion' => trim(collect([$alumno->nivel?->nombre, $ubicacion, $alumno->grupo?->clave ? 'Grupo ' . $alumno->grupo->clave : null])->filter()->join(' · ')),
            'ciclo' => $ciclo,
        ];
    }

    private function titleCase(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }

        $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        foreach ([' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y ', ' E '] as $particle) {
            $value = str_replace($particle, mb_strtolower($particle, 'UTF-8'), $value);
        }

        return $value;
    }

    public function render()
    {
        return view('livewire.tutor.crear-tutor', [
            'parentescos' => GestionResponsablesAlumnoService::PARENTESCOS,
            'estadosTutela' => GestionResponsablesAlumnoService::ESTADOS_TUTELA,
        ]);
    }
}
