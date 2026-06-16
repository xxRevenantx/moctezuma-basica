<?php

namespace App\Livewire\Profesor;

use App\Models\AsignacionMateria;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\Persona;
use Illuminate\Support\Collection;
use Livewire\Component;

class HorarioProfesor extends Component
{
    public ?int $profesorId = null;
    public ?int $nivelId = null;

    public function mount(): void
    {
        $primerProfesor = Persona::query()
            ->select('personas.id')
            ->whereIn('personas.id', function ($query) {
                $query->select('profesor_id')
                    ->from('asignacion_materias')
                    ->whereNotNull('profesor_id');
            })
            ->where('status', true)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->first();

        $this->profesorId = $primerProfesor?->id;
    }

    public function updatedProfesorId(): void
    {
        $this->nivelId = null;
    }

    public function limpiarFiltros(): void
    {
        $this->nivelId = null;
    }

    public function render()
    {
        $profesores = $this->obtenerProfesores();
        $niveles = $this->obtenerNiveles();
        $horarios = $this->obtenerHorarios();
        $matriz = $this->crearMatriz($horarios);
        $estadisticas = $this->crearEstadisticas($horarios);
        $profesorSeleccionado = $profesores->firstWhere('id', $this->profesorId);

        $pdfUrl = $this->profesorId
            ? route('profesor.horario.pdf', [
                'profesor' => $this->profesorId,
                'nivel' => $this->nivelId ?: 'todos',
            ])
            : null;

        return view('livewire.profesor.horario-profesor', [
            'profesores' => $profesores,
            'niveles' => $niveles,
            'horarios' => $horarios,
            'matriz' => $matriz,
            'estadisticas' => $estadisticas,
            'profesorSeleccionado' => $profesorSeleccionado,
            'pdfUrl' => $pdfUrl,
        ]);
    }

    private function obtenerProfesores(): Collection
    {
        return Persona::query()
            ->select(
                'personas.id',
                'personas.titulo',
                'personas.nombre',
                'personas.apellido_paterno',
                'personas.apellido_materno',
                'personas.correo',
                'personas.telefono_movil',
                'personas.foto'
            )
            ->whereIn('personas.id', function ($query) {
                $query->select('profesor_id')
                    ->from('asignacion_materias')
                    ->whereNotNull('profesor_id');
            })
            ->where('personas.status', true)
            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre')
            ->get()
            ->map(function ($profesor) {
                $profesor->nombre_completo = trim(
                    ($profesor->titulo ? $profesor->titulo . ' ' : '') .
                        $profesor->nombre . ' ' .
                        $profesor->apellido_paterno . ' ' .
                        ($profesor->apellido_materno ?? '')
                );

                return $profesor;
            });
    }

    private function obtenerNiveles(): Collection
    {
        if (!$this->profesorId) {
            return collect();
        }

        return Nivel::query()
            ->select('niveles.id', 'niveles.nombre', 'niveles.color', 'niveles.cct')
            ->whereIn('niveles.id', function ($query) {
                $query->select('horarios.nivel_id')
                    ->from('horarios')
                    ->join('asignacion_materias', 'asignacion_materias.id', '=', 'horarios.asignacion_materia_id')
                    ->where('asignacion_materias.profesor_id', $this->profesorId);
            })
            ->orderBy('niveles.id')
            ->get();
    }

    private function obtenerHorarios(): Collection
    {
        if (!$this->profesorId) {
            return collect();
        }

        return Horario::query()
            ->with([
                'nivel:id,nombre,color,cct',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,grado_id',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,orden',
                'asignacionMateria.materia:id,materia,nivel_id,grado_id,semestre_id,extra,receso,orden',
                'asignacionMateria.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
            ])
            ->whereHas('asignacionMateria', function ($query) {
                $query->where('profesor_id', $this->profesorId);
            })
            ->when($this->nivelId, function ($query) {
                $query->where('nivel_id', $this->nivelId);
            })
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('dia_id')
            ->orderBy('hora_id')
            ->get()
            ->sortBy([
                fn($a, $b) => ($a->nivel->id ?? 0) <=> ($b->nivel->id ?? 0),
                fn($a, $b) => ($a->dia->orden ?? 0) <=> ($b->dia->orden ?? 0),
                fn($a, $b) => ($a->hora->orden ?? 0) <=> ($b->hora->orden ?? 0),
            ])
            ->values();
    }

    private function crearMatriz(Collection $horarios): Collection
    {
        return $horarios
            ->groupBy('nivel_id')
            ->map(function (Collection $items) {
                $nivel = $items->first()->nivel;

                $dias = $items
                    ->pluck('dia')
                    ->filter()
                    ->unique('id')
                    ->sortBy('orden')
                    ->values();

                $horas = $items
                    ->pluck('hora')
                    ->filter()
                    ->unique('id')
                    ->sortBy('orden')
                    ->values();

                $celdas = [];

                foreach ($items as $horario) {
                    $horaId = $horario->hora_id;
                    $diaId = $horario->dia_id;

                    $celdas[$horaId][$diaId][] = $horario;
                }

                return [
                    'nivel' => $nivel,
                    'dias' => $dias,
                    'horas' => $horas,
                    'celdas' => $celdas,
                    'total_clases' => $items->count(),
                    'total_materias' => $items->pluck('asignacion_materia_id')->unique()->count(),
                    'total_grupos' => $items->pluck('grupo_id')->unique()->count(),
                ];
            })
            ->values();
    }

    private function crearEstadisticas(Collection $horarios): array
    {
        return [
            'clases' => $horarios->count(),
            'materias' => $horarios->pluck('asignacion_materia_id')->unique()->count(),
            'niveles' => $horarios->pluck('nivel_id')->unique()->count(),
            'grupos' => $horarios->pluck('grupo_id')->unique()->count(),
        ];
    }
}
