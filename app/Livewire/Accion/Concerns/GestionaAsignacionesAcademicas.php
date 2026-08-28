<?php

namespace App\Livewire\Accion\Concerns;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use App\Models\ReasignacionDocenteLote;
use App\Models\Semestre;
use App\Models\Inscripcion;
use App\Services\CicloNivelGateService;
use App\Services\PlantillaDocenteService;
use App\Services\ReasignacionDocenteMasivaService;
use App\Services\SincronizadorOrdenCargaAcademicaService;
use App\Services\ContextoCicloEscolarSesion;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

trait GestionaAsignacionesAcademicas
{
    protected function rules(): array
    {
        return [
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'profesor_id' => ['nullable', 'integer', 'exists:personas,id'],
        ];
    }

    public function updatedCicloEscolarId(): void
    {
        app(ContextoCicloEscolarSesion::class)->recordar($this->ciclo_escolar_id);
        $this->limpiarFormulario();
        $this->cerrarModalEdicion();
        $this->modalReasignacionAbierto = false;
        $this->modalHistorialReasignacionesAbierto = false;
        $this->resetEstadoReasignacion();
        $this->limpiarSeleccionTabla();
        $this->limpiarFiltros();
        $this->sincronizarCicloOrigen();
        $this->resetValidation('ciclo_origen_id');
        $this->resetPage('materiasPage');
    }

    public function updatedCicloOrigenId(): void
    {
        $this->resetValidation('ciclo_origen_id');
    }

