<?php

namespace App\Livewire\Profesor;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Persona;
use App\Models\TallerSesion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ListasProfesores extends Component
{
    public string $buscar_profesor = '';
    public ?int $profesor_id = null;
    public ?int $ciclo_escolar_id = null;
    public string $fecha_corte = '';

    public string $buscar_materia = '';
    public string $filtro_nivel = '';
    public string $filtro_grado = '';
    public string $filtro_grupo = '';
    public string $filtro_generacion = '';
    public string $filtro_semestre = '';
    public string $filtro_dia = '';
    public string $filtro_estado = '';

    /** @var array<string, int|string|null> */
    public array $periodos_por_carga = [];

    public function mount(): void
    {
        $ciclo = CicloEscolar::query()
            ->where('es_actual', true)
            ->first()
            ?? CicloEscolar::query()->orderByDesc('inicio_anio')->first();

        $this->ciclo_escolar_id = $ciclo?->id;
        $this->fecha_corte = $this->fechaSugeridaParaCiclo($ciclo);
    }

    public function esAdministrador(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function updatedCicloEscolarId($value): void
    {
        $cicloActualId = (int) CicloEscolar::query()->where('es_actual', true)->value('id');

        if (!$this->esAdministrador() && (int) $value !== $cicloActualId) {
            $this->ciclo_escolar_id = $cicloActualId ?: null;
            abort(403, 'Solo administración puede consultar listas de ciclos anteriores.');
        }

        $this->profesor_id = null;
        $this->buscar_profesor = '';
        $this->fecha_corte = $this->fechaSugeridaParaCiclo(
            CicloEscolar::query()->find($this->ciclo_escolar_id)
        );
        $this->limpiarFiltrosMaterias();
    }

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
        $this->filtro_estado = '';
        $this->periodos_por_carga = [];
    }

    #[Computed]
    public function ciclosEscolares(): Collection
    {
        $query = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio');

        if (!$this->esAdministrador()) {
            $query->where('es_actual', true);
        }

        return $query->get();
    }

    #[Computed]
    public function cicloSeleccionado(): ?CicloEscolar
    {
        return $this->ciclo_escolar_id
            ? CicloEscolar::query()->find($this->ciclo_escolar_id)
            : null;
    }

    #[Computed]
    public function profesores(): Collection
    {
        $busqueda = trim($this->buscar_profesor);

        if ($busqueda === '' || !$this->ciclo_escolar_id) {
            return collect();
        }

        return Persona::query()
            ->with(['personaRoles.rolePersona:id,nombre,slug,status'])
            ->withCount([
                'asignacionMaterias as cargas_materias_count' => fn ($q) => $q
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA),
                'tallerSesiones as cargas_talleres_count' => fn ($q) => $q
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA),
            ])
            // La carga académica del ciclo es la fuente histórica principal.
            // Así, un profesor asignado a una materia o taller aparece aunque su
            // rol laboral actual haya cambiado, esté incompleto o no sea docente.
            // También se conservan en la búsqueda los docentes activos sin carga.
            ->where(function ($candidato) {
                $candidato
                    ->where(function ($docenteActivo) {
                        $docenteActivo
                            ->where('status', 1)
                            ->whereHas('personaRoles.rolePersona', function ($consulta) {
                                $consulta->where(function ($rol) {
                                    $rol->where('slug', 'like', '%docente%')
                                        ->orWhere('slug', 'like', '%maestro%')
                                        ->orWhere('slug', 'like', '%profesor%')
                                        ->orWhere('slug', 'like', '%tutor%')
                                        ->orWhere('slug', 'director_con_grupo')
                                        ->orWhere('nombre', 'like', '%Docente%')
                                        ->orWhere('nombre', 'like', '%Maestr%')
                                        ->orWhere('nombre', 'like', '%Profesor%')
                                        ->orWhere('nombre', 'like', '%Tutor%');
                                });
                            });
                    })
                    ->orWhereHas('asignacionMaterias', fn ($q) => $q
                        ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                        ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA))
                    ->orWhereHas('tallerSesiones', fn ($q) => $q
                        ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                        ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA));
            })
            ->where(function ($consulta) use ($busqueda) {
                $consulta->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_materno', 'like', "%{$busqueda}%")
                    ->orWhere('curp', 'like', "%{$busqueda}%")
                    ->orWhere('rfc', 'like', "%{$busqueda}%")
                    ->orWhere('correo', 'like', "%{$busqueda}%")
                    ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", ["%{$busqueda}%"])
                    ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", ["%{$busqueda}%"]);
            })
            ->orderByRaw('(cargas_materias_count + cargas_talleres_count) = 0')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(30)
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
    public function cargasProfesor(): Collection
    {
        if (!$this->profesor_id || !$this->ciclo_escolar_id) {
            return collect();
        }

        $materias = AsignacionMateria::query()
            ->with([
                'materia:id,nivel_id,grado_id,semestre_id,materia,clave,slug,calificable,extra,receso,orden',
                'nivel:id,nombre,slug,color,cct,logo,director_id',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'horarios' => fn ($q) => $q
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                    ->with(['dia:id,dia,orden', 'hora:id,hora_inicio,hora_fin,orden'])
                    ->orderBy('dia_id')
                    ->orderBy('hora_id'),
            ])
            ->where('profesor_id', $this->profesor_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
            ->whereHas('materia', fn ($q) => $q->where(function ($sub) {
                $sub->whereNull('receso')->orWhere('receso', false);
            }))
            ->when($this->buscar_materia !== '', function ($q) {
                $buscar = trim($this->buscar_materia);
                $q->whereHas('materia', fn ($materia) => $materia
                    ->where('materia', 'like', "%{$buscar}%")
                    ->orWhere('clave', 'like', "%{$buscar}%"));
            })
            ->when($this->filtro_nivel !== '', fn ($q) => $q->where('nivel_id', $this->filtro_nivel))
            ->when($this->filtro_grado !== '', fn ($q) => $q->where('grado_id', $this->filtro_grado))
            ->when($this->filtro_grupo !== '', fn ($q) => $q->where('grupo_id', $this->filtro_grupo))
            ->when($this->filtro_generacion !== '', fn ($q) => $q->where('generacion_id', $this->filtro_generacion))
            ->when($this->filtro_semestre !== '', fn ($q) => $q->where('semestre_id', $this->filtro_semestre))
            ->when($this->filtro_estado !== '', fn ($q) => $q->where('estado', $this->filtro_estado))
            ->when($this->filtro_dia !== '', fn ($q) => $q->whereHas('horarios', fn ($h) => $h
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('dia_id', $this->filtro_dia)))
            ->get()
            ->map(fn (AsignacionMateria $carga) => $this->mapearMateria($carga));

        $talleres = TallerSesion::query()
            ->with([
                'taller.nivel:id,nombre,slug,color,cct,logo,director_id',
                'profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'dia:id,dia,orden',
                'hora:id,hora_inicio,hora_fin,orden',
                'grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupos.asignacionGrupo:id,nombre',
                'grupos.nivel:id,nombre,slug,color,cct,logo,director_id',
                'grupos.grado:id,nombre,orden',
                'grupos.generacion:id,anio_ingreso,anio_egreso',
                'grupos.semestre:id,grado_id,numero,orden_global',
            ])
            ->where('profesor_id', $this->profesor_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
            ->when($this->buscar_materia !== '', fn ($q) => $q->whereHas('taller', fn ($t) => $t
                ->where('nombre', 'like', '%' . trim($this->buscar_materia) . '%')
                ->orWhere('clave', 'like', '%' . trim($this->buscar_materia) . '%')))
            ->when($this->filtro_dia !== '', fn ($q) => $q->where('dia_id', $this->filtro_dia))
            ->get()
            ->flatMap(function (TallerSesion $sesion) {
                return $sesion->grupos
                    ->filter(function ($grupo) {
                        return ($this->filtro_nivel === '' || (int) $grupo->nivel_id === (int) $this->filtro_nivel)
                            && ($this->filtro_grado === '' || (int) $grupo->grado_id === (int) $this->filtro_grado)
                            && ($this->filtro_grupo === '' || (int) $grupo->id === (int) $this->filtro_grupo)
                            && ($this->filtro_generacion === '' || (int) $grupo->generacion_id === (int) $this->filtro_generacion)
                            && ($this->filtro_semestre === '' || (int) $grupo->semestre_id === (int) $this->filtro_semestre)
                            && ($this->filtro_estado === '' || $sesion->estado === $this->filtro_estado);
                    })
                    ->map(fn ($grupo) => $this->mapearTaller($sesion, $grupo));
            });

        return $materias
            ->concat($talleres)
            ->sortBy(fn (array $item) => $this->claveOrden($item))
            ->values();
    }

    private function mapearMateria(AsignacionMateria $carga): array
    {
        return [
            'clave' => 'm_' . $carga->id,
            'tipo' => 'materia',
            'carga_id' => (int) $carga->id,
            'grupo_id' => (int) $carga->grupo_id,
            'nombre' => $carga->materia?->materia ?? 'Materia',
            'codigo' => $carga->materia?->clave,
            'calificable' => (bool) ($carga->materia?->calificable ?? false),
            'extra' => (bool) ($carga->materia?->extra ?? false),
            'estado' => $carga->estado,
            'nivel' => $carga->nivel ?? $carga->grupo?->nivel,
            'grado' => $carga->grado ?? $carga->grupo?->grado,
            'grupo' => $carga->grupo,
            'generacion' => $carga->generacion ?? $carga->grupo?->generacion,
            'semestre' => $carga->semestre ?? $carga->grupo?->semestre,
            'horarios' => $carga->horarios,
            'total_horarios' => $carga->horarios->count(),
        ];
    }

    private function mapearTaller(TallerSesion $sesion, $grupo): array
    {
        return [
            'clave' => 't_' . $sesion->id . '_' . $grupo->id,
            'tipo' => 'taller',
            'carga_id' => (int) $sesion->id,
            'grupo_id' => (int) $grupo->id,
            'nombre' => $sesion->taller?->nombre ?? 'Talleres',
            'codigo' => $sesion->taller?->clave,
            'calificable' => false,
            'extra' => true,
            'estado' => $sesion->estado,
            'nivel' => $grupo->nivel ?? $sesion->taller?->nivel,
            'grado' => $grupo->grado,
            'grupo' => $grupo,
            'generacion' => $grupo->generacion,
            'semestre' => $grupo->semestre,
            'horarios' => collect([$sesion]),
            'total_horarios' => 1,
        ];
    }

    private function claveOrden(array $item): string
    {
        $nivel = Str::slug((string) ($item['nivel']?->nombre ?? ''));
        $ordenNivel = match ($nivel) {
            'preescolar' => 1,
            'primaria' => 2,
            'secundaria' => 3,
            'bachillerato' => 4,
            default => 99,
        };

        return sprintf(
            '%03d|%03d|%03d|%s|%s',
            $ordenNivel,
            (int) ($item['grado']?->orden ?? 999),
            (int) ($item['semestre']?->orden_global ?? $item['semestre']?->numero ?? 999),
            Str::lower((string) ($item['grupo']?->asignacionGrupo?->nombre ?? '')),
            Str::lower((string) $item['nombre']),
        );
    }

    public function esBachilleratoCarga(array $item): bool
    {
        return (int) ($item['nivel']?->id ?? 0) === 4
            || ($item['nivel']?->slug ?? null) === 'bachillerato';
    }

    public function periodosParaCarga(array $item): Collection
    {
        if (!$this->ciclo_escolar_id) {
            return collect();
        }

        if ($this->esBachilleratoCarga($item)) {
            return DB::table('periodos')
                ->leftJoin('parciales', 'parciales.id', '=', 'periodos.parcial_bachillerato_id')
                ->leftJoin('meses_bachilleratos', 'meses_bachilleratos.id', '=', 'periodos.mes_bachillerato_id')
                ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
                ->where('periodos.ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('periodos.nivel_id', 4)
                ->whereNotNull('periodos.parcial_bachillerato_id')
                ->when($item['generacion'], fn ($q) => $q->where('periodos.generacion_id', $item['generacion']->id))
                ->when($item['semestre'], fn ($q) => $q->where('periodos.semestre_id', $item['semestre']->id))
                ->select('periodos.*', 'parciales.parcial', 'parciales.descripcion', 'meses_bachilleratos.meses', 'ciclo_escolares.inicio_anio', 'ciclo_escolares.fin_anio')
                ->orderBy('parciales.parcial')
                ->orderBy('meses_bachilleratos.id')
                ->get();
        }

        return DB::table('periodos')
            ->leftJoin('periodos_basica', 'periodos_basica.id', '=', 'periodos.periodo_basica_id')
            ->leftJoin('meses_basica', 'meses_basica.id', '=', 'periodos.mes_basica_id')
            ->leftJoin('ciclo_escolares', 'ciclo_escolares.id', '=', 'periodos.ciclo_escolar_id')
            ->where('periodos.ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodos.nivel_id', $item['nivel']?->id)
            ->whereNotNull('periodos.periodo_basica_id')
            ->select('periodos.*', 'periodos_basica.periodo', 'periodos_basica.descripcion', 'meses_basica.meses', 'ciclo_escolares.inicio_anio', 'ciclo_escolares.fin_anio')
            ->orderBy('periodos_basica.periodo')
            ->orderBy('meses_basica.id')
            ->get();
    }

    public function etiquetaPeriodo(object $periodo, array $item): string
    {
        $ciclo = trim(($periodo->inicio_anio ?? '') . '-' . ($periodo->fin_anio ?? ''), '-');

        if ($this->esBachilleratoCarga($item)) {
            return 'Parcial ' . ($periodo->parcial ?? '—')
                . ($periodo->descripcion ? ' · ' . $periodo->descripcion : '')
                . ($periodo->meses ? ' · ' . $periodo->meses : '')
                . ($ciclo ? ' · ' . $ciclo : '');
        }

        return ($periodo->descripcion ?: 'Periodo ' . ($periodo->periodo ?? '—'))
            . ($periodo->meses ? ' · ' . $periodo->meses : '')
            . ($ciclo ? ' · ' . $ciclo : '');
    }

    public function puedeDescargarCarga(array $item, string $tipo = 'asistencia'): bool
    {
        if ($tipo === 'evaluacion' && !$item['calificable']) {
            return false;
        }

        return filled($this->periodos_por_carga[$item['clave']] ?? null);
    }

    public function puedeDescargarTodas(string $tipo = 'asistencia'): bool
    {
        $cargas = $this->cargasProfesor
            ->filter(fn (array $item) => $tipo !== 'evaluacion' || $item['calificable']);

        if (!$this->profesor_id || $cargas->isEmpty()) {
            return false;
        }

        return $cargas->every(fn (array $item) => $this->puedeDescargarCarga($item, $tipo));
    }

    public function urlAsistencia(?string $clave = null): string
    {
        return $this->urlPdf('profesor.listas.asistencia.pdf', $clave, 'asistencia');
    }

    public function urlEvaluacion(?string $clave = null): string
    {
        return $this->urlPdf('profesor.listas.evaluacion.pdf', $clave, 'evaluacion');
    }

    private function urlPdf(string $ruta, ?string $clave, string $tipo): string
    {
        $parametros = [
            'profesor_id' => $this->profesor_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'fecha_corte' => $this->fecha_corte,
        ];

        if ($clave) {
            $item = $this->cargasProfesor->firstWhere('clave', $clave);

            if (!$item) {
                return '#';
            }

            return route($ruta, array_merge($parametros, [
                'tipo_carga' => $item['tipo'],
                'carga_id' => $item['carga_id'],
                'grupo_id' => $item['grupo_id'],
                'periodo_id' => $this->periodos_por_carga[$item['clave']] ?? null,
            ]));
        }

        $cargas = $this->cargasProfesor
            ->filter(fn (array $item) => $tipo !== 'evaluacion' || $item['calificable']);

        return route($ruta, array_merge($parametros, [
            'cargas' => $cargas->map(fn (array $item) => implode(':', [
                $item['tipo'] === 'materia' ? 'm' : 't',
                $item['carga_id'],
                $item['grupo_id'],
            ]))->values()->all(),
            'periodos' => $this->periodos_por_carga,
        ]));
    }

    #[Computed]
    public function nivelesFiltro(): Collection
    {
        return $this->cargasProfesor->pluck('nivel')->filter()->unique('id')->sortBy('nombre')->values();
    }

    #[Computed]
    public function gradosFiltro(): Collection
    {
        return $this->cargasProfesor->pluck('grado')->filter()->unique('id')->sortBy('orden')->values();
    }

    #[Computed]
    public function gruposFiltro(): Collection
    {
        return $this->cargasProfesor->pluck('grupo')->filter()->unique('id')->sortBy(fn ($g) => $g->asignacionGrupo?->nombre)->values();
    }

    #[Computed]
    public function generacionesFiltro(): Collection
    {
        return $this->cargasProfesor->pluck('generacion')->filter()->unique('id')->sortByDesc('anio_ingreso')->values();
    }

    #[Computed]
    public function semestresFiltro(): Collection
    {
        return $this->cargasProfesor->pluck('semestre')->filter()->unique('id')->sortBy('numero')->values();
    }

    #[Computed]
    public function diasFiltro(): Collection
    {
        return $this->cargasProfesor->flatMap(fn (array $item) => $item['horarios']->pluck('dia'))->filter()->unique('id')->sortBy('orden')->values();
    }

    #[Computed]
    public function totalMaterias(): int
    {
        return $this->cargasProfesor->count();
    }

    #[Computed]
    public function totalHoras(): int
    {
        return (int) $this->cargasProfesor->sum('total_horarios');
    }

    public function nombreProfesor($profesor): string
    {
        return trim(($profesor->titulo ? $profesor->titulo . ' ' : '')
            . ($profesor->nombre ?? '') . ' '
            . ($profesor->apellido_paterno ?? '') . ' '
            . ($profesor->apellido_materno ?? ''));
    }

    public function rolPrincipal($profesor): string
    {
        return $profesor->personaRoles
            ->map(fn ($personaRole) => $personaRole->rolePersona?->nombre)
            ->filter()
            ->first() ?? 'Profesor';
    }

    public function totalCargasProfesor($profesor): int
    {
        return (int) ($profesor->cargas_materias_count ?? 0) + (int) ($profesor->cargas_talleres_count ?? 0);
    }

    public function textoHora($hora): string
    {
        if (!$hora) {
            return 'Horario pendiente';
        }

        return substr((string) $hora->hora_inicio, 0, 5) . ' - ' . substr((string) $hora->hora_fin, 0, 5);
    }

    public function textoGeneracion($generacion): string
    {
        return $generacion
            ? $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso
            : 'Sin generación';
    }

    public function textoSemestre($semestre): string
    {
        return $semestre ? $semestre->numero . '° semestre' : 'Sin semestre';
    }

    public function etiquetaEstado(string $estado): string
    {
        return match ($estado) {
            AsignacionMateria::ESTADO_BORRADOR => 'Borrador',
            AsignacionMateria::ESTADO_CERRADA => 'Cerrada',
            AsignacionMateria::ESTADO_ARCHIVADA => 'Archivada',
            default => 'Activa',
        };
    }

    private function fechaSugeridaParaCiclo(?CicloEscolar $ciclo): string
    {
        if (!$ciclo || $ciclo->es_actual) {
            return now()->toDateString();
        }

        return sprintf('%04d-07-31', (int) $ciclo->fin_anio);
    }

    public function render()
    {
        return view('livewire.profesor.listas-profesores');
    }
}
