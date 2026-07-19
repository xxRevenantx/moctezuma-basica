<?php

namespace App\Livewire\Profesor;

use App\Models\Horario;
use App\Models\CicloEscolar;
use App\Models\Persona;
use App\Models\AsignacionMateria;
use App\Models\TallerSesion;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class HorarioProfesor extends Component
{
    public ?int $profesorId = null;
    public ?int $nivelId = null;
    public ?int $materiaId = null;
    public ?int $gradoId = null;
    public ?int $grupoId = null;
    public ?int $cicloEscolarId = null;

    public string $diaKey = '';
    public string $busqueda = '';

    public function mount(): void
    {
        $cicloPredeterminado = CicloEscolar::query()
            ->where('es_actual', true)
            ->value('id') ?: CicloEscolar::query()->orderByDesc('id')->value('id');

        $cicloSolicitado = request()->integer('ciclo_escolar_id');

        $this->cicloEscolarId = $cicloSolicitado > 0
            && CicloEscolar::query()->whereKey($cicloSolicitado)->exists()
            ? $cicloSolicitado
            : $cicloPredeterminado;

        $profesoresQuery = Persona::query()
            ->select('personas.id')
            ->where(function ($personasQuery) {
                $personasQuery->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('asignacion_materias')
                        ->whereColumn('asignacion_materias.profesor_id', 'personas.id')
                        ->whereNotNull('asignacion_materias.profesor_id')
                        ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);
                })->orWhereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('taller_sesiones')
                        ->whereColumn('taller_sesiones.profesor_id', 'personas.id')
                        ->whereNotNull('taller_sesiones.profesor_id')
                        ->where('taller_sesiones.estado', '!=', TallerSesion::ESTADO_ARCHIVADA);
                });
            })
            ->where('personas.status', true)
            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre');

        $profesorSolicitado = request()->integer('profesor_id');

        $this->profesorId = $profesorSolicitado > 0
            && (clone $profesoresQuery)->whereKey($profesorSolicitado)->exists()
            ? $profesorSolicitado
            : $profesoresQuery->value('personas.id');
    }

    public function updatedProfesorId(): void
    {
        $this->limpiarFiltros(false);
    }

    public function limpiarFiltros(bool $limpiarProfesor = false): void
    {
        if ($limpiarProfesor) {
            $this->profesorId = null;
        }

        $this->nivelId = null;
        $this->materiaId = null;
        $this->gradoId = null;
        $this->grupoId = null;
        $this->diaKey = '';
        $this->busqueda = '';
    }

    public function render()
    {
        $profesores = $this->obtenerProfesores();
        $ciclosEscolares = CicloEscolar::query()->orderByDesc('id')->get();

        $horariosBase = $this->obtenerHorarios(false);
        $horarios = $this->obtenerHorarios(true);

        $catalogos = $this->crearCatalogos($horariosBase);
        $horarioGeneral = $this->crearHorarioGeneral($horarios);
        $estadisticas = $this->crearEstadisticas($horarios);

        $profesorSeleccionado = $profesores->firstWhere('id', $this->profesorId);

        $pdfUrl = $this->profesorId
            ? route('profesor.horario.pdf', [
                'profesor_id' => $this->profesorId,
                'nivel_id' => $this->nivelId,
                'materia_id' => $this->materiaId,
                'grado_id' => $this->gradoId,
                'grupo_id' => $this->grupoId,
                'dia_key' => $this->diaKey,
                'busqueda' => $this->busqueda,
                'ciclo_escolar_id' => $this->cicloEscolarId,
            ])
            : null;

        $todosPdfUrl = route('profesores.horarios.todos.pdf', array_filter([
            'nivel_id' => $this->nivelId,
            'materia_id' => $this->materiaId,
            'grado_id' => $this->gradoId,
            'grupo_id' => $this->grupoId,
            'dia_key' => $this->diaKey,
            'busqueda' => $this->busqueda,
            'ciclo_escolar_id' => $this->cicloEscolarId,
        ], fn($value) => filled($value)));

        return view('livewire.profesor.horario-profesor', [
            'profesores' => $profesores,
            'ciclosEscolares' => $ciclosEscolares,
            'profesorSeleccionado' => $profesorSeleccionado,
            'catalogos' => $catalogos,
            'horarios' => $horarios,
            'horarioGeneral' => $horarioGeneral,
            'estadisticas' => $estadisticas,
            'pdfUrl' => $pdfUrl,
            'todosPdfUrl' => $todosPdfUrl,
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
            ->where(function ($personasQuery) {
                $personasQuery->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('asignacion_materias')
                        ->whereColumn('asignacion_materias.profesor_id', 'personas.id')
                        ->whereNotNull('asignacion_materias.profesor_id')
                        ->where('asignacion_materias.estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);
                })->orWhereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('taller_sesiones')
                        ->whereColumn('taller_sesiones.profesor_id', 'personas.id')
                        ->whereNotNull('taller_sesiones.profesor_id')
                        ->where('taller_sesiones.estado', '!=', TallerSesion::ESTADO_ARCHIVADA);
                });
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

    private function obtenerHorarios(bool $aplicarFiltros): Collection
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
                'tallerSesion.taller:id,nivel_id,nombre,clave',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion.grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'tallerSesion.grupos.grado:id,nombre,orden',
                'tallerSesion.grupos.asignacionGrupo:id,nombre',
            ])
            ->where(function ($query) {
                $query->whereHas('asignacionMateria', function ($subQuery) {
                    $subQuery->where('profesor_id', $this->profesorId)
                        ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);
                })->orWhereHas('tallerSesion', function ($subQuery) {
                    $subQuery->where('profesor_id', $this->profesorId)
                        ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA);
                });
            })
            ->when($this->cicloEscolarId, fn($query) => $query->where('ciclo_escolar_id', $this->cicloEscolarId))
            ->when($aplicarFiltros && $this->nivelId, function ($query) {
                $query->where('nivel_id', $this->nivelId);
            })
            ->when($aplicarFiltros && $this->materiaId, function ($query) {
                $query->whereHas('asignacionMateria', function ($subQuery) {
                    $subQuery->where('materia_id', $this->materiaId);
                });
            })
            ->when($aplicarFiltros && $this->gradoId, function ($query) {
                $query->where('grado_id', $this->gradoId);
            })
            ->when($aplicarFiltros && $this->grupoId, function ($query) {
                $query->where('grupo_id', $this->grupoId);
            })
            ->when($aplicarFiltros && $this->diaKey !== '', function ($query) {
                $query->whereHas('dia', function ($subQuery) {
                    $subQuery->whereRaw('LOWER(dia) = ?', [
                        mb_strtolower(str_replace('-', ' ', $this->diaKey)),
                    ]);
                });
            })
            ->when($aplicarFiltros && trim($this->busqueda) !== '', function ($query) {
                $buscar = trim($this->busqueda);

                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery
                        ->whereHas('asignacionMateria.materia', function ($materiaQuery) use ($buscar) {
                            $materiaQuery->where('materia', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('tallerSesion.taller', function ($tallerQuery) use ($buscar) {
                            $tallerQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('nivel', function ($nivelQuery) use ($buscar) {
                            $nivelQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('grado', function ($gradoQuery) use ($buscar) {
                            $gradoQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('grupo.asignacionGrupo', function ($grupoQuery) use ($buscar) {
                            $grupoQuery->where('nombre', 'like', "%{$buscar}%");
                        });
                });
            })
            ->get()
            ->unique(fn($horario) => $horario->taller_sesion_id
                ? 'taller-' . $horario->taller_sesion_id
                : 'horario-' . $horario->id)
            ->sortBy([
                fn($a, $b) => $this->horaInicio($a) <=> $this->horaInicio($b),
                fn($a, $b) => $this->ordenDia($a->dia?->dia) <=> $this->ordenDia($b->dia?->dia),
                fn($a, $b) => ($a->nivel->id ?? 0) <=> ($b->nivel->id ?? 0),
                fn($a, $b) => ($a->grado->orden ?? 0) <=> ($b->grado->orden ?? 0),
            ])
            ->values();
    }

    private function crearCatalogos(Collection $horarios): array
    {
        return [
            'niveles' => $horarios
                ->pluck('nivel')
                ->filter()
                ->unique('id')
                ->sortBy('id')
                ->values(),

            'materias' => $horarios
                ->pluck('asignacionMateria.materia')
                ->filter()
                ->unique('id')
                ->sortBy('materia')
                ->values(),

            'grados' => $horarios
                ->pluck('grado')
                ->filter()
                ->unique('id')
                ->sortBy('orden')
                ->values(),

            'grupos' => $horarios
                ->pluck('grupo')
                ->merge($horarios->pluck('tallerSesion.grupos')->filter()->flatten(1))
                ->filter()
                ->unique('id')
                ->sortBy(fn($grupo) => ($grupo->grado?->orden ?? 0) . '-' . ($grupo->asignacionGrupo?->nombre ?? ''))
                ->values(),

            'dias' => $horarios
                ->pluck('dia')
                ->filter()
                ->map(function ($dia) {
                    return [
                        'key' => Str::slug($dia->dia),
                        'nombre' => $dia->dia,
                        'orden' => $this->ordenDia($dia->dia),
                    ];
                })
                ->unique('key')
                ->sortBy('orden')
                ->values(),
        ];
    }

    private function crearHorarioGeneral(Collection $horarios): array
    {
        $dias = $horarios
            ->pluck('dia')
            ->filter()
            ->map(function ($dia) {
                return [
                    'key' => Str::slug($dia->dia),
                    'nombre' => $dia->dia,
                    'orden' => $this->ordenDia($dia->dia),
                ];
            })
            ->unique('key')
            ->sortBy('orden')
            ->values();

        $horas = $horarios
            ->pluck('hora')
            ->filter()
            ->map(function ($hora) {
                $inicio = Carbon::parse($hora->hora_inicio)->format('H:i');
                $fin = Carbon::parse($hora->hora_fin)->format('H:i');

                return [
                    'key' => $inicio . '-' . $fin,
                    'inicio' => $inicio,
                    'fin' => $fin,
                    'orden' => $this->minutos($inicio),
                ];
            })
            ->unique('key')
            ->sortBy('orden')
            ->values();

        $celdas = [];

        foreach ($horarios as $horario) {
            if (!$horario->dia || !$horario->hora) {
                continue;
            }

            $diaKey = Str::slug($horario->dia->dia);

            $inicio = Carbon::parse($horario->hora->hora_inicio)->format('H:i');
            $fin = Carbon::parse($horario->hora->hora_fin)->format('H:i');

            $horaKey = $inicio . '-' . $fin;

            $celdas[$horaKey][$diaKey][] = $horario;
        }

        return [
            'dias' => $dias,
            'horas' => $horas,
            'celdas' => $celdas,
        ];
    }

    private function crearEstadisticas(Collection $horarios): array
    {
        $actividades = $horarios
            ->map(fn($horario) => $horario->taller_sesion_id
                ? 'taller-' . $horario->taller_sesion_id
                : 'materia-' . $horario->asignacion_materia_id)
            ->filter()
            ->unique();

        $grupos = $horarios
            ->pluck('grupo_id')
            ->merge($horarios->pluck('tallerSesion.grupos')->filter()->flatten(1)->pluck('id'))
            ->filter()
            ->unique();

        return [
            'clases' => $horarios->count(),
            'materias' => $actividades->count(),
            'niveles' => $horarios->pluck('nivel_id')->unique()->count(),
            'grupos' => $grupos->count(),
        ];
    }

    private function ordenDia(?string $dia): int
    {
        $key = Str::slug($dia ?? '');

        return match ($key) {
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'domingo' => 7,
            default => 99,
        };
    }

    private function horaInicio($horario): int
    {
        if (!$horario->hora?->hora_inicio) {
            return 9999;
        }

        return $this->minutos(Carbon::parse($horario->hora->hora_inicio)->format('H:i'));
    }

    private function minutos(string $hora): int
    {
        [$h, $m] = explode(':', $hora);

        return ((int) $h * 60) + (int) $m;
    }
}