    public function updatedBuscar(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroGeneracion(): void
    {
        $this->limpiarSeleccionTabla();
        $this->reset(['filtro_grado', 'filtro_semestre', 'filtro_grupo']);
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroEstado(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroGrado(): void
    {
        $this->limpiarSeleccionTabla();
        $this->reset(['filtro_semestre', 'filtro_grupo']);
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroSemestre(): void
    {
        $this->limpiarSeleccionTabla();
        $this->reset(['filtro_grupo']);
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroGrupo(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroHorario(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedFiltroProfesor(): void
    {
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function updatedPorPaginaMaterias($value): void
    {
        $this->limpiarSeleccionTabla();
        $permitidos = [10, 15, 25, 50];
        $this->porPaginaMaterias = in_array((int) $value, $permitidos, true) ? (int) $value : 10;
        $this->resetPage('materiasPage');
    }

    public function updatedGrupoId(): void
    {
        $this->reset(['materia_id']);
        $this->resetValidation(['grupo_id', 'materia_id']);
    }

    public function updatedEditarGrupoId(): void
    {
        $this->editar_materia_id = '';
        $this->resetValidation(['editar_grupo_id', 'editar_materia_id']);
    }

    public function guardarMateria(): void
    {
        $this->validate();

        app(CicloNivelGateService::class)->asegurar(
            (int) $this->ciclo_escolar_id,
            (int) $this->nivel->id,
            'asignacion_materias'
        );

        $grupo = Grupo::query()
            ->whereKey($this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();
        $materia = Materia::query()->find($this->materia_id);

        if (!$grupo || !$materia) {
            $this->addError('grupo_id', 'El grupo o la materia ya no están disponibles.');
            return;
        }

        if (
            (int) $materia->nivel_id !== (int) $grupo->nivel_id
            || (int) $materia->grado_id !== (int) $grupo->grado_id
            || ($this->esBachillerato && (int) $materia->semestre_id !== (int) $grupo->semestre_id)
        ) {
            $this->addError('materia_id', 'La materia no corresponde al contexto académico del grupo.');
            return;
        }

        try {
            app(SincronizadorOrdenCargaAcademicaService::class)->asegurarContextoSinDuplicados($materia);
        } catch (DomainException $e) {
            $this->addError('materia_id', $e->getMessage());
            return;
        }

        $duplicada = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->where('materia_id', $materia->id)
            ->exists();

        if ($duplicada) {
            $this->addError('materia_id', 'Esta materia ya tiene una carga en el grupo y ciclo seleccionados.');
            return;
        }

        $profesorId = $materia->receso ? null : (filled($this->profesor_id) ? (int) $this->profesor_id : null);
        app(PlantillaDocenteService::class)->validar($profesorId, (int) $this->ciclo_escolar_id, (int) $this->nivel->id);

        DB::transaction(function () use ($grupo, $materia, $profesorId) {
            $asignacion = $this->crearAsignacionBorradorOrdenada($grupo, $materia, $profesorId);

            $this->ultimoRegistroId = $asignacion->id;
            $this->ultimoMovimiento = 'registrada';
        });

        $this->limpiarFormularioDespuesDeGuardar();
        $this->filtro_estado = AsignacionMateriaModel::ESTADO_BORRADOR;
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Carga académica registrada',
            'text' => 'Se guardó como borrador. Puedes prepararla y revisarla, pero no llegará a docentes, listas ni calificaciones hasta confirmarla.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function crearCargaPendiente(int $grupoId, int $materiaId): void
    {
        $this->autorizarAdministracion();

        app(CicloNivelGateService::class)->asegurar(
            (int) $this->ciclo_escolar_id,
            (int) $this->nivel->id,
            'asignacion_materias'
        );

        $grupo = Grupo::query()
            ->whereKey($grupoId)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();

        if (! $grupo) {
            $this->dispatch('swal', [
                'title' => 'Contexto no disponible',
                'text' => 'El grupo cambió de estado o ya no pertenece al ciclo seleccionado.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $materia = $this->materiasParaGrupo($grupo)
            ->first(fn (Materia $item) => (int) $item->id === $materiaId);

        if (! $materia) {
            $this->dispatch('swal', [
                'title' => 'Materia no válida',
                'text' => 'La materia ya no corresponde al grado o semestre de este grupo.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        try {
            app(SincronizadorOrdenCargaAcademicaService::class)->asegurarContextoSinDuplicados($materia);
        } catch (DomainException $e) {
            $this->dispatch('swal', [
                'title' => 'Orden académico ambiguo',
                'text' => $e->getMessage(),
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $yaExiste = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->where('materia_id', $materia->id)
            ->exists();

        if ($yaExiste) {
            $this->dispatch('swal', [
                'title' => 'La carga ya existe',
                'text' => 'La materia ya fue agregada a este grupo. La alerta se actualizará automáticamente.',
                'icon' => 'info',
                'position' => 'top-end',
            ]);
            return;
        }

        $asignacionCreada = DB::transaction(function () use ($grupo, $materia) {
            // Revalidar bajo bloqueo para evitar duplicados si dos administradores
            // intentan reparar la misma omisión al mismo tiempo.
            AsignacionMateriaModel::query()
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('grupo_id', $grupo->id)
                ->lockForUpdate()
                ->get(['id']);

            $duplicada = AsignacionMateriaModel::query()
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('grupo_id', $grupo->id)
                ->where('materia_id', $materia->id)
                ->exists();

            if ($duplicada) {
                return null;
            }

            $asignacion = $this->crearAsignacionBorradorOrdenada($grupo, $materia, null);
            $this->ultimoRegistroId = $asignacion->id;
            $this->ultimoMovimiento = 'reparada';

            return $asignacion;
        });

        if (! $asignacionCreada) {
            $this->dispatch('swal', [
                'title' => 'La carga ya fue creada',
                'text' => 'Otro proceso agregó la materia antes de completar esta acción. No se generó un duplicado.',
                'icon' => 'info',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->filtro_estado = AsignacionMateriaModel::ESTADO_BORRADOR;
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Carga pendiente creada',
            'text' => $materia->materia . ' se agregó como borrador, respetando el orden académico. El docente puede asignarse desde Editar.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    /**
     * Crea una carga como borrador usando directamente materias.orden.
     * El orden de asignacion_materias no tiene una secuencia independiente.
     */
    private function crearAsignacionBorradorOrdenada(Grupo $grupo, Materia $materia, ?int $profesorId): AsignacionMateriaModel
    {
        $asignacion = AsignacionMateriaModel::query()->create([
            'materia_id' => $materia->id,
            'grupo_id' => $grupo->id,
            'profesor_id' => $profesorId,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'nivel_id' => $grupo->nivel_id,
            'grado_id' => $grupo->grado_id,
            'generacion_id' => $grupo->generacion_id,
            'semestre_id' => $grupo->semestre_id,
            'orden' => (int) $materia->orden,
            'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
        ]);

        app(SincronizadorOrdenCargaAcademicaService::class)
            ->sincronizarGrupo((int) $grupo->id, (int) $this->ciclo_escolar_id);

        return $asignacion->refresh();
    }

    private function normalizarOrdenAcademicoGrupo(int $grupoId): void
    {
        app(SincronizadorOrdenCargaAcademicaService::class)
            ->sincronizarGrupo($grupoId, (int) $this->ciclo_escolar_id);
    }

    public function editar(int $id): void
    {
        $asignacion = AsignacionMateriaModel::query()
            ->with(['profesor'])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        if (! $asignacion->esEditableEstructuralmente()) {
            $this->dispatch('swal', [
                'title' => 'Carga protegida',
                'text' => 'Las cargas cerradas o archivadas son históricas. Reactívala antes de modificar grupo, materia o docente.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->resetValidation([
            'editar_grupo_id',
            'editar_materia_id',
            'editar_profesor_id',
        ]);

        $this->editandoId = $asignacion->id;
        $this->edicionTieneHistorial = $asignacion->tieneHistorial();
        $this->editar_grupo_id = $asignacion->grupo_id;
        $this->editar_materia_id = $asignacion->materia_id;
        $this->editar_profesor_id = $asignacion->profesor_id ?: '';

        $this->modalEditarAbierto = true;
    }

    public function actualizarMateria(): void
    {
        $this->validate([
            'editandoId' => ['required', 'integer', 'exists:asignacion_materias,id'],
            'editar_grupo_id' => ['required', 'integer', 'exists:grupos,id'],
            'editar_materia_id' => ['required', 'integer', 'exists:materias,id'],
            'editar_profesor_id' => ['nullable', 'integer', 'exists:personas,id'],
        ], [], [
            'editar_grupo_id' => 'grupo',
            'editar_materia_id' => 'materia',
            'editar_profesor_id' => 'profesor',
        ]);

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($this->editandoId);

        if (! $asignacion->esEditableEstructuralmente()) {
            $this->cerrarModalEdicion();
            $this->dispatch('swal', [
                'title' => 'Carga protegida',
                'text' => 'El estado de la carga cambió y ya no permite edición estructural. Reactívala si necesitas modificarla.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        if (
            $asignacion->tieneHistorial()
            && (
                (int) $asignacion->grupo_id !== (int) $this->editar_grupo_id
                || (int) $asignacion->materia_id !== (int) $this->editar_materia_id
            )
        ) {
            $this->addError(
                'editar_grupo_id',
                'La carga ya tiene historial. Para proteger horarios y calificaciones solo puedes cambiar el profesor responsable.'
            );
            return;
        }

        $grupo = Grupo::query()
            ->whereKey($this->editar_grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', 'activo')
            ->first();

        $materia = Materia::query()->find($this->editar_materia_id);

        if (!$grupo || !$materia) {
            $this->addError('editar_grupo_id', 'El grupo o la materia ya no están disponibles.');
            return;
        }

        if (
            (int) $materia->nivel_id !== (int) $grupo->nivel_id
            || (int) $materia->grado_id !== (int) $grupo->grado_id
            || ($this->esBachillerato && (int) $materia->semestre_id !== (int) $grupo->semestre_id)
        ) {
            $this->addError('editar_materia_id', 'La materia no corresponde al contexto académico del grupo.');
            return;
        }

        try {
            app(SincronizadorOrdenCargaAcademicaService::class)->asegurarContextoSinDuplicados($materia);
        } catch (DomainException $e) {
            $this->addError('editar_materia_id', $e->getMessage());
            return;
        }

        $duplicada = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->where('materia_id', $materia->id)
            ->whereKeyNot($asignacion->id)
            ->exists();

        if ($duplicada) {
            $this->addError('editar_materia_id', 'Esta materia ya tiene una carga en el grupo y ciclo seleccionados.');
            return;
        }

        $profesorId = $materia->receso
            ? null
            : (filled($this->editar_profesor_id) ? (int) $this->editar_profesor_id : null);

        app(PlantillaDocenteService::class)->validar($profesorId, (int) $this->ciclo_escolar_id, (int) $this->nivel->id);

        $profesorAnteriorId = $asignacion->profesor_id ? (int) $asignacion->profesor_id : null;
        $grupoAnteriorId = (int) $asignacion->grupo_id;
        $estructuraCambio = $grupoAnteriorId !== (int) $grupo->id
            || (int) $asignacion->materia_id !== (int) $materia->id;

        DB::transaction(function () use ($asignacion, $grupo, $materia, $profesorId, $profesorAnteriorId, $grupoAnteriorId, $estructuraCambio) {
            $asignacion->update([
                'materia_id' => $materia->id,
                'grupo_id' => $grupo->id,
                'profesor_id' => $profesorId,
                'nivel_id' => $grupo->nivel_id,
                'grado_id' => $grupo->grado_id,
                'generacion_id' => $grupo->generacion_id,
                'semestre_id' => $grupo->semestre_id,
            ]);

            app(ReasignacionDocenteMasivaService::class)->sincronizarProfesorIndividual(
                asignacion: $asignacion,
                profesorAnteriorId: $profesorAnteriorId,
                profesorNuevoId: $profesorId,
                usuarioId: auth()->id(),
            );

            if ($estructuraCambio) {
                $this->normalizarOrdenAcademicoGrupo($grupoAnteriorId);

                if ($grupoAnteriorId !== (int) $grupo->id) {
                    $this->normalizarOrdenAcademicoGrupo((int) $grupo->id);
                }
            }

            $this->ultimoRegistroId = $asignacion->id;
            $this->ultimoMovimiento = 'actualizada';
        });

        $this->cerrarModalEdicion();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Carga académica actualizada',
            'text' => 'Los cambios se aplicaron únicamente a la carga seleccionada.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function cerrarModalEdicion(): void
    {
        $this->modalEditarAbierto = false;
        $this->reset([
            'editandoId',
            'edicionTieneHistorial',
            'editar_grupo_id',
            'editar_materia_id',
            'editar_profesor_id',
        ]);
        $this->resetValidation([
            'editandoId',
            'editar_grupo_id',
            'editar_materia_id',
            'editar_profesor_id',
        ]);
    }

    public function confirmar(int $id): void
    {
        $this->cambiarEstado(
            id: $id,
            estado: AsignacionMateriaModel::ESTADO_ACTIVA,
            titulo: 'Carga confirmada',
            texto: 'La carga quedó activa y disponible para horarios publicados, docentes, listas y calificaciones.',
            estadosOrigen: [AsignacionMateriaModel::ESTADO_BORRADOR],
            registrarConfirmacion: true,
        );
    }

    public function cerrar(int $id): void
    {
        $this->cambiarEstado(
            id: $id,
            estado: AsignacionMateriaModel::ESTADO_CERRADA,
            titulo: 'Carga cerrada',
            texto: 'La carga salió de la operación vigente y conserva sus horarios, calificaciones y registros históricos.',
            estadosOrigen: [AsignacionMateriaModel::ESTADO_ACTIVA],
        );
    }

    public function archivar(int $id): void
    {
        $this->cambiarEstado(
            id: $id,
            estado: AsignacionMateriaModel::ESTADO_ARCHIVADA,
            titulo: 'Carga archivada',
            texto: 'La carga quedó fuera de operación, pero todo su historial permanece disponible.',
            estadosOrigen: [
                AsignacionMateriaModel::ESTADO_BORRADOR,
                AsignacionMateriaModel::ESTADO_ACTIVA,
                AsignacionMateriaModel::ESTADO_CERRADA,
            ],
        );

        if ((int) $this->editandoId === $id) {
            $this->cerrarModalEdicion();
        }
    }

    public function reactivar(int $id): void
    {
        $this->autorizarAdministracion();

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        if (! in_array($asignacion->estado, [
            AsignacionMateriaModel::ESTADO_CERRADA,
            AsignacionMateriaModel::ESTADO_ARCHIVADA,
        ], true)) {
            $this->dispatch('swal', [
                'title' => 'Reactivación no permitida',
                'text' => 'Solo una carga cerrada o archivada puede restaurarse.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        // Una carga archivada antes de ser confirmada debe volver a borrador;
        // nunca debe saltarse la aprobación administrativa al restaurarla.
        if ($asignacion->estado === AsignacionMateriaModel::ESTADO_ARCHIVADA && blank($asignacion->confirmada_at)) {
            $asignacion->update([
                'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
                'fecha_fin' => null,
            ]);

            $this->dispatch('swal', [
                'title' => 'Carga restaurada como borrador',
                'text' => 'La carga volvió a revisión. Debes confirmarla antes de que participe en los procesos académicos.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
            return;
        }

        $this->cambiarEstado(
            id: $id,
            estado: AsignacionMateriaModel::ESTADO_ACTIVA,
            titulo: 'Carga reactivada',
            texto: 'La carga volvió a la operación sin alterar su fecha ni usuario de confirmación original.',
            estadosOrigen: [
                AsignacionMateriaModel::ESTADO_CERRADA,
                AsignacionMateriaModel::ESTADO_ARCHIVADA,
            ],
        );
    }

    public function eliminar(int $id): void
    {
        $this->autorizarAdministracion();

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        if ($asignacion->tieneHistorial()) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'Esta carga ya tiene horarios, calificaciones o movimientos de auditoría. Archívala para conservar el historial.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        DB::transaction(fn() => $asignacion->delete());

        if ((int) $this->editandoId === $id) {
            $this->cerrarModalEdicion();
        }

        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Carga eliminada',
            'text' => 'La materia se eliminó únicamente de este ciclo. No se modificaron otros ciclos.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function confirmarTodas(): void
    {
        $this->autorizarAdministracion();

        $query = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('estado', AsignacionMateriaModel::ESTADO_BORRADOR);

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->dispatch('swal', [
                'title' => 'Sin borradores pendientes',
                'text' => 'Todas las cargas de este nivel ya fueron confirmadas o tienen otro estado.',
                'icon' => 'info',
                'position' => 'top-end',
            ]);
            return;
        }

        $query->update([
            'estado' => AsignacionMateriaModel::ESTADO_ACTIVA,
            'confirmada_at' => now(),
            'confirmada_por' => auth()->id(),
            'fecha_inicio' => DB::raw('COALESCE(fecha_inicio, CURRENT_DATE)'),
            'fecha_fin' => null,
        ]);

        $this->filtro_estado = AsignacionMateriaModel::ESTADO_ACTIVA;
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');

        $this->dispatch('swal', [
            'title' => 'Cargas confirmadas',
            'text' => "Se activaron {$total} carga(s). Desde este momento pueden participar en los procesos académicos operativos.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function cambiarEstado(
        int $id,
        string $estado,
        string $titulo,
        string $texto,
        array $estadosOrigen,
        bool $registrarConfirmacion = false,
    ): void {
        $this->autorizarAdministracion();

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        if (! in_array($asignacion->estado, $estadosOrigen, true)) {
            $this->dispatch('swal', [
                'title' => 'Cambio de estado no permitido',
                'text' => 'La carga cambió de estado o la transición solicitada no corresponde al flujo Borrador → Activa → Cerrada/Archivada.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
            return;
        }

        $datos = ['estado' => $estado];

        if ($registrarConfirmacion) {
            $datos['confirmada_at'] = now();
            $datos['confirmada_por'] = auth()->id();
            $datos['fecha_inicio'] = $asignacion->fecha_inicio ?: now()->toDateString();
            $datos['fecha_fin'] = null;
        } elseif ($estado === AsignacionMateriaModel::ESTADO_ACTIVA) {
            // Reactivar no debe reescribir quién/cuándo confirmó originalmente.
            $datos['fecha_fin'] = null;
        }

        if (in_array($estado, [AsignacionMateriaModel::ESTADO_CERRADA, AsignacionMateriaModel::ESTADO_ARCHIVADA], true)) {
            $datos['fecha_fin'] = $asignacion->fecha_fin ?: now()->toDateString();
        }

        $asignacion->update($datos);

        $this->dispatch('swal', [
            'title' => $titulo,
            'text' => $texto,
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function filtrarEstado(string $estado): void
    {
        if (! in_array($estado, AsignacionMateriaModel::ESTADOS, true)) {
            return;
        }

        $this->filtro_estado = $this->filtro_estado === $estado ? '' : $estado;
        $this->limpiarSeleccionTabla();
        $this->resetPage('materiasPage');
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'buscar',
            'filtro_generacion',
            'filtro_estado',
            'filtro_grado',
            'filtro_semestre',
            'filtro_grupo',
            'filtro_horario',
            'filtro_profesor',
        ]);

        $this->resetPage('materiasPage');
    }

    public function limpiarFormularioDespuesDeGuardar(): void
    {
        $grupo = $this->grupo_id;
        $this->reset(['materia_id', 'profesor_id']);
        $this->grupo_id = $grupo;
        $this->resetValidation(['grupo_id', 'materia_id', 'profesor_id']);
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['grupo_id', 'materia_id', 'profesor_id']);
        $this->resetValidation(['grupo_id', 'materia_id', 'profesor_id']);
    }

    public function sincronizarOrdenesCicloNivel(): void
    {
        $this->autorizarAdministracion();

        if (! $this->ciclo_escolar_id || ! $this->nivel?->id) {
            return;
        }

        $resultado = app(SincronizadorOrdenCargaAcademicaService::class)
            ->sincronizarNivelCiclo((int) $this->nivel->id, (int) $this->ciclo_escolar_id);

        $conflictos = $resultado['conflictos'];

        if ($conflictos->isNotEmpty()) {
            $this->dispatch('swal', [
                'title' => 'Sincronización parcial',
                'text' => 'Se corrigieron los contextos válidos, pero existen órdenes duplicados en Materias. Corrige primero el catálogo para sincronizar los contextos pendientes.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);
        } else {
            $this->dispatch('swal', [
                'title' => 'Orden académico sincronizado',
                'text' => $resultado['actualizadas'] > 0
                    ? "Se corrigieron {$resultado['actualizadas']} carga(s) usando el orden oficial de Materias."
                    : 'Todas las cargas ya respetan el orden oficial de Materias.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        }

        $this->resetPage('materiasPage');
    }

}
