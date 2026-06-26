<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria as AsignacionMateriaModel;
use App\Models\cicloEscolar;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\TrayectoriaAcademica;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AsignacionMateria extends Component
{
    public string $slug_nivel = '';
    public $nivel = null;

    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_origen_id = null;
    public bool $copiar_profesores = true;
    public bool $copiar_horarios = false;

    public string $buscar = '';
    public ?int $editandoId = null;
    public $grupo_id = '';
    public $materia_id = '';
    public $profesor_id = '';
    public string $buscarProfesor = '';
    public ?int $ultimoRegistroId = null;
    public string $ultimoMovimiento = '';

    public function mount($slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

        $actual = cicloEscolar::query()->where('es_actual', true)->first()
            ?? cicloEscolar::query()->orderByDesc('inicio_anio')->first();

        $this->ciclo_escolar_id = $actual?->id;
        $this->ciclo_origen_id = cicloEscolar::query()
            ->when($actual, fn ($q) => $q->where('id', '!=', $actual->id)->where('inicio_anio', '<=', $actual->inicio_anio))
            ->orderByDesc('inicio_anio')
            ->value('id');
    }

    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel?->id === 4;
    }

    public function getCiclosEscolaresProperty(): Collection
    {
        return cicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get();
    }

    public function getCicloSeleccionadoProperty(): ?cicloEscolar
    {
        return $this->ciclo_escolar_id
            ? cicloEscolar::query()->find($this->ciclo_escolar_id)
            : null;
    }

    public function getGruposProperty(): Collection
    {
        if (!$this->nivel?->id) {
            return collect();
        }

        $gruposConTrayectoria = TrayectoriaAcademica::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->pluck('grupo_id')
            ->filter()
            ->unique();

        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,nivel_id,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,orden_global',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->when($gruposConTrayectoria->isNotEmpty(), fn ($q) => $q->whereIn('id', $gruposConTrayectoria))
            ->get()
            ->sortBy(fn ($grupo) => sprintf(
                '%03d|%03d|%s|%04d',
                (int) ($grupo->grado?->orden ?? 999),
                (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 999),
                mb_strtolower((string) ($grupo->asignacionGrupo?->nombre ?? '')),
                (int) ($grupo->generacion?->anio_ingreso ?? 0),
            ))
            ->values();
    }

    public function getGrupoSeleccionadoProperty(): ?Grupo
    {
        if (blank($this->grupo_id)) {
            return null;
        }

        return Grupo::query()
            ->with(['asignacionGrupo', 'grado', 'generacion', 'semestre'])
            ->whereKey($this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->first();
    }

    public function getMateriasDisponiblesProperty(): Collection
    {
        $grupo = $this->grupoSeleccionado;

        if (!$grupo) {
            return collect();
        }

        return Materia::query()
            ->where('nivel_id', $grupo->nivel_id)
            ->where('grado_id', $grupo->grado_id)
            ->when(
                $this->esBachillerato,
                fn ($q) => $q->where('semestre_id', $grupo->semestre_id),
                fn ($q) => $q->whereNull('semestre_id')
            )
            // Receso nunca genera carga. Los talleres conjuntos de secundaria
            // se administran en su módulo específico y no se duplican aquí.
            ->where('receso', false)
            ->when($this->nivel?->slug === 'secundaria', fn ($q) => $q->where('slug', '!=', 'taller'))
            ->orderBy('orden')
            ->orderBy('materia')
            ->get();
    }

    public function getProfesoresProperty(): Collection
    {
        return PersonaNivel::query()
            ->with('persona')
            ->where('nivel_id', $this->nivel->id)
            ->whereHas('persona', fn ($q) => $q->where('status', true))
            ->get()
            ->map(function ($registro) {
                $persona = $registro->persona;
                $nombre = trim(($persona->titulo ?? '') . ' ' . ($persona->nombre ?? '') . ' '
                    . ($persona->apellido_paterno ?? '') . ' ' . ($persona->apellido_materno ?? ''));

                return [
                    'id' => (int) $persona->id,
                    'nombre' => $nombre,
                    'buscar' => mb_strtolower($nombre),
                ];
            })
            ->filter(fn ($item) => filled($item['nombre']))
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    public function getProfesoresFiltradosProperty(): Collection
    {
        $buscar = mb_strtolower(trim($this->buscarProfesor));

        return $buscar === ''
            ? $this->profesores
            : $this->profesores->filter(fn ($item) => str_contains($item['buscar'], $buscar))->values();
    }

    public function getAsignacionesFiltradasProperty(): Collection
    {
        if (!$this->ciclo_escolar_id) {
            return collect();
        }

        return AsignacionMateriaModel::query()
            ->with([
                'materia',
                'profesor',
                'cicloEscolar',
                'grupo.nivel',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'grupo.asignacionGrupo',
                'horarios' => fn ($q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id),
            ])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->when($this->buscar !== '', function ($q) {
                $buscar = '%' . trim($this->buscar) . '%';
                $q->where(function ($sub) use ($buscar) {
                    $sub->whereHas('materia', fn ($m) => $m->where('materia', 'like', $buscar)->orWhere('clave', 'like', $buscar))
                        ->orWhereHas('profesor', fn ($p) => $p->where('nombre', 'like', $buscar)
                            ->orWhere('apellido_paterno', 'like', $buscar)
                            ->orWhere('apellido_materno', 'like', $buscar))
                        ->orWhereHas('grupo.asignacionGrupo', fn ($g) => $g->where('nombre', 'like', $buscar));
                });
            })
            ->get()
            ->sortBy(fn ($a) => sprintf(
                '%03d|%03d|%s|%03d|%s',
                (int) ($a->grupo?->grado?->orden ?? 999),
                (int) ($a->grupo?->semestre?->orden_global ?? $a->grupo?->semestre?->numero ?? 999),
                mb_strtolower((string) ($a->grupo?->asignacionGrupo?->nombre ?? '')),
                (int) ($a->orden ?? 999),
                mb_strtolower((string) ($a->materia?->materia ?? '')),
            ))
            ->values();
    }

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
        $this->limpiarFormulario();
    }

    public function updatedGrupoId(): void
    {
        $this->reset(['materia_id']);
        $this->resetValidation(['grupo_id', 'materia_id']);
    }

    public function updatedBuscarProfesor(): void
    {
        if (blank($this->buscarProfesor)) {
            $this->profesor_id = '';
        }
    }

    public function seleccionarProfesor(int $profesorId): void
    {
        $profesor = $this->profesores->firstWhere('id', $profesorId);
        $this->profesor_id = $profesorId;
        $this->buscarProfesor = $profesor['nombre'] ?? '';
    }

    public function guardarMateria(): void
    {
        $this->validate();

        $grupo = Grupo::query()->whereKey($this->grupo_id)->where('nivel_id', $this->nivel->id)->first();
        $materia = Materia::query()->find($this->materia_id);

        if (!$grupo || !$materia) {
            $this->addError('grupo_id', 'El grupo o la materia ya no están disponibles.');
            return;
        }

        if ((int) $materia->nivel_id !== (int) $grupo->nivel_id
            || (int) $materia->grado_id !== (int) $grupo->grado_id
            || ($this->esBachillerato && (int) $materia->semestre_id !== (int) $grupo->semestre_id)) {
            $this->addError('materia_id', 'La materia no corresponde al contexto académico del grupo.');
            return;
        }

        $duplicada = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('grupo_id', $grupo->id)
            ->where('materia_id', $materia->id)
            ->when($this->editandoId, fn ($q) => $q->where('id', '!=', $this->editandoId))
            ->exists();

        if ($duplicada) {
            $this->addError('materia_id', 'Esta materia ya tiene una carga en el grupo y ciclo seleccionados.');
            return;
        }

        $profesorId = $materia->receso ? null : (filled($this->profesor_id) ? (int) $this->profesor_id : null);

        DB::transaction(function () use ($grupo, $materia, $profesorId) {
            if ($this->editandoId) {
                $asignacion = AsignacionMateriaModel::query()->findOrFail($this->editandoId);
                abort_unless((int) $asignacion->ciclo_escolar_id === (int) $this->ciclo_escolar_id, 422);

                $asignacion->update([
                    'materia_id' => $materia->id,
                    'grupo_id' => $grupo->id,
                    'profesor_id' => $profesorId,
                    'nivel_id' => $grupo->nivel_id,
                    'grado_id' => $grupo->grado_id,
                    'generacion_id' => $grupo->generacion_id,
                    'semestre_id' => $grupo->semestre_id,
                ]);
                $this->ultimoMovimiento = 'actualizada';
            } else {
                $asignacion = AsignacionMateriaModel::query()->create([
                    'materia_id' => $materia->id,
                    'grupo_id' => $grupo->id,
                    'profesor_id' => $profesorId,
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    'nivel_id' => $grupo->nivel_id,
                    'grado_id' => $grupo->grado_id,
                    'generacion_id' => $grupo->generacion_id,
                    'semestre_id' => $grupo->semestre_id,
                    'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
                ]);
                $this->ultimoMovimiento = 'registrada';
            }

            $this->ultimoRegistroId = $asignacion->id;
        });

        $this->limpiarFormularioDespuesDeGuardar();
        $this->dispatch('swal', [
            'title' => 'Carga académica ' . $this->ultimoMovimiento,
            'text' => 'Se guardó dentro del ciclo seleccionado sin modificar ciclos anteriores.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function editar(int $id): void
    {
        $asignacion = AsignacionMateriaModel::query()
            ->with(['profesor'])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->findOrFail($id);

        $this->editandoId = $asignacion->id;
        $this->grupo_id = $asignacion->grupo_id;
        $this->materia_id = $asignacion->materia_id;
        $this->profesor_id = $asignacion->profesor_id ?: '';
        $this->buscarProfesor = $asignacion->profesor
            ? trim(($asignacion->profesor->titulo ?? '') . ' ' . ($asignacion->profesor->nombre ?? '') . ' '
                . ($asignacion->profesor->apellido_paterno ?? '') . ' ' . ($asignacion->profesor->apellido_materno ?? ''))
            : '';

        $this->dispatch('scroll-editar-materia');
    }

    public function confirmar(int $id): void
    {
        $this->cambiarEstado($id, AsignacionMateriaModel::ESTADO_ACTIVA);
    }

    public function cerrar(int $id): void
    {
        $this->cambiarEstado($id, AsignacionMateriaModel::ESTADO_CERRADA);
    }

    public function archivar(int $id): void
    {
        $this->cambiarEstado($id, AsignacionMateriaModel::ESTADO_ARCHIVADA);
    }

    public function reactivar(int $id): void
    {
        $this->cambiarEstado($id, AsignacionMateriaModel::ESTADO_ACTIVA);
    }

    public function confirmarTodas(): void
    {
        $this->autorizarAdministracion();

        AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('estado', AsignacionMateriaModel::ESTADO_BORRADOR)
            ->update([
                'estado' => AsignacionMateriaModel::ESTADO_ACTIVA,
                'confirmada_at' => now(),
                'confirmada_por' => auth()->id(),
                'fecha_inicio' => DB::raw('COALESCE(fecha_inicio, CURRENT_DATE)'),
            ]);

        $this->dispatch('swal', ['title' => 'Cargas confirmadas', 'icon' => 'success', 'position' => 'top-end']);
    }

    private function cambiarEstado(int $id, string $estado): void
    {
        $this->autorizarAdministracion();

        $asignacion = AsignacionMateriaModel::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->findOrFail($id);

        $datos = ['estado' => $estado];

        if ($estado === AsignacionMateriaModel::ESTADO_ACTIVA) {
            $datos['confirmada_at'] = now();
            $datos['confirmada_por'] = auth()->id();
            $datos['fecha_inicio'] = $asignacion->fecha_inicio ?: now()->toDateString();
            $datos['fecha_fin'] = null;
        }

        if (in_array($estado, [AsignacionMateriaModel::ESTADO_CERRADA, AsignacionMateriaModel::ESTADO_ARCHIVADA], true)) {
            $datos['fecha_fin'] = now()->toDateString();
        }

        $asignacion->update($datos);

        $this->dispatch('swal', [
            'title' => 'Estado actualizado',
            'text' => 'No se eliminó ningún horario, calificación o lista histórica.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    /**
     * Copia por nivel las cargas del ciclo origen. Siempre crea IDs nuevos.
     * Los horarios se copian únicamente cuando el administrador lo solicita.
     */
    public function copiarDesdeCiclo(): void
    {
        $this->autorizarAdministracion();

        $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'ciclo_origen_id' => ['required', 'integer', 'exists:ciclo_escolares,id', Rule::notIn([(int) $this->ciclo_escolar_id])],
        ]);

        $creadas = 0;
        $omitidas = 0;
        $horariosCopiados = 0;

        DB::transaction(function () use (&$creadas, &$omitidas, &$horariosCopiados) {
            $origenes = AsignacionMateriaModel::query()
                ->with(['grupo', 'horarios' => fn ($q) => $q->where('ciclo_escolar_id', $this->ciclo_origen_id)])
                ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                ->where('nivel_id', $this->nivel->id)
                ->where('estado', '!=', AsignacionMateriaModel::ESTADO_ARCHIVADA)
                ->get();

            foreach ($origenes as $origen) {
                $grupoDestino = $this->resolverGrupoDestino($origen);

                if (!$grupoDestino) {
                    $omitidas++;
                    continue;
                }

                $existe = AsignacionMateriaModel::query()
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('grupo_id', $grupoDestino->id)
                    ->where('materia_id', $origen->materia_id)
                    ->first();

                if ($existe) {
                    $omitidas++;
                    continue;
                }

                $nueva = AsignacionMateriaModel::query()->create([
                    'materia_id' => $origen->materia_id,
                    'grupo_id' => $grupoDestino->id,
                    'profesor_id' => $this->copiar_profesores ? $origen->profesor_id : null,
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    'nivel_id' => $grupoDestino->nivel_id,
                    'grado_id' => $grupoDestino->grado_id,
                    'generacion_id' => $grupoDestino->generacion_id,
                    'semestre_id' => $grupoDestino->semestre_id,
                    'orden' => $origen->orden,
                    'estado' => AsignacionMateriaModel::ESTADO_BORRADOR,
                    'asignacion_origen_id' => $origen->id,
                ]);
                $creadas++;

                if (!$this->copiar_horarios) {
                    continue;
                }

                foreach ($origen->horarios as $horarioOrigen) {
                    $ocupada = Horario::query()
                        ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                        ->where('grupo_id', $grupoDestino->id)
                        ->where('dia_id', $horarioOrigen->dia_id)
                        ->where('hora_id', $horarioOrigen->hora_id)
                        ->exists();

                    if ($ocupada) {
                        continue;
                    }

                    Horario::query()->create([
                        'nivel_id' => $grupoDestino->nivel_id,
                        'grado_id' => $grupoDestino->grado_id,
                        'generacion_id' => $grupoDestino->generacion_id,
                        'semestre_id' => $grupoDestino->semestre_id,
                        'grupo_id' => $grupoDestino->id,
                        'hora_id' => $horarioOrigen->hora_id,
                        'dia_id' => $horarioOrigen->dia_id,
                        'asignacion_materia_id' => $nueva->id,
                        'taller_sesion_id' => null,
                        'ciclo_escolar_id' => $this->ciclo_escolar_id,
                    ]);
                    $horariosCopiados++;
                }
            }
        });

        $this->dispatch('swal', [
            'title' => 'Preparación del ciclo terminada',
            'text' => "Nuevas: {$creadas}. Omitidas: {$omitidas}. Horarios copiados: {$horariosCopiados}. Revisa y confirma las cargas.",
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function resolverGrupoDestino(AsignacionMateriaModel $origen): ?Grupo
    {
        $origen->loadMissing('grupo');
        $grupoOrigen = $origen->grupo;

        if (!$grupoOrigen) {
            return null;
        }

        $idsVigentes = TrayectoriaAcademica::query()
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $grupoOrigen->grado_id)
            ->whereNotNull('grupo_id')
            ->when($grupoOrigen->semestre_id, fn ($q) => $q->where('semestre_id', $grupoOrigen->semestre_id))
            ->pluck('grupo_id')
            ->unique();

        return Grupo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $grupoOrigen->grado_id)
            ->where('asignacion_grupo_id', $grupoOrigen->asignacion_grupo_id)
            ->when(
                $grupoOrigen->semestre_id,
                fn ($q) => $q->where('semestre_id', $grupoOrigen->semestre_id),
                fn ($q) => $q->whereNull('semestre_id')
            )
            ->when($idsVigentes->isNotEmpty(), fn ($q) => $q->whereIn('id', $idsVigentes))
            ->orderByDesc('generacion_id')
            ->first()
            ?? Grupo::query()->find($grupoOrigen->id);
    }

    public function limpiarFormularioDespuesDeGuardar(): void
    {
        $grupo = $this->grupo_id;
        $this->reset(['editandoId', 'materia_id', 'profesor_id', 'buscarProfesor']);
        $this->grupo_id = $grupo;
        $this->resetValidation();
    }

    public function limpiarFormulario(): void
    {
        $this->reset(['editandoId', 'grupo_id', 'materia_id', 'profesor_id', 'buscarProfesor']);
        $this->resetValidation();
    }

    public function ordenarMateriasPorGrupoJs($grupoId, $ids): void
    {
        if (!is_array($ids)) {
            return;
        }

        foreach ($ids as $index => $id) {
            AsignacionMateriaModel::query()
                ->whereKey($id)
                ->where('grupo_id', $grupoId)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->update(['orden' => $index + 1]);
        }
    }

    private function autorizarAdministracion(): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede confirmar, cerrar, archivar o copiar cargas.');
    }

    public function render()
    {
        return view('livewire.accion.asignacion-materia');
    }
}
