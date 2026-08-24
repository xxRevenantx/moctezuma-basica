<?php

namespace App\Livewire\Accion\Concerns;

use App\Exports\CalificacionExport;
use App\Exports\PlantillaCalificacionesImportExport;
use App\Imports\CalificacionesImport;
use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as ModelsCalificacion;
use App\Models\CalificacionEntrega;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use App\Services\GroqCalificacionService;
use App\Services\CalificacionCorreccionService;
use App\Services\CicloNivelGateService;
use App\Services\HistorialCalificacionesGeneracionService;
use App\Services\ListaAcademicaService;
use App\Services\TeacherAcademicScopeService;
use App\Services\CalificacionEntregaService;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use Throwable;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

trait GestionaCapturaCalificaciones
{
    public function cargarPeriodoSeleccionado(): void
    {
        $query = Periodos::query()
            ->with(['cicloEscolar', 'mesesBasica', 'periodoBasica', 'mesesBachillerato', 'parcialBachillerato'])
            ->where('nivel_id', $this->nivel_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id);

        if ($this->contextoBusquedaGlobal && $this->periodoBusquedaGlobalId) {
            $query->whereKey($this->periodoBusquedaGlobalId);
        } elseif ($this->esBachillerato) {
            $query->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->where('parcial_bachillerato_id', $this->parcial_bachillerato_id);
        } else {
            $query->where('periodo_basica_id', $this->periodo_basica_id);
        }

        $periodo = $query->latest('id')->first();

        if (! $periodo) {
            $this->periodo_id = null;
            $this->periodoSeleccionado = null;
            return;
        }

        $this->periodo_id = $periodo->id;
        $this->ciclo_escolar_id = $this->obtenerCicloEscolarId($periodo);

        $this->periodoSeleccionado = [
            'id' => $periodo->id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'ciclo_escolar' => $periodo->cicloEscolar
                ? trim(($periodo->cicloEscolar->inicio_anio ?? '') . ' - ' . ($periodo->cicloEscolar->fin_anio ?? ''))
                : 'Global',
            'ciclo_actual' => (bool) ($periodo->cicloEscolar?->es_actual),
            'ciclo_cerrado' => filled($periodo->cicloEscolar?->cerrado_at)
                || ! (bool) ($periodo->cicloEscolar?->es_actual),
            'periodo' => $this->esBachillerato
                ? ($periodo->mesesBachillerato->meses ?? 'Sin periodo')
                : 'Periodo global',
            'parcial' => $this->esBachillerato
                ? ($periodo->parcialBachillerato->descripcion ?? 'Sin parcial')
                : ($periodo->periodoBasica->descripcion ?? 'Sin periodo'),
            'fecha_inicio' => $periodo->fecha_inicio,
            'fecha_fin' => $periodo->fecha_fin,
        ];
    }

    private function obtenerCicloEscolarId($periodo): ?int
    {
        if (filled($periodo->ciclo_escolar_id)) {
            return (int) $periodo->ciclo_escolar_id;
        }

        return filled($this->ciclo_escolar_id)
            ? (int) $this->ciclo_escolar_id
            : null;
    }

    private function obtenerGrupoIdsParaAlumnos(): array
    {
        /*
         * El historial por ciclo ya conserva el grupo exacto vigente en la
         * fecha del periodo. No se mezclan grupos lógicos de otros semestres,
         * porque eso podría incorporar alumnos fuera del contexto consultado.
         */
        return filled($this->grupo_id)
            ? [(int) $this->grupo_id]
            : [];
    }

    private function cargarInscripciones(): void
    {
        $grupoIds = $this->obtenerGrupoIdsParaAlumnos();

        if (empty($grupoIds) || blank($this->ciclo_escolar_id)) {
            $this->inscripciones = [];
            $this->inscripcionesTabla = [];
            return;
        }

        $fechaInicio = $this->periodoSeleccionado['fecha_inicio']
            ?? now()->toDateString();
        $fechaFin = $this->periodoSeleccionado['fecha_fin']
            ?? $fechaInicio;

        $alumnos = app(ListaAcademicaService::class)->alumnosPorContexto(
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            grupoIds: $grupoIds,
            fechaCorte: $fechaInicio,
            nivelId: (int) $this->nivel_id,
            gradoId: (int) $this->grado_id,
            generacionId: (int) $this->generacion_id,
            semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
            usarHistorialCiclo: true,
            incluirNoActivos: false,
            fechaInicio: $fechaInicio,
            fechaFin: $fechaFin,
            periodoId: (int) $this->periodo_id,
            usarActualComoRespaldo: $this->cicloSeleccionadoEsActual(),
            incluirTodaGeneracionBachillerato: $this->esBachillerato,
        );

        $this->inscripciones = $alumnos
            ->map(function ($inscripcion) {
                return [
                    'inscripcion_id' => (int) $inscripcion->id,
                    'inscripcion_ciclo_id' => $inscripcion->getAttribute('inscripcion_ciclo_id')
                        ? (int) $inscripcion->getAttribute('inscripcion_ciclo_id')
                        : null,
                    'matricula' => $inscripcion->matricula ?? 'SIN MATRÍCULA',
                    'alumno' => trim(
                        ($inscripcion->apellido_paterno ?? '') . ' ' .
                        ($inscripcion->apellido_materno ?? '') . ' ' .
                        ($inscripcion->nombre ?? '')
                    ),
                    'estatus_historico' => $inscripcion->getAttribute('estatus_historico') ?? 'activo',
                    'ubicacion_actual' => data_get($inscripcion->getAttribute('ubicacion_actual'), 'texto'),
                    'ubicacion_actual_distinta' => (bool) $inscripcion->getAttribute('ubicacion_actual_distinta'),
                    'incluido_por_generacion' => (bool) $inscripcion->getAttribute('incluido_por_generacion'),
                    'asignacion_contexto_pendiente' => (bool) $inscripcion->getAttribute('asignacion_contexto_pendiente'),
                    'historial_inferido' => (bool) $inscripcion->getAttribute('historial_inferido'),
                ];
            })
            ->values()
            ->toArray();

        $this->inscripcionesTabla = $this->inscripciones;
    }

