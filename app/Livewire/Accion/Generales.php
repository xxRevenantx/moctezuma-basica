<?php

namespace App\Livewire\Accion;

use App\Models\Ciclo;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class Generales extends Component
{
    public $nivel;

    public Collection $niveles;
    public Collection $grados;
    public Collection $generaciones;

    public string $slug_nivel = '';

    public string $generacion_id = '';

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
    }

    public function updatedGeneracionId(): void
    {
        // Livewire actualiza la tabla automáticamente.
    }

    public function limpiarFiltroEstadistica(): void
    {
        $this->generacion_id = '';
    }

    public function getEstadisticaGeneralProperty(): Collection
    {
        $idInicio = $this->obtenerIdCiclo('inicio', 1);
        $idMedio = $this->obtenerIdCiclo('medio', 2);

        return $this->grados
            ->map(function ($grado) use ($idInicio, $idMedio) {
                $base = Inscripcion::query()
                    ->where('nivel_id', $this->nivel->id)
                    ->where('grado_id', $grado->id)
                    ->when($this->generacion_id !== '', function ($consulta) {
                        $consulta->where('generacion_id', $this->generacion_id);
                    });

                $inicialH = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'H',
                    cicloId: $idInicio
                );

                $inicialM = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'M',
                    cicloId: $idInicio
                );

                $altasH = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'H',
                    cicloId: $idMedio
                );

                $altasM = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'M',
                    cicloId: $idMedio
                );

                $bajasH = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'H',
                    soloBajas: true
                );

                $bajasM = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'M',
                    soloBajas: true
                );

                $existenciaH = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'H',
                    soloActivos: true
                );

                $existenciaM = $this->obtenerAlumnos(
                    base: $base,
                    genero: 'M',
                    soloActivos: true
                );

                $inscripcionTotalH = $inicialH
                    ->merge($altasH)
                    ->unique('id')
                    ->values();

                $inscripcionTotalM = $inicialM
                    ->merge($altasM)
                    ->unique('id')
                    ->values();

                return [
                    'grado_id' => $grado->id,
                    'grado' => $grado->nombre,

                    'inicial' => $this->crearGrupoEstadistica($inicialH, $inicialM),

                    'altas' => $this->crearGrupoEstadistica($altasH, $altasM),

                    'inscripcion_total' => $this->crearGrupoEstadistica($inscripcionTotalH, $inscripcionTotalM),

                    'bajas' => $this->crearGrupoEstadistica($bajasH, $bajasM),

                    'existencia_actual' => $this->crearGrupoEstadistica($existenciaH, $existenciaM),
                ];
            });
    }

    public function getTotalesEstadisticaProperty(): array
    {
        $filas = $this->estadisticaGeneral;

        return [
            'inicial' => $this->crearTotalEstadistica($filas, 'inicial'),
            'altas' => $this->crearTotalEstadistica($filas, 'altas'),
            'inscripcion_total' => $this->crearTotalEstadistica($filas, 'inscripcion_total'),
            'bajas' => $this->crearTotalEstadistica($filas, 'bajas'),
            'existencia_actual' => $this->crearTotalEstadistica($filas, 'existencia_actual'),
        ];
    }

    private function obtenerAlumnos(
        Builder $base,
        string $genero,
        ?int $cicloId = null,
        bool $soloBajas = false,
        bool $soloActivos = false
    ): Collection {
        return (clone $base)
            ->select([
                'id',
                'matricula',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'genero',
                'activo',
                'fecha_baja',
                'ciclo_id',
            ])
            ->where('genero', $genero)
            ->when($cicloId, function ($consulta) use ($cicloId) {
                $consulta->where('ciclo_id', $cicloId);
            })
            ->when($soloBajas, function ($consulta) {
                $consulta->where(function ($subconsulta) {
                    $subconsulta->where('activo', false)
                        ->orWhereNotNull('fecha_baja');
                });
            })
            ->when($soloActivos, function ($consulta) {
                $consulta->where('activo', true)
                    ->whereNull('fecha_baja');
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
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

    public function render()
    {
        return view('livewire.accion.generales');
    }
}
