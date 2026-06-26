<?php

namespace App\Livewire\Accion;

use App\Models\cicloEscolar;
use App\Models\Dia;
use App\Models\Grupo;
use App\Models\Grado;
use App\Models\Hora;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\Taller;
use App\Models\TallerSesion;
use App\Services\HorarioTallerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class TallerConjunto extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public bool $mostrarPanel = false;
    public bool $mostrarCatalogo = false;
    public ?int $editandoSesionId = null;

    public ?int $taller_id = null;
    public ?int $profesor_id = null;
    public ?int $ciclo_escolar_id = null;
    public ?int $dia_id = null;
    public ?int $hora_id = null;
    public array $grupos_seleccionados = [];
    public string $ubicacion = '';

    public bool $requiereAutorizacion = false;
    public array $conflictos = [];
    public bool $autorizar_conflicto = false;
    public string $motivo_conflicto = '';

    public string $nuevo_taller_nombre = '';
    public string $nuevo_taller_clave = '';
    public string $nuevo_taller_descripcion = '';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->ciclo_escolar_id = cicloEscolar::query()
            ->where('es_actual', true)
            ->value('id') ?: cicloEscolar::query()->max('id');
    }

    public function getEsSecundariaProperty(): bool
    {
        return $this->nivel?->slug === 'secundaria';
    }

    public function getTalleresProperty()
    {
        return Taller::query()
            ->where('nivel_id', $this->nivel?->id)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    public function getCiclosEscolaresProperty()
    {
        return cicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get();
    }

    public function getProfesoresProperty()
    {
        return Persona::query()
            ->select([
                'id',
                'titulo',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'status',
            ])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    public function getGruposProperty()
    {
        return Grupo::query()
            ->with([
                'grado:id,nombre,orden,nivel_id',
                'generacion:id,anio_ingreso,anio_egreso,status',
                'asignacionGrupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where('nivel_id', $this->nivel?->id)
            ->whereHas('generacion', fn($query) => $query->where('status', true))
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->grado?->orden ?? 999) <=> ($b->grado?->orden ?? 999),
                fn($a, $b) => strcmp(
                    $a->asignacionGrupo?->nombre ?? '',
                    $b->asignacionGrupo?->nombre ?? ''
                ),
                fn($a, $b) => ($b->generacion?->anio_ingreso ?? 0) <=> ($a->generacion?->anio_ingreso ?? 0),
            ])
            ->values();
    }

    public function getDiasProperty()
    {
        return Dia::query()
            ->where('nivel_id', $this->nivel?->id)
            ->orderBy('orden')
            ->get()
            ->unique(fn($dia) => mb_strtolower($dia->dia))
            ->values();
    }

    public function getHorasProperty()
    {
        return Hora::query()
            ->where('nivel_id', $this->nivel?->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();
    }

    public function getHorasDisponiblesProperty()
    {
        $service = app(HorarioTallerService::class);

        return $this->horas->map(function ($hora) use ($service) {
            $conflictos = [];

            if (
                $this->dia_id &&
                $this->profesor_id &&
                $this->ciclo_escolar_id &&
                count($this->grupos_seleccionados) >= 3
            ) {
                $conflictos = $service->detectarConflictos(
                    grupoIds: $this->grupos_seleccionados,
                    profesorId: (int) $this->profesor_id,
                    cicloEscolarId: (int) $this->ciclo_escolar_id,
                    diaId: (int) $this->dia_id,
                    horaId: (int) $hora->id,
                    sesionActualId: $this->editandoSesionId,
                );
            }

            return [
                'id' => $hora->id,
                'hora_inicio' => $hora->hora_inicio,
                'hora_fin' => $hora->hora_fin,
                'disponible' => count($conflictos) === 0,
                'conflictos' => count($conflictos),
            ];
        });
    }

    public function getSesionesProperty()
    {
        return TallerSesion::query()
            ->with([
                'taller:id,nivel_id,nombre,clave',
                'profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'dia:id,dia,orden',
                'hora:id,hora_inicio,hora_fin,orden',
                'grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupos.asignacionGrupo:id,nombre',
                'grupos.grado:id,nombre,orden',
                'grupos.generacion:id,anio_ingreso,anio_egreso',
            ])
            ->whereHas('taller', fn($query) => $query->where('nivel_id', $this->nivel?->id))
            ->orderByDesc('ciclo_escolar_id')
            ->orderBy('dia_id')
            ->orderBy('hora_id')
            ->get();
    }

    #[On('abrir-taller-conjunto')]
    public function abrir(?int $sesionId = null): void
    {
        if (!$this->esSecundaria) {
            return;
        }

        $this->mostrarPanel = true;

        if ($sesionId) {
            $this->editar($sesionId);
            return;
        }

        $this->limpiarFormulario(false);
    }

    public function cerrar(): void
    {
        $this->mostrarPanel = false;
        $this->limpiarFormulario(false);
    }

    public function crearTaller(): void
    {
        $datos = $this->validate([
            'nuevo_taller_nombre' => [
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('talleres', 'nombre')
                    ->where(fn($query) => $query->where('nivel_id', $this->nivel?->id)),
            ],
            'nuevo_taller_clave' => ['nullable', 'string', 'max:30'],
            'nuevo_taller_descripcion' => ['nullable', 'string', 'max:1000'],
        ], [
            'nuevo_taller_nombre.required' => 'Escribe el nombre del taller.',
            'nuevo_taller_nombre.unique' => 'Ya existe un taller con ese nombre en secundaria.',
        ]);

        $taller = Taller::query()->create([
            'nivel_id' => $this->nivel->id,
            'nombre' => trim($datos['nuevo_taller_nombre']),
            'slug' => Str::slug($datos['nuevo_taller_nombre']),
            'clave' => filled($datos['nuevo_taller_clave'])
                ? trim($datos['nuevo_taller_clave'])
                : null,
            'descripcion' => filled($datos['nuevo_taller_descripcion'])
                ? trim($datos['nuevo_taller_descripcion'])
                : null,
            'activo' => true,
        ]);

        $this->taller_id = $taller->id;
        $this->reset([
            'nuevo_taller_nombre',
            'nuevo_taller_clave',
            'nuevo_taller_descripcion',
        ]);
        $this->mostrarCatalogo = false;
        $this->resetValidation();

        $this->dispatch('swal', [
            'title' => 'Taller registrado correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function guardarSesion(bool $forzar = false): void
    {
        $this->resetValidation();

        $datos = $this->validate([
            'taller_id' => ['required', 'integer', 'exists:talleres,id'],
            'profesor_id' => ['required', 'integer', 'exists:personas,id'],
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grupos_seleccionados' => ['required', 'array', 'min:3'],
            'grupos_seleccionados.*' => ['integer', 'distinct', 'exists:grupos,id'],
            'dia_id' => ['required', 'integer', 'exists:dias,id'],
            'hora_id' => ['required', 'integer', 'exists:horas,id'],
            'ubicacion' => ['nullable', 'string', 'max:120'],
        ], [
            'taller_id.required' => 'Selecciona un taller.',
            'profesor_id.required' => 'Selecciona el profesor que impartirá el taller.',
            'ciclo_escolar_id.required' => 'Selecciona el ciclo escolar.',
            'grupos_seleccionados.required' => 'Selecciona los grupos participantes.',
            'grupos_seleccionados.min' => 'Selecciona al menos un grupo de cada uno de los tres grados.',
            'dia_id.required' => 'Selecciona el día.',
            'hora_id.required' => 'Selecciona la hora.',
        ]);

        $tallerValido = Taller::query()
            ->whereKey($datos['taller_id'])
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->exists();

        if (!$tallerValido) {
            $this->addError('taller_id', 'El taller no pertenece al nivel secundaria o está inactivo.');
            return;
        }

        $profesorValido = Persona::query()
            ->whereKey($datos['profesor_id'])
            ->exists();

        if (!$profesorValido) {
            $this->addError('profesor_id', 'La persona seleccionada no existe.');
            return;
        }

        $gruposValidos = Grupo::query()
            ->whereIn('id', $datos['grupos_seleccionados'])
            ->where('nivel_id', $this->nivel->id)
            ->count();

        if ($gruposValidos !== count(array_unique($datos['grupos_seleccionados']))) {
            $this->addError('grupos_seleccionados', 'Todos los grupos deben pertenecer a secundaria.');
            return;
        }

        $gradosRequeridos = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values();

        $gradosSeleccionados = Grupo::query()
            ->whereIn('id', $datos['grupos_seleccionados'])
            ->pluck('grado_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->sort()
            ->values();

        if ($gradosRequeridos->diff($gradosSeleccionados)->isNotEmpty()) {
            $this->addError(
                'grupos_seleccionados',
                'Debes seleccionar al menos un grupo de 1.º, 2.º y 3.º de secundaria.'
            );
            return;
        }

        $diaValido = Dia::query()
            ->whereKey($datos['dia_id'])
            ->where('nivel_id', $this->nivel->id)
            ->exists();

        $horaValida = Hora::query()
            ->whereKey($datos['hora_id'])
            ->where('nivel_id', $this->nivel->id)
            ->exists();

        if (!$diaValido || !$horaValida) {
            $this->addError('hora_id', 'El día o la hora no pertenecen a secundaria.');
            return;
        }

        $service = app(HorarioTallerService::class);
        $conflictos = $service->detectarConflictos(
            grupoIds: $datos['grupos_seleccionados'],
            profesorId: (int) $datos['profesor_id'],
            cicloEscolarId: (int) $datos['ciclo_escolar_id'],
            diaId: (int) $datos['dia_id'],
            horaId: (int) $datos['hora_id'],
            sesionActualId: $this->editandoSesionId,
        );

        if (count($conflictos) > 0 && !$forzar) {
            $this->conflictos = $conflictos;
            $this->requiereAutorizacion = true;
            $this->autorizar_conflicto = false;
            return;
        }

        if (count($conflictos) > 0 && $forzar) {
            $this->validate([
                'autorizar_conflicto' => ['accepted'],
                'motivo_conflicto' => ['required', 'string', 'min:10', 'max:1000'],
            ], [
                'autorizar_conflicto.accepted' => 'Confirma la autorización administrativa.',
                'motivo_conflicto.required' => 'Escribe el motivo para guardar con conflicto.',
                'motivo_conflicto.min' => 'El motivo debe explicar la autorización con al menos 10 caracteres.',
            ]);
        }

        DB::transaction(function () use ($datos, $conflictos, $service) {
            $sesion = $this->editandoSesionId
                ? TallerSesion::query()->lockForUpdate()->findOrFail($this->editandoSesionId)
                : new TallerSesion();

            $sesion->fill([
                'taller_id' => $datos['taller_id'],
                'profesor_id' => $datos['profesor_id'],
                'ciclo_escolar_id' => $datos['ciclo_escolar_id'],
                'dia_id' => $datos['dia_id'],
                'hora_id' => $datos['hora_id'],
                'ubicacion' => filled($datos['ubicacion']) ? trim($datos['ubicacion']) : null,
                'conflicto_forzado' => count($conflictos) > 0,
                'forzado_por' => count($conflictos) > 0 ? auth()->id() : null,
                'motivo_conflicto' => count($conflictos) > 0
                    ? trim($this->motivo_conflicto)
                    : null,
                'estado' => $sesion->exists
                    ? ($sesion->estado ?: TallerSesion::ESTADO_ACTIVA)
                    : TallerSesion::ESTADO_ACTIVA,
                'fecha_inicio' => $sesion->exists
                    ? $sesion->fecha_inicio
                    : now()->toDateString(),
                'confirmada_at' => $sesion->exists
                    ? ($sesion->confirmada_at ?: now())
                    : now(),
                'confirmada_por' => $sesion->exists
                    ? ($sesion->confirmada_por ?: auth()->id())
                    : auth()->id(),
            ]);
            $sesion->save();

            $sesion->grupos()->sync($datos['grupos_seleccionados']);
            $sesion->load('grupos');
            $service->sincronizarHorarios($sesion);
        });

        $this->dispatch('taller-conjunto-actualizado');
        $this->dispatch('swal', [
            'title' => $this->editandoSesionId
                ? 'Taller conjunto actualizado'
                : 'Taller conjunto asignado',
            'text' => 'La sesión cuenta como una sola hora semanal para el profesor.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->limpiarFormulario(false);
        $this->mostrarPanel = true;
    }

    public function editar(int $sesionId): void
    {
        $sesion = TallerSesion::query()
            ->with('taller', 'grupos')
            ->whereHas('taller', fn($query) => $query->where('nivel_id', $this->nivel?->id))
            ->findOrFail($sesionId);

        if ($sesion->estado === TallerSesion::ESTADO_ARCHIVADA) {
            $this->dispatch('swal', [
                'title' => 'La sesión está archivada',
                'text' => 'Reactívala antes de modificarla.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->mostrarPanel = true;
        $this->editandoSesionId = $sesion->id;
        $this->taller_id = $sesion->taller_id;
        $this->profesor_id = $sesion->profesor_id;
        $this->ciclo_escolar_id = $sesion->ciclo_escolar_id;
        $this->dia_id = $sesion->dia_id;
        $this->hora_id = $sesion->hora_id;
        $this->grupos_seleccionados = $sesion->grupos->pluck('id')->map(fn($id) => (string) $id)->all();
        $this->ubicacion = (string) ($sesion->ubicacion ?? '');
        $this->motivo_conflicto = (string) ($sesion->motivo_conflicto ?? '');
        $this->autorizar_conflicto = false;
        $this->requiereAutorizacion = false;
        $this->conflictos = [];
        $this->resetValidation();
    }

    public function cerrarSesion(int $sesionId): void
    {
        $this->cambiarEstadoSesion(
            sesionId: $sesionId,
            estado: TallerSesion::ESTADO_CERRADA,
            titulo: 'Taller conjunto cerrado'
        );
    }

    public function archivarSesion(int $sesionId): void
    {
        $this->cambiarEstadoSesion(
            sesionId: $sesionId,
            estado: TallerSesion::ESTADO_ARCHIVADA,
            titulo: 'Taller conjunto archivado'
        );
    }

    public function reactivarSesion(int $sesionId): void
    {
        $this->cambiarEstadoSesion(
            sesionId: $sesionId,
            estado: TallerSesion::ESTADO_ACTIVA,
            titulo: 'Taller conjunto reactivado'
        );
    }

    /**
     * Compatibilidad con vistas antiguas: una solicitud de eliminación se
     * convierte en archivado. No se borran horarios ni relaciones históricas.
     */
    public function eliminar(int $sesionId): void
    {
        $this->archivarSesion($sesionId);
    }

    private function cambiarEstadoSesion(int $sesionId, string $estado, string $titulo): void
    {
        $this->autorizarAdministracion();

        $estadosPermitidos = [
            TallerSesion::ESTADO_ACTIVA,
            TallerSesion::ESTADO_CERRADA,
            TallerSesion::ESTADO_ARCHIVADA,
        ];

        abort_unless(in_array($estado, $estadosPermitidos, true), 422);

        $sesion = TallerSesion::query()
            ->whereHas('taller', fn($query) => $query->where('nivel_id', $this->nivel?->id))
            ->findOrFail($sesionId);

        $sesion->forceFill([
            'estado' => $estado,
            'fecha_fin' => $estado === TallerSesion::ESTADO_ACTIVA
                ? null
                : now()->toDateString(),
            'confirmada_at' => $estado === TallerSesion::ESTADO_ACTIVA
                ? now()
                : $sesion->confirmada_at,
            'confirmada_por' => $estado === TallerSesion::ESTADO_ACTIVA
                ? auth()->id()
                : $sesion->confirmada_por,
        ])->save();

        if ($this->editandoSesionId === $sesionId) {
            $this->limpiarFormulario(false);
        }

        $this->dispatch('taller-conjunto-actualizado');
        $this->dispatch('swal', [
            'title' => $titulo,
            'text' => 'La información histórica y sus horarios permanecen guardados.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function autorizarAdministracion(): void
    {
        abort_unless(
            auth()->user()?->is_admin,
            403,
            'Solo administración puede cerrar, archivar o reactivar talleres.'
        );
    }

    public function cancelarAutorizacion(): void
    {
        $this->requiereAutorizacion = false;
        $this->autorizar_conflicto = false;
        $this->motivo_conflicto = '';
        $this->conflictos = [];
        $this->resetValidation(['autorizar_conflicto', 'motivo_conflicto']);
    }

    public function limpiarFormulario(bool $conservarPanel = true): void
    {
        $ciclo = $this->ciclo_escolar_id ?: cicloEscolar::query()->max('id');

        $this->reset([
            'editandoSesionId',
            'taller_id',
            'profesor_id',
            'dia_id',
            'hora_id',
            'grupos_seleccionados',
            'ubicacion',
            'requiereAutorizacion',
            'conflictos',
            'autorizar_conflicto',
            'motivo_conflicto',
        ]);

        $this->ciclo_escolar_id = $ciclo;
        $this->mostrarCatalogo = false;
        $this->resetValidation();

        if (!$conservarPanel) {
            // No se altera mostrarPanel: permite reutilizar el método al abrir o guardar.
        }
    }

    public function nombreProfesor($persona): string
    {
        return app(HorarioTallerService::class)->nombrePersona($persona);
    }

    public function nombreGrupo($grupo): string
    {
        return app(HorarioTallerService::class)->nombreGrupo($grupo);
    }

    public function render()
    {
        return view('livewire.accion.taller-conjunto');
    }
}
