<?php

namespace App\Services;

use App\Models\AsignacionGrupo;
use App\Models\CicloEscolar;
use App\Models\CicloEscolarNivel;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PreparacionEstructuraCicloService
{
    public function __construct(
        private readonly AsignacionEscolarService $asignacionEscolar,
    ) {}

    public function diagnostico(CicloEscolar $cicloEscolar): Collection
    {
        return Nivel::query()
            ->orderBy('id')
            ->get()
            ->map(fn (Nivel $nivel): array => $this->diagnosticoNivel($cicloEscolar, $nivel));
    }

    public function preparar(CicloEscolar $cicloEscolar, ?int $usuarioId): array
    {
        return DB::transaction(function () use ($cicloEscolar, $usuarioId): array {
            $cicloAnterior = CicloEscolar::query()
                ->where('inicio_anio', (int) $cicloEscolar->inicio_anio - 1)
                ->first();

            $resumen = [
                'generaciones_creadas' => 0,
                'grupos_nuevo_ingreso' => 0,
                'grupos_continuidad' => 0,
                'grupos_no_promovidos' => 0,
                'niveles_listos' => 0,
                'advertencias' => [],
            ];

            foreach (Nivel::query()->orderBy('id')->get() as $nivel) {
                $antesGeneracion = Generacion::query()
                    ->where('nivel_id', $nivel->id)
                    ->where('anio_ingreso', $cicloEscolar->inicio_anio)
                    ->exists();

                $generacionIngreso = $this->generacionNuevoIngreso($cicloEscolar, $nivel);

                if (! $antesGeneracion) {
                    $resumen['generaciones_creadas']++;
                }

                $secciones = $this->seccionesNuevoIngreso($cicloAnterior, $nivel);

                foreach ($secciones as $asignacionGrupoId) {
                    if ($this->crearGrupoNuevoIngreso(
                        $cicloEscolar,
                        $nivel,
                        $generacionIngreso,
                        (int) $asignacionGrupoId,
                    )) {
                        $resumen['grupos_nuevo_ingreso']++;
                    }
                }

                if ($cicloAnterior) {
                    $gruposAnteriores = Grupo::query()
                        ->with(['grado', 'semestre', 'generacion'])
                        ->where('ciclo_escolar_id', $cicloAnterior->id)
                        ->where('nivel_id', $nivel->id)
                        ->where('estado', 'activo')
                        ->get();

                    foreach ($gruposAnteriores as $grupoAnterior) {
                        if ($this->crearGrupoContinuidad($cicloEscolar, $nivel, $grupoAnterior)) {
                            $resumen['grupos_continuidad']++;
                        }
                    }

                    $resumen['grupos_no_promovidos'] += $this->crearGruposNoPromovidos(
                        $cicloEscolar,
                        $cicloAnterior,
                        $nivel,
                    );
                } else {
                    $resumen['advertencias'][] = "{$nivel->nombre}: no existe el ciclo anterior para copiar grupos de continuidad.";
                }

                $diagnostico = $this->diagnosticoNivel($cicloEscolar, $nivel);
                $this->guardarEstado($cicloEscolar, $nivel, $diagnostico, $usuarioId);

                if ($diagnostico['estado'] === 'listo') {
                    $resumen['niveles_listos']++;
                }
            }

            return $resumen;
        });
    }

    public function sincronizarEstados(CicloEscolar $cicloEscolar, ?int $usuarioId = null): Collection
    {
        return $this->diagnostico($cicloEscolar)
            ->each(function (array $diagnostico) use ($cicloEscolar, $usuarioId): void {
                $nivel = Nivel::query()->find($diagnostico['nivel_id']);

                if ($nivel) {
                    $this->guardarEstado($cicloEscolar, $nivel, $diagnostico, $usuarioId);
                }
            });
    }

    private function diagnosticoNivel(CicloEscolar $cicloEscolar, Nivel $nivel): array
    {
        [$gradoIngreso, $semestreIngreso] = $this->ubicacionNuevoIngreso($nivel);
        $etiqueta = $this->asignacionEscolar->etiquetaGeneracionEsperada(
            $cicloEscolar,
            $nivel,
            $gradoIngreso,
            $semestreIngreso,
        );

        [$anioIngreso, $anioEgreso] = array_map('intval', explode('-', $etiqueta));

        $generacion = Generacion::query()
            ->where('nivel_id', $nivel->id)
            ->where('anio_ingreso', $anioIngreso)
            ->where('anio_egreso', $anioEgreso)
            ->first();

        $grupos = Grupo::query()
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('nivel_id', $nivel->id)
            ->where('estado', 'activo')
            ->count();

        $gruposIngreso = 0;

        if ($generacion && $gradoIngreso) {
            $gruposIngreso = Grupo::query()
                ->where('ciclo_escolar_id', $cicloEscolar->id)
                ->where('nivel_id', $nivel->id)
                ->where('generacion_id', $generacion->id)
                ->where('grado_id', $gradoIngreso->id)
                ->where('estado', 'activo')
                ->when(
                    $semestreIngreso,
                    fn (Builder $query) => $query->where('semestre_id', $semestreIngreso->id),
                    fn (Builder $query) => $query->whereNull('semestre_id'),
                )
                ->count();
        }

        $periodos = DB::table('periodos')
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('nivel_id', $nivel->id)
            ->count();

        $cargas = DB::table('asignacion_materias')
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('nivel_id', $nivel->id)
            ->count();

        $horarios = DB::table('horarios')
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('nivel_id', $nivel->id)
            ->count();

        $faltantes = [];

        if (! $generacion) {
            $faltantes[] = "Generación {$etiqueta}";
        }

        if ($gruposIngreso < 1) {
            $faltantes[] = 'Grupo de nuevo ingreso';
        }

        if ($periodos < 1) {
            $faltantes[] = 'Periodos académicos';
        }

        if ($cargas < 1) {
            $faltantes[] = 'Asignaciones de materias';
        }

        if ($horarios < 1) {
            $faltantes[] = 'Horarios';
        }

        $estado = $generacion && $gruposIngreso > 0
            ? 'listo'
            : (($generacion || $grupos > 0) ? 'en_preparacion' : 'pendiente');

        return [
            'nivel_id' => (int) $nivel->id,
            'nivel' => $nivel->nombre,
            'estado' => $estado,
            'generacion_esperada' => $etiqueta,
            'generacion_existe' => (bool) $generacion,
            'grupos' => $grupos,
            'grupos_nuevo_ingreso' => $gruposIngreso,
            'periodos' => $periodos,
            'asignaciones' => $cargas,
            'horarios' => $horarios,
            'faltantes' => $faltantes,
            'inscripcion_habilitada' => $generacion && $gruposIngreso > 0,
        ];
    }

    private function generacionNuevoIngreso(CicloEscolar $cicloEscolar, Nivel $nivel): Generacion
    {
        [$grado, $semestre] = $this->ubicacionNuevoIngreso($nivel);

        return $this->asignacionEscolar->resolverOCrearGeneracion(
            $cicloEscolar,
            $nivel,
            $grado,
            $semestre,
        );
    }

    private function ubicacionNuevoIngreso(Nivel $nivel): array
    {
        if ($nivel->slug === 'bachillerato') {
            $semestre = Semestre::query()
                ->whereHas('grado', fn (Builder $query) => $query->where('nivel_id', $nivel->id))
                ->with('grado')
                ->orderBy('numero')
                ->first();

            return [$semestre?->grado, $semestre];
        }

        return [
            Grado::query()
                ->where('nivel_id', $nivel->id)
                ->orderBy('orden')
                ->orderBy('id')
                ->first(),
            null,
        ];
    }

    private function seccionesNuevoIngreso(?CicloEscolar $cicloAnterior, Nivel $nivel): Collection
    {
        [$grado, $semestre] = $this->ubicacionNuevoIngreso($nivel);

        if ($cicloAnterior && $grado) {
            $ids = Grupo::query()
                ->where('ciclo_escolar_id', $cicloAnterior->id)
                ->where('nivel_id', $nivel->id)
                ->where('grado_id', $grado->id)
                ->where('estado', 'activo')
                ->when(
                    $semestre,
                    fn (Builder $query) => $query->where('semestre_id', $semestre->id),
                    fn (Builder $query) => $query->whereNull('semestre_id'),
                )
                ->pluck('asignacion_grupo_id')
                ->filter()
                ->unique()
                ->values();

            if ($ids->isNotEmpty()) {
                return $ids;
            }
        }

        $primeraSeccion = AsignacionGrupo::query()->orderBy('nombre')->value('id');

        return $primeraSeccion ? collect([(int) $primeraSeccion]) : collect();
    }

    private function crearGrupoNuevoIngreso(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        Generacion $generacion,
        int $asignacionGrupoId,
    ): bool {
        [$grado, $semestre] = $this->ubicacionNuevoIngreso($nivel);

        if (! $grado) {
            return false;
        }

        return $this->crearGrupo(
            $cicloEscolar,
            $nivel,
            $grado,
            $generacion,
            $asignacionGrupoId,
            $semestre,
        );
    }

    private function crearGrupoContinuidad(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        Grupo $grupoAnterior,
    ): bool {
        if (! $grupoAnterior->generacion || ! $grupoAnterior->grado) {
            return false;
        }

        if ($nivel->slug === 'bachillerato') {
            $numeroSiguiente = (int) ($grupoAnterior->semestre?->numero ?? 0) + 1;
            $semestre = Semestre::query()
                ->where('numero', $numeroSiguiente)
                ->whereHas('grado', fn (Builder $query) => $query->where('nivel_id', $nivel->id))
                ->with('grado')
                ->first();

            if (! $semestre || ! $semestre->grado) {
                return false;
            }

            return $this->crearGrupo(
                $cicloEscolar,
                $nivel,
                $semestre->grado,
                $grupoAnterior->generacion,
                (int) $grupoAnterior->asignacion_grupo_id,
                $semestre,
            );
        }

        $gradoSiguiente = Grado::query()
            ->where('nivel_id', $nivel->id)
            ->where('orden', '>', (int) $grupoAnterior->grado->orden)
            ->orderBy('orden')
            ->orderBy('id')
            ->first();

        if (! $gradoSiguiente) {
            return false;
        }

        return $this->crearGrupo(
            $cicloEscolar,
            $nivel,
            $gradoSiguiente,
            $grupoAnterior->generacion,
            (int) $grupoAnterior->asignacion_grupo_id,
            null,
        );
    }

    private function crearGruposNoPromovidos(
        CicloEscolar $cicloEscolar,
        CicloEscolar $cicloAnterior,
        Nivel $nivel,
    ): int {
        $creados = 0;

        $alumnos = Inscripcion::query()
            ->with(['grupo', 'grado', 'generacion', 'semestre'])
            ->where('ciclo_escolar_id', $cicloAnterior->id)
            ->where('nivel_id', $nivel->id)
            ->where('estatus', 'no_promovido')
            ->where('activo', true)
            ->whereNotNull('grupo_id')
            ->get()
            ->unique('grupo_id');

        foreach ($alumnos as $alumno) {
            $grupoAnterior = $alumno->grupo;

            if (! $grupoAnterior || ! $alumno->grado || ! $alumno->generacion) {
                continue;
            }

            if ($this->crearGrupo(
                $cicloEscolar,
                $nivel,
                $alumno->grado,
                $alumno->generacion,
                (int) $grupoAnterior->asignacion_grupo_id,
                $alumno->semestre,
                'Grupo excepcional creado para alumnos no promovidos que conservan su generación original.',
            )) {
                $creados++;
            }
        }

        return $creados;
    }

    private function crearGrupo(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        Grado $grado,
        Generacion $generacion,
        int $asignacionGrupoId,
        ?Semestre $semestre,
        ?string $motivoGeneracionExcepcional = null,
    ): bool {
        $existente = Grupo::withTrashed()
            ->where('ciclo_escolar_id', $cicloEscolar->id)
            ->where('nivel_id', $nivel->id)
            ->where('grado_id', $grado->id)
            ->where('generacion_id', $generacion->id)
            ->where('asignacion_grupo_id', $asignacionGrupoId)
            ->when(
                $semestre,
                fn (Builder $query) => $query->where('semestre_id', $semestre->id),
                fn (Builder $query) => $query->whereNull('semestre_id'),
            )
            ->first();

        if ($existente) {
            $existente->restore();
            $existente->update([
                'estado' => 'activo',
                'motivo_generacion_excepcional' => $motivoGeneracionExcepcional,
                'clave' => $this->asignacionEscolar->claveGrupo(
                    $cicloEscolar,
                    $nivel,
                    $grado,
                    $generacion,
                    $asignacionGrupoId,
                    $semestre,
                ),
            ]);

            return false;
        }

        Grupo::query()->create([
            'ciclo_escolar_id' => $cicloEscolar->id,
            'clave' => $this->asignacionEscolar->claveGrupo(
                $cicloEscolar,
                $nivel,
                $grado,
                $generacion,
                $asignacionGrupoId,
                $semestre,
            ),
            'estado' => 'activo',
            'motivo_generacion_excepcional' => $motivoGeneracionExcepcional,
            'asignacion_grupo_id' => $asignacionGrupoId,
            'nivel_id' => $nivel->id,
            'grado_id' => $grado->id,
            'generacion_id' => $generacion->id,
            'semestre_id' => $semestre?->id,
        ]);

        return true;
    }

    private function guardarEstado(
        CicloEscolar $cicloEscolar,
        Nivel $nivel,
        array $diagnostico,
        ?int $usuarioId,
    ): void {
        CicloEscolarNivel::query()->updateOrCreate(
            [
                'ciclo_escolar_id' => $cicloEscolar->id,
                'nivel_id' => $nivel->id,
            ],
            [
                'estado' => $diagnostico['estado'],
                'preparado_at' => $diagnostico['estado'] === 'listo' ? now() : null,
                'preparado_por' => $diagnostico['estado'] === 'listo' ? $usuarioId : null,
                'diagnostico' => $diagnostico,
                'observaciones' => $diagnostico['faltantes']
                    ? 'Pendiente: ' . implode(', ', $diagnostico['faltantes'])
                    : 'Estructura de inscripción disponible. El cupo de los grupos es ilimitado.',
            ],
        );
    }
}
