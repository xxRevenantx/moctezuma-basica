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

trait ProveeAnaliticaCalificaciones
{
    public function claseInputCalificacion(int $inscripcionId, int $asignacionMateriaId): string
    {
        $valor = $this->normalizarCalificacion($this->calificaciones[$inscripcionId][$asignacionMateriaId] ?? null);
        $valorOriginal = $this->normalizarCalificacion($this->calificacionesOriginales[$inscripcionId][$asignacionMateriaId] ?? null);

        $base = 'w-full rounded-xl border px-3 py-2 text-center text-sm font-bold outline-none transition focus:ring-2 dark:bg-neutral-950 dark:text-white';

        if ($valor !== $valorOriginal) {
            return $base . ' border-sky-300 bg-sky-50 text-sky-900 focus:ring-sky-300 dark:border-sky-700 dark:bg-sky-950/30';
        }

        if ($valor === null) {
            return $base . ' border-neutral-200 bg-white text-neutral-900 focus:ring-sky-300 dark:border-neutral-800';
        }

        if ($this->esCalificacionEspecial($valor)) {
            return $base . ' border-violet-300 bg-violet-50 text-violet-900 focus:ring-violet-300 dark:border-violet-800 dark:bg-violet-950/30';
        }

        if ($this->esCalificacionNumerica($valor) && (float) $valor < 6) {
            return $base . ' border-rose-300 bg-rose-50 text-rose-900 focus:ring-rose-300 dark:border-rose-800 dark:bg-rose-950/30';
        }

        return $base . ' border-emerald-300 bg-emerald-50 text-emerald-900 focus:ring-emerald-300 dark:border-emerald-800 dark:bg-emerald-950/30';
    }

    private function materiasParaEstadisticasAcademicas(): Collection
    {
        return $this->esBachillerato
            ? $this->obtenerMateriasOrdenadasParaPromedio()
            : collect($this->materias);
    }

    private function idsMateriasAcademicas(): Collection
    {
        return $this->materiasParaEstadisticasAcademicas()
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values();
    }

    private function materiasExtraBachillerato(): Collection
    {
        if (!$this->esBachillerato) {
            return collect();
        }

        return collect($this->materias)
            ->filter(fn($materia) => ReglasMateriaBachillerato::esExtraInformativa($materia))
            ->values();
    }

    private function contarCeldasCapturadas(Collection $idsMaterias): int
    {
        if ($idsMaterias->isEmpty()) {
            return 0;
        }

        $ids = $idsMaterias->all();

        return collect($this->calificaciones)
            ->sum(function ($materiasAlumno) use ($ids): int {
                return collect($materiasAlumno)
                    ->only($ids)
                    ->filter(fn($valor) => $this->normalizarCalificacion($valor) !== null)
                    ->count();
            });
    }

    public function getTotalCeldasProperty(): int
    {
        return count($this->inscripciones) * $this->idsMateriasAcademicas()->count();
    }

    public function getCeldasCapturadasProperty(): int
    {
        return $this->contarCeldasCapturadas($this->idsMateriasAcademicas());
    }

    public function getPorcentajeCapturaProperty(): int
    {
        if ($this->totalCeldas === 0) {
            return 0;
        }

        return (int) round(($this->celdasCapturadas / $this->totalCeldas) * 100);
    }

    public function getTotalCeldasExtraProperty(): int
    {
        return count($this->inscripciones) * $this->materiasExtraBachillerato()->count();
    }

    public function getCeldasExtraCapturadasProperty(): int
    {
        $ids = $this->materiasExtraBachillerato()
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->filter()
            ->values();

        return $this->contarCeldasCapturadas($ids);
    }

    public function getPorcentajeCapturaExtraProperty(): int
    {
        if ($this->totalCeldasExtra === 0) {
            return 0;
        }

        return (int) round(($this->celdasExtraCapturadas / $this->totalCeldasExtra) * 100);
    }

    public function getPuedeGuardarProperty(): bool
    {
        return $this->edicionCalificacionesHabilitada
            && filled($this->periodo_id)
            && filled($this->ciclo_escolar_id)
            && filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && (!$this->esBachillerato || filled($this->semestre_id))
            && count($this->inscripciones) > 0
            && count($this->materias) > 0;
    }

    public function getPuedeUsarPlantillaImportacionProperty(): bool
    {
        return filled($this->periodo_id)
            && filled($this->ciclo_escolar_id)
            && filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && (! $this->esBachillerato || filled($this->semestre_id))
            && count($this->inscripciones) > 0
            && count($this->materias) > 0;
    }

    public function getPuedeImportarPlantillaProperty(): bool
    {
        if (! $this->puedeUsarPlantillaImportacion) {
            return false;
        }

        if ($this->cicloSeleccionadoEsActual()) {
            if ($this->hayAlumnosConContextoPendiente) {
                return $this->puedeAdministrarCorreccionHistorica
                    && $this->correccionHistoricaHabilitada;
            }

            return $this->edicionCalificacionesHabilitada
                && (bool) auth()->user()?->canAccess('calificaciones.capturar');
        }

        return $this->puedeAdministrarCorreccionHistorica
            && $this->correccionHistoricaHabilitada;
    }