    private function cargarMaterias(): void
    {
        if (blank($this->grupo_id) || blank($this->grado_id) || blank($this->nivel_id)) {
            $this->materias = [];
            return;
        }

        $asignaciones = AsignacionMateria::query()
            ->with([
                'profesor:id,nombre,apellido_paterno,apellido_materno',
                'materia:id,nivel_id,grado_id,semestre_id,materia,clave,slug,calificable,extra,receso,participa_en_calificacion_oficial,orden',
            ])
            ->where('grupo_id', $this->grupo_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->when(
                ! auth()->user()?->is_admin && auth()->user()?->rol_sistema === 'profesor',
                function ($query): void {
                    $personaId = (int) (auth()->user()?->persona_id ?? 0);

                    $personaId > 0
                        ? $query->where('profesor_id', $personaId)
                        : $query->whereRaw('1 = 0');
                }
            )
            ->when(
                $this->cicloSeleccionadoEsActual(),
                fn ($query) => $query->operativas()
            )
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('grado_id', $this->grado_id);

                if ($this->esBachillerato) {
                    /*
                     * En bachillerato también se cargan las materias extra para
                     * poder capturar y mostrar su calificación en la boleta.
                     * Nunca se cargan recesos.
                     */
                    $query->where('semestre_id', $this->semestre_id);
                    ReglasMateriaBachillerato::aplicarCapturables($query, '');
                } else {
                    $query->where('calificable', true)
                        ->whereNull('semestre_id');
                }
            })

            // Se respeta el orden de la asignación de materias.
            ->orderByRaw('CASE WHEN asignacion_materias.orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('asignacion_materias.orden')
            ->orderBy('asignacion_materias.id')
            ->get();

        $this->materias = $asignaciones
            ->map(function ($asignacion) {
                $profesor = $asignacion->profesor;

                return [
                    'id' => (int) $asignacion->id,
                    'materia_id' => (int) $asignacion->materia_id,

                    // Se manda el orden al Blade por si se desea mostrar.
                    'orden' => $asignacion->orden,
                    'materia' => $asignacion->materia?->materia ?? 'Materia',
                    'clave' => $asignacion->materia?->clave,
                    'slug' => $asignacion->materia?->slug,
                    'extra' => (bool) ($asignacion->materia?->extra ?? false),
                    'receso' => (bool) ($asignacion->materia?->receso ?? false),
                    'calificable' => (bool) ($asignacion->materia?->calificable ?? false),
                    'participa_en_calificacion_oficial' => (bool) ($asignacion->materia?->participa_en_calificacion_oficial ?? true),

                    'profesor' => $profesor
                        ? trim(
                            ($profesor->nombre ?? '') . ' ' .
                            ($profesor->apellido_paterno ?? '') . ' ' .
                            ($profesor->apellido_materno ?? '')
                        )
                        : 'SIN PROFESOR ASIGNADO',
                ];
            })
            ->values()
            ->toArray();
    }

