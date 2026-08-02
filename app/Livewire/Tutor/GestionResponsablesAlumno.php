<?php

namespace App\Livewire\Tutor;

use App\Models\Inscripcion;
use App\Models\InscripcionTutor;
use App\Models\Tutor;
use App\Rules\CurpMexicana;
use App\Services\CurpService;
use App\Services\GestionResponsablesAlumnoService;
use App\Services\TutorCurpAutofillService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GestionResponsablesAlumno extends Component
{
    #[Locked]
    public int $inscripcionId;
    public string $buscar = '';
    public array $resultados = [];
    public array $relaciones = [];
    public array $motivosRetiro = [];
    public bool $mostrarNuevo = false;
    public ?string $mensaje = null;
    #[Locked]
    public bool $puedeGestionarResponsablesSensibles = false;
    #[Locked]
    public bool $puedeCrearResponsables = false;

    public array $nuevo = [
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

    public bool $consultandoCurpNuevo = false;
    public string $curpNuevoEstado = 'inicial';
    public string $curpNuevoMensaje = 'Escribe una CURP para comprobar si el responsable ya existe.';
    public bool $curpNuevoValida = false;
    public ?string $ultimaCurpNuevoValidada = null;
    public ?string $ultimaCurpNuevoConsultada = null;
    public ?string $curpNuevoAdvertencia = null;
    public ?string $curpNuevoExito = null;
    public ?array $tutorExistenteNuevo = null;
    public ?array $alumnoMismaCurpNuevo = null;
    public array $datosCurpNuevo = [];
    public array $diferenciasCurpNuevo = [];

    public function mount(int $inscripcionId): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.editar'), 403);
        $this->puedeGestionarResponsablesSensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');
        $this->puedeCrearResponsables = (bool) auth()->user()?->canAccess('alumnos.crear');
        $this->inscripcionId = $inscripcionId;
        $this->cargarRelaciones();
    }

    public function updatedBuscar(): void
    {
        $this->buscarTutores();
    }

    public function buscarTutores(): void
    {
        $this->autorizarGestion();
        $termino = trim($this->buscar);

        if (mb_strlen($termino) < 2) {
            $this->resultados = [];
            return;
        }

        $normalizado = mb_strtoupper($termino);
        $yaRelacionados = collect($this->relaciones)->pluck('tutor_id')->map(fn ($id) => (int) $id)->all();

        $this->resultados = Tutor::query()
            ->activos()
            ->whereNotIn('id', $yaRelacionados ?: [0])
            ->where(function ($query) use ($termino, $normalizado): void {
                $like = '%' . $termino . '%';
                $query->where('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhere('telefono_celular', 'like', $like)
                    ->orWhere('telefono_casa', 'like', $like)
                    ->orWhere('correo_electronico', 'like', $like)
                    ->orWhere('identificador_alternativo', 'like', $like)
                    ->orWhere('curp', 'like', '%' . $normalizado . '%');
            })
            ->withCount(['relacionesActivas'])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(8)
            ->get()
            ->map(fn (Tutor $tutor): array => $this->tutorParaVista($tutor))
            ->all();
    }

    public function agregarTutor(int $tutorId, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizarGestion();
        $alumno = Inscripcion::query()->findOrFail($this->inscripcionId);
        $tutor = Tutor::query()->activos()->findOrFail($tutorId);
        $tienePrincipal = collect($this->relaciones)->contains(fn (array $item): bool => $item['activo'] && $item['es_principal']);

        $service->agregarOActualizar($alumno, $tutor, [
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
        ], auth()->id());

        $this->buscar = '';
        $this->resultados = [];
        $this->mensaje = 'Responsable agregado. Configura su parentesco y funciones.';
        $this->cargarRelaciones();
        $this->dispatch('responsables-actualizados');
    }

    public function guardarRelacion(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizarGestion();
        $relacion = $this->relaciones[$indice] ?? null;

        if (! $relacion) {
            return;
        }

        $this->validate([
            "relaciones.{$indice}.parentesco" => ['required', Rule::in(GestionResponsablesAlumnoService::PARENTESCOS)],
            "relaciones.{$indice}.estado_tutela" => ['required', Rule::in(GestionResponsablesAlumnoService::ESTADOS_TUTELA)],
            "relaciones.{$indice}.observaciones" => ['nullable', 'string', 'max:1000'],
        ]);

        $alumno = Inscripcion::query()->findOrFail($this->inscripcionId);
        $persistida = $this->relacionPersistida($indice);

        if (! $persistida->activo) {
            throw ValidationException::withMessages([
                'responsables' => 'La relación está archivada. Reactívala antes de modificarla.',
            ]);
        }

        $tutor = Tutor::query()->findOrFail((int) $persistida->tutor_id);
        $this->puedeGestionarResponsablesSensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');

        if (! $this->puedeGestionarResponsablesSensibles) {
            foreach (['es_tutor_legal', 'estado_tutela', 'recibe_calificaciones', 'autorizado_recoger'] as $campo) {
                $relacion[$campo] = $persistida->{$campo};
            }
        }

        $relacion['tutor_id'] = $persistida->tutor_id;
        $service->agregarOActualizar($alumno, $tutor, $relacion, auth()->id());

        $this->mensaje = 'La relación del responsable fue actualizada.';
        $this->cargarRelaciones();
        $this->dispatch('responsables-actualizados');
    }

    public function establecerPrincipal(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizarGestion();
        $relacion = $this->relaciones[$indice] ?? null;

        if (! $relacion || ! $relacion['activo']) {
            return;
        }

        $persistida = $this->relacionPersistida($indice);
        $service->establecerPrincipal(
            Inscripcion::query()->findOrFail($this->inscripcionId),
            (int) $persistida->tutor_id,
            auth()->id(),
        );

        $this->mensaje = 'Contacto principal actualizado sin eliminar al responsable anterior.';
        $this->cargarRelaciones();
        $this->dispatch('responsables-actualizados');
    }

    public function desactivar(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizarGestion();
        $relacion = $this->relaciones[$indice] ?? null;

        if (! $relacion) {
            return;
        }

        $persistida = $this->relacionPersistida($indice);
        $service->desactivar(
            Inscripcion::query()->findOrFail($this->inscripcionId),
            (int) $persistida->tutor_id,
            (string) ($this->motivosRetiro[$persistida->id] ?? ''),
            auth()->id(),
        );

        unset($this->motivosRetiro[$persistida->id]);
        $this->mensaje = 'La relación quedó archivada y permanece disponible en el historial.';
        $this->cargarRelaciones();
        $this->dispatch('responsables-actualizados');
    }

    public function reactivar(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizarGestion();
        $relacion = $this->relaciones[$indice] ?? null;

        if (! $relacion) {
            return;
        }

        $persistida = $this->relacionPersistida($indice);
        $service->reactivar(
            Inscripcion::query()->findOrFail($this->inscripcionId),
            (int) $persistida->tutor_id,
            auth()->id(),
        );

        $this->mensaje = 'La relación del responsable fue reactivada.';
        $this->cargarRelaciones();
        $this->dispatch('responsables-actualizados');
    }

    public function usarDomicilio(int $indice): void
    {
        $this->autorizarGestion();
        $relacion = $this->relaciones[$indice] ?? null;

        if (! $relacion) {
            return;
        }

        $persistida = $this->relacionPersistida($indice);

        if (! $persistida->activo) {
            throw ValidationException::withMessages([
                'responsables' => 'No puedes utilizar el domicilio de una relación histórica.',
            ]);
        }

        $this->dispatch('usar-domicilio-responsable', tutorId: (int) $persistida->tutor_id);
    }

    public function updatedNuevo(mixed $value, string $key): void
    {
        if ($key === 'sin_curp') {
            if ((bool) $value) {
                $this->nuevo['curp'] = '';
                $this->limpiarCurpNuevo();
            } else {
                $this->nuevo['identificador_alternativo'] = '';
                $this->nuevo['motivo_sin_curp'] = '';
            }

            $this->resetValidation(['nuevo.curp', 'nuevo.identificador_alternativo', 'nuevo.motivo_sin_curp']);
            return;
        }

        if ($key === 'curp') {
            $this->validarCurpNuevo();
        }
    }

    public function validarCurpNuevo(): void
    {
        if ($this->nuevo['sin_curp'] ?? false) {
            return;
        }

        $resultado = app(TutorCurpAutofillService::class)->validarLocal($this->nuevo['curp'] ?? '');
        $this->nuevo['curp'] = $resultado['curp'];
        $this->curpNuevoEstado = $resultado['estado'];
        $this->curpNuevoMensaje = $resultado['mensaje'];
        $this->curpNuevoValida = (bool) $resultado['valida'];
        $this->ultimaCurpNuevoValidada = $resultado['curp'];
        $this->tutorExistenteNuevo = $resultado['tutor_existente'];
        $this->alumnoMismaCurpNuevo = $resultado['alumno_existente'];
        $this->curpNuevoAdvertencia = null;
        $this->curpNuevoExito = null;
        $this->datosCurpNuevo = [];
        $this->diferenciasCurpNuevo = [];
        $this->resetValidation('nuevo.curp');

        if ($this->tutorExistenteNuevo) {
            $this->addError('nuevo.curp', 'La CURP ya pertenece a un responsable. Relaciona el registro existente.');
        }
    }

    public function consultarCurpNuevo(): void
    {
        $servicio = app(TutorCurpAutofillService::class);
        $this->nuevo['curp'] = $servicio->normalizar($this->nuevo['curp'] ?? '');
        $this->curpNuevoAdvertencia = null;
        $this->curpNuevoExito = null;

        if (! $this->curpNuevoValida || $this->ultimaCurpNuevoValidada !== $this->nuevo['curp']) {
            $this->validarCurpNuevo();
        }

        if (! $this->curpNuevoValida || $this->tutorExistenteNuevo) {
            $this->curpNuevoAdvertencia = $this->tutorExistenteNuevo
                ? 'El responsable ya existe y debe relacionarse sin duplicarlo.'
                : 'Captura una CURP válida antes de consultar.';
            return;
        }

        if ($this->ultimaCurpNuevoConsultada === $this->nuevo['curp'] && $this->datosCurpNuevo !== []) {
            return;
        }

        $this->consultandoCurpNuevo = true;
        try {
            $payload = app(CurpService::class)->obtenerDatosPorCurp($this->nuevo['curp']);
        } catch (\Throwable) {
            $payload = ['error' => true, 'message' => 'No fue posible consultar el servicio. Puedes continuar manualmente.'];
        } finally {
            $this->consultandoCurpNuevo = false;
        }

        $this->ultimaCurpNuevoConsultada = $this->nuevo['curp'];
        if (($payload['error'] ?? true) === true) {
            $this->curpNuevoAdvertencia = (string) ($payload['message'] ?? 'No se encontraron datos.');
            return;
        }

        $this->datosCurpNuevo = $servicio->datosDesdePayload($payload);
        $this->aplicarCurpNuevo(false);
    }

    public function aplicarCurpNuevo(bool $reemplazar = false): void
    {
        if ($this->datosCurpNuevo === []) {
            return;
        }

        $resultado = app(TutorCurpAutofillService::class)->aplicar([
            'nombre' => $this->nuevo['nombre'] ?? '',
            'apellido_paterno' => $this->nuevo['apellido_paterno'] ?? '',
            'apellido_materno' => $this->nuevo['apellido_materno'] ?? '',
            'genero' => $this->nuevo['genero'] ?? '',
            'fecha_nacimiento' => $this->nuevo['fecha_nacimiento'] ?? '',
            'estado_nacimiento' => '',
        ], $this->datosCurpNuevo, $reemplazar);

        foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'genero', 'fecha_nacimiento'] as $campo) {
            $this->nuevo[$campo] = $resultado['valores'][$campo] ?? $this->nuevo[$campo];
        }

        $this->diferenciasCurpNuevo = $resultado['diferencias'];
        $this->curpNuevoExito = $reemplazar
            ? 'Se aplicaron todos los datos del servicio.'
            : 'Se completaron campos vacíos sin reemplazar la captura previa.';
    }

    public function usarTutorExistenteNuevo(GestionResponsablesAlumnoService $service): void
    {
        $id = (int) ($this->tutorExistenteNuevo['id'] ?? 0);
        if ($id <= 0) {
            return;
        }

        if (! (bool) ($this->tutorExistenteNuevo['activo'] ?? false)) {
            throw ValidationException::withMessages([
                'nuevo.curp' => 'El responsable está archivado. Reactívalo desde el directorio de Tutores antes de relacionarlo.',
            ]);
        }

        $this->agregarTutor($id, $service);
        $this->mostrarNuevo = false;
        $this->resetNuevo();
    }

    public function limpiarCurpNuevo(): void
    {
        $this->curpNuevoEstado = 'inicial';
        $this->curpNuevoMensaje = 'Escribe una CURP para comprobar si el responsable ya existe.';
        $this->curpNuevoValida = false;
        $this->ultimaCurpNuevoValidada = null;
        $this->ultimaCurpNuevoConsultada = null;
        $this->curpNuevoAdvertencia = null;
        $this->curpNuevoExito = null;
        $this->tutorExistenteNuevo = null;
        $this->alumnoMismaCurpNuevo = null;
        $this->datosCurpNuevo = [];
        $this->diferenciasCurpNuevo = [];
        $this->resetValidation('nuevo.curp');
    }

    public function crearTutor(GestionResponsablesAlumnoService $service): void
    {
        $this->autorizarGestion();
        abort_unless($this->puedeCrearResponsables, 403);
        $this->normalizarNuevo();
        $sinCurp = (bool) ($this->nuevo['sin_curp'] ?? false);

        $rules = [
            'nuevo.sin_curp' => ['boolean'],
            'nuevo.curp' => [Rule::requiredIf(! $sinCurp), 'nullable', 'string', 'size:18', new CurpMexicana(), 'unique:tutores,curp'],
            'nuevo.identificador_alternativo' => [Rule::requiredIf($sinCurp), 'nullable', 'string', 'max:80', 'unique:tutores,identificador_alternativo'],
            'nuevo.motivo_sin_curp' => [Rule::requiredIf($sinCurp), 'nullable', 'string', 'min:5', 'max:255'],
            'nuevo.nombre' => ['required', 'string', 'max:255'],
            'nuevo.apellido_paterno' => ['required', 'string', 'max:255'],
            'nuevo.apellido_materno' => ['nullable', 'string', 'max:255'],
            'nuevo.genero' => ['nullable', Rule::in(['M', 'F', 'O'])],
            'nuevo.fecha_nacimiento' => ['nullable', 'date'],
            'nuevo.telefono_celular' => ['nullable', 'string', 'max:20'],
            'nuevo.telefono_casa' => ['nullable', 'string', 'max:20'],
            'nuevo.correo_electronico' => ['nullable', 'email', 'max:255'],
            'nuevo.codigo_postal' => ['nullable', 'regex:/^[0-9]{5}$/'],
        ];

        $this->validate($rules);

        if (blank($this->nuevo['telefono_celular']) && blank($this->nuevo['telefono_casa']) && blank($this->nuevo['correo_electronico'])) {
            throw ValidationException::withMessages([
                'nuevo.telefono_celular' => 'Captura al menos teléfono celular, teléfono de casa o correo.',
            ]);
        }

        $tutor = DB::transaction(function (): Tutor {
            return Tutor::query()->create([
                'curp' => blank($this->nuevo['curp']) ? null : $this->nuevo['curp'],
                'identificador_alternativo' => blank($this->nuevo['identificador_alternativo']) ? null : $this->nuevo['identificador_alternativo'],
                'motivo_sin_curp' => blank($this->nuevo['motivo_sin_curp']) ? null : $this->nuevo['motivo_sin_curp'],
                'parentesco' => 'NO ESPECIFICADO',
                'nombre' => $this->nuevo['nombre'],
                'apellido_paterno' => $this->nuevo['apellido_paterno'],
                'apellido_materno' => blank($this->nuevo['apellido_materno']) ? null : $this->nuevo['apellido_materno'],
                'genero' => blank($this->nuevo['genero']) ? null : $this->nuevo['genero'],
                'fecha_nacimiento' => blank($this->nuevo['fecha_nacimiento']) ? null : $this->nuevo['fecha_nacimiento'],
                'telefono_celular' => blank($this->nuevo['telefono_celular']) ? null : $this->nuevo['telefono_celular'],
                'telefono_casa' => blank($this->nuevo['telefono_casa']) ? null : $this->nuevo['telefono_casa'],
                'correo_electronico' => blank($this->nuevo['correo_electronico']) ? null : $this->nuevo['correo_electronico'],
                'calle' => blank($this->nuevo['calle']) ? null : $this->nuevo['calle'],
                'numero' => blank($this->nuevo['numero']) ? null : $this->nuevo['numero'],
                'colonia' => blank($this->nuevo['colonia']) ? null : $this->nuevo['colonia'],
                'codigo_postal' => blank($this->nuevo['codigo_postal']) ? null : $this->nuevo['codigo_postal'],
                'municipio' => blank($this->nuevo['municipio']) ? null : $this->nuevo['municipio'],
                'estado' => blank($this->nuevo['estado']) ? null : $this->nuevo['estado'],
                'ciudad' => blank($this->nuevo['ciudad']) ? null : $this->nuevo['ciudad'],
                'activo' => true,
            ]);
        });

        $this->dispatch('tutorRegistered', tutor: $tutor->id);
        $this->mostrarNuevo = false;
        $this->resetNuevo();
        $this->agregarTutor($tutor->id, $service);
    }

    private function relacionPersistida(int $indice): InscripcionTutor
    {
        $relacionId = (int) ($this->relaciones[$indice]['id'] ?? 0);

        return InscripcionTutor::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->findOrFail($relacionId);
    }

    private function cargarRelaciones(): void
    {
        $this->relaciones = InscripcionTutor::query()
            ->where('inscripcion_id', $this->inscripcionId)
            ->with('tutor:id,curp,identificador_alternativo,nombre,apellido_paterno,apellido_materno,telefono_celular,telefono_casa,correo_electronico,calle,numero,colonia,codigo_postal,municipio,estado,ciudad,activo')
            ->orderByDesc('activo')
            ->orderByDesc('es_principal')
            ->orderBy('orden_contacto')
            ->get()
            ->map(function (InscripcionTutor $relacion): array {
                return [
                    'id' => $relacion->id,
                    'tutor_id' => $relacion->tutor_id,
                    'nombre' => $relacion->tutor?->nombre_completo ?: 'Responsable no disponible',
                    'curp' => $relacion->tutor?->identidad_protegida ?: 'Sin identificador',
                    'telefono' => $relacion->tutor?->telefono_celular ?: $relacion->tutor?->telefono_casa,
                    'correo' => $relacion->tutor?->correo_electronico,
                    'parentesco' => $relacion->parentesco,
                    'es_principal' => (bool) $relacion->es_principal,
                    'es_tutor_legal' => $this->puedeGestionarResponsablesSensibles
                        ? (bool) $relacion->es_tutor_legal
                        : false,
                    'estado_tutela' => $this->puedeGestionarResponsablesSensibles
                        ? $relacion->estado_tutela
                        : 'no_aplica',
                    'vive_con_alumno' => (bool) $relacion->vive_con_alumno,
                    'recibe_avisos' => (bool) $relacion->recibe_avisos,
                    'recibe_calificaciones' => $this->puedeGestionarResponsablesSensibles
                        ? (bool) $relacion->recibe_calificaciones
                        : false,
                    'contacto_emergencia' => (bool) $relacion->contacto_emergencia,
                    'autorizado_recoger' => $this->puedeGestionarResponsablesSensibles
                        ? (bool) $relacion->autorizado_recoger
                        : false,
                    'responsable_economico' => (bool) $relacion->responsable_economico,
                    'activo' => (bool) $relacion->activo,
                    'fecha_inicio' => optional($relacion->fecha_inicio)->format('d/m/Y'),
                    'fecha_fin' => optional($relacion->fecha_fin)->format('d/m/Y'),
                    'motivo_fin' => $relacion->motivo_fin,
                    'observaciones' => $relacion->observaciones,
                ];
            })
            ->all();
    }

    private function tutorParaVista(Tutor $tutor): array
    {
        return [
            'id' => $tutor->id,
            'nombre' => $tutor->nombre_completo,
            'curp' => $tutor->identidad_protegida,
            'telefono' => $tutor->telefono_celular ?: $tutor->telefono_casa,
            'correo' => $tutor->correo_electronico,
            'alumnos' => (int) $tutor->relaciones_activas_count,
        ];
    }

    private function normalizarNuevo(): void
    {
        foreach ($this->nuevo as $campo => $valor) {
            if (is_string($valor)) {
                $this->nuevo[$campo] = trim($valor);
            }
        }

        $this->nuevo['curp'] = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->nuevo['curp']) ?: '');
        $this->nuevo['identificador_alternativo'] = mb_strtoupper((string) $this->nuevo['identificador_alternativo']);

        if ($this->nuevo['sin_curp'] ?? false) {
            $this->nuevo['curp'] = '';
        } else {
            $this->nuevo['identificador_alternativo'] = '';
            $this->nuevo['motivo_sin_curp'] = '';
        }

        $this->nuevo['nombre'] = mb_convert_case((string) $this->nuevo['nombre'], MB_CASE_TITLE, 'UTF-8');
        $this->nuevo['apellido_paterno'] = mb_convert_case((string) $this->nuevo['apellido_paterno'], MB_CASE_TITLE, 'UTF-8');
        $this->nuevo['apellido_materno'] = mb_convert_case((string) $this->nuevo['apellido_materno'], MB_CASE_TITLE, 'UTF-8');
    }

    public function resetNuevo(): void
    {
        $this->nuevo = [
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
        $this->limpiarCurpNuevo();
        $this->resetValidation('nuevo');
    }

    private function autorizarGestion(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.editar'), 403);
    }

    public function render()
    {
        return view('livewire.tutor.gestion-responsables-alumno', [
            'parentescos' => GestionResponsablesAlumnoService::PARENTESCOS,
            'estadosTutela' => GestionResponsablesAlumnoService::ESTADOS_TUTELA,
        ]);
    }
}
