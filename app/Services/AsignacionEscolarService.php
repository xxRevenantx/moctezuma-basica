<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AsignacionEscolarService
{
    public function duracionNivel(Nivel $nivel): int
    {
        if ($nivel->slug === 'bachillerato') {
            return 3;
        }

        return max(
            1,
            Grado::query()->where('nivel_id', $nivel->id)->count()
        );
    }

    public function resolverGeneracion(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        ?Grado $grado = null,
        ?Semestre $semestre = null,
        ?Generacion $generacionOriginal = null,
        string $tipoIngreso = 'nuevo_ingreso',
    ): ?Generacion {
        if ($tipoIngreso === 'no_promovido' && $generacionOriginal) {
            return $generacionOriginal;
        }

        [$anioIngreso, $anioEgreso] = $this->aniosGeneracion(
            $cicloEscolar,
            $nivel,
            $grado,
            $semestre,
        );

        return Generacion::query()
            ->where('nivel_id', $nivel->id)
            ->where('anio_ingreso', $anioIngreso)
            ->where('anio_egreso', $anioEgreso)
            ->where('status', true)
            ->first();
    }

    public function resolverOCrearGeneracion(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        ?Grado $grado = null,
        ?Semestre $semestre = null,
    ): Generacion {
        [$anioIngreso, $anioEgreso] = $this->aniosGeneracion(
            $cicloEscolar,
            $nivel,
            $grado,
            $semestre,
        );

        $cicloInicio = CicloEscolar::query()
            ->where('inicio_anio', $anioIngreso)
            ->first();

        $cicloFin = CicloEscolar::query()
            ->where('fin_anio', $anioEgreso)
            ->first();

        $generacion = Generacion::query()->firstOrCreate(
            [
                'nivel_id' => $nivel->id,
                'anio_ingreso' => $anioIngreso,
                'anio_egreso' => $anioEgreso,
            ],
            [
                'nombre' => $anioIngreso . '-' . $anioEgreso,
                'status' => true,
                'fecha_inicio' => sprintf('%d-08-01', $anioIngreso),
                'fecha_termino' => sprintf('%d-07-31', $anioEgreso),
            ]
        );

        $generacion->forceFill([
            'nombre' => $generacion->nombre ?: $anioIngreso . '-' . $anioEgreso,
            'ciclo_escolar_inicio_id' => $cicloInicio?->id,
            // Si el ciclo de egreso aún no existe, se conserva nulo; nunca se asigna el ciclo actual por aproximación.
            'ciclo_escolar_fin_id' => $cicloFin?->id,
            'fecha_inicio' => $generacion->fecha_inicio ?: sprintf('%d-08-01', $anioIngreso),
            'fecha_termino' => $generacion->fecha_termino ?: sprintf('%d-07-31', $anioEgreso),
        ])->save();

        return $generacion->fresh();
    }

    public function etiquetaGeneracionEsperada(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        ?Grado $grado = null,
        ?Semestre $semestre = null,
    ): string {
        [$anioIngreso, $anioEgreso] = $this->aniosGeneracion(
            $cicloEscolar,
            $nivel,
            $grado,
            $semestre,
        );

        return $anioIngreso . '-' . $anioEgreso;
    }

    public function semestresPermitidos(
        CicloEscolar $cicloEscolar,
        Generacion $generacion,
    ): EloquentCollection {
        $desplazamiento = (int) $cicloEscolar->inicio_anio - (int) $generacion->anio_ingreso;

        if ($desplazamiento < 0 || $desplazamiento > 2) {
            return new EloquentCollection();
        }

        $primero = ($desplazamiento * 2) + 1;

        return Semestre::query()
            ->whereIn('numero', [$primero, $primero + 1])
            ->with('grado:id,nivel_id,nombre,orden')
            ->orderBy('numero')
            ->get();
    }

    public function semestrePropuesto(
        CicloEscolar $cicloEscolar,
        Generacion $generacion,
        ?int $momentoIngresoId,
    ): ?Semestre {
        $semestres = $this->semestresPermitidos($cicloEscolar, $generacion);

        if ($semestres->isEmpty()) {
            return null;
        }

        // Inicio de ciclo = primer semestre del ciclo; medio o fin = segundo.
        return (int) $momentoIngresoId === 1
            ? $semestres->first()
            : ($semestres->get(1) ?: $semestres->first());
    }

    public function gruposCompatibles(
        int $cicloEscolarId,
        int $nivelId,
        int $generacionId,
        ?int $gradoId,
        ?int $semestreId,
    ): Collection {
        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'generacion:id,anio_ingreso,anio_egreso',
                'grado:id,nombre',
                'semestre:id,numero',
            ])
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn ($query) => $query
                    ->where('activo', true)
                    ->whereNull('deleted_at'),
            ])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->where('generacion_id', $generacionId)
            ->where('estado', 'activo')
            ->when(
                $semestreId,
                fn ($query) => $query
                    ->where('semestre_id', $semestreId)
                    ->where('grado_id', $gradoId),
                fn ($query) => $query
                    ->whereNull('semestre_id')
                    ->where('grado_id', $gradoId),
            )
            ->orderBy('asignacion_grupo_id')
            ->orderBy('id')
            ->get()
            ->map(fn (Grupo $grupo): array => [
                'id' => (int) $grupo->id,
                'label' => sprintf(
                    '%s · %d alumnos · cupo ilimitado',
                    $grupo->asignacionGrupo?->nombre ?? 'Sin grupo',
                    (int) $grupo->alumnos_activos_count,
                ),
                'alumnos' => (int) $grupo->alumnos_activos_count,
                'clave' => $grupo->clave,
            ]);
    }

    public function validarAsignacion(array $datos, bool $permitirGeneracionExcepcional = false): Grupo
    {
        $grupo = Grupo::query()
            ->with(['nivel', 'grado', 'generacion', 'semestre', 'cicloEscolar'])
            ->whereKey((int) ($datos['grupo_id'] ?? 0))
            ->where('estado', 'activo')
            ->first();

        if (!$grupo) {
            throw ValidationException::withMessages([
                'grupo_id' => 'El grupo seleccionado no existe, está inactivo o fue eliminado.',
            ]);
        }

        $comparaciones = [
            'ciclo_escolar_id' => 'El grupo no pertenece al ciclo escolar seleccionado.',
            'nivel_id' => 'El grupo no pertenece al nivel seleccionado.',
            'generacion_id' => 'El grupo no pertenece a la generación calculada.',
            'grado_id' => 'El grupo no pertenece al grado seleccionado.',
        ];

        foreach ($comparaciones as $campo => $mensaje) {
            if ((int) $grupo->{$campo} !== (int) ($datos[$campo] ?? 0)) {
                throw ValidationException::withMessages([$campo => $mensaje]);
            }
        }

        $semestreEsperado = !empty($datos['semestre_id'])
            ? (int) $datos['semestre_id']
            : null;

        if (($grupo->semestre_id ? (int) $grupo->semestre_id : null) !== $semestreEsperado) {
            throw ValidationException::withMessages([
                'semestre_id' => 'El grupo no corresponde al semestre seleccionado.',
            ]);
        }

        if (!$grupo->cicloEscolar || !$grupo->nivel || !$grupo->grado || !$grupo->generacion) {
            throw ValidationException::withMessages([
                'grupo_id' => 'El grupo tiene relaciones académicas incompletas.',
            ]);
        }

        $generacionEsperada = $this->etiquetaGeneracionEsperada(
            $grupo->cicloEscolar,
            $grupo->nivel,
            $grupo->grado,
            $grupo->semestre,
        );
        $generacionReal = $grupo->generacion->anio_ingreso . '-' . $grupo->generacion->anio_egreso;

        if (!$permitirGeneracionExcepcional && $generacionEsperada !== $generacionReal) {
            throw ValidationException::withMessages([
                'generacion_id' => "El grupo tiene la generación {$generacionReal}, pero para ese ciclo y grado corresponde {$generacionEsperada}.",
            ]);
        }

        return $grupo;
    }

    public function esGeneracionEsperada(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        Generacion $generacion,
        ?Grado $grado = null,
        ?Semestre $semestre = null,
    ): bool {
        return $this->etiquetaGeneracionEsperada(
            $cicloEscolar,
            $nivel,
            $grado,
            $semestre,
        ) === $generacion->anio_ingreso . '-' . $generacion->anio_egreso;
    }

    public function claveGrupo(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        Grado $grado,
        Generacion $generacion,
        int $asignacionGrupoId,
        ?Semestre $semestre = null,
    ): string {
        return implode('-', [
            $cicloEscolar->inicio_anio . '-' . $cicloEscolar->fin_anio,
            strtoupper(substr($nivel->slug ?: 'NIV', 0, 5)),
            'G' . $grado->id,
            $generacion->anio_ingreso . '-' . $generacion->anio_egreso,
            'S' . ($semestre?->numero ?: 0),
            'A' . $asignacionGrupoId,
        ]);
    }

    private function aniosGeneracion(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        ?Grado $grado,
        ?Semestre $semestre,
    ): array {
        if ($nivel->slug === 'bachillerato') {
            if (!$semestre) {
                throw ValidationException::withMessages([
                    'semestre_id' => 'Selecciona un semestre para calcular la generación.',
                ]);
            }

            $desplazamiento = intdiv(max(1, (int) $semestre->numero) - 1, 2);
            $duracion = $this->duracionNivel($nivel);
        } else {
            if (!$grado || (int) $grado->nivel_id !== (int) $nivel->id) {
                throw ValidationException::withMessages([
                    'grado_id' => 'Selecciona un grado válido del nivel indicado.',
                ]);
            }

            $gradosNivel = Grado::query()
                ->where('nivel_id', $nivel->id)
                ->orderBy('orden')
                ->orderBy('id')
                ->pluck('id')
                ->values();

            $indice = $gradosNivel->search(fn ($id) => (int) $id === (int) $grado->id);

            if ($indice === false) {
                throw ValidationException::withMessages([
                    'grado_id' => 'No fue posible determinar la posición del grado seleccionado.',
                ]);
            }

            $desplazamiento = (int) $indice;
            $duracion = $this->duracionNivel($nivel);
        }

        $anioIngreso = (int) $cicloEscolar->inicio_anio - $desplazamiento;

        return [$anioIngreso, $anioIngreso + $duracion];
    }
}
