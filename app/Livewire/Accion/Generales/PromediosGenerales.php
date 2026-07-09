<?php

namespace App\Livewire\Accion\Generales;

use App\Exports\PromediosGeneralesExport;
use App\Models\CicloEscolar;
use App\Models\DecisionPromocionOficial;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\CalificacionOficialPrimariaService;
use App\Services\PromedioAnualBachilleratoService;
use App\Services\PromedioBachilleratoService;
use App\Services\PromedioSecundariaService;
use App\Support\PromedioExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromediosGenerales extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $cicloEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;

    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';
    public string $semestre_id = '';
    public string $orden = 'promedio_desc';
    public string $fecha_pdf = '';
    public string $modalidad_bachillerato = 'semestral';

    public string $busqueda_alumno = '';
    public ?int $alumno_seleccionado_id = null;
    public int $indice_sugerencia = 0;
    public bool $mostrar_sugerencias = false;

    public function mount(string $slug_nivel): void
    {
        $this->fecha_pdf = now()->format('Y-m-d');
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->grupos = collect();
        $this->semestres = collect();

        $this->ciclo_escolar_id = (string) (
            $this->cicloEscolares->firstWhere('es_actual', true)?->id
            ?? $this->cicloEscolares->first()?->id
            ?? ''
        );

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = '';
        $this->semestre_id = '';
        $this->cargarGrupos();
        $this->cargarSemestres();
        $this->validarAlumnoSeleccionado();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
        $this->semestre_id = '';
        $this->cargarGrupos();
        $this->cargarSemestres();
        $this->validarAlumnoSeleccionado();
    }

    public function updatedCicloEscolarId(): void
    {
        if ($this->esAnualBachillerato) {
            $this->grado_id = '';
            $this->grupo_id = '';
            $this->semestre_id = '';
        }

        $this->validarAlumnoSeleccionado();
    }

    public function updatedModalidadBachillerato(): void
    {
        if (!in_array($this->modalidad_bachillerato, ['semestral', 'anual'], true)) {
            $this->modalidad_bachillerato = 'semestral';
        }

        $this->grado_id = '';
        $this->grupo_id = '';
        $this->semestre_id = '';
        $this->cargarGrupos();
        $this->cargarSemestres();
        $this->validarAlumnoSeleccionado();
    }

    public function updatedGrupoId(): void
    {
        $this->validarAlumnoSeleccionado();
    }

    public function updatedSemestreId(): void
    {
        $this->validarAlumnoSeleccionado();
    }

    public function updatedBusquedaAlumno(): void
    {
        $this->alumno_seleccionado_id = null;
        $this->indice_sugerencia = 0;
        $this->mostrar_sugerencias = mb_strlen(trim($this->busqueda_alumno)) >= 2;
    }

    public function limpiarFiltros(): void
    {
        $this->ciclo_escolar_id = (string) (
            $this->cicloEscolares->firstWhere('es_actual', true)?->id
            ?? $this->cicloEscolares->first()?->id
            ?? ''
        );
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->semestre_id = '';
        $this->orden = 'promedio_desc';
        $this->modalidad_bachillerato = 'semestral';
        $this->limpiarBusquedaAlumno();

        $this->cargarGrupos();
        $this->cargarSemestres();
    }

    public function abrirSugerencias(): void
    {
        $this->mostrar_sugerencias = mb_strlen(trim($this->busqueda_alumno)) >= 2
            && $this->alumno_seleccionado_id === null;
    }

    public function cerrarSugerencias(): void
    {
        $this->mostrar_sugerencias = false;
    }

    public function moverSugerencia(int $direccion): void
    {
        $cantidad = $this->sugerenciasAlumnos->count();

        if ($cantidad === 0) {
            $this->indice_sugerencia = 0;
            return;
        }

        $this->mostrar_sugerencias = true;
        $this->indice_sugerencia = ($this->indice_sugerencia + $direccion + $cantidad) % $cantidad;
    }

    public function seleccionarSugerenciaActual(): void
    {
        $sugerencia = $this->sugerenciasAlumnos->get($this->indice_sugerencia);

        if ($sugerencia) {
            $this->seleccionarAlumno((int) $sugerencia['id']);
        }
    }

    public function seleccionarAlumno(int $inscripcionId): void
    {
        $alumno = $this->consultaAlumnosSegunFiltros()
            ->whereKey($inscripcionId)
            ->first();

        if (!$alumno) {
            $this->addError('busqueda_alumno', 'El alumno no pertenece a los filtros académicos seleccionados.');
            return;
        }

        $this->resetErrorBag('busqueda_alumno');
        $this->alumno_seleccionado_id = (int) $alumno->id;
        $this->busqueda_alumno = $this->nombreCompletoInscripcion($alumno);
        $this->indice_sugerencia = 0;
        $this->mostrar_sugerencias = false;
    }

    public function limpiarBusquedaAlumno(): void
    {
        $this->busqueda_alumno = '';
        $this->alumno_seleccionado_id = null;
        $this->indice_sugerencia = 0;
        $this->mostrar_sugerencias = false;
        $this->resetErrorBag('busqueda_alumno');
    }

    public function getEsPrimariaProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'primaria';
    }

    public function getEsPreescolarProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'preescolar';
    }

    public function getEsSecundariaProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'secundaria';
    }

    public function getEsBachilleratoProperty(): bool
    {
        return ($this->nivel?->slug ?? null) === 'bachillerato'
            || (int) ($this->nivel?->id ?? 0) === 4;
    }

    public function getEsAnualBachilleratoProperty(): bool
    {
        return $this->esBachillerato && $this->modalidad_bachillerato === 'anual';
    }

    public function getSugerenciasAlumnosProperty(): Collection
    {
        $termino = trim($this->busqueda_alumno);

        if (mb_strlen($termino) < 2 || $this->alumno_seleccionado_id !== null) {
            return collect();
        }

        return $this->consultaAlumnosSegunFiltros()
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'grado:id,nombre',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where(function ($query) use ($termino): void {
                $like = '%' . $termino . '%';

                $query
                    ->where('matricula', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhereRaw(
                        "TRIM(CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre)) LIKE ?",
                        [$like]
                    )
                    ->orWhereRaw(
                        "TRIM(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno)) LIKE ?",
                        [$like]
                    );
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->limit(8)
            ->get()
            ->map(fn(Inscripcion $alumno): array => $this->normalizarSugerenciaAlumno($alumno))
            ->values();
    }

    public function getAvisoBusquedaProperty(): ?string
    {
        $termino = trim($this->busqueda_alumno);

        if (
            mb_strlen($termino) < 2
            || $this->alumno_seleccionado_id !== null
            || $this->generacion_id === ''
            || $this->sugerenciasAlumnos->isNotEmpty()
        ) {
            return null;
        }

        $like = '%' . $termino . '%';

        $coincidencia = Inscripcion::query()
            ->with('generacion:id,anio_ingreso,anio_egreso')
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->where('generacion_id', '!=', (int) $this->generacion_id)
            ->where(function ($query) use ($like): void {
                $query
                    ->where('matricula', 'like', $like)
                    ->orWhere('curp', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('apellido_paterno', 'like', $like)
                    ->orWhere('apellido_materno', 'like', $like)
                    ->orWhereRaw(
                        "TRIM(CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre)) LIKE ?",
                        [$like]
                    );
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->first();

        if (!$coincidencia) {
            return null;
        }

        $generacion = $coincidencia->generacion
            ? $coincidencia->generacion->anio_ingreso . ' - ' . $coincidencia->generacion->anio_egreso
            : 'otra generación';

        return 'Se encontró a ' . $this->nombreCompletoInscripcion($coincidencia)
            . ' en la generación ' . $generacion
            . '. La búsqueda actual respeta la generación seleccionada.';
    }

    public function getDatosAlumnoSeleccionadoProperty(): ?array
    {
        if ($this->alumno_seleccionado_id === null) {
            return null;
        }

        $alumno = Inscripcion::query()
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'grado:id,nombre',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
            ])
            ->whereKey($this->alumno_seleccionado_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->first();

        return $alumno ? $this->normalizarSugerenciaAlumno($alumno) : null;
    }

    public function getContextoAnualBachilleratoProperty(): array
    {
        if (!$this->esAnualBachillerato || $this->ciclo_escolar_id === '' || $this->generacion_id === '') {
            return [
                'valido' => false,
                'errores' => $this->esAnualBachillerato && $this->generacion_id === ''
                    ? ['Selecciona una generación para calcular el promedio anual de bachillerato.']
                    : [],
                'semestres' => collect(),
                'numeros_semestre' => [],
                'nombre_anio' => 'Año no determinado',
            ];
        }

        return app(PromedioAnualBachilleratoService::class)->resolverContexto(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: (int) $this->generacion_id,
        );
    }

    public function getEncabezadosPeriodosProperty(): array
    {
        if ($this->esAnualBachillerato) {
            $numeros = $this->contextoAnualBachillerato['numeros_semestre'] ?? [];

            return [
                1 => isset($numeros[0]) ? 'Prom. semestre ' . $numeros[0] : 'Primer semestre',
                2 => isset($numeros[1]) ? 'Prom. semestre ' . $numeros[1] : 'Segundo semestre',
            ];
        }

        return $this->esBachillerato
            ? [1 => '1er parcial', 2 => '2do parcial']
            : [1 => '1er periodo', 2 => '2do periodo', 3 => '3er periodo'];
    }

    public function getConcentradoProperty(): array
    {
        return $this->construirConcentrado(true);
    }

    private function construirConcentrado(bool $aplicarSeleccion): array
    {
        if ($this->esAnualBachillerato) {
            $reporte = app(PromedioAnualBachilleratoService::class)->reporteAnual(
                nivelId: (int) $this->nivel->id,
                cicloEscolarId: (int) ($this->ciclo_escolar_id ?: 0),
                generacionId: (int) ($this->generacion_id ?: 0),
                orden: $this->orden,
            );

            $alumnos = $this->prepararAlumnosParaVista(
                collect($reporte['alumnos'] ?? []),
                $aplicarSeleccion
            );

            return $this->reconstruirConcentradoAnual($reporte, $alumnos);
        }

        $alumnos = $this->prepararAlumnosParaVista(
            $this->obtenerAlumnosConPromedio(),
            $aplicarSeleccion
        );

        return [
            'resumen' => $this->construirResumen($alumnos),
            'grupos' => $this->agruparAlumnos($alumnos),
            'grafica' => $this->construirDatosGrafica($alumnos),
            'campos' => $this->esPrimaria
                ? app(CalificacionOficialPrimariaService::class)->campos()
                : collect(),
            'diagnostico' => null,
            'contexto' => null,
        ];
    }

    private function cargarGrupos(): void
    {
        if ($this->esAnualBachillerato) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'semestre:id,numero,grado_id',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->generacion_id !== '', fn($query) => $query->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($query) => $query->where('grado_id', $this->grado_id))
            ->get([
                'id',
                'asignacion_grupo_id',
                'nivel_id',
                'grado_id',
                'generacion_id',
                'semestre_id',
            ])
            ->sort(function (Grupo $grupoA, Grupo $grupoB): int {
                $comparacion = (int) ($grupoA->grado?->orden ?? PHP_INT_MAX)
                    <=> (int) ($grupoB->grado?->orden ?? PHP_INT_MAX);

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = strnatcasecmp(
                    trim((string) ($grupoA->asignacionGrupo?->nombre ?? '')),
                    trim((string) ($grupoB->asignacionGrupo?->nombre ?? ''))
                );

                if ($comparacion !== 0) {
                    return $comparacion;
                }

                return (int) ($grupoA->semestre?->numero ?? PHP_INT_MAX)
                    <=> (int) ($grupoB->semestre?->numero ?? PHP_INT_MAX);
            })
            ->values();
    }

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->whereHas('grado', fn($query) => $query->where('nivel_id', $this->nivel->id))
            ->when($this->grado_id !== '', fn($query) => $query->where('grado_id', $this->grado_id))
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global']);
    }

    private function consultaAlumnosSegunFiltros()
    {
        return Inscripcion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->when(
                $this->generacion_id !== '',
                fn($query) => $query->where('generacion_id', (int) $this->generacion_id)
            )
            ->when(
                !$this->esAnualBachillerato && $this->grado_id !== '',
                fn($query) => $query->where('grado_id', (int) $this->grado_id)
            )
            ->when(
                !$this->esAnualBachillerato && $this->grupo_id !== '',
                fn($query) => $query->where('grupo_id', (int) $this->grupo_id)
            )
            ->when(
                $this->esBachillerato && !$this->esAnualBachillerato && $this->semestre_id !== '',
                fn($query) => $query->where('semestre_id', (int) $this->semestre_id)
            );
    }

    private function validarAlumnoSeleccionado(): void
    {
        if ($this->alumno_seleccionado_id === null) {
            return;
        }

        $valido = $this->consultaAlumnosSegunFiltros()
            ->whereKey($this->alumno_seleccionado_id)
            ->exists();

        if ($valido) {
            return;
        }

        $this->alumno_seleccionado_id = null;
        $this->indice_sugerencia = 0;
        $this->mostrar_sugerencias = mb_strlen(trim($this->busqueda_alumno)) >= 2;
    }

    private function nombreCompletoInscripcion(Inscripcion $alumno): string
    {
        return trim(collect([
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            $alumno->nombre,
        ])->filter()->implode(' '));
    }

    private function normalizarSugerenciaAlumno(Inscripcion $alumno): array
    {
        $generacion = $alumno->generacion
            ? $alumno->generacion->anio_ingreso . ' - ' . $alumno->generacion->anio_egreso
            : 'Sin generación';

        return [
            'id' => (int) $alumno->id,
            'alumno' => $this->nombreCompletoInscripcion($alumno),
            'matricula' => $alumno->matricula ?: 'Sin matrícula',
            'curp' => $alumno->curp ?: 'Sin CURP',
            'generacion_id' => $alumno->generacion_id ? (int) $alumno->generacion_id : null,
            'generacion' => $generacion,
            'grado_id' => $alumno->grado_id ? (int) $alumno->grado_id : null,
            'grado' => $alumno->grado?->nombre ?? 'Sin grado',
            'grupo_id' => $alumno->grupo_id ? (int) $alumno->grupo_id : null,
            'grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo',
            'semestre_id' => $alumno->semestre_id ? (int) $alumno->semestre_id : null,
            'semestre' => $alumno->semestre?->numero,
            'estatus_inscripcion' => str($alumno->estatus ?: 'activo')->replace('_', ' ')->headline()->toString(),
            'activo' => (bool) $alumno->activo,
        ];
    }

    private function prepararAlumnosParaVista(Collection $alumnos, bool $aplicarSeleccion): Collection
    {
        $ids = $alumnos
            ->pluck('inscripcion_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        $inscripciones = Inscripcion::query()
            ->with([
                'generacion:id,anio_ingreso,anio_egreso',
                'grado:id,nombre',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
            ])
            ->whereIn('id', $ids)
            ->where('nivel_id', $this->nivel->id)
            ->where('activo', true)
            ->get()
            ->keyBy('id');

        return $alumnos
            ->filter(fn(array $alumno): bool => $inscripciones->has((int) ($alumno['inscripcion_id'] ?? 0)))
            ->map(function (array $alumno) use ($inscripciones): array {
                /** @var Inscripcion $inscripcion */
                $inscripcion = $inscripciones->get((int) $alumno['inscripcion_id']);
                $identidad = $this->normalizarSugerenciaAlumno($inscripcion);

                $alumno['curp'] = $identidad['curp'];
                $alumno['generacion'] = $identidad['generacion'];
                $alumno['estatus_inscripcion'] = $identidad['estatus_inscripcion'];
                $alumno['activo'] = true;

                return $alumno;
            })
            ->when(
                $aplicarSeleccion && $this->alumno_seleccionado_id !== null,
                fn(Collection $items): Collection => $items
                    ->where('inscripcion_id', $this->alumno_seleccionado_id)
                    ->values()
            )
            ->values();
    }

    private function reconstruirConcentradoAnual(array $reporte, Collection $alumnos): array
    {
        $definitivos = $alumnos
            ->where('completo', true)
            ->filter(fn(array $alumno): bool => is_numeric($alumno['promedio_final'] ?? null));
        $mejor = $definitivos->sortByDesc('promedio_final')->first();

        $resumen = [
            'total_alumnos' => $alumnos->count(),
            'promedio_general' => $this->formatearDecimal(PromedioExcel::calcular($definitivos->pluck('promedio_final'))),
            'aprobados' => $alumnos->where('elegible_reconocimiento', true)->count(),
            'riesgo' => $alumnos->filter(fn(array $alumno): bool => ($alumno['completo'] ?? false)
                && !($alumno['todas_materias_acreditadas'] ?? false))->count(),
            'incompletos' => $alumnos->where('completo', false)->count(),
            'pendientes_decision' => 0,
            'mejor_alumno' => $mejor['alumno'] ?? 'Sin datos',
            'mejor_promedio' => $this->formatearDecimal($mejor['promedio_final'] ?? null),
            'con_reconocimiento' => $alumnos->where('reconocimiento_disponible', true)->count(),
        ];

        $grupoOriginal = collect($reporte['grupos'] ?? [])->first();
        $grupos = collect();

        if ($grupoOriginal || $alumnos->isNotEmpty()) {
            $grupo = is_array($grupoOriginal) ? $grupoOriginal : [];
            $grupo['titulo'] = $grupo['titulo'] ?? 'Concentrado anual de bachillerato';
            $grupo['total'] = $resumen['total_alumnos'];
            $grupo['promedio'] = $resumen['promedio_general'];
            $grupo['aprobados'] = $resumen['aprobados'];
            $grupo['riesgo'] = $resumen['riesgo'];
            $grupo['incompletos'] = $resumen['incompletos'];
            $grupo['con_reconocimiento'] = $resumen['con_reconocimiento'];
            $grupo['pendientes_decision'] = 0;
            $grupo['alumnos'] = $alumnos;
            $grupos = collect([$grupo]);
        }

        $reporte['alumnos'] = $alumnos;
        $reporte['grupos'] = $grupos;
        $reporte['resumen'] = $resumen;
        $reporte['grafica'] = [
            'categorias' => $grupos->pluck('titulo')->values()->all(),
            'promedios' => $grupos->pluck('promedio')->map(fn($valor) => $valor === '—' ? 0 : (float) $valor)->values()->all(),
            'aprobados' => $grupos->pluck('aprobados')->values()->all(),
            'riesgo' => $grupos->pluck('riesgo')->values()->all(),
            'incompletos' => $grupos->pluck('incompletos')->values()->all(),
        ];

        return $reporte;
    }

    private function obtenerAlumnosConPromedio(): Collection
    {
        if ($this->ciclo_escolar_id === '') {
            return collect();
        }

        if ($this->esPrimaria) {
            return $this->agregarDisponibilidadDiploma($this->obtenerAlumnosPrimaria());
        }

        if ($this->esSecundaria) {
            return $this->agregarDisponibilidadDiploma($this->obtenerAlumnosSecundaria());
        }

        if ($this->esBachillerato) {
            return $this->obtenerAlumnosBachillerato();
        }

        return $this->agregarDisponibilidadDiploma($this->obtenerAlumnosGenericos());
    }

    private function agregarDisponibilidadDiploma(Collection $alumnos): Collection
    {
        $gradoTerminalId = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');

        $semestreTerminalNumero = $this->esBachillerato
            ? Semestre::query()
                ->whereHas('grado', fn($query) => $query->where('nivel_id', $this->nivel->id))
                ->max('numero')
            : null;

        return $alumnos->map(function (array $alumno) use ($gradoTerminalId, $semestreTerminalNumero): array {
            $esGradoTerminal = $gradoTerminalId !== null
                && (int) ($alumno['grado_id'] ?? 0) === (int) $gradoTerminalId;

            $esSemestreTerminal = !$this->esBachillerato
                || (
                    $semestreTerminalNumero !== null
                    && (int) ($alumno['semestre'] ?? 0) === (int) $semestreTerminalNumero
                );

            $diplomaDisponible = false;

            if ($esGradoTerminal && $esSemestreTerminal && ($alumno['completo'] ?? false)) {
                if (in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
                    $diplomaDisponible = ($alumno['promocion_confirmada'] ?? null) === true;
                } elseif ($this->esBachillerato) {
                    $diplomaDisponible = $this->estaAprobadoAcademicamente($alumno);
                }
            }

            $alumno['es_grado_terminal'] = $esGradoTerminal;
            $alumno['es_semestre_terminal'] = $esSemestreTerminal;
            $alumno['diploma_disponible'] = $diplomaDisponible;

            return $alumno;
        })->values();
    }

    private function obtenerAlumnosPrimaria(): Collection
    {
        $reporte = app(CalificacionOficialPrimariaService::class)->reporteAnual(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
        );

        $alumnos = collect($reporte['alumnos'])->map(function (array $fila): array {
            $periodos = $fila['promedios_periodo_precisos'];
            $periodosCompletos = $fila['periodos_completos'] ?? [1 => false, 2 => false, 3 => false];
            $capturados = collect($periodos)->filter(fn($valor) => $valor !== null)->count();

            $filaNormalizada = [
                'inscripcion_id' => $fila['inscripcion_id'],
                'generacion_id' => $fila['generacion_id'],
                'matricula' => $fila['matricula'],
                'alumno' => $fila['alumno'],
                'grado_id' => $fila['grado_id'],
                'grado' => $fila['grado'],
                'grado_orden' => $fila['grado_orden'],
                'grupo_id' => $fila['grupo_id'],
                'grupo' => $fila['grupo'],
                'semestre_id' => null,
                'semestre' => null,
                'periodos' => $periodos,
                'periodos_completos' => $periodosCompletos,
                'suma_periodos' => (float) collect($periodos)->filter(fn($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'promedio_provisional' => $fila['promedio_provisional_preciso'],
                'promedio_mostrado' => $fila['promedio_general_preciso'] ?? $fila['promedio_provisional_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => collect($periodosCompletos)->filter(fn($valor) => !$valor)->count(),
                'materias_capturadas' => $fila['materias_capturadas'] ?? 0,
                'completo' => (bool) $fila['completo'],
                'campos' => $fila['campos'] ?? [],
                'materias' => $fila['materias'] ?? [],
                'campos_reprobados' => $fila['campos_reprobados'] ?? [],
                'materias_reprobadas' => [],
                'promocion_sugerida' => $fila['promocion_sugerida'],
                'promocion_confirmada' => $fila['promocion_confirmada'],
                'fuente_calculo' => 'campos_formativos',
            ];

            $filaNormalizada['estatus'] = $this->obtenerEstatusAlumno($filaNormalizada);

            return $filaNormalizada;
        })->values();

        return $this->ordenarAlumnos($this->asignarLugaresPorGrupo($alumnos));
    }

    private function obtenerAlumnosSecundaria(): Collection
    {
        $reporte = app(PromedioSecundariaService::class)->reporteAnual(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
        );

        $alumnos = collect($reporte['alumnos'])->map(function (array $fila): array {
            $periodos = $fila['promedios_periodo_precisos'];
            $periodosCompletos = $fila['periodos_completos'] ?? [1 => false, 2 => false, 3 => false];
            $capturados = collect($periodos)->filter(fn($valor) => $valor !== null)->count();

            $filaNormalizada = [
                'inscripcion_id' => $fila['inscripcion_id'],
                'generacion_id' => $fila['generacion_id'],
                'matricula' => $fila['matricula'],
                'alumno' => $fila['alumno'],
                'grado_id' => $fila['grado_id'],
                'grado' => $fila['grado'],
                'grado_orden' => $fila['grado_orden'],
                'grupo_id' => $fila['grupo_id'],
                'grupo' => $fila['grupo'],
                'semestre_id' => null,
                'semestre' => null,
                'periodos' => $periodos,
                'periodos_completos' => $periodosCompletos,
                'suma_periodos' => (float) collect($periodos)->filter(fn($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'promedio_provisional' => $fila['promedio_provisional_preciso'],
                'promedio_mostrado' => $fila['promedio_general_preciso'] ?? $fila['promedio_provisional_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => collect($periodosCompletos)->filter(fn($valor) => !$valor)->count(),
                'materias_capturadas' => $fila['materias_completas'] ?? 0,
                'materias_esperadas' => $fila['materias_esperadas'] ?? 0,
                'completo' => (bool) $fila['completo'],
                'campos' => $fila['campos'] ?? [],
                'materias' => $fila['materias'] ?? [],
                'campos_reprobados' => [],
                'materias_reprobadas' => $fila['materias_reprobadas'] ?? [],
                'promocion_sugerida' => null,
                'promocion_confirmada' => $fila['promocion_confirmada'],
                'fuente_calculo' => 'materias',
            ];

            $filaNormalizada['estatus'] = $this->obtenerEstatusAlumno($filaNormalizada);

            return $filaNormalizada;
        })->values();

        return $this->ordenarAlumnos($this->asignarLugaresPorGrupo($alumnos));
    }

    private function obtenerAlumnosBachillerato(): Collection
    {
        if ($this->generacion_id === '') {
            return collect();
        }

        $reporte = app(PromedioBachilleratoService::class)->reporteSemestral(
            nivelId: (int) $this->nivel->id,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
            semestreId: $this->semestre_id !== '' ? (int) $this->semestre_id : null,
        );

        $alumnos = collect($reporte['alumnos'])->map(function (array $fila): array {
            $periodos = $fila['promedios_periodo_precisos'] ?? [1 => null, 2 => null];
            $periodosCompletos = $fila['periodos_completos'] ?? [1 => false, 2 => false];
            $capturados = collect($periodos)->filter(fn($valor) => $valor !== null)->count();

            $filaNormalizada = [
                'inscripcion_id' => $fila['inscripcion_id'],
                'generacion_id' => $fila['generacion_id'],
                'matricula' => $fila['matricula'],
                'alumno' => $fila['alumno'],
                'grado_id' => $fila['grado_id'],
                'grado' => $fila['grado'],
                'grado_orden' => $fila['grado_orden'],
                'grupo_id' => $fila['grupo_id'],
                'grupo' => $fila['grupo'],
                'semestre_id' => $fila['semestre_id'],
                'semestre' => $fila['semestre'],
                'periodos' => $periodos,
                'periodos_completos' => $periodosCompletos,
                'suma_periodos' => (float) collect($periodos)->filter(fn($valor) => $valor !== null)->sum(),
                'promedio_final' => $fila['promedio_general_preciso'],
                'promedio_provisional' => $fila['promedio_provisional_preciso'],
                'promedio_mostrado' => $fila['promedio_general_preciso'] ?? $fila['promedio_provisional_preciso'],
                'periodos_capturados' => $capturados,
                'periodos_faltantes' => collect($periodosCompletos)->filter(fn($valor) => !$valor)->count(),
                'materias_capturadas' => $fila['materias_completas'] ?? 0,
                'materias_esperadas' => $fila['materias_esperadas'] ?? 0,
                'completo' => (bool) ($fila['completo'] ?? false),
                'campos' => [],
                'materias' => $fila['materias'] ?? [],
                'campos_reprobados' => [],
                'materias_reprobadas' => $fila['materias_reprobadas'] ?? [],
                'claves_especiales' => $fila['claves_especiales'] ?? [],
                'promocion_sugerida' => $fila['aprobado_general'] ?? false,
                'promocion_confirmada' => null,
                'lugar' => $fila['lugar'] ?? null,
                'texto_lugar' => $fila['texto_lugar'] ?? 'Pendiente',
                'reconocimiento_disponible' => (bool) ($fila['reconocimiento_disponible'] ?? false),
                'diploma_disponible' => (bool) ($fila['diploma_disponible'] ?? false),
                'es_grado_terminal' => (int) ($fila['semestre'] ?? 0) === 6,
                'es_semestre_terminal' => (int) ($fila['semestre'] ?? 0) === 6,
                'fuente_calculo' => 'materias_bachillerato',
            ];

            $filaNormalizada['estatus'] = $this->obtenerEstatusAlumno($filaNormalizada);

            return $filaNormalizada;
        })->values();

        return $this->ordenarAlumnos($alumnos);
    }

    private function obtenerAlumnosGenericos(): Collection
    {
        $campoPeriodo = $this->esBachillerato
            ? 'periodos.parcial_bachillerato_id'
            : 'periodos.periodo_basica_id';
        $limitePeriodos = $this->esBachillerato ? [1, 2] : [1, 2, 3];

        $filas = DB::table('calificaciones')
            ->join('periodos', 'periodos.id', '=', 'calificaciones.periodo_id')
            ->join('inscripciones', 'inscripciones.id', '=', 'calificaciones.inscripcion_id')
            ->join('asignacion_materias', 'asignacion_materias.id', '=', 'calificaciones.asignacion_materia_id')
            ->join('materias', 'materias.id', '=', 'asignacion_materias.materia_id')
            ->join('grados', 'grados.id', '=', 'calificaciones.grado_id')
            ->join('grupos', 'grupos.id', '=', 'calificaciones.grupo_id')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->leftJoin('semestres', 'semestres.id', '=', 'calificaciones.semestre_id')
            ->whereNull('inscripciones.deleted_at')
            ->where('inscripciones.activo', true)
            ->where('calificaciones.nivel_id', $this->nivel->id)
            ->where('calificaciones.ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('calificaciones.es_numerica', true)
            ->whereNotNull('calificaciones.valor_numerico')
            ->whereBetween('calificaciones.valor_numerico', [0, 10])
            ->where('materias.calificable', true)
            ->where('materias.extra', false)
            ->where('materias.receso', false)
            ->whereIn(DB::raw($campoPeriodo), $limitePeriodos)
            ->when($this->generacion_id !== '', fn($query) => $query->where('calificaciones.generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn($query) => $query->where('calificaciones.grado_id', $this->grado_id))
            ->when($this->grupo_id !== '', fn($query) => $query->where('calificaciones.grupo_id', $this->grupo_id))
            ->when($this->semestre_id !== '', fn($query) => $query->where('calificaciones.semestre_id', $this->semestre_id))
            ->selectRaw(
                ''
                . 'calificaciones.id as calificacion_id, '
                . 'calificaciones.inscripcion_id, '
                . 'calificaciones.generacion_id, '
                . 'calificaciones.grado_id, '
                . 'calificaciones.grupo_id, '
                . 'calificaciones.semestre_id, '
                . 'calificaciones.asignacion_materia_id, '
                . 'calificaciones.valor_numerico, '
                . 'inscripciones.matricula, '
                . 'inscripciones.nombre, '
                . 'inscripciones.apellido_paterno, '
                . 'inscripciones.apellido_materno, '
                . 'grados.nombre as grado_nombre, '
                . 'grados.orden as grado_orden, '
                . 'asignacion_grupos.nombre as grupo_nombre, '
                . 'semestres.numero as semestre_numero, '
                . $campoPeriodo . ' as numero_periodo, '
                . 'asignacion_materias.orden as orden_materia'
            )
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->orderBy('calificaciones.id')
            ->get();

        $alumnos = $filas
            ->groupBy('inscripcion_id')
            ->map(function (Collection $registros) use ($limitePeriodos): array {
                $primero = $registros->first();
                $periodos = [];
                $materiasCapturadas = 0;

                foreach ($limitePeriodos as $periodo) {
                    $registrosPeriodo = $registros
                        ->filter(fn($registro) => (int) $registro->numero_periodo === (int) $periodo)
                        ->sortBy([
                            fn($registro) => $registro->orden_materia === null ? 1 : 0,
                            fn($registro) => $registro->orden_materia ?? 999,
                            fn($registro) => $registro->asignacion_materia_id ?? 999,
                            fn($registro) => $registro->calificacion_id ?? 999,
                        ])
                        ->groupBy('asignacion_materia_id')
                        ->map(fn(Collection $items) => $items->last())
                        ->values();

                    $valores = $registrosPeriodo
                        ->pluck('valor_numerico')
                        ->filter(fn($valor) => is_numeric($valor) && (float) $valor >= 0 && (float) $valor <= 10)
                        ->map(fn($valor) => (float) $valor)
                        ->values();

                    $periodos[$periodo] = PromedioExcel::calcular($valores);
                    $materiasCapturadas += $valores->count();
                }

                $capturados = collect($periodos)->filter(fn($valor) => $valor !== null)->count();
                $completo = $capturados === count($limitePeriodos);
                $promedioProvisional = PromedioExcel::calcular($periodos);

                $fila = [
                    'inscripcion_id' => (int) $primero->inscripcion_id,
                    'generacion_id' => (int) $primero->generacion_id,
                    'matricula' => $primero->matricula,
                    'alumno' => trim(($primero->apellido_paterno ?? '') . ' ' . ($primero->apellido_materno ?? '') . ' ' . ($primero->nombre ?? '')),
                    'grado_id' => (int) $primero->grado_id,
                    'grado' => $primero->grado_nombre,
                    'grado_orden' => (int) ($primero->grado_orden ?? 0),
                    'grupo_id' => (int) $primero->grupo_id,
                    'grupo' => $primero->grupo_nombre ?? 'Sin grupo',
                    'semestre_id' => $primero->semestre_id ? (int) $primero->semestre_id : null,
                    'semestre' => $primero->semestre_numero ? (int) $primero->semestre_numero : null,
                    'periodos' => $periodos,
                    'periodos_completos' => collect($limitePeriodos)->mapWithKeys(fn($numero) => [$numero => $periodos[$numero] !== null])->all(),
                    'suma_periodos' => (float) collect($periodos)->filter(fn($valor) => $valor !== null)->sum(),
                    'promedio_final' => $completo ? $promedioProvisional : null,
                    'promedio_provisional' => $promedioProvisional,
                    'promedio_mostrado' => $promedioProvisional,
                    'periodos_capturados' => $capturados,
                    'periodos_faltantes' => max(count($limitePeriodos) - $capturados, 0),
                    'materias_capturadas' => $materiasCapturadas,
                    'completo' => $completo,
                    'campos' => [],
                    'materias' => [],
                    'campos_reprobados' => [],
                    'materias_reprobadas' => [],
                    'promocion_sugerida' => $completo ? $promedioProvisional >= 6 : null,
                    'promocion_confirmada' => null,
                    'fuente_calculo' => 'periodos',
                ];

                $fila['estatus'] = $this->obtenerEstatusAlumno($fila);

                return $fila;
            })
            ->values();

        return $this->ordenarAlumnos($this->asignarLugaresPorGrupo($alumnos));
    }

    private function asignarLugaresPorGrupo(Collection $alumnos): Collection
    {
        return $alumnos
            ->groupBy(fn(array $alumno) => $this->claveGrupoAlumno($alumno))
            ->flatMap(function (Collection $items): Collection {
                $promediosUnicosDesc = $items
                    ->filter(fn(array $alumno) => $this->esElegibleParaLugar($alumno))
                    ->sortByDesc('promedio_final')
                    ->pluck('promedio_final')
                    ->map(fn($promedio) => PromedioExcel::claveComparacion($promedio))
                    ->filter()
                    ->unique()
                    ->values();

                return $items->map(function (array $alumno) use ($promediosUnicosDesc): array {
                    if (!$this->esElegibleParaLugar($alumno)) {
                        $alumno['lugar'] = null;
                        $alumno['texto_lugar'] = 'Pendiente';
                        return $alumno;
                    }

                    $indice = $promediosUnicosDesc->search(
                        PromedioExcel::claveComparacion($alumno['promedio_final'])
                    );
                    $lugar = $indice !== false ? ((int) $indice) + 1 : null;

                    $alumno['lugar'] = $lugar;
                    $alumno['texto_lugar'] = $lugar ? $lugar . '° lugar' : 'Pendiente';

                    return $alumno;
                });
            })
            ->values();
    }

    private function esElegibleParaLugar(array $alumno): bool
    {
        if (!($alumno['completo'] ?? false) || !is_numeric($alumno['promedio_final'] ?? null)) {
            return false;
        }

        if ($this->esPrimaria) {
            return ($alumno['promocion_sugerida'] ?? false) === true;
        }

        if ($this->esSecundaria) {
            return ($alumno['materias_reprobadas'] ?? []) === [];
        }

        return (float) $alumno['promedio_final'] >= 6.0;
    }

    private function claveGrupoAlumno(array $alumno): string
    {
        return implode('|', [
            $alumno['grado_id'] ?? 'grado',
            $alumno['grupo_id'] ?? 'grupo',
            $this->esBachillerato ? ($alumno['semestre_id'] ?? 'semestre') : 'basica',
        ]);
    }

    private function ordenarAlumnos(Collection $alumnos): Collection
    {
        return $alumnos->sort(function (array $a, array $b): int {
            if ($this->orden === 'nombre_asc') {
                return strnatcasecmp($a['alumno'] ?? '', $b['alumno'] ?? '');
            }

            $promedioA = $a['promedio_final'] ?? $a['promedio_provisional'] ?? -1;
            $promedioB = $b['promedio_final'] ?? $b['promedio_provisional'] ?? -1;
            $comparacion = (float) $promedioA <=> (float) $promedioB;

            if ($this->orden !== 'promedio_asc') {
                $comparacion *= -1;
            }

            return $comparacion !== 0
                ? $comparacion
                : strnatcasecmp($a['alumno'] ?? '', $b['alumno'] ?? '');
        })->values();
    }

    private function agruparAlumnos(Collection $alumnos): Collection
    {
        return $alumnos
            ->groupBy(fn(array $alumno) => $this->claveGrupoAlumno($alumno))
            ->map(function (Collection $items): array {
                $primero = $items->first();
                $titulo = ($primero['grado'] ?? 'Sin grado') . ' · Grupo ' . ($primero['grupo'] ?? 'Sin grupo');

                if ($this->esBachillerato) {
                    $titulo .= ' · Semestre ' . ($primero['semestre'] ?? '—');
                }

                $promediosDefinitivos = $items
                    ->where('completo', true)
                    ->pluck('promedio_final')
                    ->filter(fn($valor) => $valor !== null);

                return [
                    '_grado_orden' => (int) ($primero['grado_orden'] ?? PHP_INT_MAX),
                    '_grupo_nombre' => trim((string) ($primero['grupo'] ?? '')),
                    '_semestre' => (int) ($primero['semestre'] ?? PHP_INT_MAX),
                    'titulo' => $titulo,
                    'total' => $items->count(),
                    'promedio' => $this->formatearDecimal(PromedioExcel::calcular($promediosDefinitivos)),
                    'aprobados' => $items->filter(fn(array $item) => $this->estaAprobadoAcademicamente($item))->count(),
                    'riesgo' => $items->filter(fn(array $item) => $this->estaEnRiesgoAcademico($item))->count(),
                    'incompletos' => $items->where('completo', false)->count(),
                    'pendientes_decision' => $items->filter(fn(array $item) => ($item['completo'] ?? false)
                        && in_array($this->slug_nivel, ['primaria', 'secundaria'], true)
                        && ($item['promocion_confirmada'] ?? null) === null)->count(),
                    'alumnos' => $items->values(),
                ];
            })
            ->sort(function (array $a, array $b): int {
                $comparacion = $a['_grado_orden'] <=> $b['_grado_orden'];
                if ($comparacion !== 0) {
                    return $comparacion;
                }

                $comparacion = strnatcasecmp($a['_grupo_nombre'], $b['_grupo_nombre']);
                return $comparacion !== 0 ? $comparacion : ($a['_semestre'] <=> $b['_semestre']);
            })
            ->map(function (array $grupo): array {
                unset($grupo['_grado_orden'], $grupo['_grupo_nombre'], $grupo['_semestre']);
                return $grupo;
            })
            ->values();
    }

    private function construirResumen(Collection $alumnos): array
    {
        $definitivos = $alumnos
            ->where('completo', true)
            ->filter(fn(array $alumno) => is_numeric($alumno['promedio_final'] ?? null));
        $mejor = $definitivos->sortByDesc('promedio_final')->first();

        return [
            'total_alumnos' => $alumnos->count(),
            'promedio_general' => $this->formatearDecimal(PromedioExcel::calcular($definitivos->pluck('promedio_final'))),
            'aprobados' => $alumnos->filter(fn(array $alumno) => $this->estaAprobadoAcademicamente($alumno))->count(),
            'riesgo' => $alumnos->filter(fn(array $alumno) => $this->estaEnRiesgoAcademico($alumno))->count(),
            'incompletos' => $alumnos->where('completo', false)->count(),
            'pendientes_decision' => $alumnos->filter(fn(array $alumno) => ($alumno['completo'] ?? false)
                && in_array($this->slug_nivel, ['primaria', 'secundaria'], true)
                && ($alumno['promocion_confirmada'] ?? null) === null)->count(),
            'mejor_alumno' => $mejor['alumno'] ?? 'Sin datos',
            'mejor_promedio' => $this->formatearDecimal($mejor['promedio_final'] ?? null),
        ];
    }

    private function construirDatosGrafica(Collection $alumnos): array
    {
        $porGrupo = $this->agruparAlumnos($alumnos);

        return [
            'categorias' => $porGrupo->pluck('titulo')->values()->all(),
            'promedios' => $porGrupo->pluck('promedio')->map(fn($valor) => (float) $valor)->values()->all(),
            'aprobados' => $porGrupo->pluck('aprobados')->values()->all(),
            'riesgo' => $porGrupo->pluck('riesgo')->values()->all(),
            'incompletos' => $porGrupo->pluck('incompletos')->values()->all(),
        ];
    }

    private function estaAprobadoAcademicamente(array $alumno): bool
    {
        if (!($alumno['completo'] ?? false)) {
            return false;
        }

        if ($this->esPrimaria) {
            return ($alumno['promocion_sugerida'] ?? false) === true;
        }

        if ($this->esSecundaria) {
            return ($alumno['materias_reprobadas'] ?? []) === [];
        }

        return is_numeric($alumno['promedio_final'] ?? null)
            && (float) $alumno['promedio_final'] >= 6.0;
    }

    private function estaEnRiesgoAcademico(array $alumno): bool
    {
        if (!($alumno['completo'] ?? false)) {
            return false;
        }

        if ($this->esPrimaria) {
            return ($alumno['promocion_sugerida'] ?? null) === false;
        }

        if ($this->esSecundaria) {
            return ($alumno['materias_reprobadas'] ?? []) !== [];
        }

        return is_numeric($alumno['promedio_final'] ?? null)
            && (float) $alumno['promedio_final'] < 6.0;
    }

    private function obtenerEstatusAlumno(array $alumno): string
    {
        $tieneDatos = collect($alumno['periodos'] ?? [])->contains(fn($valor) => $valor !== null)
            || ($alumno['promedio_provisional'] ?? null) !== null;

        if (!$tieneDatos) {
            return 'Sin captura';
        }

        if (!($alumno['completo'] ?? false)) {
            return 'Incompleto';
        }

        if (in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
            if (($alumno['promocion_confirmada'] ?? null) === true) {
                return 'Promovido';
            }

            if (($alumno['promocion_confirmada'] ?? null) === false) {
                return 'No promovido';
            }

            return $this->estaEnRiesgoAcademico($alumno)
                ? 'En riesgo · decisión pendiente'
                : 'Decisión pendiente';
        }

        $promedio = $alumno['promedio_final'] ?? null;

        if (!is_numeric($promedio)) {
            return 'Incompleto';
        }

        if ((float) $promedio < 6) {
            return 'En riesgo';
        }

        return (float) $promedio >= 9 ? 'Destacado' : 'Aprobado';
    }

    public function confirmarPromocion(int $inscripcionId, bool $promovido): void
    {
        if (!in_array($this->slug_nivel, ['primaria', 'secundaria'], true)) {
            return;
        }

        $fila = $this->concentrado['grupos']
            ->flatMap(fn(array $grupo) => $grupo['alumnos'])
            ->firstWhere('inscripcion_id', $inscripcionId);

        if (!$fila) {
            $this->addError('promocion', 'No se encontró al alumno dentro del concentrado actual.');
            return;
        }

        if (!($fila['completo'] ?? false) || !is_numeric($fila['promedio_final'] ?? null)) {
            $this->addError('promocion', 'No se puede confirmar la promoción mientras existan periodos, campos o materias pendientes.');
            return;
        }

        DecisionPromocionOficial::query()->updateOrCreate(
            [
                'inscripcion_id' => $inscripcionId,
                'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                'grado_id' => (int) $fila['grado_id'],
            ],
            [
                'nivel_id' => (int) $this->nivel->id,
                'grupo_id' => (int) $fila['grupo_id'],
                'generacion_id' => (int) $fila['generacion_id'],
                'promedio_final' => (float) $fila['promedio_final'],
                'promocion_sugerida' => $this->esPrimaria ? $fila['promocion_sugerida'] : null,
                'promocion_confirmada' => $promovido,
                'confirmada_por' => auth()->id(),
                'confirmada_at' => now(),
            ]
        );


        $this->dispatch('swal', [
            'title' => $promovido ? 'Promoción confirmada' : 'No promoción confirmada',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    public function formatearDecimal(null|int|float|string $valor): string
    {
        return PromedioExcel::formatear($valor, 1, '—');
    }

    public function exportarExcelGeneracion(): BinaryFileResponse
    {
        return $this->exportarExcel('generacion');
    }

    public function exportarExcelAlumno(): BinaryFileResponse
    {
        return $this->exportarExcel('alumno');
    }

    public function exportarExcel(string $alcance = 'generacion'): BinaryFileResponse
    {
        $alcance = in_array($alcance, ['generacion', 'alumno'], true) ? $alcance : 'generacion';

        if ($this->esBachillerato && $this->generacion_id === '') {
            throw ValidationException::withMessages([
                'generacion_id' => 'Selecciona una generación de bachillerato antes de exportar.',
            ]);
        }

        if ($alcance === 'alumno' && $this->alumno_seleccionado_id === null) {
            throw ValidationException::withMessages([
                'busqueda_alumno' => 'Selecciona un alumno antes de generar la exportación individual.',
            ]);
        }

        if ($this->esAnualBachillerato && !($this->contextoAnualBachillerato['valido'] ?? false)) {
            throw ValidationException::withMessages([
                'generacion_id' => implode(' ', $this->contextoAnualBachillerato['errores'] ?? [
                    'La generación no corresponde al ciclo escolar seleccionado.',
                ]),
            ]);
        }

        $concentrado = $this->construirConcentrado($alcance === 'alumno');

        if ($alcance === 'alumno' && (int) data_get($concentrado, 'resumen.total_alumnos', 0) === 0) {
            throw ValidationException::withMessages([
                'busqueda_alumno' => 'El alumno seleccionado no tiene promedios en el ciclo y filtros actuales.',
            ]);
        }

        $datosAlumno = $alcance === 'alumno' ? $this->datosAlumnoSeleccionado : null;

        $nombreArchivo = ($alcance === 'alumno' ? 'PROMEDIO_ALUMNO_' : ($this->esAnualBachillerato ? 'PROMEDIO_ANUAL_BACHILLERATO_' : 'PROMEDIOS_GENERALES_'))
            . Str::slug($this->nivel?->nombre ?? $this->slug_nivel, '_')
            . ($datosAlumno ? '_' . Str::slug($datosAlumno['alumno'], '_') : '')
            . '_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(
            new PromediosGeneralesExport(
                nivelNombre: $this->nivel?->nombre ?? 'Nivel',
                nivelSlug: $this->slug_nivel,
                esBachillerato: $this->esBachillerato,
                encabezadosPeriodos: $this->encabezadosPeriodos,
                resumen: $concentrado['resumen'],
                gruposPromedios: $concentrado['grupos'],
                modalidadBachillerato: $this->modalidad_bachillerato,
                filtros: [
                    'Nivel' => $this->nivel?->nombre ?? 'Sin nivel',
                    'Ciclo escolar' => $this->ciclo_escolar_id ?: 'Sin seleccionar',
                    'Generación' => $this->generacion_id ?: 'Todas',
                    'Grado' => $this->grado_id ?: 'Todos',
                    'Grupo' => $this->grupo_id ?: 'Todos',
                    'Alcance' => $alcance === 'alumno' ? 'Alumno seleccionado' : 'Generación / filtros actuales',
                    'Alumno' => $datosAlumno['alumno'] ?? 'Todos',
                    'Matrícula' => $datosAlumno['matricula'] ?? 'No aplica',
                    'Modalidad bachillerato' => $this->esBachillerato ? ($this->esAnualBachillerato ? 'Promedio anual' : 'Promedio semestral') : 'No aplica',
                    'Semestre' => !$this->esBachillerato
                        ? 'No aplica'
                        : ($this->esAnualBachillerato
                            ? 'Automático por ciclo y generación'
                            : ($this->semestre_id ?: 'Todos')),
                    'Orden' => match ($this->orden) {
                        'promedio_asc' => 'Promedio menor a mayor',
                        'nombre_asc' => 'Nombre A-Z',
                        default => 'Promedio mayor a menor',
                    },
                ],
            ),
            $nombreArchivo
        );
    }

    public function render()
    {
        return view('livewire.accion.generales.promedios-generales');
    }
}