    public function getPuedeExportarPdfProperty(): bool
    {
        return filled($this->slug_nivel)
            && filled($this->periodo_id)
            && filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && (!$this->esBachillerato || filled($this->semestre_id));
    }

    public function getPuedeExportarBoletaProperty(): bool
    {
        return $this->puedeExportarPdf && filled($this->boleta_inscripcion_id);
    }

    public function getPuedeExportarReconocimientoProperty(): bool
    {
        return $this->puedeExportarPdf
            && $this->hayPromediosParaReconocimiento
            && filled($this->reconocimiento_inscripcion_id);
    }

    public function getClaseGuardarProperty(): string
    {
        $base = 'inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 text-sm font-bold shadow-lg transition disabled:cursor-not-allowed disabled:opacity-50';

        if ($this->hayCambios) {
            return $base . ' bg-gradient-to-r from-emerald-500 via-sky-500 to-indigo-600 text-white shadow-sky-500/20 hover:opacity-95';
        }

        return $base . ' bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400';
    }

    public function getClaseEstadoCambiosProperty(): string
    {
        return $this->hayCambios
            ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/40'
            : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/40';
    }

    public function getMensajeCambiosProperty(): string
    {
        return $this->hayCambios
            ? 'Hay cambios sin guardar'
            : 'Sin cambios pendientes';
    }

