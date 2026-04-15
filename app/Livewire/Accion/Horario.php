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
use Livewire\Component;

class Horario extends Component
{
    public $slug_nivel;
    public $nivel;
    public $niveles;

    public ?int $grado_id = null;
    public ?int $grupo_id = null;
    public ?int $semestre_id = null;

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->get();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = null;
        $this->semestre_id = null;
    }

    public function esBachillerato(): bool
    {
        $nombre = mb_strtolower((string) $this->nivel->nombre);
        $slug = mb_strtolower((string) $this->nivel->slug);

        return str_contains($nombre, 'bachillerato') || $slug === 'bachillerato';
    }

    public function guardarMateriaHorario(int $horaId, int $diaId, $asignacionMateriaId): void
    {
        if (!$this->grado_id || !$this->grupo_id) {
            return;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return;
        }

        $grupo = Grupo::query()
            ->where('id', $this->grupo_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->first();

        if (!$grupo || !$grupo->generacion_id) {
            return;
        }

        if (blank($asignacionMateriaId)) {
            HorarioModel::query()
                ->where('nivel_id', $this->nivel->id)
                ->where('grado_id', $this->grado_id)
                ->where('grupo_id', $this->grupo_id)
                ->where('generacion_id', $grupo->generacion_id)
                ->where('hora_id', $horaId)
                ->where('dia_id', $diaId)
                ->when(
                    $this->esBachillerato(),
                    fn($query) => $query->where('semestre_id', $this->semestre_id),
                    fn($query) => $query->whereNull('semestre_id')
                )
                ->delete();

            return;
        }

        $materiaValida = AsignacionMateria::query()
            ->where('id', $asignacionMateriaId)
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->when(
                $this->esBachillerato(),
                fn($query) => $query->where('semestre', $this->semestre_id),
                fn($query) => $query->whereNull('semestre')
            )
            ->exists();

        if (!$materiaValida) {
            return;
        }

        HorarioModel::updateOrCreate(
            [
                'nivel_id' => $this->nivel->id,
                'grado_id' => $this->grado_id,
                'generacion_id' => $grupo->generacion_id,
                'grupo_id' => $this->grupo_id,
                'hora_id' => $horaId,
                'dia_id' => $diaId,
                'semestre_id' => $this->esBachillerato() ? $this->semestre_id : null,
            ],
            [
                'asignacion_materia_id' => (int) $asignacionMateriaId,
            ]
        );
    }

    public function render()
    {
        $grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        $grupos = Grupo::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($this->grado_id, function ($query) {
                $query->where('grado_id', $this->grado_id);
            })
            ->orderBy('nombre')
            ->get();

        $semestres = collect();

        if ($this->esBachillerato()) {
            $semestres = Semestre::query()
                ->where('nivel_id', $this->nivel->id)
                ->orderBy('orden')
                ->get();
        }

        $horas = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();

        $dias = Dia::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('dia')
            ->get();

        $materiasDisponibles = collect();
        $horariosGuardados = collect();

        if ($this->grado_id && $this->grupo_id && (!$this->esBachillerato() || $this->semestre_id)) {
            $materiasDisponibles = AsignacionMateria::query()
                ->where('nivel_id', $this->nivel->id)
                ->where('grado_id', $this->grado_id)
                ->where('grupo_id', $this->grupo_id)
                ->when(
                    $this->esBachillerato(),
                    fn($query) => $query->where('semestre', $this->semestre_id),
                    fn($query) => $query->whereNull('semestre')
                )
                ->orderBy('orden')
                ->orderBy('materia')
                ->get();

            $grupo = Grupo::query()->find($this->grupo_id);

            if ($grupo && $grupo->generacion_id) {
                $horariosGuardados = HorarioModel::query()
                    ->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $this->grado_id)
                    ->where('grupo_id', $this->grupo_id)
                    ->where('generacion_id', $grupo->generacion_id)
                    ->when(
                        $this->esBachillerato(),
                        fn($query) => $query->where('semestre_id', $this->semestre_id),
                        fn($query) => $query->whereNull('semestre_id')
                    )
                    ->get()
                    ->keyBy(fn($item) => $item->hora_id . '-' . $item->dia_id);
            }
        }

        return view('livewire.accion.horario', [
            'grados' => $grados,
            'grupos' => $grupos,
            'semestres' => $semestres,
            'horas' => $horas,
            'dias' => $dias,
            'materiasDisponibles' => $materiasDisponibles,
            'horariosGuardados' => $horariosGuardados,
            'esBachillerato' => $this->esBachillerato(),
        ]);
    }
}
