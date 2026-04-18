<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\Dia;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario as HorarioModel;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Support\Collection;
use Livewire\Component;

class Horario extends Component
{
    public string $slug_nivel;

    public ?Nivel $nivel = null;
    public Collection $niveles;
    public Collection $grados;
    public Collection $grupos;
    public Collection $horas;
    public Collection $dias;
    public Collection $semestres;
    public Collection $materiasDisponibles;
    public Collection $horariosGuardados;

    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;

    public bool $esBachillerato = false;

    // =========================
    // Estado visual por celda
    // =========================
    public array $seleccionesHorario = [];

    // =========================
    // Modal de confirmación
    // =========================
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

        $this->esBachillerato = str($this->nivel->slug)->lower()->contains('bachillerato');

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->get();

        $this->cargarGrados();
        $this->cargarGrupos();
        $this->cargarHoras();
        $this->cargarDias();
        $this->cargarSemestres();
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
        $this->cargarHoras();
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

    /**
     * Este método se dispara cuando cambia cualquier celda del horario.
     * La llave llega con formato "horaId-diaId".
     */
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

        $generacionId = $grupo->generacion_id;

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $generacionId)
            ->where('grupo_id', $this->grupo_id)
            ->where('hora_id', $horaId)
            ->where('dia_id', $diaId);

        if ($this->esBachillerato) {
            $consulta->where('semestre_id', $this->semestre_id);
        } else {
            $consulta->whereNull('semestre_id');
        }

        $horarioExistente = $consulta->first();

        // Si se limpió la celda
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
            ->with('profesor')
            ->find($asignacionMateriaId);

        if (!$asignacion) {
            $this->sincronizarSeleccionesHorario();
            return;
        }

        if (blank($asignacion->profesor_id)) {
            $this->guardarHorarioDirecto(
                horaId: $horaId,
                diaId: $diaId,
                generacionId: $generacionId,
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

            // Muy importante: regresa visualmente al valor guardado real
            $this->restaurarCeldaDesdeHorarioGuardado($claveCelda);

            return;
        }

        $this->guardarHorarioDirecto(
            horaId: $horaId,
            diaId: $diaId,
            generacionId: $generacionId,
            asignacionMateriaId: $asignacionMateriaId,
            horarioExistente: $horarioExistente
        );
    }

    protected function guardarHorarioDirecto(
        int $horaId,
        int $diaId,
        int $generacionId,
        int $asignacionMateriaId,
        ?HorarioModel $horarioExistente = null
    ): void {
        $datosBusqueda = [
            'nivel_id' => $this->nivel->id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $generacionId,
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
                'grupo',
                'dia',
                'semestre',
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
                'nivel' => $item->nivel->nombre ?? 'N/D',
                'grado' => $item->grado->nombre ?? 'N/D',
                'grupo' => $item->grupo->nombre ?? 'N/D',
                'dia' => $item->dia->dia ?? 'N/D',
                'hora_inicio' => $item->hora?->hora_inicio,
                'hora_fin' => $item->hora?->hora_fin,
                'semestre' => $item->semestre->semestre ?? ($item->semestre->nombre ?? null),
                'materia' => $item->asignacionMateria->materia ?? 'N/D',
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

        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            $this->resetEstadoTraslapeProfesor();
            $this->sincronizarSeleccionesHorario();
            return;
        }

        $generacionId = $grupo->generacion_id;

        $consulta = HorarioModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $generacionId)
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
            generacionId: $generacionId,
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

    protected function filtrosCompletos(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->grado_id) && filled($this->grupo_id) && filled($this->semestre_id);
        }

        return filled($this->grado_id) && filled($this->grupo_id);
    }

    protected function filtrosMinimosParaMaterias(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->grado_id) && filled($this->grupo_id) && filled($this->semestre_id);
        }

        return filled($this->grado_id) && filled($this->grupo_id);
    }

    protected function obtenerGrupoSeleccionado(): ?Grupo
    {
        if (!$this->grupo_id) {
            return null;
        }

        return Grupo::query()
            ->select('id', 'grado_id', 'generacion_id', 'nombre')
            ->find($this->grupo_id);
    }

    protected function cargarGrados(): void
    {
        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('nombre')
            ->get();
    }

    protected function cargarGrupos(): void
    {
        $this->grupos = Grupo::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($this->grado_id, function ($query) {
                $query->where('grado_id', $this->grado_id);
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->when($this->esBachillerato, function ($query) {
                if ($this->semestre_id) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('nombre')
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

        $semestreIds = Grupo::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->whereNotNull('semestre_id')
            ->pluck('semestre_id')
            ->unique()
            ->values();

        $this->semestres = Semestre::query()
            ->whereIn('id', $semestreIds)
            ->orderBy('id')
            ->get();
    }

    protected function cargarMateriasDisponibles(): void
    {
        if (!$this->filtrosMinimosParaMaterias()) {
            $this->materiasDisponibles = collect();
            return;
        }

        $this->materiasDisponibles = AsignacionMateria::query()
            ->with('profesor')
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('semestre', $this->semestre_id),
                fn($query) => $query->whereNull('semestre')
            )
            ->orderBy('orden')
            ->orderBy('materia')
            ->get();
    }

    protected function cargarHorariosGuardados(): void
    {
        if (!$this->filtrosCompletos()) {
            $this->horariosGuardados = collect();
            return;
        }

        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            $this->horariosGuardados = collect();
            return;
        }

        $horarios = HorarioModel::query()
            ->with([
                'asignacionMateria.profesor',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $grupo->generacion_id)
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
