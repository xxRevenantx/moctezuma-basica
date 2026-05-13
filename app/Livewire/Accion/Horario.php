<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Dia;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario as HorarioModel;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class Horario extends Component
{
    public string $mensajeActualizacionHorario = '';
    public string $slug_nivel;

    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $horas;
    public Collection $dias;
    public Collection $semestres;
    public Collection $materiasDisponibles;
    public Collection $horariosGuardados;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;

    public bool $esBachillerato = false;

    public array $seleccionesHorario = [];

    public bool $mostrarModalTraslapeProfesor = false;

    public array $pendienteHorario = [
        'hora_id' => null,
        'dia_id' => null,
        'asignacion_materia_id' => null,
        'clave_celda' => null,
    ];

    public array $conflictosProfesor = [];

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->esBachillerato = (int) $this->nivel->id === 4;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->horas = collect();
        $this->dias = collect();
        $this->semestres = collect();
        $this->materiasDisponibles = collect();
        $this->horariosGuardados = collect();

        $this->cargarGeneraciones();
        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;

        if ($this->esBachillerato) {
            $this->semestre_id = null;
        }

        $this->resetEstadoTraslapeProfesor();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedGrupoId(): void
    {
        $this->resetEstadoTraslapeProfesor();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarGrupos();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    #[On('refrescarHorasDias')]
    public function refrescarHorasDias(): void
    {
        $this->mensajeActualizacionHorario = 'Actualizando horarios...';

        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();

        $this->mensajeActualizacionHorario = 'Horario actualizado correctamente.';
    }

    public function updatedSeleccionesHorario($value, $key): void
    {
        if (!$this->filtrosCompletos()) {
            return;
        }

        if (!str_contains((string) $key, '-')) {
            return;
        }

        [$horaId, $diaId] = array_map('intval', explode('-', (string) $key));

        $this->procesarCambioHorario(
            horaId: $horaId,
            diaId: $diaId,
            asignacionMateriaId: filled($value) ? (int) $value : null,
            claveCelda: (string) $key
        );
    }

    protected function procesarCambioHorario(
        int $horaId,
        int $diaId,
        ?int $asignacionMateriaId,
        string $claveCelda,
        bool $forzar = false
    ): void {
        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('hora_id', $horaId)
            ->where('dia_id', $diaId);

        if ($this->esBachillerato) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $horarioExistente = $consulta->first();

        if (blank($asignacionMateriaId)) {
            if ($horarioExistente) {
                $horarioExistente->delete();
            }

            $this->resetEstadoTraslapeProfesor();
            $this->cargarHorariosGuardados();
            $this->sincronizarSeleccionesHorario();

            return;
        }

        $asignacion = AsignacionMateria::query()
            ->with([
                'materia',
                'profesor',
            ])
            ->where('id', $asignacionMateriaId)
            ->where('grupo_id', $this->grupo_id)
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $this->grado_id);

                if ($this->esBachillerato) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereNull('semestre_id');
                }
            })
            ->first();

        if (!$asignacion) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        if (blank($asignacion->profesor_id)) {
            $this->guardarHorarioDirecto(
                horaId: $horaId,
                diaId: $diaId,
                asignacionMateriaId: $asignacionMateriaId,
                horarioExistente: $horarioExistente
            );

            return;
        }

        $horaActual = Hora::query()->find($horaId);

        if (!$horaActual) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        $conflictos = $this->buscarConflictosProfesor(
            profesorId: (int) $asignacion->profesor_id,
            diaId: $diaId,
            horaInicio: $horaActual->hora_inicio,
            horaFin: $horaActual->hora_fin,
            horarioActualId: $horarioExistente?->id
        );

        if (!$forzar && count($conflictos) > 0) {
            $this->pendienteHorario = [
                'hora_id' => $horaId,
                'dia_id' => $diaId,
                'asignacion_materia_id' => $asignacionMateriaId,
                'clave_celda' => $claveCelda,
            ];

            $this->conflictosProfesor = $conflictos;
            $this->mostrarModalTraslapeProfesor = true;

            $this->restaurarCeldaDesdeHorarioGuardado($claveCelda);

            return;
        }

        $this->guardarHorarioDirecto(
            horaId: $horaId,
            diaId: $diaId,
            asignacionMateriaId: $asignacionMateriaId,
            horarioExistente: $horarioExistente
        );
    }

    protected function guardarHorarioDirecto(
        int $horaId,
        int $diaId,
        int $asignacionMateriaId,
        ?HorarioModel $horarioExistente = null
    ): void {
        $datosBusqueda = [
            'nivel_id' => $this->nivel->id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'grupo_id' => $this->grupo_id,
            'hora_id' => $horaId,
            'dia_id' => $diaId,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
        ];

        if ($horarioExistente) {
            $horarioExistente->update([
                'asignacion_materia_id' => $asignacionMateriaId,
                'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            ]);
        } else {
            HorarioModel::query()->create([
                ...$datosBusqueda,
                'asignacion_materia_id' => $asignacionMateriaId,
            ]);
        }

        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();
    }

    protected function buscarConflictosProfesor(
        int $profesorId,
        int $diaId,
        string $horaInicio,
        string $horaFin,
        ?int $horarioActualId = null
    ): array {
        $conflictos = HorarioModel::query()
            ->with([
                'hora',
                'nivel',
                'grado',
                'grupo.asignacionGrupo',
                'dia',
                'semestre',
                'asignacionMateria.materia',
                'asignacionMateria.profesor',
            ])
            ->where('dia_id', $diaId)
            ->when($horarioActualId, function ($query) use ($horarioActualId) {
                $query->where('id', '!=', $horarioActualId);
            })
            ->whereHas('asignacionMateria', function ($query) use ($profesorId) {
                $query->where('profesor_id', $profesorId);
            })
            ->whereHas('hora', function ($query) use ($horaInicio, $horaFin) {
                $query->where('hora_inicio', '<', $horaFin)
                    ->where('hora_fin', '>', $horaInicio);
            })
            ->get();

        return $conflictos->map(function ($item) {
            $profesor = $item->asignacionMateria?->profesor;

            $nombreProfesor = trim(
                ($profesor->nombre ?? '') . ' ' .
                    ($profesor->apellido_paterno ?? '') . ' ' .
                    ($profesor->apellido_materno ?? '')
            );

            return [
                'id' => $item->id,
                'profesor' => $nombreProfesor ?: 'Sin profesor asignado',
                'nivel' => $item->nivel?->nombre ?? 'N/D',
                'grado' => $item->grado?->nombre ?? 'N/D',
                'grupo' => $this->textoGrupo($item->grupo, 'N/D'),
                'dia' => $item->dia?->dia ?? 'N/D',
                'hora_inicio' => $item->hora?->hora_inicio,
                'hora_fin' => $item->hora?->hora_fin,
                'semestre' => $item->semestre?->numero ? $item->semestre->numero . '° semestre' : null,
                'materia' => $item->asignacionMateria?->materia?->materia ?? 'N/D',
            ];
        })->toArray();
    }

    public function confirmarGuardarConTraslape(): void
    {
        if (
            blank($this->pendienteHorario['hora_id']) ||
            blank($this->pendienteHorario['dia_id']) ||
            blank($this->pendienteHorario['asignacion_materia_id']) ||
            blank($this->pendienteHorario['clave_celda'])
        ) {
            $this->resetEstadoTraslapeProfesor();
            $this->sincronizarSeleccionesHorario();

            return;
        }

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('hora_id', (int) $this->pendienteHorario['hora_id'])
            ->where('dia_id', (int) $this->pendienteHorario['dia_id']);

        if ($this->esBachillerato) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $horarioExistente = $consulta->first();

        $this->guardarHorarioDirecto(
            horaId: (int) $this->pendienteHorario['hora_id'],
            diaId: (int) $this->pendienteHorario['dia_id'],
            asignacionMateriaId: (int) $this->pendienteHorario['asignacion_materia_id'],
            horarioExistente: $horarioExistente
        );
    }

    public function cancelarGuardarConTraslape(): void
    {
        $claveCelda = $this->pendienteHorario['clave_celda'] ?? null;

        $this->resetEstadoTraslapeProfesor();
        $this->cargarHorariosGuardados();
        $this->sincronizarSeleccionesHorario();

        if ($claveCelda) {
            $this->restaurarCeldaDesdeHorarioGuardado($claveCelda);
        }
    }

    protected function resetEstadoTraslapeProfesor(): void
    {
        $this->mostrarModalTraslapeProfesor = false;

        $this->pendienteHorario = [
            'hora_id' => null,
            'dia_id' => null,
            'asignacion_materia_id' => null,
            'clave_celda' => null,
        ];

        $this->conflictosProfesor = [];
    }

    protected function sincronizarSeleccionesHorario(): void
    {
        $selecciones = [];

        foreach ($this->horas as $hora) {
            foreach ($this->dias as $dia) {
                $clave = $hora->id . '-' . $dia->id;
                $horario = $this->horariosGuardados->get($clave);

                $selecciones[$clave] = $horario?->asignacion_materia_id;
            }
        }

        $this->seleccionesHorario = $selecciones;
    }

    protected function restaurarCeldaDesdeHorarioGuardado(string $claveCelda): void
    {
        $horario = $this->horariosGuardados->get($claveCelda);

        $this->seleccionesHorario[$claveCelda] = $horario?->asignacion_materia_id;
    }

    public function getPuedeDescargarHorarioProperty(): bool
    {
        return $this->filtrosCompletos();
    }

    public function getUrlDescargaHorarioProperty(): string
    {
        if (!$this->puedeDescargarHorario) {
            return '#';
        }

        return route('misrutas.horarios.pdf', [
            'slug_nivel' => $this->slug_nivel,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
        ]);
    }

    protected function obtenerColorPastel(?string $texto = null): string
    {
        $texto = filled($texto) ? $texto : 'sin-profesor';

        $paleta = [
            '#FDE68A',
            '#BFDBFE',
            '#C7D2FE',
            '#A7F3D0',
            '#FBCFE8',
            '#DDD6FE',
            '#FECACA',
            '#BAE6FD',
            '#D9F99D',
            '#FED7AA',
            '#E9D5FF',
            '#99F6E4',
        ];

        $indice = abs(crc32((string) $texto)) % count($paleta);

        return $paleta[$indice];
    }

    protected function obtenerColorTexto(string $fondoHex): string
    {
        $hex = ltrim($fondoHex, '#');

        if (strlen($hex) !== 6) {
            return '#1F2937';
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $luminosidad = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        return $luminosidad > 170 ? '#1F2937' : '#FFFFFF';
    }

    public function obtenerEstiloProfesor(?string $nombreProfesor = null): array
    {
        $fondo = $this->obtenerColorPastel($nombreProfesor);
        $texto = $this->obtenerColorTexto($fondo);

        return [
            'background' => $fondo,
            'color' => $texto,
            'border' => $texto === '#FFFFFF'
                ? 'rgba(255,255,255,0.25)'
                : 'rgba(15,23,42,0.08)',
        ];
    }

    public function textoGrupo($grupo, string $valorPorDefecto = 'Sin grupo'): string
    {
        if (!$grupo) {
            return $valorPorDefecto;
        }

        return $grupo->asignacionGrupo?->nombre ?? $valorPorDefecto;
    }

    public function limpiarFiltros(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->semestre_id = null;
        $this->grupos = collect();
        $this->semestres = collect();
        $this->materiasDisponibles = collect();
        $this->horariosGuardados = collect();
        $this->seleccionesHorario = [];
        $this->resetEstadoTraslapeProfesor();
    }

    public function getTotalCeldasProperty(): int
    {
        return $this->horas->count() * $this->dias->count();
    }

    public function getCeldasAsignadasProperty(): int
    {
        return $this->horariosGuardados->count();
    }

    public function getAvanceHorarioProperty(): int
    {
        if ($this->totalCeldas <= 0) {
            return 0;
        }

        return min(100, (int) round(($this->celdasAsignadas / $this->totalCeldas) * 100));
    }

    protected function consultaGruposBase(): Builder
    {
        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->where('grupos.nivel_id', $this->nivel->id);
    }

    protected function filtrosCompletos(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->grupo_id)
                && filled($this->semestre_id);
        }

        return filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id);
    }

    protected function filtrosMinimosParaMaterias(): bool
    {
        return $this->filtrosCompletos();
    }

    protected function obtenerGrupoSeleccionado(): ?Grupo
    {
        if (!$this->grupo_id) {
            return null;
        }

        return $this->consultaGruposBase()
            ->where('grupos.id', $this->grupo_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->where('grupos.generacion_id', $this->generacion_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('grupos.semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('grupos.semestre_id')
            )
            ->first();
    }

    protected function cargarGeneraciones(): void
    {
        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();
    }

    protected function cargarGrados(): void
    {
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    protected function cargarGrupos(): void
    {
        if (!$this->generacion_id || !$this->grado_id) {
            $this->grupos = collect();
            return;
        }

        if ($this->esBachillerato && !$this->semestre_id) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = $this->consultaGruposBase()
            ->where('grupos.generacion_id', $this->generacion_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('grupos.semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('grupos.semestre_id')
            )
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get();
    }

    protected function cargarHoras(): void
    {
        $this->horas = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();
    }

    protected function cargarDias(): void
    {
        $this->dias = Dia::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->get()
            ->unique('dia')
            ->values();
    }

    protected function cargarSemestres(): void
    {
        if (!$this->esBachillerato) {
            $this->semestres = collect();
            return;
        }

        if (!$this->grado_id) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get();
    }

    protected function cargarMateriasDisponibles(): void
    {
        if (!$this->filtrosMinimosParaMaterias()) {
            $this->materiasDisponibles = collect();
            return;
        }

        $this->materiasDisponibles = AsignacionMateria::query()
            ->with([
                'materia',
                'profesor',
            ])
            ->where('grupo_id', $this->grupo_id)
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $this->grado_id);

                if ($this->esBachillerato) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereNull('semestre_id');
                }
            })
            ->orderBy('asignacion_materias.orden')
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->orden ?? 0) <=> ($b->orden ?? 0),
                fn($a, $b) => ($a->materia?->orden ?? 0) <=> ($b->materia?->orden ?? 0),
                fn($a, $b) => strcmp($a->materia?->materia ?? '', $b->materia?->materia ?? ''),
            ])
            ->values();
    }

    protected function cargarHorariosGuardados(): void
    {
        if (!$this->filtrosCompletos()) {
            $this->horariosGuardados = collect();
            return;
        }

        $horarios = HorarioModel::query()
            ->with([
                'asignacionMateria.materia',
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grupo_id', $this->grupo_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('semestre_id')
            )
            ->get();

        $this->horariosGuardados = $horarios->keyBy(function ($horario) {
            return $horario->hora_id . '-' . $horario->dia_id;
        });
    }

    public function render()
    {
        return view('livewire.accion.horario');
    }
}
