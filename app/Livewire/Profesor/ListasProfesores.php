<?php

namespace App\Livewire\Profesor;

use App\Models\Horario;
use App\Models\Persona;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public array $periodos_por_materia = [];

    public array $parciales_por_materia = [];

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
        $this->periodos_por_materia = [];
        $this->parciales_por_materia = [];
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
            ->when($this->filtro_nivel !== '', fn($q) => $q->where('horarios.nivel_id', $this->filtro_nivel))
            ->when($this->filtro_grado !== '', fn($q) => $q->where('horarios.grado_id', $this->filtro_grado))
            ->when($this->filtro_grupo !== '', fn($q) => $q->where('horarios.grupo_id', $this->filtro_grupo))
            ->when($this->filtro_generacion !== '', fn($q) => $q->where('horarios.generacion_id', $this->filtro_generacion))
            ->when($this->filtro_semestre !== '', fn($q) => $q->where('horarios.semestre_id', $this->filtro_semestre))
            ->when($this->filtro_dia !== '', fn($q) => $q->where('horarios.dia_id', $this->filtro_dia))
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
            ->sortBy(function (array $item) {
                $nivel = Str::slug((string) ($item['nivel']?->nombre ?? ''));

                $ordenNivel = match ($nivel) {
                    'preescolar' => 1,
                    'primaria' => 2,
                    'secundaria' => 3,
                    'bachillerato' => 4,
                    default => 99,
                };

                $ordenGrado = (int) ($item['grado']?->orden ?? 999);
                $ordenSemestre = (int) ($item['semestre']?->orden_global
                    ?? $item['semestre']?->numero
                    ?? 999);
                $grupo = Str::lower((string) ($item['grupo']?->asignacionGrupo?->nombre ?? ''));
                $materia = Str::lower((string) ($item['materia']?->materia ?? ''));

                return sprintf(
                    '%03d|%03d|%03d|%s|%s',
                    $ordenNivel,
                    $ordenGrado,
                    $ordenSemestre,
                    $grupo,
                    $materia,
                );
            })
            ->values();
    }

    public function esBachilleratoMateria(array $item): bool
    {
        $nivel = $item['nivel'] ?? null;

        if (!$nivel) {
            return false;
        }

        return (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';
    }

    public function periodosParaMateria(array $item): Collection
    {
        $nivel = $item['nivel'] ?? null;

        if (!$nivel || $this->esBachilleratoMateria($item)) {
            return collect();
        }

        return DB::table('periodos')
            ->leftJoin('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->leftJoin('meses_basica', 'meses_basica.id', '=', 'periodos.mes_basica_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->where('periodos.nivel_id', $nivel->id)
            ->whereNotNull('periodos.periodo_basica_id')
            ->select(
                'periodos.id',
                'periodos.nivel_id',
                'periodos.ciclo_escolar_id',
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
            ->orderBy('periodos_basica.periodo')
            ->orderBy('meses_basica.id')
            ->get();
    }

    public function parcialesParaMateria(array $item): Collection
    {
        if (!$this->esBachilleratoMateria($item)) {
            return collect();
        }

        $generacion = $item['generacion'] ?? null;
        $semestre = $item['semestre'] ?? null;

        return DB::table('periodos')
            ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
            ->leftJoin('meses_bachilleratos', 'meses_bachilleratos.id', '=', 'periodos.mes_bachillerato_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->where('periodos.nivel_id', 4)
            ->whereNotNull('periodos.parcial_bachillerato_id')
            ->when($generacion, function ($consulta) use ($generacion) {
                $consulta->where('periodos.generacion_id', $generacion->id);
            })
            ->when($semestre, function ($consulta) use ($semestre) {
                $consulta->where('periodos.semestre_id', $semestre->id);
            })
            ->select(
                'periodos.id',
                'periodos.nivel_id',
                'periodos.generacion_id',
                'periodos.semestre_id',
                'periodos.ciclo_escolar_id',
                'periodos.parcial_bachillerato_id',
                'periodos.mes_bachillerato_id',
                'periodos.fecha_inicio',
                'periodos.fecha_fin',
                'parciales.parcial',
                'parciales.descripcion',
                'meses_bachilleratos.meses',
                'ciclo_escolares.inicio_anio',
                'ciclo_escolares.fin_anio'
            )
            ->orderBy('parciales.parcial')
            ->orderBy('meses_bachilleratos.id')
            ->get();
    }

    public function puedeDescargarMateria(array $item): bool
    {
        $asignacionId = (int) $item['asignacion_id'];

        if ($this->esBachilleratoMateria($item)) {
            return filled($this->parciales_por_materia[$asignacionId] ?? null);
        }

        return filled($this->periodos_por_materia[$asignacionId] ?? null);
    }

    public function puedeDescargarTodas(): bool
    {
        if (!$this->profesor_id || $this->materiasAgrupadas->isEmpty()) {
            return false;
        }

        foreach ($this->materiasAgrupadas as $item) {
            if (!$this->puedeDescargarMateria($item)) {
                return false;
            }
        }

        return true;
    }

    public function urlAsistencia(?int $asignacionMateriaId = null): string
    {
        return $this->urlPdf('profesor.listas.asistencia.pdf', $asignacionMateriaId);
    }

    public function urlEvaluacion(?int $asignacionMateriaId = null): string
    {
        return $this->urlPdf('profesor.listas.evaluacion.pdf', $asignacionMateriaId);
    }

    private function urlPdf(string $ruta, ?int $asignacionMateriaId = null): string
    {
        $parametros = [
            'profesor_id' => $this->profesor_id,
            'asignacion_materia_id' => $asignacionMateriaId ?: 'todas',
        ];

        if ($asignacionMateriaId) {
            $item = $this->materiasAgrupadas->firstWhere('asignacion_id', $asignacionMateriaId);

            if ($item && $this->esBachilleratoMateria($item)) {
                $parametros['parcial_id'] = $this->parciales_por_materia[$asignacionMateriaId] ?? null;
            } else {
                $parametros['periodo_id'] = $this->periodos_por_materia[$asignacionMateriaId] ?? null;
            }

            return route($ruta, $parametros);
        }

        return route($ruta, array_merge($parametros, [
            'asignaciones' => $this->materiasAgrupadas
                ->pluck('asignacion_id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all(),
            'periodos' => $this->periodos_por_materia,
            'parciales' => $this->parciales_por_materia,
        ]));
    }

    #[Computed]
    public function nivelesFiltro(): Collection
    {
        return $this->horariosProfesor
            ->pluck('nivel')
            ->filter()
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    #[Computed]
    public function gradosFiltro(): Collection
    {
        return $this->horariosProfesor
            ->pluck('grado')
            ->filter()
            ->unique('id')
            ->sortBy('orden')
            ->values();
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
            ->sortBy('numero')
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
