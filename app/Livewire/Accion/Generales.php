<?php

namespace App\Livewire\Accion;

use App\Exports\EstadisticaGeneralExport;
use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class Generales extends Component
{
    public $nivel;

    public Collection $niveles;
    public Collection $grados;
    public Collection $generaciones;
    public Collection $cicloEscolares;

    public string $slug_nivel = '';
    public string $generacion_id = '';
    public string $ciclo_escolar_id = '';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('id')
            ->get();

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderBy('anio_ingreso', 'desc')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio']);

        // Se selecciona el ciclo escolar más reciente para cargar la estadística al entrar.
        $this->ciclo_escolar_id = (string) ($this->cicloEscolares->first()?->id ?? '');
    }

    public function updatedGeneracionId(): void
    {
        // Livewire actualiza las tablas automáticamente.
    }

    public function updatedCicloEscolarId(): void
    {
        // Livewire actualiza las tablas automáticamente.
    }

    public function limpiarFiltroEstadistica(): void
    {
        $this->generacion_id = '';
        $this->ciclo_escolar_id = (string) ($this->cicloEscolares->first()?->id ?? '');
    }

    public function getEstadisticaInicioCursoProperty(): Collection
    {
        return $this->construirEstadisticaPorCorte('inicio');
    }

    public function getTotalesInicioCursoProperty(): array
    {
        return $this->crearTotalesPorBloques($this->estadisticaInicioCurso, [
            'inicial',
            'altas',
            'inscripcion_total',
            'bajas',
            'existencia',
        ]);
    }

    public function getEstadisticaMedioCursoProperty(): Collection
    {
        return $this->construirEstadisticaPorCorte('medio');
    }

    public function getTotalesMedioCursoProperty(): array
    {
        return $this->crearTotalesPorBloques($this->estadisticaMedioCurso, [
            'inicial',
            'altas',
            'inscripcion_total',
            'bajas',
            'existencia',
        ]);
    }

    public function getEstadisticaFinCursoProperty(): Collection
    {
        return $this->construirEstadisticaPorCorte('fin');
    }

    public function getTotalesFinCursoProperty(): array
    {
        return $this->crearTotalesPorBloques($this->estadisticaFinCurso, [
            'altas',
            'inscripcion_total',
            'bajas',
            'existencia',
            'promovidos',
            'no_promovidos',
        ]);
    }

    // Se mantiene para compatibilidad con partes antiguas del Blade.
    public function getEstadisticaGeneralProperty(): Collection
    {
        return $this->estadisticaMedioCurso;
    }

    // Se mantiene para compatibilidad con partes antiguas del Blade.
    public function getTotalesEstadisticaProperty(): array
    {
        return $this->totalesMedioCurso;
    }

    private function construirEstadisticaPorCorte(string $corte): Collection
    {
        if ($this->ciclo_escolar_id === '') {
            return collect();
        }

        $idInicio = $this->obtenerIdCiclo('inicio', 1);
        $idMedio = $this->obtenerIdCiclo('medio', 2);
        $idFin = $this->obtenerIdCiclo('fin', 3);

        return $this->grados
            ->map(function ($grado) use ($corte, $idInicio, $idMedio, $idFin) {
                $base = $this->consultaBaseTrayectoria($grado);

                $inicioH = $this->obtenerAlumnos($base, 'H', [$idInicio]);
                $inicioM = $this->obtenerAlumnos($base, 'M', [$idInicio]);

                $medioH = $this->obtenerAlumnos($base, 'H', [$idMedio]);
                $medioM = $this->obtenerAlumnos($base, 'M', [$idMedio]);

                $finH = $this->obtenerAlumnos($base, 'H', [$idFin]);
                $finM = $this->obtenerAlumnos($base, 'M', [$idFin]);

                if ($corte === 'inicio') {
                    $altasH = collect();
                    $altasM = collect();

                    $inscripcionTotalH = $inicioH->merge($altasH)->unique('id')->values();
                    $inscripcionTotalM = $inicioM->merge($altasM)->unique('id')->values();

                    return [
                        'grado_id' => $grado->id,
                        'grado' => $grado->nombre,
                        'inicial' => $this->crearGrupoEstadistica($inicioH, $inicioM),
                        'altas' => $this->crearGrupoEstadistica($altasH, $altasM),
                        'inscripcion_total' => $this->crearGrupoEstadistica($inscripcionTotalH, $inscripcionTotalM),
                        'bajas' => $this->crearGrupoEstadistica(
                            $this->filtrarBajas($inscripcionTotalH),
                            $this->filtrarBajas($inscripcionTotalM)
                        ),
                        'existencia' => $this->crearGrupoEstadistica(
                            $this->filtrarActivos($inscripcionTotalH),
                            $this->filtrarActivos($inscripcionTotalM)
                        ),
                    ];
                }

                if ($corte === 'medio') {
                    $altasH = $medioH;
                    $altasM = $medioM;

                    $inscripcionTotalH = $inicioH->merge($altasH)->unique('id')->values();
                    $inscripcionTotalM = $inicioM->merge($altasM)->unique('id')->values();

                    return [
                        'grado_id' => $grado->id,
                        'grado' => $grado->nombre,
                        'inicial' => $this->crearGrupoEstadistica($inicioH, $inicioM),
                        'altas' => $this->crearGrupoEstadistica($altasH, $altasM),
                        'inscripcion_total' => $this->crearGrupoEstadistica($inscripcionTotalH, $inscripcionTotalM),
                        'bajas' => $this->crearGrupoEstadistica(
                            $this->filtrarBajas($inscripcionTotalH),
                            $this->filtrarBajas($inscripcionTotalM)
                        ),
                        'existencia' => $this->crearGrupoEstadistica(
                            $this->filtrarActivos($inscripcionTotalH),
                            $this->filtrarActivos($inscripcionTotalM)
                        ),
                    ];
                }

                $altasH = $finH;
                $altasM = $finM;

                $inscripcionTotalH = $inicioH
                    ->merge($medioH)
                    ->merge($altasH)
                    ->unique('id')
                    ->values();

                $inscripcionTotalM = $inicioM
                    ->merge($medioM)
                    ->merge($altasM)
                    ->unique('id')
                    ->values();

                $existenciaH = $this->filtrarActivos($inscripcionTotalH);
                $existenciaM = $this->filtrarActivos($inscripcionTotalM);

                return [
                    'grado_id' => $grado->id,
                    'grado' => $grado->nombre,
                    'altas' => $this->crearGrupoEstadistica($altasH, $altasM),
                    'inscripcion_total' => $this->crearGrupoEstadistica($inscripcionTotalH, $inscripcionTotalM),
                    'bajas' => $this->crearGrupoEstadistica(
                        $this->filtrarBajas($inscripcionTotalH),
                        $this->filtrarBajas($inscripcionTotalM)
                    ),
                    'existencia' => $this->crearGrupoEstadistica($existenciaH, $existenciaM),
                    'promovidos' => $this->crearGrupoEstadistica(
                        $this->filtrarPromovidos($existenciaH),
                        $this->filtrarPromovidos($existenciaM)
                    ),
                    'no_promovidos' => $this->crearGrupoEstadistica(
                        $this->filtrarNoPromovidos($existenciaH),
                        $this->filtrarNoPromovidos($existenciaM)
                    ),
                ];
            });
    }

    private function consultaBaseTrayectoria(Grado $grado): Builder
    {
        return TrayectoriaAcademica::query()
            ->with('inscripcion:id,matricula,nombre,apellido_paterno,apellido_materno,genero')
            ->where('trayectorias_academicas.ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('trayectorias_academicas.nivel_id', $this->nivel->id)
            ->where('trayectorias_academicas.grado_id', $grado->id)
            ->when($this->generacion_id !== '', function ($consulta) {
                $consulta->where('trayectorias_academicas.generacion_id', $this->generacion_id);
            });
    }

    private function obtenerAlumnos(Builder $base, string $genero, ?array $ciclosIds = null): Collection
    {
        return (clone $base)
            ->join('inscripciones', 'inscripciones.id', '=', 'trayectorias_academicas.inscripcion_id')
            ->where('inscripciones.genero', $genero)
            ->whereNull('inscripciones.deleted_at')
            ->when(!empty($ciclosIds), function ($consulta) use ($ciclosIds) {
                $consulta->whereIn('trayectorias_academicas.ciclo_id', $ciclosIds);
            })
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->select('trayectorias_academicas.*')
            ->get()
            ->map(function ($trayectoria) {
                $alumno = $trayectoria->inscripcion;

                if (!$alumno) {
                    return null;
                }

                // Se toma el estado desde la trayectoria del ciclo escolar consultado.
                $alumno->activo = (bool) $trayectoria->activo;
                $alumno->fecha_baja = $trayectoria->fecha_baja;
                $alumno->ciclo_id = $trayectoria->ciclo_id;
                $alumno->promovido = (bool) ($trayectoria->promovido ?? false);
                $alumno->fecha_promocion = $trayectoria->fecha_promocion ?? null;

                return $alumno;
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    private function filtrarBajas(Collection $alumnos): Collection
    {
        return $alumnos
            ->filter(fn($alumno) => !$alumno->activo || filled($alumno->fecha_baja))
            ->values();
    }

    private function filtrarActivos(Collection $alumnos): Collection
    {
        return $alumnos
            ->filter(fn($alumno) => $alumno->activo && blank($alumno->fecha_baja))
            ->values();
    }

    private function filtrarPromovidos(Collection $alumnos): Collection
    {
        return $alumnos
            ->filter(fn($alumno) => (bool) ($alumno->promovido ?? false))
            ->values();
    }

    private function filtrarNoPromovidos(Collection $alumnos): Collection
    {
        return $alumnos
            ->filter(fn($alumno) => !(bool) ($alumno->promovido ?? false))
            ->values();
    }

    private function crearGrupoEstadistica(Collection $hombres, Collection $mujeres): array
    {
        $todos = $hombres
            ->merge($mujeres)
            ->unique('id')
            ->sortBy([
                ['apellido_paterno', 'asc'],
                ['apellido_materno', 'asc'],
                ['nombre', 'asc'],
            ])
            ->values();

        return [
            'h' => $hombres->count(),
            'm' => $mujeres->count(),
            't' => $todos->count(),
            'nombres_h' => $this->obtenerNombresAlumnos($hombres),
            'nombres_m' => $this->obtenerNombresAlumnos($mujeres),
            'nombres_t' => $this->obtenerNombresAlumnos($todos),
        ];
    }

    private function crearTotalesPorBloques(Collection $filas, array $bloques): array
    {
        $totales = [];

        foreach ($bloques as $bloque) {
            $totales[$bloque] = $this->crearTotalEstadistica($filas, $bloque);
        }

        return $totales;
    }

    private function crearTotalEstadistica(Collection $filas, string $grupo): array
    {
        $nombresH = $filas
            ->flatMap(fn($fila) => $fila[$grupo]['nombres_h'] ?? [])
            ->values()
            ->all();

        $nombresM = $filas
            ->flatMap(fn($fila) => $fila[$grupo]['nombres_m'] ?? [])
            ->values()
            ->all();

        $nombresT = $filas
            ->flatMap(fn($fila) => $fila[$grupo]['nombres_t'] ?? [])
            ->values()
            ->all();

        return [
            'h' => $filas->sum($grupo . '.h'),
            'm' => $filas->sum($grupo . '.m'),
            't' => $filas->sum($grupo . '.t'),
            'nombres_h' => $nombresH,
            'nombres_m' => $nombresM,
            'nombres_t' => $nombresT,
        ];
    }

    private function obtenerNombresAlumnos(Collection $alumnos): array
    {
        return $alumnos
            ->map(function ($alumno) {
                $nombreCompleto = trim(
                    ($alumno->apellido_paterno ?? '') . ' ' .
                        ($alumno->apellido_materno ?? '') . ' ' .
                        ($alumno->nombre ?? '')
                );

                if ($alumno->matricula) {
                    return $nombreCompleto . ' · ' . $alumno->matricula;
                }

                return $nombreCompleto;
            })
            ->filter()
            ->values()
            ->all();
    }

    private function obtenerIdCiclo(string $palabra, int $respaldo): int
    {
        $ciclo = Ciclo::query()
            ->where('ciclo', 'like', '%' . $palabra . '%')
            ->first();

        return $ciclo?->id ?? $respaldo;
    }


    public function exportarEstadisticaExcel(): BinaryFileResponse
    {
        $nombreNivel = str($this->nivel->nombre ?? 'nivel')
            ->lower()
            ->ascii()
            ->replace(' ', '-')
            ->replaceMatches('/[^a-z0-9\-]/', '')
            ->value();

        $nombreCiclo = $this->ciclo_escolar_id !== ''
            ? str($this->textoCicloEscolar((int) $this->ciclo_escolar_id))
            ->replace(' ', '')
            ->replace('-', '_')
            ->value()
            : 'sin_ciclo';

        $archivo = 'estadistica_general_' . $nombreNivel . '_' . $nombreCiclo . '.xlsx';

        return Excel::download(
            new EstadisticaGeneralExport(
                nivelNombre: $this->nivel->nombre ?? 'Sin nivel',
                cicloEscolarTexto: $this->textoCicloEscolar($this->ciclo_escolar_id ? (int) $this->ciclo_escolar_id : null),
                generacionTexto: $this->textoGeneracion($this->generacion_id ? (int) $this->generacion_id : null),
                inicioCurso: $this->estadisticaInicioCurso->toArray(),
                totalesInicioCurso: $this->totalesInicioCurso,
                medioCurso: $this->estadisticaMedioCurso->toArray(),
                totalesMedioCurso: $this->totalesMedioCurso,
                finCurso: $this->estadisticaFinCurso->toArray(),
                totalesFinCurso: $this->totalesFinCurso,
            ),
            $archivo
        );
    }

    public function textoGeneracion(?int $generacionId = null): string
    {
        if (!$generacionId) {
            return 'Todas las generaciones';
        }

        $generacion = $this->generaciones->firstWhere('id', $generacionId);

        if (!$generacion) {
            return 'Generación no encontrada';
        }

        return $generacion->anio_ingreso . ' - ' . $generacion->anio_egreso;
    }

    public function textoCicloEscolar(?int $cicloEscolarId = null): string
    {
        if (!$cicloEscolarId) {
            return 'Sin ciclo escolar seleccionado';
        }

        $cicloEscolar = $this->cicloEscolares->firstWhere('id', $cicloEscolarId);

        if (!$cicloEscolar) {
            return 'Ciclo escolar no encontrado';
        }

        return $cicloEscolar->inicio_anio . ' - ' . $cicloEscolar->fin_anio;
    }

    public function render()
    {
        return view('livewire.accion.generales');
    }
}
