<?php

namespace App\Livewire\Profesor;

use App\Models\Horario;
use App\Models\Persona;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ListasProfesores extends Component
{
    public string $buscar_profesor = '';

    public ?int $profesor_id = null;

    public string $buscar_materia = '';

    public string $filtro_nivel = '';

    public string $filtro_grado = '';

    public string $filtro_grupo = '';

    public string $filtro_generacion = '';

    public string $filtro_semestre = '';

    public string $filtro_dia = '';

    public string $periodo_id = '';

    public string $parcial_id = '';

    public function updatedBuscarProfesor(): void
    {
        $this->profesor_id = null;
        $this->limpiarFiltrosMaterias();
    }

    public function updatedProfesorId(): void
    {
        $this->limpiarFiltrosMaterias();
    }

    public function seleccionarProfesor(int $profesorId): void
    {
        $this->profesor_id = $profesorId;
        $this->buscar_profesor = '';
        $this->limpiarFiltrosMaterias();
    }

    public function limpiarTodo(): void
    {
        $this->buscar_profesor = '';
        $this->profesor_id = null;
        $this->limpiarFiltrosMaterias();
    }

    public function limpiarFiltrosMaterias(): void
    {
        $this->buscar_materia = '';
        $this->filtro_nivel = '';
        $this->filtro_grado = '';
        $this->filtro_grupo = '';
        $this->filtro_generacion = '';
        $this->filtro_semestre = '';
        $this->filtro_dia = '';
        $this->periodo_id = '';
        $this->parcial_id = '';
    }

    #[Computed]
    public function profesores(): Collection
    {
        $busqueda = trim($this->buscar_profesor);

        if ($busqueda === '') {
            return collect();
        }

        return Persona::query()
            ->with(['personaRoles.rolePersona:id,nombre,slug,status'])
            ->where('status', 1)
            ->whereHas('personaRoles.rolePersona', function ($consulta) {
                $consulta->where(function ($rol) {
                    $rol->where('slug', 'like', '%docente%')
                        ->orWhere('slug', 'like', '%maestro%')
                        ->orWhere('slug', 'like', '%maestroa%')
                        ->orWhere('slug', 'like', '%profesor%')
                        ->orWhere('slug', 'like', '%tutor%')
                        ->orWhere('slug', 'director_con_grupo')
                        ->orWhere('nombre', 'like', '%Docente%')
                        ->orWhere('nombre', 'like', '%Maestro%')
                        ->orWhere('nombre', 'like', '%Maestra%')
                        ->orWhere('nombre', 'like', '%Profesor%')
                        ->orWhere('nombre', 'like', '%Tutor%');
                });
            })
            ->where(function ($consulta) use ($busqueda) {
                $consulta
                    ->where('nombre', 'like', '%' . $busqueda . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $busqueda . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $busqueda . '%')
                    ->orWhere('curp', 'like', '%' . $busqueda . '%')
                    ->orWhere('rfc', 'like', '%' . $busqueda . '%')
                    ->orWhere('correo', 'like', '%' . $busqueda . '%')
                    ->orWhereRaw(
                        "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                        ['%' . $busqueda . '%']
                    )
                    ->orWhereRaw(
                        "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                        ['%' . $busqueda . '%']
                    );
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(20)
            ->get();
    }

    #[Computed]
    public function profesorSeleccionado(): ?Persona
    {
        if (!$this->profesor_id) {
            return null;
        }

        return Persona::query()
            ->with(['personaRoles.rolePersona:id,nombre,slug,status'])
            ->find($this->profesor_id);
    }

    #[Computed]
    public function horariosProfesor(): Collection
    {
        if (!$this->profesor_id) {
            return collect();
        }

        return Horario::query()
            ->with([
                'nivel:id,nombre,slug,color,cct,logo,director_id',
                'nivel.director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'dia:id,dia,orden',
                'hora:id,hora_inicio,hora_fin,orden',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,orden',
                'asignacionMateria.materia:id,nivel_id,grado_id,semestre_id,materia,clave,calificable,extra,receso,orden',
            ])
            ->whereHas('asignacionMateria', function ($consulta) {
                $consulta->where('profesor_id', $this->profesor_id);
            })
            ->when($this->buscar_materia !== '', function ($consulta) {
                $busqueda = trim($this->buscar_materia);

                $consulta->whereHas('asignacionMateria.materia', function ($materia) use ($busqueda) {
                    $materia
                        ->where('materia', 'like', '%' . $busqueda . '%')
                        ->orWhere('clave', 'like', '%' . $busqueda . '%');
                });
            })
            ->when($this->filtro_nivel !== '', fn($q) => $q->where('nivel_id', $this->filtro_nivel))
            ->when($this->filtro_grado !== '', fn($q) => $q->where('grado_id', $this->filtro_grado))
            ->when($this->filtro_grupo !== '', fn($q) => $q->where('grupo_id', $this->filtro_grupo))
            ->when($this->filtro_generacion !== '', fn($q) => $q->where('generacion_id', $this->filtro_generacion))
            ->when($this->filtro_semestre !== '', fn($q) => $q->where('semestre_id', $this->filtro_semestre))
            ->when($this->filtro_dia !== '', fn($q) => $q->where('dia_id', $this->filtro_dia))
            ->join('dias', 'dias.id', '=', 'horarios.dia_id')
            ->join('horas', 'horas.id', '=', 'horarios.hora_id')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'horarios.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->select('horarios.*')
            ->orderBy('materias.orden')
            ->orderBy('dias.orden')
            ->orderBy('horas.orden')
            ->get();
    }

    #[Computed]
    public function materiasAgrupadas(): Collection
    {
        return $this->horariosProfesor
            ->groupBy('asignacion_materia_id')
            ->map(function ($horarios) {
                $primero = $horarios->first();

                return [
                    'asignacion_id' => $primero->asignacion_materia_id,
                    'materia' => $primero->asignacionMateria?->materia,
                    'nivel' => $primero->nivel,
                    'grado' => $primero->grado,
                    'grupo' => $primero->grupo,
                    'generacion' => $primero->generacion,
                    'semestre' => $primero->semestre,
                    'horarios' => $horarios->values(),
                    'total_horarios' => $horarios->count(),
                ];
            })
            ->values();
    }

    #[Computed]
    public function periodos(): Collection
    {
        return DB::table('periodos')
            ->leftJoin('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->leftJoin('meses_basica', 'meses_basica.id', '=', 'periodos.mes_basica_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->select(
                'periodos.id',
                'periodos.nivel_id',
                'periodos.generacion_id',
                'periodos.semestre_id',
                'periodos.periodo_basica_id',
                'periodos.mes_basica_id',
                'periodos.fecha_inicio',
                'periodos.fecha_fin',
                'periodos_basica.periodo',
                'periodos_basica.descripcion',
                'meses_basica.meses',
                'ciclo_escolares.inicio_anio',
                'ciclo_escolares.fin_anio'
            )
            ->whereNotNull('periodos.periodo_basica_id')
            ->orderBy('periodos_basica.periodo')
            ->get();
    }

    #[Computed]
    public function parciales(): Collection
    {
        return DB::table('parciales')
            ->select('id', 'parcial', 'descripcion')
            ->orderBy('parcial')
            ->get();
    }

    #[Computed]
    public function nivelesFiltro(): Collection
    {
        return $this->horariosProfesor->pluck('nivel')->filter()->unique('id')->sortBy('nombre')->values();
    }

    #[Computed]
    public function gradosFiltro(): Collection
    {
        return $this->horariosProfesor->pluck('grado')->filter()->unique('id')->sortBy('orden')->values();
    }

    #[Computed]
    public function gruposFiltro(): Collection
    {
        return $this->horariosProfesor
            ->pluck('grupo')
            ->filter()
            ->unique('id')
            ->sortBy(fn($grupo) => $grupo->asignacionGrupo?->nombre)
            ->values();
    }

    #[Computed]
    public function generacionesFiltro(): Collection
    {
        return $this->horariosProfesor
            ->pluck('generacion')
            ->filter()
            ->unique('id')
            ->sortByDesc('anio_ingreso')
            ->values();
    }

    #[Computed]
    public function semestresFiltro(): Collection
    {
        return $this->horariosProfesor
            ->pluck('semestre')
            ->filter()
            ->unique('id')
            ->sortBy('orden_global')
            ->values();
    }

    #[Computed]
    public function diasFiltro(): Collection
    {
        return $this->horariosProfesor
            ->pluck('dia')
            ->filter()
            ->unique('id')
            ->sortBy('orden')
            ->values();
    }

    #[Computed]
    public function totalMaterias(): int
    {
        return $this->materiasAgrupadas->count();
    }

    #[Computed]
    public function totalHoras(): int
    {
        return $this->horariosProfesor->count();
    }

    public function urlAsistencia(?int $asignacionMateriaId = null): string
    {
        return route('profesor.listas.asistencia.pdf', [
            'profesor_id' => $this->profesor_id,
            'asignacion_materia_id' => $asignacionMateriaId ?: 'todas',
            'periodo_id' => $this->periodo_id,
            'parcial_id' => $this->parcial_id,
        ]);
    }

    public function urlEvaluacion(?int $asignacionMateriaId = null): string
    {
        return route('profesor.listas.evaluacion.pdf', [
            'profesor_id' => $this->profesor_id,
            'asignacion_materia_id' => $asignacionMateriaId ?: 'todas',
            'periodo_id' => $this->periodo_id,
            'parcial_id' => $this->parcial_id,
        ]);
    }

    public function puedeDescargarPdf(): bool
    {
        return filled($this->profesor_id)
            && filled($this->periodo_id)
            && filled($this->parcial_id)
            && $this->materiasAgrupadas->isNotEmpty();
    }

    public function nombreProfesor($profesor): string
    {
        return trim(
            ($profesor->titulo ? $profesor->titulo . ' ' : '') .
                ($profesor->nombre ?? '') . ' ' .
                ($profesor->apellido_paterno ?? '') . ' ' .
                ($profesor->apellido_materno ?? '')
        );
    }

    public function rolPrincipal($profesor): string
    {
        return $profesor->personaRoles
            ->map(fn($personaRole) => $personaRole->rolePersona?->nombre)
            ->filter()
            ->first() ?? 'Profesor';
    }

    public function textoHora($hora): string
    {
        if (!$hora) {
            return 'Sin horario';
        }

        return substr($hora->hora_inicio, 0, 5) . ' - ' . substr($hora->hora_fin, 0, 5);
    }

    public function textoGeneracion($generacion): string
    {
        if (!$generacion) {
            return 'Sin generación';
        }

        return $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso;
    }

    public function textoSemestre($semestre): string
    {
        if (!$semestre) {
            return 'Sin semestre';
        }

        return $semestre->numero . '° semestre';
    }

    public function render()
    {
        return view('livewire.profesor.listas-profesores');
    }
}
