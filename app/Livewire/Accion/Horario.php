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
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;

        if ($this->esBachillerato) {
            $this->semestre_id = null;
        }

        $this->cargarGrupos();
        $this->cargarHoras();
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
    }

    public function updatedGrupoId(): void
    {
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
    }

    public function updatedSemestreId(): void
    {
        $this->cargarMateriasDisponibles();
        $this->cargarHorariosGuardados();
    }

    public function guardarMateriaHorario(int $horaId, int $diaId, $asignacionMateriaId = null): void
    {
        if (!$this->filtrosCompletos()) {
            return;
        }

        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            return;
        }

        $generacionId = $grupo->generacion_id;

        $asignacionMateriaId = filled($asignacionMateriaId) ? (int) $asignacionMateriaId : null;

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

        if (blank($asignacionMateriaId)) {
            if ($horarioExistente) {
                $horarioExistente->delete();
            }

            $this->cargarHorariosGuardados();
            return;
        }

        $datos = [
            'nivel_id' => $this->nivel->id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $generacionId,
            'grupo_id' => $this->grupo_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'hora_id' => $horaId,
            'dia_id' => $diaId,
            'asignacion_materia_id' => $asignacionMateriaId,
        ];

        if ($horarioExistente) {
            $horarioExistente->update([
                'generacion_id' => $generacionId,
                'asignacion_materia_id' => $asignacionMateriaId,
            ]);
        } else {
            HorarioModel::create($datos);
        }

        $this->cargarHorariosGuardados();
    }

    public function getPuedeDescargarHorarioProperty(): bool
    {
        return $this->filtrosCompletos();
    }

    public function getUrlDescargaHorarioProperty(): ?string
    {
        if (!$this->puedeDescargarHorario) {
            return null;
        }

        $parametros = [
            'slug_nivel' => $this->slug_nivel,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
        ];

        if ($this->esBachillerato) {
            $parametros['semestre_id'] = $this->semestre_id;
        }

        return route('misrutas.horarios.pdf', $parametros);
    }

    public function obtenerColorPastel(?string $texto = null): string
    {
        $colores = [
            '#FADADD',
            '#FDE2C8',
            '#FFF1B6',
            '#DFF7E2',
            '#D9F2E6',
            '#D9ECFF',
            '#E4D9FF',
            '#F3D9FA',
            '#E8EAF6',
            '#FFE5EC',
            '#E0F7FA',
            '#F1F8E9',
        ];

        $texto = trim((string) $texto);

        if ($texto === '') {
            return $colores[0];
        }

        $indice = abs(crc32($texto)) % count($colores);

        return $colores[$indice];
    }

    public function obtenerColorTexto(string $colorHex): string
    {
        $colorHex = ltrim($colorHex, '#');

        if (strlen($colorHex) !== 6) {
            return '#1F2937';
        }

        $r = hexdec(substr($colorHex, 0, 2));
        $g = hexdec(substr($colorHex, 2, 2));
        $b = hexdec(substr($colorHex, 4, 2));

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
            ->when($this->grado_id, function ($query) {
                $query->where('grado_id', $this->grado_id);
            }, function ($query) {
                $query->whereRaw('1 = 0');
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

        $this->semestres = Semestre::query()
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