    private function cargarCalificaciones(): void
    {
        if (
            empty($this->inscripciones) ||
            empty($this->materias) ||
            blank($this->periodo_id) ||
            blank($this->ciclo_escolar_id)
        ) {
            return;
        }

        $inscripcionIds = collect($this->inscripciones)
            ->pluck('inscripcion_id')
            ->values()
            ->all();

        $asignacionMateriaIds = collect($this->materias)
            ->pluck('id')
            ->values()
            ->all();

        $calificacionesGuardadas = ModelsCalificacion::query()
            ->where('periodo_id', $this->periodo_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->when($this->esBachillerato, fn($query) => $query->where('semestre_id', $this->semestre_id))
            ->when(!$this->esBachillerato, fn($query) => $query->whereNull('semestre_id'))
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->whereIn('asignacion_materia_id', $asignacionMateriaIds)
            ->get();

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $materia) {
                $asignacionMateriaId = (int) $materia['id'];

                $calificacion = $calificacionesGuardadas
                    ->where('inscripcion_id', $inscripcionId)
                    ->where('asignacion_materia_id', $asignacionMateriaId)
                    ->first();

                $valor = $calificacion?->calificacion;
                $observacion = $calificacion?->observacion;

                if (
                    $this->esBachillerato
                    && $calificacion
                    && (bool) $calificacion->es_numerica
                    && is_numeric($calificacion->valor_numerico)
                ) {
                    $valor = CalificacionBachillerato::formatearEntero($calificacion->valor_numerico, '');
                }

                $this->calificaciones[$inscripcionId][$asignacionMateriaId] = $valor !== null ? (string) $valor : '';
                $this->calificacionesOriginales[$inscripcionId][$asignacionMateriaId] = $valor !== null ? (string) $valor : '';
                $this->observaciones[$inscripcionId][$asignacionMateriaId] = $observacion !== null ? (string) $observacion : '';
                $this->observacionesOriginales[$inscripcionId][$asignacionMateriaId] = $observacion !== null ? (string) $observacion : '';
            }
        }
    }

    private function clavesEspecialesPermitidas(): array
    {
        return ['AC', 'ED', 'RA', 'NP', 'SD'];
    }

    private function normalizarCalificacion($valor): ?string
    {
        $valor = strtoupper(trim((string) $valor));

        if ($valor === '') {
            return null;
        }

        if ($this->esBachillerato && is_numeric($valor)) {
            $entero = CalificacionBachillerato::truncarParcial($valor);

            return $entero !== null ? (string) $entero : $valor;
        }

        return $valor;
    }

    private function esCalificacionEspecial($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);

        return $valor !== null && in_array($valor, $this->clavesEspecialesPermitidas(), true);
    }

    private function esCalificacionNumerica($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null || !is_numeric($valor)) {
            return false;
        }

        if ($this->esBachillerato) {
            return CalificacionBachillerato::esEnteraValida($valor);
        }

        $numero = (float) $valor;

        return $numero >= 0 && $numero <= 10;
    }

    private function obtenerValorNumerico($valor): ?float
    {
        return $this->esCalificacionNumerica($valor)
            ? (float) $this->normalizarCalificacion($valor)
            : null;
    }

    private function validarCalificacionPermitida($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null) {
            return true;
        }

        return $this->esCalificacionNumerica($valor) || $this->esCalificacionEspecial($valor);
    }

    private function tipoValorCalificacion($valor): string
    {
        if ($this->esCalificacionNumerica($valor)) {
            return 'numerico';
        }

        if ($this->esCalificacionEspecial($valor)) {
            return 'especial';
        }

        return 'vacio';
    }

    private function reglasCalificaciones(): array
    {
        $reglas = [];

        foreach ($this->calificaciones as $inscripcionId => $materiasAlumno) {
            foreach ($materiasAlumno as $asignacionMateriaId => $valor) {
                $reglas["calificaciones.{$inscripcionId}.{$asignacionMateriaId}"] = [
                    'nullable',
                    'string',
                    'max:5',
                    function ($attribute, $value, $fail) {
                        if (!$this->validarCalificacionPermitida($value)) {
                            $fail($this->esBachillerato
                                ? 'En bachillerato usa una calificación de 0 a 10 o una clave válida: AC, ED, RA, NP, SD. Los decimales se truncarán a entero.'
                                : 'Usa una calificación de 0 a 10 o una clave válida: AC, ED, RA, NP, SD.');
                        }
                    },
                ];
            }
        }

        return $reglas;
    }

    private function mensajesCalificaciones(): array
    {
        return [
            'calificaciones.*.*.max' => 'La calificación no debe exceder 5 caracteres.',
        ];
    }

    private function obtenerNumeroMateriasPromediar(): ?int
    {
        if (blank($this->nivel_id) || blank($this->grado_id)) {
            return null;
        }

        if ($this->esBachillerato && blank($this->semestre_id)) {
            return null;
        }

        $query = MateriaPromediar::query()
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('grado_id', (int) $this->grado_id);

        if ($this->esBachillerato) {
            $query->where('semestre_id', (int) $this->semestre_id);
        } else {
            $query->whereNull('semestre_id');
        }

        $numeroConfigurado = (int) ($query->value('numero_materias') ?? 0);

        if ($numeroConfigurado > 0) {
            return $numeroConfigurado;
        }

        /*
         * Respaldo automático:
         * En bachillerato, cuando no existe configuración en materia_promediar,
         * se utiliza el número real de materias calificables del semestre.
         * Los demás niveles conservan su comportamiento previo.
         */
        if ($this->esBachillerato) {
            return $this->obtenerMateriasOrdenadasParaPromedio()->count();
        }

        return null;
    }

    private function obtenerMateriasOrdenadasParaPromedio(): Collection
    {
        /*
         * Se toman todas las materias normales, no extras.
         * No se usa take(), porque si una materia contiene AC, NP, SD
         * o cualquier texto, se ignora al calcular el promedio.
         *
         * Si se limita aquí con take(10), puede dejar fuera materias numéricas
         * y meter materias con AC dentro de las primeras posiciones.
         */
        return collect($this->materias)
            ->filter(function ($materia): bool {
                if (empty($materia['calificable'])) {
                    return false;
                }

                /*
                 * Las materias extra y los recesos nunca participan en ningún
                 * promedio de bachillerato: parcial, semestral o final.
                 */
                if (!empty($materia['extra']) || !empty($materia['receso'])) {
                    return false;
                }

                if ($this->esBachillerato) {
                    return ReglasMateriaBachillerato::esPromediable($materia);
                }

                if (in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
                    return (bool) ($materia['participa_en_calificacion_oficial'] ?? true);
                }

                return true;
            })
            ->sortBy([
                fn($materia) => ($materia['orden'] ?? null) === null ? 1 : 0,
                fn($materia) => $materia['orden'] ?? 999,
                fn($materia) => $materia['id'] ?? 999,
            ])
            ->values();
    }

    private function calcularPromedioAlumnoPreciso(
        int $inscripcionId,
        ?int $numeroMateriasPromediar = null,
        ?Collection $materiasOrdenadas = null
    ): ?float {
        $materiasOrdenadas ??= $this->obtenerMateriasOrdenadasParaPromedio();
        $numeroMateriasPromediar ??= $this->obtenerNumeroMateriasPromediar();

        if (
            $inscripcionId <= 0
            || $materiasOrdenadas->isEmpty()
            || !$numeroMateriasPromediar
            || $numeroMateriasPromediar <= 0
        ) {
            return null;
        }

        $suma = 0.0;
        $tieneNumericas = false;

        foreach ($materiasOrdenadas as $materia) {
            $asignacionMateriaId = (int) ($materia['id'] ?? 0);

            if ($asignacionMateriaId <= 0) {
                continue;
            }

            $valor = $this->normalizarCalificacion(
                $this->calificaciones[$inscripcionId][$asignacionMateriaId] ?? null
            );

            if (!$this->esCalificacionNumerica($valor)) {
                continue;
            }

            $suma += (float) $valor;
            $tieneNumericas = true;
        }

        if (!$tieneNumericas) {
            return null;
        }

        /*
         * materia_promediar define el divisor. Si no existe configuración en
         * bachillerato, el divisor es el total de materias calificables que no son extra ni receso.
         */
        return $suma / $numeroMateriasPromediar;
    }

    private function calcularPromedioAlumno(
        int $inscripcionId,
        ?int $numeroMateriasPromediar = null,
        ?Collection $materiasOrdenadas = null
    ): string {
        $promedioPreciso = $this->calcularPromedioAlumnoPreciso(
            inscripcionId: $inscripcionId,
            numeroMateriasPromediar: $numeroMateriasPromediar,
            materiasOrdenadas: $materiasOrdenadas,
        );

        return PromedioExcel::formatear($promedioPreciso, 1, '0.0');
    }

    public function promedioAlumnoTabla(int $inscripcionId): string
    {
        /*
         * Se calcula directo para evitar mostrar promedios viejos en la tabla.
         */
        return $this->calcularPromedioAlumno(
            inscripcionId: $inscripcionId,
            numeroMateriasPromediar: $this->obtenerNumeroMateriasPromediar(),
            materiasOrdenadas: $this->obtenerMateriasOrdenadasParaPromedio()
        );
    }

    public function calcularPromedios(): void
    {
        $this->promedios = [];
        $this->promediosPrecisos = [];

        $materiasOrdenadas = $this->obtenerMateriasOrdenadasParaPromedio();
        $numeroMateriasPromediar = $this->obtenerNumeroMateriasPromediar();

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) ($fila['inscripcion_id'] ?? 0);

            if ($inscripcionId <= 0) {
                continue;
            }

            $promedioPreciso = $this->calcularPromedioAlumnoPreciso(
                inscripcionId: $inscripcionId,
                numeroMateriasPromediar: $numeroMateriasPromediar,
                materiasOrdenadas: $materiasOrdenadas,
            );

            $this->promediosPrecisos[$inscripcionId] = $promedioPreciso;
            $this->promedios[$inscripcionId] = PromedioExcel::formatear($promedioPreciso, 1, '0.0');
        }
    }

    private function aplicarFiltroEstado(): void
    {
        $filas = collect($this->inscripciones);

        /*
         * En bachillerato los filtros académicos se evalúan únicamente con las
         * materias oficiales que participan en el promedio. Las materias extra
         * permanecen capturables y visibles, pero no convierten al alumno en
         * pendiente, aprobado, reprobado o con situación especial.
         */
        $idsMateriasAcademicas = $this->esBachillerato
            ? $this->obtenerMateriasOrdenadasParaPromedio()
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->all()
            : [];

        if (filled($this->busqueda)) {
            $buscar = mb_strtolower(trim($this->busqueda));
            $filas = $filas->filter(function (array $fila) use ($buscar): bool {
                $texto = mb_strtolower(trim(implode(' ', [
                    $fila['matricula'] ?? '',
                    $fila['alumno'] ?? '',
                ])));

                return str_contains($texto, $buscar);
            });
        }

        if ($this->filtro_estatus_historico !== '') {
            $filas = $filas->filter(
                fn ($fila) => ($fila['estatus_historico'] ?? 'activo') === $this->filtro_estatus_historico
            );
        }

        if ($this->filtro_registros !== 'todos') {
            $filas = $filas->filter(function ($fila): bool {
                $inscripcionId = (int) ($fila['inscripcion_id'] ?? 0);
                $tieneCalificaciones = collect($this->calificaciones[$inscripcionId] ?? [])
                    ->contains(fn ($valor) => $this->normalizarCalificacion($valor) !== null);

                return match ($this->filtro_registros) {
                    'con_calificaciones' => $tieneCalificaciones,
                    'sin_calificaciones' => ! $tieneCalificaciones,
                    'incluidos_generacion' => (bool) ($fila['incluido_por_generacion'] ?? false),
                    'contexto_pendiente' => (bool) ($fila['asignacion_contexto_pendiente'] ?? false),
                    default => true,
                };
            });
        }

        if ($this->filtro_estado !== '') {
            $filas = $filas->filter(function ($fila) use ($idsMateriasAcademicas) {
                $inscripcionId = (int) $fila['inscripcion_id'];
                $materiasAlumno = collect($this->calificaciones[$inscripcionId] ?? []);

                if ($this->esBachillerato) {
                    $materiasAlumno = $materiasAlumno->only($idsMateriasAcademicas);
                }

                $valores = $materiasAlumno
                    ->map(fn($valor) => $this->normalizarCalificacion($valor));

                $tieneNumericas = $this->alumnoTieneCalificacionesNumericas($inscripcionId);

                return match ($this->filtro_estado) {
                    'pendientes' => !$tieneNumericas || $valores->contains(fn($valor) => $valor === null || $valor === ''),

                    'aprobados' => $tieneNumericas
                    && $valores
                        ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                        ->every(fn($valor) => (float) $valor >= 6),

                    'reprobados' => $tieneNumericas
                    && $valores->contains(
                        fn($valor) => $this->esCalificacionNumerica($valor) && (float) $valor < 6
                    ),

                    'especiales' => $valores->contains(fn($valor) => $this->esCalificacionEspecial($valor)),

                    'cambios' => $this->tieneCambiosInscripcion($inscripcionId),

                    default => true,
                };
            });
        }

        $filas = $this->ordenarFilasPorPromedio($filas);

        $this->inscripcionesTabla = $filas
            ->values()
            ->toArray();
    }

    private function ordenarFilasPorPromedio(Collection $filas): Collection
    {
        if ($this->orden_promedio === '') {
            return $filas;
        }

        return match ($this->orden_promedio) {
            'mayor_menor' => $filas->sortByDesc(
                fn($fila) => $this->obtenerPromedioOrdenable((int) $fila['inscripcion_id'])
            ),
            'menor_mayor' => $filas->sortBy(
                fn($fila) => $this->obtenerPromedioOrdenable((int) $fila['inscripcion_id'])
            ),
            default => $filas,
        };
    }

    private function obtenerPromedioOrdenable(int $inscripcionId): float
    {
        $promedio = $this->promediosPrecisos[$inscripcionId] ?? null;

        if (!is_numeric($promedio)) {
            return -1;
        }

        return (float) $promedio;
    }

    private function tieneCambiosInscripcion(int $inscripcionId): bool
    {
        foreach (($this->calificaciones[$inscripcionId] ?? []) as $asignacionMateriaId => $valor) {
            $nuevo = $this->normalizarCalificacion($valor);
            $anterior = $this->normalizarCalificacion($this->calificacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? null);

            $observacionNueva = trim((string) ($this->observaciones[$inscripcionId][$asignacionMateriaId] ?? ''));
            $observacionAnterior = trim((string) ($this->observacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? ''));

            if ($nuevo !== $anterior || $observacionNueva !== $observacionAnterior) {
                return true;
            }
        }

        return false;
    }

    public function getHayCambiosProperty(): bool
    {
        foreach ($this->calificaciones as $inscripcionId => $materiasAlumno) {
            if ($this->tieneCambiosInscripcion((int) $inscripcionId)) {
                return true;
            }
        }

        return false;
    }

    public function abrirRevisionGuardado(): void
    {
        $this->resetErrorBag(['calificaciones', 'acepta_conformidad', 'password_confirmacion']);
        $this->acepta_conformidad = false;
        $this->password_confirmacion = '';

        if ($this->hayCambiosEnAlumnosConContextoPendiente() && ! $this->correccionHistoricaHabilitada) {
            $this->addError(
                'calificaciones',
                'Hay alumnos incluidos únicamente por su generación. Administración debe habilitar la corrección e indicar el motivo antes de confirmar su primer contexto académico.'
            );
            return;
        }

        if (!$this->puedeGuardar) {
            $mensaje = $this->esConsultaHistorica && ! $this->correccionHistoricaHabilitada
                ? 'Habilita primero la corrección histórica para editar este ciclo.'
                : 'Selecciona todos los filtros requeridos antes de guardar.';
            $this->addError('calificaciones', $mensaje);
            return;
        }

        if ($this->esConsultaHistorica || $this->hayCambiosEnAlumnosConContextoPendiente()) {
            abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede modificar calificaciones históricas.');
            $this->motivo_guardado = $this->motivoCorreccionCompleto;
        }

        $this->validate($this->reglasCalificaciones(), $this->mensajesCalificaciones());

        $cambios = [];

        foreach ($this->calificaciones as $inscripcionId => $materiasAlumno) {
            foreach ($materiasAlumno as $asignacionMateriaId => $valorNuevo) {
                $valorNuevo = $this->normalizarCalificacion($valorNuevo);
                $valorAnterior = $this->normalizarCalificacion($this->calificacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? null);

                $observacionNueva = trim((string) ($this->observaciones[$inscripcionId][$asignacionMateriaId] ?? ''));
                $observacionAnterior = trim((string) ($this->observacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? ''));

                if ($valorNuevo === $valorAnterior && $observacionNueva === $observacionAnterior) {
                    continue;
                }

                $alumno = collect($this->inscripciones)->firstWhere('inscripcion_id', (int) $inscripcionId);
                $materia = collect($this->materias)->firstWhere('id', (int) $asignacionMateriaId);

                $cambios[] = [
                    'inscripcion_id' => (int) $inscripcionId,
                    'asignacion_materia_id' => (int) $asignacionMateriaId,
                    'matricula' => $alumno['matricula'] ?? '—',
                    'alumno' => $alumno['alumno'] ?? 'Alumno',
                    'materia' => $materia['materia'] ?? 'Materia',
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo,
                    'tipo' => $this->tipoValorCalificacion($valorNuevo),
                    'observacion' => $observacionNueva,
                ];
            }
        }

        $this->resumenRevision = [
            'total' => count($cambios),
            'numericas' => collect($cambios)->where('tipo', 'numerico')->count(),
            'especiales' => collect($cambios)->where('tipo', 'especial')->count(),
            'reprobatorias' => collect($cambios)
                ->filter(fn($item) => is_numeric($item['nuevo'] ?? null) && (float) $item['nuevo'] < 6)
                ->count(),
            'alumnos_afectados' => collect($cambios)->pluck('inscripcion_id')->unique()->count(),
            'materias_afectadas' => collect($cambios)->pluck('asignacion_materia_id')->unique()->count(),
            'cambios' => $cambios,
        ];

        $this->mostrarModalRevision = true;
    }

    public function cerrarRevisionGuardado(): void
    {
        $this->mostrarModalRevision = false;
    }

    public function guardarBorrador(
        CalificacionCorreccionService $correcciones,
        CicloNivelGateService $gate,
        TeacherAcademicScopeService $scope,
    ): void {
        abort_unless($this->esProfesorAutenticado, 403);
        $this->persistirCalificaciones(false, $correcciones, $gate, $scope);
    }

    public function guardarCalificaciones(
        CalificacionCorreccionService $correcciones,
        CicloNivelGateService $gate,
        TeacherAcademicScopeService $scope,
        CalificacionEntregaService $deliveryService,
    ): void {
        $this->persistirCalificaciones(true, $correcciones, $gate, $scope, $deliveryService);
    }

    private function persistirCalificaciones(
        bool $confirmarEntrega,
        CalificacionCorreccionService $correcciones,
        CicloNivelGateService $gate,
        TeacherAcademicScopeService $scope,
        ?CalificacionEntregaService $deliveryService = null,
    ): void {
        $this->resetErrorBag(['calificaciones', 'acepta_conformidad', 'password_confirmacion']);

        if ($this->esProfesorAutenticado) {
            abort_if($this->esConsultaHistorica, 403, 'El profesor solo puede capturar en el ciclo escolar vigente.');
            abort_if($this->entregaConfirmadaActual, 423, 'La entrega ya fue confirmada y está bloqueada.');

            if ($confirmarEntrega) {
                $this->validate([
                    'acepta_conformidad' => ['accepted'],
                    'password_confirmacion' => ['required', 'string'],
                ], [
                    'acepta_conformidad.accepted' => 'Debes aceptar expresamente la declaración de conformidad.',
                    'password_confirmacion.required' => 'Escribe tu contraseña actual para confirmar la entrega.',
                ]);

                if (! Hash::check($this->password_confirmacion, (string) auth()->user()->password)) {
                    $this->addError('password_confirmacion', 'La contraseña actual no es correcta.');
                    return;
                }

                if (! $this->capturaCompleta) {
                    $this->addError(
                        'calificaciones',
                        'Para confirmar la entrega debes capturar todas las calificaciones de los alumnos y materias mostrados.'
                    );
                    return;
                }
            }
        }

        $cambiosEnContextoPendiente = $this->hayCambiosEnAlumnosConContextoPendiente();

        if ($cambiosEnContextoPendiente && ! $this->correccionHistoricaHabilitada) {
            $this->addError(
                'calificaciones',
                'Habilita primero la corrección para confirmar el grupo histórico de los alumnos incluidos por generación.'
            );
            return;
        }

        if (! $this->puedeGuardar) {
            $mensaje = $this->esConsultaHistorica && ! $this->correccionHistoricaHabilitada
                ? 'Habilita primero la corrección histórica para guardar cambios en este ciclo.'
                : 'Selecciona todos los filtros requeridos antes de guardar.';
            $this->addError('calificaciones', $mensaje);
            return;
        }

        if (blank($this->ciclo_escolar_id)) {
            $this->addError('calificaciones', 'No se pudo determinar el ciclo escolar para guardar las calificaciones.');
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);
        $this->validate($this->reglasCalificaciones(), $this->mensajesCalificaciones());

        if (
            $ciclo?->cerrado_at
            || ! (bool) $ciclo?->es_actual
            || ($cambiosEnContextoPendiente && $this->correccionHistoricaHabilitada)
        ) {
            abort_if($this->esProfesorAutenticado, 403, 'El profesor no puede modificar ciclos históricos o cerrados.');
            $this->aplicarCorreccionesHistoricas($correcciones);
            return;
        }

        $gate->asegurar((int) $this->ciclo_escolar_id, (int) $this->nivel_id, 'calificaciones');

        $authorized = [
            'assignment_ids' => collect($this->materias)->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'student_ids' => collect($this->inscripciones)->pluck('inscripcion_id')->map(fn ($id) => (int) $id)->all(),
        ];

        DB::transaction(function () use ($scope, &$authorized): void {
            if ($this->esProfesorAutenticado) {
                $alreadyConfirmed = CalificacionEntrega::query()
                    ->where('user_id', auth()->id())
                    ->where('periodo_id', (int) $this->periodo_id)
                    ->where('grupo_id', (int) $this->grupo_id)
                    ->where('estado', 'confirmada')
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyConfirmed) {
                    throw ValidationException::withMessages([
                        'calificaciones' => 'La entrega ya fue confirmada y no admite cambios docentes.',
                    ]);
                }

                $authorized = $scope->validateGradePayload(
                    user: auth()->user(),
                    cicloEscolarId: (int) $this->ciclo_escolar_id,
                    nivelId: (int) $this->nivel_id,
                    generacionId: (int) $this->generacion_id,
                    gradoId: (int) $this->grado_id,
                    grupoId: (int) $this->grupo_id,
                    semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
                    periodoId: (int) $this->periodo_id,
                    payloadAssignmentIds: $authorized['assignment_ids'],
                    payloadStudentIds: $authorized['student_ids'],
                    lock: true,
                );
            }

            foreach ($authorized['student_ids'] as $inscripcionId) {
                foreach ($authorized['assignment_ids'] as $asignacionMateriaId) {
                    $valorNuevo = $this->normalizarCalificacion(
                        $this->calificaciones[$inscripcionId][$asignacionMateriaId] ?? null
                    );
                    $valorAnterior = $this->normalizarCalificacion(
                        $this->calificacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? null
                    );

                    $observacionNueva = trim((string) ($this->observaciones[$inscripcionId][$asignacionMateriaId] ?? ''));
                    $observacionAnterior = trim((string) ($this->observacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? ''));

                    if ($valorNuevo === $valorAnterior && $observacionNueva === $observacionAnterior) {
                        continue;
                    }

                    $condiciones = [
                        'periodo_id' => (int) $this->periodo_id,
                        'inscripcion_id' => (int) $inscripcionId,
                        'asignacion_materia_id' => (int) $asignacionMateriaId,
                    ];

                    if ($valorNuevo === null) {
                        $calificacion = ModelsCalificacion::query()->where($condiciones)->lockForUpdate()->first();

                        if ($calificacion) {
                            $calificacion->delete();
                            $this->crearBitacoraCalificacion(
                                accion: 'eliminar',
                                inscripcionId: (int) $inscripcionId,
                                asignacionMateriaId: (int) $asignacionMateriaId,
                                anterior: $valorAnterior,
                                nuevo: null,
                                observacion: $observacionNueva
                            );
                        }

                        continue;
                    }

                    $existe = ModelsCalificacion::query()->where($condiciones)->lockForUpdate()->exists();
                    $accion = $existe ? 'editar' : 'crear';

                    ModelsCalificacion::query()->updateOrCreate(
                        $condiciones,
                        [
                            'inscripcion_ciclo_id' => $this->inscripcionCicloIdPara((int) $inscripcionId, true),
                            'nivel_id' => (int) $this->nivel_id,
                            'grado_id' => (int) $this->grado_id,
                            'grupo_id' => (int) $this->grupo_id,
                            'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                            'generacion_id' => (int) $this->generacion_id,
                            'semestre_id' => $this->esBachillerato ? (int) $this->semestre_id : null,
                            'calificacion' => $valorNuevo,
                            'valor_numerico' => $this->obtenerValorNumerico($valorNuevo),
                            'es_numerica' => $this->esCalificacionNumerica($valorNuevo),
                            'clave_especial' => $this->esCalificacionEspecial($valorNuevo) ? $valorNuevo : null,
                            'observacion' => $observacionNueva !== '' ? $observacionNueva : null,
                            'capturado_por' => Auth::id(),
                            'fecha_captura' => now(),
                            'ip_captura' => request()->ip(),
                        ]
                    );

                    $this->crearBitacoraCalificacion(
                        accion: $accion,
                        inscripcionId: (int) $inscripcionId,
                        asignacionMateriaId: (int) $asignacionMateriaId,
                        anterior: $valorAnterior,
                        nuevo: $valorNuevo,
                        observacion: $observacionNueva
                    );
                }
            }
        }, 3);

        $delivery = null;
        if ($confirmarEntrega && $this->esProfesorAutenticado) {
            $delivery = $deliveryService?->create(auth()->user(), [
                'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                'nivel_id' => (int) $this->nivel_id,
                'generacion_id' => (int) $this->generacion_id,
                'grado_id' => (int) $this->grado_id,
                'grupo_id' => (int) $this->grupo_id,
                'semestre_id' => $this->esBachillerato ? (int) $this->semestre_id : null,
                'periodo_id' => (int) $this->periodo_id,
                'assignment_ids' => $authorized['assignment_ids'],
                'student_ids' => $authorized['student_ids'],
            ]);
        }

        $this->calificacionesOriginales = $this->calificaciones;
        $this->observacionesOriginales = $this->observaciones;
        $this->mostrarModalRevision = false;
        $this->motivo_guardado = '';
        $this->acepta_conformidad = false;
        $this->password_confirmacion = '';

        $this->calcularPromedios();
        $this->aplicarFiltroEstado();
        $this->dispatch('calificaciones-internas-guardadas');

        if ($delivery) {
            $this->dispatch('abrir-pdf-entrega', url: route('docente.entregas.pdf', $delivery));
        }

        $this->dispatch('swal', [
            'title' => $delivery
                ? 'Entrega confirmada y PDF generado'
                : ($this->esProfesorAutenticado ? 'Borrador guardado correctamente' : '¡Calificaciones guardadas correctamente!'),
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function aplicarCorreccionesHistoricas(CalificacionCorreccionService $service): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede modificar calificaciones históricas.');

        if (! $this->correccionHistoricaHabilitada) {
            $this->addError('calificaciones', 'Habilita primero la corrección histórica para este ciclo.');
            return;
        }

        $motivo = trim($this->motivoCorreccionCompleto);

        if (mb_strlen($motivo) < 10) {
            $this->addError('calificaciones', 'La corrección histórica requiere un motivo y una descripción de al menos 10 caracteres.');
            return;
        }

        $periodo = Periodos::query()->findOrFail((int) $this->periodo_id);
        $aplicadas = 0;

        DB::transaction(function () use ($service, $periodo, $motivo, &$aplicadas): void {
            foreach ($this->calificaciones as $inscripcionId => $materiasAlumno) {
                foreach ($materiasAlumno as $asignacionMateriaId => $valorNuevo) {
                    $valorNuevo = $this->normalizarCalificacion($valorNuevo);
                    $valorAnterior = $this->normalizarCalificacion(
                        $this->calificacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? null
                    );
                    $observacionNueva = trim((string) ($this->observaciones[$inscripcionId][$asignacionMateriaId] ?? ''));
                    $observacionAnterior = trim((string) ($this->observacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? ''));

                    if ($valorNuevo === $valorAnterior && $observacionNueva === $observacionAnterior) {
                        continue;
                    }

                    $alumno = Inscripcion::withTrashed()->findOrFail((int) $inscripcionId);
                    $calificacion = ModelsCalificacion::query()
                        ->where('periodo_id', $periodo->id)
                        ->where('inscripcion_id', $alumno->id)
                        ->where('asignacion_materia_id', (int) $asignacionMateriaId)
                        ->first();

                    $accion = $valorNuevo === null
                        ? 'eliminar'
                        : ($calificacion ? 'actualizar' : 'crear');

                    $propuesto = [
                        'accion' => $accion,
                        'inscripcion_ciclo_id' => $valorNuevo !== null
                            ? $this->inscripcionCicloIdPara((int) $inscripcionId, true)
                            : $this->inscripcionCicloIdPara((int) $inscripcionId),
                        'asignacion_materia_id' => (int) $asignacionMateriaId,
                        'nivel_id' => (int) $this->nivel_id,
                        'grado_id' => (int) $this->grado_id,
                        'grupo_id' => (int) $this->grupo_id,
                        'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                        'generacion_id' => (int) $this->generacion_id,
                        'semestre_id' => $this->esBachillerato ? (int) $this->semestre_id : null,
                        'calificacion' => $valorNuevo,
                        'valor_numerico' => $this->obtenerValorNumerico($valorNuevo),
                        'es_numerica' => $this->esCalificacionNumerica($valorNuevo),
                        'clave_especial' => $this->esCalificacionEspecial($valorNuevo) ? $valorNuevo : null,
                        'observacion' => $observacionNueva !== '' ? $observacionNueva : null,
                        'capturado_por' => Auth::id(),
                        'fecha_captura' => now()->toDateTimeString(),
                        'ip_captura' => request()->ip(),
                    ];

                    $service->aplicarDirecta(
                        alumno: $alumno,
                        periodo: $periodo,
                        calificacion: $calificacion,
                        valorPropuesto: $propuesto,
                        motivo: $motivo,
                        usuarioId: Auth::id(),
                    );

                    $this->motivo_guardado = $motivo;
                    $this->crearBitacoraCalificacion(
                        accion: $accion === 'actualizar' ? 'editar' : $accion,
                        inscripcionId: (int) $inscripcionId,
                        asignacionMateriaId: (int) $asignacionMateriaId,
                        anterior: $valorAnterior,
                        nuevo: $valorNuevo,
                        observacion: $observacionNueva,
                    );

                    $aplicadas++;
                }
            }
        });

        $this->calificacionesOriginales = $this->calificaciones;
        $this->observacionesOriginales = $this->observaciones;
        $this->mostrarModalRevision = false;
        $this->motivo_guardado = $motivo;
        $this->calcularPromedios();
        $this->aplicarFiltroEstado();
        $this->dispatch('calificaciones-internas-guardadas');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Corrección histórica aplicada',
            'text' => "Se aplicaron {$aplicadas} cambio(s). La evidencia anterior, el usuario, la fecha, la IP y el motivo quedaron registrados.",
            'position' => 'top-end',
        ]);
    }

    private function inscripcionCicloIdPara(int $inscripcionId, bool $asegurarContexto = false): ?int
    {
        if ($this->esProfesorAutenticado) {
            $id = (int) InscripcionCiclo::query()
                ->where('inscripcion_id', $inscripcionId)
                ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
                ->where('nivel_id', (int) $this->nivel_id)
                ->where('generacion_id', (int) $this->generacion_id)
                ->where('grado_id', (int) $this->grado_id)
                ->where('grupo_id', (int) $this->grupo_id)
                ->when(
                    $this->esBachillerato,
                    fn ($query) => $query->where('semestre_id', (int) $this->semestre_id),
                    fn ($query) => $query->whereNull('semestre_id')
                )
                ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO)
                ->latest('id')
                ->value('id');
        } else {
            $fila = collect($this->inscripciones)
                ->firstWhere('inscripcion_id', $inscripcionId);

            $id = (int) ($fila['inscripcion_ciclo_id'] ?? 0);
        }

        if (! $asegurarContexto || ! $this->esBachillerato || $id > 0) {
            return $id > 0 ? $id : null;
        }

        $clave = implode(':', [
            $inscripcionId,
            (int) $this->ciclo_escolar_id,
            (int) $this->grado_id,
            (int) $this->grupo_id,
            (int) $this->semestre_id,
        ]);

        if (isset($this->contextosGeneracionConfirmados[$clave])) {
            return $this->contextosGeneracionConfirmados[$clave];
        }

        $motivo = trim($this->motivoCorreccionCompleto);

        if ($motivo === 'Corrección histórica') {
            $motivo = 'Confirmación del contexto académico al capturar la primera calificación del alumno dentro de su generación.';
        }

        $registro = app(HistorialCalificacionesGeneracionService::class)->asegurarContexto(
            inscripcionId: $inscripcionId,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel_id,
            gradoId: (int) $this->grado_id,
            generacionId: (int) $this->generacion_id,
            grupoId: (int) $this->grupo_id,
            semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
            periodoId: filled($this->periodo_id) ? (int) $this->periodo_id : null,
            usuarioId: Auth::id(),
            motivo: $motivo,
        );

        $id = (int) $registro->id;
        $this->contextosGeneracionConfirmados[$clave] = $id;
        $this->actualizarFilaContextoConfirmado($inscripcionId, $id);

        return $id > 0 ? $id : null;
    }

    private function actualizarFilaContextoConfirmado(int $inscripcionId, int $inscripcionCicloId): void
    {
        foreach (['inscripciones', 'inscripcionesTabla'] as $propiedad) {
            foreach ($this->{$propiedad} as $indice => $fila) {
                if ((int) ($fila['inscripcion_id'] ?? 0) !== $inscripcionId) {
                    continue;
                }

                $this->{$propiedad}[$indice]['inscripcion_ciclo_id'] = $inscripcionCicloId;
                $this->{$propiedad}[$indice]['asignacion_contexto_pendiente'] = false;
                $this->{$propiedad}[$indice]['historial_inferido'] = true;
            }
        }
    }

    private function hayCambiosEnAlumnosConContextoPendiente(): bool
    {
        return collect($this->inscripciones)
            ->filter(fn (array $fila) => (bool) ($fila['asignacion_contexto_pendiente'] ?? false))
            ->contains(fn (array $fila) => $this->tieneCambiosInscripcion((int) $fila['inscripcion_id']));
    }

    private function crearBitacoraCalificacion(
        string $accion,
        int $inscripcionId,
        int $asignacionMateriaId,
        mixed $anterior,
        mixed $nuevo,
        ?string $observacion = null
    ): void {
        BitacoraCalificacion::query()->create([
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'periodo_id' => $this->periodo_id,
            'inscripcion_id' => $inscripcionId,
            'inscripcion_ciclo_id' => $this->inscripcionCicloIdPara($inscripcionId),
            'asignacion_materia_id' => $asignacionMateriaId,
            'user_id' => auth()->id(),
            'accion' => $accion,
            'calificacion_anterior' => $anterior,
            'calificacion_nueva' => $nuevo,
            'valor_anterior_numerico' => $this->obtenerValorNumerico($anterior),
            'valor_nuevo_numerico' => $this->obtenerValorNumerico($nuevo),
            'tipo_valor' => $this->tipoValorCalificacion($nuevo),
            'observacion' => filled($observacion) ? $observacion : null,
            'motivo' => filled($this->motivo_guardado) ? $this->motivo_guardado : null,
            'ip' => request()->ip(),
        ]);
    }

}