    public function getMostrarBotonBitacoraProperty(): bool
    {
        return filled($this->periodo_id)
            && filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id);
    }

    public function abrirModalBitacora(): void
    {
        abort_if(
            $this->esProfesorAutenticado,
            403,
            'La bitácora institucional está reservada para administración.'
        );

        $this->mostrarModalBitacora = true;
    }

    public function cerrarModalBitacora(): void
    {
        $this->mostrarModalBitacora = false;
    }

    public function getNombrePeriodoProperty(): string
    {
        return $this->periodoSeleccionado['periodo'] ?? 'Sin periodo';
    }

    public function getEstadoPeriodoProperty(): string
    {
        if (!$this->periodoSeleccionado) {
            return 'Sin periodo';
        }

        if ((bool) ($this->periodoSeleccionado['ciclo_cerrado'] ?? false)) {
            return 'Histórico · ciclo cerrado';
        }

        $inicio = !empty($this->periodoSeleccionado['fecha_inicio'])
            ? Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->startOfDay()
            : null;

        $fin = !empty($this->periodoSeleccionado['fecha_fin'])
            ? Carbon::parse($this->periodoSeleccionado['fecha_fin'])->endOfDay()
            : null;

        if (!$inicio || !$fin) {
            return 'Sin fechas';
        }

        $hoy = Carbon::today();

        if ($hoy->lt($inicio)) {
            return 'Próximo';
        }

        if ($hoy->gt($fin)) {
            return 'Finalizado';
        }

        return 'Activo';
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return 'Sin grupo';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    public function grupoSeleccionado(): ?Grupo
    {
        if (blank($this->grupo_id)) {
            return null;
        }

        return $this->grupos->firstWhere('id', (int) $this->grupo_id)
            ?? Grupo::query()
                ->with('asignacionGrupo:id,nombre')
                ->find($this->grupo_id);
    }

    public function getClaseEstadoPeriodoProperty(): string
    {
        return match ($this->estadoPeriodo) {
            'Histórico · ciclo cerrado' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-200 dark:ring-amber-900/40',
            'Activo' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/40',
            'Próximo' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/40',
            'Finalizado' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/40',
            default => 'bg-neutral-100 text-neutral-600 ring-1 ring-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:ring-neutral-700',
        };
    }

    public function getPorcentajePeriodoProperty(): int
    {
        if (
            !$this->periodoSeleccionado ||
            empty($this->periodoSeleccionado['fecha_inicio']) ||
            empty($this->periodoSeleccionado['fecha_fin'])
        ) {
            return 0;
        }

        $inicio = Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($this->periodoSeleccionado['fecha_fin'])->endOfDay();
        $hoy = Carbon::today();

        if ($hoy->lte($inicio)) {
            return 0;
        }

        if ($hoy->gte($fin)) {
            return 100;
        }

        $totalDias = max(1, $inicio->diffInDays($fin));
        $diasTranscurridos = $inicio->diffInDays($hoy);

        return min(100, max(0, (int) round(($diasTranscurridos / $totalDias) * 100)));
    }

    public function getEstadisticasCalificacionesProperty(): array
    {
        $idsMateriasAcademicas = $this->idsMateriasAcademicas()->all();

        $valores = collect($this->calificaciones)
            ->flatMap(fn($materiasAlumno) => collect($materiasAlumno)->only($idsMateriasAcademicas)->values())
            ->map(fn($valor) => $this->normalizarCalificacion($valor))
            ->values();

        $especiales = $valores
            ->filter(fn($valor) => $this->esCalificacionEspecial($valor))
            ->count();

        $pendientes = max(0, $this->totalCeldas - $this->celdasCapturadas);
        $hayMateriasPromediables = $this->tieneMateriasPromediables();

        $promediosAlumnos = collect($this->promediosPrecisos)
            ->filter(function ($valor, $inscripcionId) use ($hayMateriasPromediables) {
                return $hayMateriasPromediables
                    && is_numeric($valor)
                    && $this->alumnoTieneCalificacionesNumericas((int) $inscripcionId);
            })
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGlobal = PromedioExcel::calcular($promediosAlumnos) ?? 0.0;

        $aprobados = $promediosAlumnos
            ->filter(fn($valor) => $valor >= 6)
            ->count();

        $reprobados = $promediosAlumnos
            ->filter(fn($valor) => $valor < 6)
            ->count();

        return [
            'promedio_global' => PromedioExcel::formatear($promedioGlobal, 1, '0.0'),
            'porcentaje_aprobacion' => $promediosAlumnos->isNotEmpty()
                ? (int) round(($aprobados / $promediosAlumnos->count()) * 100)
                : 0,
            'pendientes' => $pendientes,
            'reprobadas' => $hayMateriasPromediables ? $reprobados : 0,
            'especiales' => $especiales,
            'porcentaje_captura' => $this->porcentajeCaptura,
            'extras_total' => $this->totalCeldasExtra,
            'extras_capturadas' => $this->celdasExtraCapturadas,
            'porcentaje_captura_extra' => $this->porcentajeCapturaExtra,
        ];
    }

    public function getGraficasCalificacionesProperty(): array
    {
        $hayMateriasPromediables = $this->tieneMateriasPromediables();

        $alumnos = collect($this->inscripciones)
            ->map(function ($fila) use ($hayMateriasPromediables) {
                $inscripcionId = (int) ($fila['inscripcion_id'] ?? 0);
                $promedio = $this->promediosPrecisos[$inscripcionId] ?? null;

                if (
                    !$hayMateriasPromediables ||
                    !is_numeric($promedio) ||
                    !$this->alumnoTieneCalificacionesNumericas($inscripcionId)
                ) {
                    return null;
                }

                return [
                    'nombre' => $this->recortarTexto($fila['alumno'] ?? 'Alumno', 24),
                    'promedio' => (float) $promedio,
                ];
            })
            ->filter()
            ->values();

        $materiasOrdenadas = $this->obtenerMateriasOrdenadasParaPromedio();
        $numeroMateriasPromediar = $this->obtenerNumeroMateriasPromediar();

        $materias = $materiasOrdenadas
            ->map(function ($materia) {
                $asignacionMateriaId = (int) ($materia['id'] ?? 0);

                $valores = collect($this->calificaciones)
                    ->map(fn($materiasAlumno) => $materiasAlumno[$asignacionMateriaId] ?? null)
                    ->map(fn($valor) => $this->normalizarCalificacion($valor))
                    ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                    ->map(fn($valor) => (float) $valor)
                    ->values();

                if ($valores->isEmpty()) {
                    return null;
                }

                $promedioMateria = PromedioExcel::calcular($valores);

                return [
                    'materia' => $this->recortarTexto($materia['materia'] ?? 'Materia', 18),
                    'promedio' => PromedioExcel::truncar($promedioMateria) ?? 0.0,
                ];
            })
            ->filter()
            ->when($numeroMateriasPromediar, fn($coleccion) => $coleccion->take((int) $numeroMateriasPromediar))
            ->values();

        $promediosAlumnos = collect($this->promediosPrecisos)
            ->filter(function ($valor, $inscripcionId) use ($hayMateriasPromediables) {
                return $hayMateriasPromediables
                    && is_numeric($valor)
                    && $this->alumnoTieneCalificacionesNumericas((int) $inscripcionId);
            })
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGlobal = PromedioExcel::calcular($promediosAlumnos) ?? 0.0;

        $aprobadas = $promediosAlumnos
            ->filter(fn($valor) => $valor >= 6)
            ->count();

        $reprobadas = $promediosAlumnos
            ->filter(fn($valor) => $valor < 6)
            ->count();

        return [
            'alumnos' => [
                'labels' => $alumnos->pluck('nombre')->toArray(),
                'series' => $alumnos->pluck('promedio')->toArray(),
            ],
            'materias' => [
                'labels' => $materias->pluck('materia')->toArray(),
                'series' => $materias->pluck('promedio')->toArray(),
            ],
            'global' => [
                'promedio' => PromedioExcel::truncar($promedioGlobal) ?? 0.0,
                'porcentaje' => min(100, round(($promedioGlobal / 10) * 100)),
                'total_numericas' => $promediosAlumnos->count(),
                'aprobadas' => $aprobadas,
                'reprobadas' => $reprobadas,
                'porcentaje_aprobacion' => $promediosAlumnos->isEmpty()
                    ? 0
                    : (int) round(($aprobadas / $promediosAlumnos->count()) * 100),
            ],
        ];
    }

    private function recortarTexto(string $texto, int $limite): string
    {
        return mb_strlen($texto) > $limite
            ? mb_substr($texto, 0, $limite) . '...'
            : $texto;
    }

    public function getDiagnosticoCalificacionesProperty(): array
    {
        if (empty($this->inscripciones) || empty($this->materias)) {
            return [
                'hay_datos' => false,
                'titulo' => 'Selecciona los filtros para generar el diagnóstico',
                'descripcion' => 'El diagnóstico académico se mostrará cuando existan alumnos, materias y periodo cargado.',
                'color' => 'slate',
                'salud' => 0,
                'tarjetas' => [],
                'alertas' => collect(),
                'ranking_alumnos' => collect(),
                'alumnos_riesgo' => collect(),
                'alumnos_captura_incompleta' => collect(),
                'candidatos_reconocimiento' => collect(),
                'materias_resumen' => collect(),
                'materia_mas_baja' => null,
                'materia_mas_alta' => null,
                'recomendaciones' => collect(),
            ];
        }

        $totalCeldas = (int) $this->totalCeldas;
        $celdasCapturadas = (int) $this->celdasCapturadas;
        $pendientes = max(0, $totalCeldas - $celdasCapturadas);
        $porcentajeCaptura = (int) $this->porcentajeCaptura;

        $materiasPromediables = ($this->esBachillerato
            ? $this->obtenerMateriasOrdenadasParaPromedio()
            : collect($this->materias)->filter(fn($materia) => empty($materia['extra'])))
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values();

        $alumnosResumen = collect($this->inscripciones)
            ->map(function ($fila) use ($materiasPromediables) {
                $inscripcionId = (int) $fila['inscripcion_id'];
                $materiasAlumno = collect($this->calificaciones[$inscripcionId] ?? []);

                $valores = $materiasAlumno
                    ->map(fn($valor, $asignacionMateriaId) => [
                        'asignacion_materia_id' => (int) $asignacionMateriaId,
                        'valor' => $this->normalizarCalificacion($valor),
                    ])
                    ->values();

                $valoresPromediables = $valores
                    ->filter(fn($item) => $materiasPromediables->contains((int) $item['asignacion_materia_id']));

                $numericas = $valoresPromediables
                    ->pluck('valor')
                    ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                    ->map(fn($valor) => (float) $valor)
                    ->values();

                $reprobadas = $numericas->filter(fn($valor) => $valor < 6)->count();

                /*
                 * En bachillerato las materias extra son informativas. Para el
                 * diagnóstico académico solo se consideran las materias que sí
                 * forman parte de los promedios parcial, semestral y final.
                 */
                $valoresAcademicos = $this->esBachillerato
                    ? $valoresPromediables
                    : $valores;

                $especiales = $valoresAcademicos
                    ->pluck('valor')
                    ->filter(fn($valor) => $this->esCalificacionEspecial($valor))
                    ->count();

                $pendientesAlumno = $valoresAcademicos
                    ->filter(fn($item) => blank($item['valor']))
                    ->count();

                $promedio = $this->promediosPrecisos[$inscripcionId] ?? null;

                $tieneNumericas = $this->alumnoTieneCalificacionesNumericas($inscripcionId);

                $promedioNumerico = $tieneNumericas && is_numeric($promedio)
                    ? (float) $promedio
                    : null;

                return [
                    'inscripcion_id' => $inscripcionId,
                    'matricula' => $fila['matricula'] ?? 'Sin matrícula',
                    'alumno' => $fila['alumno'] ?? 'Sin alumno',
                    'promedio' => $promedioNumerico,
                    'promedio_texto' => PromedioExcel::formatear($promedioNumerico, 1, '—'),
                    'reprobadas' => $reprobadas,
                    'especiales' => $especiales,
                    'pendientes' => $pendientesAlumno,
                    'captura_completa' => $pendientesAlumno === 0,
                    'estado' => $this->estadoAlumnoCalificacion($promedioNumerico, $reprobadas, $pendientesAlumno),
                    'clase' => $this->claseEstadoAlumnoCalificacion($promedioNumerico, $reprobadas, $pendientesAlumno),
                ];
            })
            ->values();

        $rankingAlumnos = $alumnosResumen
            ->filter(fn($alumno) => $alumno['promedio'] !== null)
            ->sortByDesc('promedio')
            ->values();

        $alumnosRiesgo = $alumnosResumen
            ->filter(fn($alumno) => ($alumno['promedio'] !== null && $alumno['promedio'] < 6) || $alumno['reprobadas'] >= 2)
            ->sortBy('promedio')
            ->values();

        $alumnosCapturaIncompleta = $alumnosResumen
            ->filter(fn($alumno) => $alumno['pendientes'] > 0)
            ->sortByDesc('pendientes')
            ->values();

        $candidatosReconocimiento = $alumnosResumen
            ->filter(fn($alumno) => $alumno['promedio'] !== null && $alumno['promedio'] >= 9.5 && $alumno['reprobadas'] === 0 && $alumno['captura_completa'])
            ->sortByDesc('promedio')
            ->values();

        $materiasResumen = ($this->esBachillerato
            ? $this->obtenerMateriasOrdenadasParaPromedio()
            : collect($this->materias))
            ->map(function ($materia) {
                $asignacionMateriaId = (int) $materia['id'];

                $valores = collect($this->calificaciones)
                    ->map(fn($materiasAlumno) => $this->normalizarCalificacion($materiasAlumno[$asignacionMateriaId] ?? null))
                    ->values();

                $numericas = $valores
                    ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                    ->map(fn($valor) => (float) $valor)
                    ->values();

                $aprobadas = $numericas->filter(fn($valor) => $valor >= 6)->count();
                $reprobadas = $numericas->filter(fn($valor) => $valor < 6)->count();
                $pendientesMateria = $valores->filter(fn($valor) => blank($valor))->count();
                $especiales = $valores->filter(fn($valor) => $this->esCalificacionEspecial($valor))->count();
                $promedio = PromedioExcel::calcular($numericas);

                return [
                    'id' => $asignacionMateriaId,
                    'materia' => $materia['materia'] ?? 'Sin materia',
                    'profesor' => $materia['profesor'] ?? 'Sin profesor asignado',
                    'extra' => (bool) ($materia['extra'] ?? false),
                    'promedio' => $promedio,
                    'promedio_texto' => PromedioExcel::formatear($promedio, 1, '—'),
                    'aprobadas' => $aprobadas,
                    'reprobadas' => $reprobadas,
                    'pendientes' => $pendientesMateria,
                    'especiales' => $especiales,
                    'estado' => $this->estadoMateriaCalificacion($promedio, $reprobadas, $pendientesMateria),
                    'clase' => $this->claseEstadoMateriaCalificacion($promedio, $reprobadas, $pendientesMateria),
                ];
            })
            ->sortBy(fn($materia) => $materia['promedio'] === null ? 999 : $materia['promedio'])
            ->values();

        $materiasConPromedio = $materiasResumen->filter(fn($materia) => $materia['promedio'] !== null)->values();
        $materiaMasBaja = $materiasConPromedio->sortBy('promedio')->first();
        $materiaMasAlta = $materiasConPromedio->sortByDesc('promedio')->first();

        $estadisticas = $this->estadisticasCalificaciones;
        $promedioGlobal = $estadisticas['promedio_global'] ?? '—';
        $porcentajeAprobacion = (int) ($estadisticas['porcentaje_aprobacion'] ?? 0);
        $reprobadasGlobal = (int) ($estadisticas['reprobadas'] ?? 0);
        $especialesGlobal = (int) ($estadisticas['especiales'] ?? 0);

        $salud = 100;

        if ($pendientes > 0) {
            $salud -= 25;
        }

        if ($porcentajeAprobacion < 80) {
            $salud -= 20;
        }

        if ($alumnosRiesgo->count() > 0) {
            $salud -= 25;
        }

        if ($materiaMasBaja && $materiaMasBaja['promedio'] < 7) {
            $salud -= 15;
        }

        if ($especialesGlobal > 0) {
            $salud -= 5;
        }

        $salud = max(0, min(100, $salud));

        $color = 'emerald';
        $titulo = 'Grupo estable académicamente';
        $descripcion = 'La captura y el rendimiento general del grupo se encuentran en buen estado.';

        if ($salud < 75) {
            $color = 'amber';
            $titulo = 'Grupo con observaciones académicas';
            $descripcion = 'Hay elementos que conviene revisar antes de generar boletas, reconocimientos o reportes.';
        }

        if ($salud < 50) {
            $color = 'rose';
            $titulo = 'Grupo con riesgo académico';
            $descripcion = 'Se recomienda revisar alumnos en riesgo, materias con bajo promedio y calificaciones pendientes.';
        }

        $alertas = collect();

        if ($pendientes > 0) {
            $alertas->push([
                'tipo' => 'warning',
                'titulo' => 'Captura incompleta',
                'mensaje' => 'Hay ' . $pendientes . ' calificación(es) pendiente(s) por capturar.',
            ]);
        }

        if ($alumnosRiesgo->count() > 0) {
            $alertas->push([
                'tipo' => 'danger',
                'titulo' => 'Alumnos en riesgo',
                'mensaje' => 'Hay ' . $alumnosRiesgo->count() . ' alumno(s) con promedio bajo o varias materias reprobadas.',
            ]);
        }

        if ($materiaMasBaja) {
            $alertas->push([
                'tipo' => ($materiaMasBaja['promedio'] < 7 ? 'warning' : 'info'),
                'titulo' => 'Materia con menor rendimiento',
                'mensaje' => $materiaMasBaja['materia'] . ' tiene el promedio más bajo con ' . $materiaMasBaja['promedio_texto'] . '.',
            ]);
        }

        if ($candidatosReconocimiento->count() > 0) {
            $alertas->push([
                'tipo' => 'success',
                'titulo' => 'Candidatos a reconocimiento',
                'mensaje' => 'Hay ' . $candidatosReconocimiento->count() . ' alumno(s) con promedio destacado y captura completa.',
            ]);
        }

        if ($especialesGlobal > 0) {
            $alertas->push([
                'tipo' => 'info',
                'titulo' => 'Valores especiales registrados',
                'mensaje' => 'Hay ' . $especialesGlobal . ' valor(es) especiales como AC, ED, RA, NP o SD.',
            ]);
        }

        $recomendaciones = collect();

        if ($pendientes > 0) {
            $recomendaciones->push('Completar las calificaciones pendientes antes de generar boletas o reportes finales.');
        }

        if ($alumnosRiesgo->count() > 0) {
            $recomendaciones->push('Dar seguimiento a los alumnos en riesgo académico y revisar las materias reprobadas.');
        }

        if ($materiaMasBaja && $materiaMasBaja['promedio'] < 7) {
            $recomendaciones->push('Revisar estrategias de apoyo en ' . $materiaMasBaja['materia'] . ', ya que presenta el promedio más bajo.');
        }

        if ($alumnosCapturaIncompleta->count() > 0) {
            $recomendaciones->push('Revisar a los alumnos con captura incompleta para evitar boletas con datos faltantes.');
        }

        if ($candidatosReconocimiento->count() > 0) {
            $recomendaciones->push('Validar los candidatos a reconocimiento antes de descargar los reconocimientos.');
        }

        if ($recomendaciones->isEmpty()) {
            $recomendaciones->push('El grupo no presenta observaciones críticas en este momento.');
        }

        return [
            'hay_datos' => true,
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'color' => $color,
            'salud' => $salud,
            'tarjetas' => [
                [
                    'titulo' => 'Salud académica',
                    'valor' => $salud . '%',
                    'detalle' => 'Estado general del grupo',
                    'color' => $color,
                ],
                [
                    'titulo' => 'Captura',
                    'valor' => $porcentajeCaptura . '%',
                    'detalle' => $celdasCapturadas . ' de ' . $totalCeldas . ' celdas',
                    'color' => $pendientes > 0 ? 'amber' : 'emerald',
                ],
                [
                    'titulo' => 'Aprobación',
                    'valor' => $porcentajeAprobacion . '%',
                    'detalle' => 'Calificaciones aprobatorias',
                    'color' => $porcentajeAprobacion >= 80 ? 'emerald' : 'rose',
                ],
                [
                    'titulo' => 'En riesgo',
                    'valor' => $alumnosRiesgo->count(),
                    'detalle' => 'Alumnos por revisar',
                    'color' => $alumnosRiesgo->count() > 0 ? 'rose' : 'emerald',
                ],
                [
                    'titulo' => 'Candidatos',
                    'valor' => $candidatosReconocimiento->count(),
                    'detalle' => 'Posibles reconocimientos',
                    'color' => 'amber',
                ],
                [
                    'titulo' => 'Pendientes',
                    'valor' => $pendientes,
                    'detalle' => 'Calificaciones faltantes',
                    'color' => $pendientes > 0 ? 'amber' : 'emerald',
                ],
            ],
            'promedio_global' => $promedioGlobal,
            'reprobadas_global' => $reprobadasGlobal,
            'especiales_global' => $especialesGlobal,
            'alertas' => $alertas,
            'ranking_alumnos' => $rankingAlumnos,
            'alumnos_riesgo' => $alumnosRiesgo,
            'alumnos_captura_incompleta' => $alumnosCapturaIncompleta,
            'candidatos_reconocimiento' => $candidatosReconocimiento,
            'materias_resumen' => $materiasResumen,
            'materia_mas_baja' => $materiaMasBaja,
            'materia_mas_alta' => $materiaMasAlta,
            'recomendaciones' => $recomendaciones,
        ];
    }

    public function generarDiagnosticoIa(GroqCalificacionService $groq): void
    {
        $this->assertAdministrativeAcademicToolAccess();
        $this->validate([
            'tipoDiagnosticoIa' => ['required', 'in:pedagogico,direccion,consejo_tecnico,familias'],
        ], [
            'tipoDiagnosticoIa.required' => 'Selecciona el tipo de informe.',
            'tipoDiagnosticoIa.in' => 'El tipo de informe seleccionado no es válido.',
        ]);

        $diagnosticoBase = $this->diagnosticoCalificaciones;

        if (!($diagnosticoBase['hay_datos'] ?? false)) {
            $this->dispatch('swal', [
                'title' => 'Selecciona generación, grado, grupo y periodo antes de generar el informe.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        try {
            $this->diagnosticoIa = $groq->generarDiagnostico(
                $this->construirEstadisticasAnonimasParaIa(),
                $this->tipoDiagnosticoIa
            );

            $this->diagnosticoIaGeneradoEn = Carbon::now()->format('d/m/Y H:i');

            $this->dispatch('swal', [
                'title' => 'Diagnóstico con IA generado correctamente.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (Throwable $exception) {
            report($exception);

            $this->diagnosticoIa = [];
            $this->diagnosticoIaGeneradoEn = null;

            $this->dispatch('swal', [
                'title' => $exception->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function limpiarDiagnosticoIa(): void
    {
        $this->diagnosticoIa = [];
        $this->diagnosticoIaGeneradoEn = null;
    }

    private function construirEstadisticasAnonimasParaIa(): array
    {
        $diagnostico = $this->diagnosticoCalificaciones;
        $estadisticas = $this->estadisticasCalificaciones;

        $nivel = $this->niveles
            ->firstWhere('id', (int) $this->nivel_id)?->nombre
            ?? Nivel::query()->whereKey($this->nivel_id)->value('nombre')
            ?? $this->slug_nivel;

        $grado = $this->grados
            ->firstWhere('id', (int) $this->grado_id)?->nombre
            ?? Grado::query()->whereKey($this->grado_id)->value('nombre')
            ?? 'Sin grado';

        $grupo = $this->textoGrupo($this->grupoSeleccionado());

        $generacionSeleccionada = $this->generaciones
            ->firstWhere('id', (int) $this->generacion_id);

        $generacion = $generacionSeleccionada
            ? trim($generacionSeleccionada->anio_ingreso . ' - ' . $generacionSeleccionada->anio_egreso)
            : 'Sin generación';

        $semestre = null;

        if ($this->esBachillerato && filled($this->semestre_id)) {
            $semestreSeleccionado = $this->semestres
                ->firstWhere('id', (int) $this->semestre_id);

            $semestre = $semestreSeleccionado?->numero;
        }

        $materias = collect($diagnostico['materias_resumen'] ?? [])
            ->map(fn($materia) => [
                'materia' => (string) ($materia['materia'] ?? 'Sin materia'),
                'es_extra' => (bool) ($materia['extra'] ?? false),
                'promedio' => isset($materia['promedio']) && is_numeric($materia['promedio'])
                    ? (float) $materia['promedio']
                    : null,
                'calificaciones_reprobatorias' => (int) ($materia['reprobadas'] ?? 0),
                'capturas_pendientes' => (int) ($materia['pendientes'] ?? 0),
                'valores_especiales' => (int) ($materia['especiales'] ?? 0),
                'estado' => (string) ($materia['estado'] ?? 'Sin datos'),
            ])
            ->take(30)
            ->values()
            ->all();

        $materiaMasBaja = $diagnostico['materia_mas_baja'] ?? null;
        $materiaMasAlta = $diagnostico['materia_mas_alta'] ?? null;

        return [
            'contexto' => [
                'nivel' => (string) $nivel,
                'generacion' => $generacion,
                'grado' => (string) $grado,
                'grupo' => $grupo,
                'semestre' => $semestre,
                'periodo' => $this->nombrePeriodo,
                'estado_periodo' => $this->estadoPeriodo,
            ],
            'captura' => [
                'total_alumnos' => count($this->inscripciones),
                'total_materias' => $this->materiasParaEstadisticasAcademicas()->count(),
                'total_celdas' => (int) $this->totalCeldas,
                'celdas_capturadas' => (int) $this->celdasCapturadas,
                'porcentaje_captura' => (int) ($estadisticas['porcentaje_captura'] ?? 0),
                'calificaciones_pendientes' => (int) ($estadisticas['pendientes'] ?? 0),
                'alumnos_con_captura_incompleta' => collect(
                    $diagnostico['alumnos_captura_incompleta'] ?? []
                )->count(),
            ],
            'rendimiento' => [
                'promedio_global' => is_numeric($estadisticas['promedio_global'] ?? null)
                    ? (float) $estadisticas['promedio_global']
                    : null,
                'porcentaje_aprobacion' => (int) ($estadisticas['porcentaje_aprobacion'] ?? 0),
                'alumnos_en_riesgo' => collect($diagnostico['alumnos_riesgo'] ?? [])->count(),
                'candidatos_reconocimiento' => collect(
                    $diagnostico['candidatos_reconocimiento'] ?? []
                )->count(),
                'valores_especiales' => (int) ($estadisticas['especiales'] ?? 0),
                'salud_academica_calculada' => (int) ($diagnostico['salud'] ?? 0),
            ],
            'materia_menor_rendimiento' => $materiaMasBaja ? [
                'materia' => (string) ($materiaMasBaja['materia'] ?? 'Sin materia'),
                'promedio' => isset($materiaMasBaja['promedio']) && is_numeric($materiaMasBaja['promedio'])
                    ? (float) $materiaMasBaja['promedio']
                    : null,
                'calificaciones_reprobatorias' => (int) ($materiaMasBaja['reprobadas'] ?? 0),
                'capturas_pendientes' => (int) ($materiaMasBaja['pendientes'] ?? 0),
            ] : null,
            'materia_mayor_rendimiento' => $materiaMasAlta ? [
                'materia' => (string) ($materiaMasAlta['materia'] ?? 'Sin materia'),
                'promedio' => isset($materiaMasAlta['promedio']) && is_numeric($materiaMasAlta['promedio'])
                    ? (float) $materiaMasAlta['promedio']
                    : null,
            ] : null,
            'materias' => $materias,
            'observaciones_del_sistema' => collect($diagnostico['recomendaciones'] ?? [])
                ->map(fn($recomendacion) => (string) $recomendacion)
                ->take(8)
                ->values()
                ->all(),
            'advertencias' => [
                'datos_anonimos' => true,
                'sin_nombres' => true,
                'sin_matriculas' => true,
                'sin_calificaciones_individuales' => true,
                'hay_cambios_sin_guardar' => $this->hayCambios,
            ],
        ];
    }

    public function clasePrioridadDiagnosticoIa(string $prioridad): string
    {
        return match ($prioridad) {
            'alta' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300',
            'baja' => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300',
            default => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300',
        };
    }

    private function tieneMateriasPromediables(): bool
    {
        $materiasPromediables = $this->obtenerMateriasOrdenadasParaPromedio();
        $numeroMaterias = $this->obtenerNumeroMateriasPromediar();

        return $numeroMaterias !== null
            && $numeroMaterias > 0
            && $materiasPromediables->isNotEmpty();
    }

    private function obtenerPromediosRealesParaReconocimiento(): Collection
    {
        /*
         * Se respeta primero materia_promediar. En bachillerato, cuando no hay
         * registro, se usa automáticamente el total de materias calificables.
         */
        if (!$this->tieneMateriasPromediables()) {
            return collect();
        }

        return collect($this->promediosPrecisos)
            ->filter(fn($valor) => is_numeric($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();
    }

    public function getHayPromediosParaReconocimientoProperty(): bool
    {
        return $this->obtenerPromediosRealesParaReconocimiento()->isNotEmpty();
    }

    public function getAlumnosReconocimientoOrdenadosProperty(): array
    {
        /*
         * Este accessor evita el error PropertyNotFoundException del Blade.
         * El Blade puede llamar $this->alumnosReconocimientoOrdenados.
         */
        if (!$this->hayPromediosParaReconocimiento) {
            return [];
        }

        $alumnosBase = collect($this->inscripciones)
            ->map(function ($fila) {
                $inscripcionId = (int) ($fila['inscripcion_id'] ?? 0);
                $promedio = $this->promediosPrecisos[$inscripcionId] ?? null;

                if ($inscripcionId <= 0 || !is_numeric($promedio)) {
                    return null;
                }

                $promedioNumerico = (float) $promedio;

                return [
                    'inscripcion_id' => $inscripcionId,
                    'matricula' => $fila['matricula'] ?? 'SIN MATRÍCULA',
                    'alumno' => $fila['alumno'] ?? 'Alumno',
                    'promedio' => $promedioNumerico,
                    'promedio_texto' => PromedioExcel::formatear($promedioNumerico, 1, '—'),
                    'promedio_clave' => PromedioExcel::claveComparacion($promedioNumerico),
                ];
            })
            ->filter()
            ->values();

        /*
         * El lugar siempre se calcula de mayor a menor,
         * aunque el usuario cambie el orden visual del select.
         */
        $promediosUnicosDesc = $alumnosBase
            ->sortByDesc('promedio')
            ->pluck('promedio_clave')
            ->unique()
            ->values();

        $alumnosConLugar = $alumnosBase
            ->map(function ($alumno) use ($promediosUnicosDesc) {
                $indiceLugar = $promediosUnicosDesc->search($alumno['promedio_clave']);

                $lugar = $indiceLugar !== false
                    ? $indiceLugar + 1
                    : null;

                $alumno['lugar'] = $lugar;
                $alumno['texto_lugar'] = $lugar ? $lugar . '° lugar' : 'Pendiente';

                return $alumno;
            });

        $alumnosOrdenados = match ($this->orden_promedio) {
            'menor_mayor' => $alumnosConLugar->sortBy('promedio'),
            default => $alumnosConLugar->sortByDesc('promedio'),
        };

        return $alumnosOrdenados
            ->values()
            ->toArray();
    }

    public function estadoAlumnoCalificacion(?float $promedio, int $reprobadas, int $pendientes): string
    {
        if (!$this->tieneMateriasPromediables()) {
            return 'Pendiente';
        }

        if ($promedio === null) {
            return 'Pendiente';
        }

        if ($pendientes > 0) {
            return 'Captura incompleta';
        }

        if ($promedio < 6 || $reprobadas >= 2) {
            return 'En riesgo';
        }

        if ($promedio >= 9) {
            return 'Excelente';
        }

        if ($promedio >= 8) {
            return 'Bueno';
        }

        return 'Regular';
    }

    public function claseEstadoAlumnoCalificacion(?float $promedio, int $reprobadas, int $pendientes): string
    {
        if (!$this->tieneMateriasPromediables()) {
            return 'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300';
        }

        if ($promedio === null) {
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300';
        }

        if ($pendientes > 0) {
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300';
        }

        if ($promedio < 6 || $reprobadas >= 2) {
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300';
        }

        if ($promedio >= 9) {
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300';
        }

        if ($promedio >= 8) {
            return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300';
        }

        return 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300';
    }

    public function estadoMateriaCalificacion(?float $promedio, int $reprobadas, int $pendientes): string
    {
        if (!$this->tieneMateriasPromediables()) {
            return 'Pendiente';
        }

        if ($pendientes > 0) {
            return 'Captura incompleta';
        }

        if ($promedio === null) {
            return 'Sin datos';
        }

        if ($promedio < 7 || $reprobadas > 0) {
            return 'Atención';
        }

        if ($promedio >= 9) {
            return 'Excelente';
        }

        return 'Estable';
    }

    public function claseEstadoMateriaCalificacion(?float $promedio, int $reprobadas, int $pendientes): string
    {
        if (!$this->tieneMateriasPromediables()) {
            return 'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300';
        }

        if ($pendientes > 0) {
            return 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300';
        }

        if ($promedio === null) {
            return 'border-slate-200 bg-slate-50 text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300';
        }

        if ($promedio < 7 || $reprobadas > 0) {
            return 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300';
        }

        if ($promedio >= 9) {
            return 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300';
        }

        return 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300';
    }

    public function claseTarjetaDiagnosticoCalificacion(string $color): string
    {
        return match ($color) {
            'emerald' => 'border-emerald-100 bg-emerald-50 text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300',
            'amber' => 'border-amber-100 bg-amber-50 text-amber-700 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-300',
            'rose' => 'border-rose-100 bg-rose-50 text-rose-700 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300',
            'sky' => 'border-sky-100 bg-sky-50 text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-300',
            'indigo' => 'border-indigo-100 bg-indigo-50 text-indigo-700 dark:border-indigo-900/40 dark:bg-indigo-950/30 dark:text-indigo-300',
            default => 'border-slate-100 bg-slate-50 text-slate-700 dark:border-neutral-800 dark:bg-neutral-900 dark:text-slate-300',
        };
    }

    public function claseAlertaDiagnosticoCalificacion(string $tipo): string
    {
        return match ($tipo) {
            'danger' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-200',
            'warning' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200',
            'success' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-200',
            default => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200',
        };
    }

}
