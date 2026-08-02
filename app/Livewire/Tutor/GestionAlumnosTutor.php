<?php

namespace App\Livewire\Tutor;

use App\Models\Inscripcion;
use App\Models\InscripcionTutor;
use App\Models\Tutor;
use App\Services\GestionResponsablesAlumnoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class GestionAlumnosTutor extends Component
{
    #[Locked]
    public ?int $tutorId = null;

    public ?string $tutorNombre = null;
    public bool $tutorActivo = true;
    public string $buscarAlumno = '';
    public bool $incluirHistoricos = false;
    public array $resultados = [];
    public array $seleccionados = [];
    public array $relaciones = [];
    public array $motivosRetiro = [];
    public ?string $mensaje = null;

    public string $nuevoParentesco = 'OTRO';
    public bool $nuevoPrincipal = false;
    public bool $nuevoTutorLegal = false;
    public string $nuevoEstadoTutela = 'no_aplica';
    public bool $nuevoViveConAlumno = false;
    public bool $nuevoRecibeAvisos = true;
    public bool $nuevoRecibeCalificaciones = true;
    public bool $nuevoContactoEmergencia = false;
    public bool $nuevoAutorizadoRecoger = false;
    public bool $nuevoResponsableEconomico = false;
    public string $nuevaFechaInicio = '';
    public ?string $nuevasObservaciones = null;

    #[Locked]
    public bool $puedeSensibles = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.editar'), 403);
        $this->puedeSensibles = (bool) auth()->user()?->canAccess('alumnos.responsables_sensibles');
        $this->nuevaFechaInicio = now()->toDateString();
    }

    #[On('administrarAlumnosTutor')]
    public function abrir(int $id): void
    {
        $this->autorizar();
        $tutor = Tutor::query()->findOrFail($id);

        $this->tutorId = $tutor->id;
        $this->tutorNombre = $tutor->nombre_completo;
        $this->tutorActivo = (bool) $tutor->activo;
        $this->buscarAlumno = '';
        $this->incluirHistoricos = false;
        $this->resultados = [];
        $this->seleccionados = [];
        $this->mensaje = null;
        $this->reiniciarConfiguracionNuevaRelacion();
        $this->resetValidation();
        $this->cargarRelaciones();
        $this->dispatch('gestion-alumnos-tutor-cargada');
    }

    public function updatedBuscarAlumno(): void
    {
        $this->buscarAlumnos();
    }

    public function updatedIncluirHistoricos(): void
    {
        $this->buscarAlumnos();
    }

    public function buscarAlumnos(): void
    {
        $this->autorizar();
        $termino = trim($this->buscarAlumno);

        if ($this->tutorId === null || ! $this->tutorActivo || mb_strlen($termino) < 2) {
            $this->resultados = [];
            return;
        }

        $query = $this->incluirHistoricos
            ? Inscripcion::withTrashed()
            : Inscripcion::query()->visiblesEnListas();

        $alumnos = $query
            ->with(['nivel:id,nombre', 'grado:id,nombre', 'semestre:id,numero', 'grupo:id,clave', 'cicloEscolar:id,inicio_anio,fin_anio'])
            ->with(['relacionesTutores' => fn ($q) => $q->where('tutor_id', $this->tutorId)])
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
            ->limit(15)
            ->get();

        $this->resultados = $alumnos->map(function (Inscripcion $alumno): array {
            $relacion = $alumno->relacionesTutores->first();

            return [
                ...$this->alumnoParaVista($alumno),
                'relacion_id' => $relacion?->id,
                'relacion_activa' => (bool) ($relacion?->activo && $relacion?->fecha_fin === null),
                'relacion_historica' => $relacion !== null && ! ($relacion->activo && $relacion->fecha_fin === null),
            ];
        })->all();
    }

    public function relacionarSeleccionados(GestionResponsablesAlumnoService $service): void
    {
        $this->autorizar();
        if (! $this->tutorActivo) {
            throw ValidationException::withMessages([
                'seleccionados' => 'El responsable está archivado. Reactívalo desde el directorio antes de agregar alumnos.',
            ]);
        }

        $tutor = Tutor::query()->activos()->findOrFail($this->tutorId);
        $ids = collect($this->seleccionados)
            ->filter(fn ($seleccionado) => (bool) $seleccionado)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($ids->isEmpty()) {
            throw ValidationException::withMessages([
                'seleccionados' => 'Selecciona al menos un alumno.',
            ]);
        }

        $this->validate([
            'nuevoParentesco' => ['required', Rule::in(GestionResponsablesAlumnoService::PARENTESCOS)],
            'nuevoEstadoTutela' => ['required', Rule::in(GestionResponsablesAlumnoService::ESTADOS_TUTELA)],
            'nuevaFechaInicio' => ['required', 'date'],
            'nuevasObservaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $alumnos = Inscripcion::withTrashed()->whereIn('id', $ids)->get()->keyBy('id');

        DB::transaction(function () use ($ids, $alumnos, $tutor, $service): void {
            foreach ($ids as $id) {
                $alumno = $alumnos->get($id);
                if (! $alumno) {
                    continue;
                }

                $service->agregarOActualizar($alumno, $tutor, [
                    'parentesco' => $this->nuevoParentesco,
                    'es_principal' => $this->nuevoPrincipal,
                    'es_tutor_legal' => $this->puedeSensibles ? $this->nuevoTutorLegal : false,
                    'estado_tutela' => $this->puedeSensibles ? $this->nuevoEstadoTutela : 'no_aplica',
                    'vive_con_alumno' => $this->nuevoViveConAlumno,
                    'recibe_avisos' => $this->nuevoRecibeAvisos,
                    'recibe_calificaciones' => $this->puedeSensibles ? $this->nuevoRecibeCalificaciones : true,
                    'contacto_emergencia' => $this->nuevoContactoEmergencia,
                    'autorizado_recoger' => $this->puedeSensibles ? $this->nuevoAutorizadoRecoger : false,
                    'responsable_economico' => $this->nuevoResponsableEconomico,
                    'fecha_inicio' => $this->nuevaFechaInicio,
                    'observaciones' => $this->nuevasObservaciones,
                ], auth()->id());
            }
        });

        $cantidad = $ids->count();
        $this->seleccionados = [];
        $this->buscarAlumno = '';
        $this->resultados = [];
        $this->mensaje = "Se relacionó el responsable con {$cantidad} alumno(s).";
        $this->cargarRelaciones();
        $this->dispatch('refreshTutor');
    }

    public function guardarRelacion(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizar();
        $relacionVista = $this->relaciones[$indice] ?? null;
        if (! $relacionVista) {
            return;
        }

        $this->validate([
            "relaciones.{$indice}.parentesco" => ['required', Rule::in(GestionResponsablesAlumnoService::PARENTESCOS)],
            "relaciones.{$indice}.estado_tutela" => ['required', Rule::in(GestionResponsablesAlumnoService::ESTADOS_TUTELA)],
            "relaciones.{$indice}.fecha_inicio" => ['required', 'date'],
            "relaciones.{$indice}.observaciones" => ['nullable', 'string', 'max:1000'],
        ]);

        $persistida = $this->relacionPersistida($indice);
        if (! $persistida->activo) {
            throw ValidationException::withMessages([
                'relaciones' => 'Reactiva la relación antes de modificarla.',
            ]);
        }

        if (! $this->puedeSensibles) {
            foreach (['es_tutor_legal', 'estado_tutela', 'recibe_calificaciones', 'autorizado_recoger'] as $campo) {
                $relacionVista[$campo] = $persistida->{$campo};
            }
        }

        $alumno = Inscripcion::withTrashed()->findOrFail($persistida->inscripcion_id);
        $tutor = Tutor::query()->findOrFail($persistida->tutor_id);
        $service->agregarOActualizar($alumno, $tutor, $relacionVista, auth()->id());

        $this->mensaje = 'La relación se actualizó correctamente.';
        $this->cargarRelaciones();
        $this->dispatch('refreshTutor');
    }

    public function establecerPrincipal(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizar();
        $persistida = $this->relacionPersistida($indice);
        $service->establecerPrincipal(
            Inscripcion::withTrashed()->findOrFail($persistida->inscripcion_id),
            (int) $persistida->tutor_id,
            auth()->id(),
        );

        $this->mensaje = 'El responsable quedó como contacto principal; el anterior se conservó como secundario.';
        $this->cargarRelaciones();
        $this->dispatch('refreshTutor');
    }

    public function desactivar(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizar();
        $persistida = $this->relacionPersistida($indice);
        $service->desactivar(
            Inscripcion::withTrashed()->findOrFail($persistida->inscripcion_id),
            (int) $persistida->tutor_id,
            (string) ($this->motivosRetiro[$persistida->id] ?? ''),
            auth()->id(),
        );

        unset($this->motivosRetiro[$persistida->id]);
        $this->mensaje = 'La relación quedó en el historial sin borrar al alumno ni al responsable.';
        $this->cargarRelaciones();
        $this->dispatch('refreshTutor');
    }

    public function reactivar(int $indice, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizar();
        $persistida = $this->relacionPersistida($indice);
        $service->reactivar(
            Inscripcion::withTrashed()->findOrFail($persistida->inscripcion_id),
            (int) $persistida->tutor_id,
            auth()->id(),
        );

        $this->mensaje = 'La relación histórica se reactivó.';
        $this->cargarRelaciones();
        $this->dispatch('refreshTutor');
    }

    public function copiarDomicilio(int $indice, bool $reemplazar = false, GestionResponsablesAlumnoService $service): void
    {
        $this->autorizar();
        $persistida = $this->relacionPersistida($indice);
        if (! $persistida->activo) {
            throw ValidationException::withMessages([
                'relaciones' => 'No se puede modificar el domicilio desde una relación histórica.',
            ]);
        }

        $tutor = Tutor::query()->findOrFail($persistida->tutor_id);
        $alumno = Inscripcion::withTrashed()->findOrFail($persistida->inscripcion_id);
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

            if ($reemplazar || blank($alumno->{$campo})) {
                $alumno->{$campo} = $valor;
                $copiados++;
            } else {
                $conservados++;
            }
        }
        $alumno->save();

        $vista = $this->relaciones[$indice];
        $vista['vive_con_alumno'] = true;
        $service->agregarOActualizar($alumno, $tutor, $vista, auth()->id());

        $this->mensaje = "Se copiaron {$copiados} datos del domicilio y se conservaron {$conservados}.";
        $this->cargarRelaciones();
    }

    public function cerrar(): void
    {
        $this->reset([
            'tutorId', 'tutorNombre', 'tutorActivo', 'buscarAlumno', 'incluirHistoricos', 'resultados',
            'seleccionados', 'relaciones', 'motivosRetiro', 'mensaje',
        ]);
        $this->reiniciarConfiguracionNuevaRelacion();
        $this->resetValidation();
    }

    private function reiniciarConfiguracionNuevaRelacion(): void
    {
        $this->nuevoParentesco = 'OTRO';
        $this->nuevoPrincipal = false;
        $this->nuevoTutorLegal = false;
        $this->nuevoEstadoTutela = 'no_aplica';
        $this->nuevoViveConAlumno = false;
        $this->nuevoRecibeAvisos = true;
        $this->nuevoRecibeCalificaciones = true;
        $this->nuevoContactoEmergencia = false;
        $this->nuevoAutorizadoRecoger = false;
        $this->nuevoResponsableEconomico = false;
        $this->nuevaFechaInicio = now()->toDateString();
        $this->nuevasObservaciones = null;
    }

    private function cargarRelaciones(): void
    {
        if ($this->tutorId === null) {
            $this->relaciones = [];
            return;
        }

        $this->relaciones = InscripcionTutor::query()
            ->where('tutor_id', $this->tutorId)
            ->with([
                'inscripcion' => fn ($q) => $q->withTrashed()->with([
                    'nivel:id,nombre', 'grado:id,nombre', 'semestre:id,numero',
                    'grupo:id,clave', 'cicloEscolar:id,inicio_anio,fin_anio',
                ]),
            ])
            ->orderByDesc('activo')
            ->orderByRaw('CASE WHEN fecha_fin IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('es_principal')
            ->orderByDesc('id')
            ->get()
            ->map(function (InscripcionTutor $relacion): array {
                $alumno = $relacion->inscripcion;

                return [
                    'id' => (int) $relacion->id,
                    'inscripcion_id' => (int) $relacion->inscripcion_id,
                    'tutor_id' => (int) $relacion->tutor_id,
                    ...($alumno ? $this->alumnoParaVista($alumno) : [
                        'nombre' => 'Alumno no disponible',
                        'matricula' => 'Sin matrícula',
                        'curp' => null,
                        'estatus' => 'No disponible',
                        'activo_listas' => false,
                        'ubicacion' => 'Sin ubicación',
                        'ciclo' => 'Sin ciclo',
                    ]),
                    'parentesco' => $relacion->parentesco,
                    'es_principal' => (bool) $relacion->es_principal,
                    'es_tutor_legal' => $this->puedeSensibles ? (bool) $relacion->es_tutor_legal : false,
                    'estado_tutela' => $this->puedeSensibles ? $relacion->estado_tutela : 'no_aplica',
                    'vive_con_alumno' => (bool) $relacion->vive_con_alumno,
                    'recibe_avisos' => (bool) $relacion->recibe_avisos,
                    'recibe_calificaciones' => $this->puedeSensibles ? (bool) $relacion->recibe_calificaciones : false,
                    'contacto_emergencia' => (bool) $relacion->contacto_emergencia,
                    'autorizado_recoger' => $this->puedeSensibles ? (bool) $relacion->autorizado_recoger : false,
                    'responsable_economico' => (bool) $relacion->responsable_economico,
                    'activo' => (bool) $relacion->activo && $relacion->fecha_fin === null,
                    'fecha_inicio' => optional($relacion->fecha_inicio)->format('Y-m-d') ?: now()->toDateString(),
                    'fecha_fin' => optional($relacion->fecha_fin)->format('d/m/Y'),
                    'motivo_fin' => $relacion->motivo_fin,
                    'observaciones' => $relacion->observaciones,
                ];
            })
            ->all();
    }

    private function relacionPersistida(int $indice): InscripcionTutor
    {
        $id = (int) ($this->relaciones[$indice]['id'] ?? 0);

        return InscripcionTutor::query()
            ->where('tutor_id', $this->tutorId)
            ->findOrFail($id);
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

    private function autorizar(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.editar'), 403);
    }

    public function render()
    {
        return view('livewire.tutor.gestion-alumnos-tutor', [
            'parentescos' => GestionResponsablesAlumnoService::PARENTESCOS,
            'estadosTutela' => GestionResponsablesAlumnoService::ESTADOS_TUTELA,
        ]);
    }
}
