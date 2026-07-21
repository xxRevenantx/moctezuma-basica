<?php

namespace App\Livewire\Accion;

use App\Exports\CalificacionExport;
use App\Exports\PlantillaCalificacionesImportExport;
use App\Imports\CalificacionesImport;
use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as ModelsCalificacion;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use App\Services\GroqCalificacionService;
use App\Services\CalificacionCorreccionService;
use App\Services\CicloNivelGateService;
use App\Services\ListaAcademicaService;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Throwable;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Calificacion extends Component
{
    use WithFileUploads;

    public string $slug_nivel = '';

    public $nivel_id = null;
    public $generacion_id = null;
    public $grado_id = null;
    public $grupo_id = null;
    public $semestre_id = null;

    public $parcial_bachillerato_id = null;
    public $periodo_basica_id = null;

    public $periodo_id = null;
    public $ciclo_escolar_id = null;

    public string $busqueda = '';
    public string $filtro_estado = '';
    public string $orden_promedio = '';
    public string $mensajeContexto = '';

    public bool $contextoBusquedaGlobal = false;
    public ?int $alumnoBusquedaId = null;
    public ?int $periodoBusquedaGlobalId = null;

    public array $inscripciones = [];
    public array $inscripcionesTabla = [];
    public array $materias = [];
    public array $calificaciones = [];
    public array $calificacionesOriginales = [];
    public array $observaciones = [];
    public array $observacionesOriginales = [];
    public array $promedios = [];
    public array $promediosPrecisos = [];

    public bool $mostrarModalBitacora = false;
    public bool $mostrarModalRevision = false;

    public array $resumenRevision = [];
    public string $motivo_guardado = '';

    public $archivo_calificaciones = null;
    public array $resumenImportacion = [];

    public string $tipoDiagnosticoIa = 'pedagogico';
    public array $diagnosticoIa = [];
    public ?string $diagnosticoIaGeneradoEn = null;

    public $boleta_inscripcion_id = '';
    public $reconocimiento_inscripcion_id = '';

    public Collection $niveles;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public Collection $parciales;
    public Collection $periodosBasica;

    public ?array $periodoSeleccionado = null;

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->nivel_id = $nivel->id;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->generaciones = collect();
        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
        $this->periodosBasica = collect();

        $this->cargarCatalogos();
        $this->cargarContextoBusquedaGlobal();
    }

    private function cargarContextoBusquedaGlobal(): void
    {
        if (request()->string('origen')->toString() !== 'busqueda-global') {
            return;
        }

        $this->contextoBusquedaGlobal = true;
        $this->alumnoBusquedaId = request()->integer('alumno') ?: null;
        $this->periodoBusquedaGlobalId = request()->integer('periodo') ?: null;

        $generacionId = request()->integer('generacion');
        $gradoId = request()->integer('grado');
        $grupoId = request()->integer('grupo');
        $semestreId = request()->integer('semestre');

        $generacion = Generacion::query()
            ->whereKey($generacionId)
            ->where('nivel_id', $this->nivel_id)
            ->first();

        if (! $generacion) {
            $this->mensajeContexto = 'No fue posible restaurar la generación de la calificación seleccionada.';
            return;
        }

        if (! $this->generaciones->contains('id', $generacion->id)) {
            $this->generaciones->prepend($generacion);
        }

        $this->generacion_id = $generacion->id;
        $this->cargarGrados();

        if (! $this->grados->contains(fn ($grado) => (int) $grado->id === $gradoId)) {
            $this->mensajeContexto = 'El grado guardado en la calificación ya no pertenece al contexto académico actual.';
            return;
        }

        $this->grado_id = $gradoId;

        if ($this->esBachillerato) {
            $this->cargarSemestres();

            if (! $this->semestres->contains(fn ($semestre) => (int) $semestre->id === $semestreId)) {
                $this->mensajeContexto = 'El semestre de la calificación ya no está disponible.';
                return;
            }

            $this->semestre_id = $semestreId;
        }

        $this->cargarGrupos();

        if (! $this->grupos->contains(fn ($grupo) => (int) $grupo->id === $grupoId)) {
            $this->mensajeContexto = 'El grupo de la calificación ya no está disponible.';
            return;
        }

        $this->grupo_id = $grupoId;

        $periodo = $this->periodoBusquedaGlobalId
            ? Periodos::query()->find($this->periodoBusquedaGlobalId)
            : null;

        if ($this->esBachillerato) {
            $this->cargarParcialesDisponibles();
            $this->parcial_bachillerato_id = request()->integer('parcial')
                ?: $periodo?->parcial_bachillerato_id;
        } else {
            $this->periodo_basica_id = request()->integer('periodo_basica')
                ?: $periodo?->periodo_basica_id;
        }

        $buscar = trim((string) request('buscar', ''));

        if ($buscar === '' && $this->alumnoBusquedaId) {
            $buscar = (string) Inscripcion::withTrashed()
                ->whereKey($this->alumnoBusquedaId)
                ->value('matricula');
        }

        $this->busqueda = $buscar;

        if ($this->puedeCargarDatos()) {
            $this->cargarDatos();
        }
    }

    public function getEsBachilleratoProperty(): bool
    {
        return $this->slug_nivel === 'bachillerato';
    }

    public function cargarCatalogos(): void
    {
        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();

        // En bachillerato los parciales se cargan de forma dinámica,
        // únicamente cuando existe un periodo para la generación y semestre elegidos.
        $this->parciales = collect();

        $this->periodosBasica = PeriodosBasica::query()
            ->orderBy('periodo')
            ->get();
    }

    public function updatedGeneracionId($value = null): void
    {
        $this->resetEstadoAcademico([
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();

        if (blank($value)) {
            return;
        }

        $esValida = Generacion::query()
            ->whereKey($value)
            ->where('nivel_id', $this->nivel_id)
            ->where('status', 1)
            ->exists();

        if (!$esValida) {
            $this->generacion_id = null;
            $this->mensajeContexto = 'La generación seleccionada no pertenece al nivel actual o ya no está activa.';
            return;
        }

        $this->cargarGrados();
    }

    public function updatedGradoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();

        if (blank($value)) {
            return;
        }

        $esValido = $this->grados->contains(fn($grado) => (int) $grado->id === (int) $value);

        if (!$esValido) {
            $this->grado_id = null;
            $this->mensajeContexto = 'El grado seleccionado no corresponde a la generación actual.';
            return;
        }

        if ($this->esBachillerato) {
            $this->cargarSemestres();
            return;
        }

        $this->cargarGrupos();
    }

    public function updatedSemestreId($value = null): void
    {
        $this->resetEstadoAcademico([
            'grupo_id',
            'parcial_bachillerato_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        $this->grupos = collect();
        $this->parciales = collect();

        if (blank($value)) {
            return;
        }

        $esValido = $this->semestres->contains(fn($semestre) => (int) $semestre->id === (int) $value);

        if (!$esValido) {
            $this->semestre_id = null;
            $this->mensajeContexto = 'Ese semestre no tiene periodos registrados para la generación seleccionada.';
            return;
        }

        $this->cargarGrupos();
        $this->cargarParcialesDisponibles();
    }

    public function updatedGrupoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $esValido = $this->grupos->contains(fn($grupo) => (int) $grupo->id === (int) $value);

        if (!$esValido) {
            $this->grupo_id = null;
            $this->mensajeContexto = 'El grupo seleccionado no corresponde al contexto académico actual.';
            return;
        }

        if (!$this->esBachillerato) {
            return;
        }

        /*
         * En bachillerato no se cargan datos al seleccionar grupo.
         * Primero se debe seleccionar un parcial disponible.
         */
    }

    public function updatedParcialBachilleratoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $esValido = $this->parciales->contains(fn($parcial) => (int) $parcial->id === (int) $value);

        if (!$esValido) {
            $this->parcial_bachillerato_id = null;
            $this->mensajeContexto = 'El parcial seleccionado no tiene un periodo registrado para este semestre.';
            return;
        }

        $this->cargarDatos();
    }

    public function updatedPeriodoBasicaId($value = null): void
    {
        $this->resetEstadoAcademico([
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $this->cargarDatos();
    }

    public function updatedBusqueda(): void
    {
        if ($this->puedeCargarDatos()) {
            $this->cargarDatos();
        }
    }

    public function updatedFiltroEstado(): void
    {
        $this->aplicarFiltroEstado();
    }

    public function updatedOrdenPromedio(): void
    {
        $this->aplicarFiltroEstado();
    }


    public function updatedCalificaciones($value = null, $key = null): void
    {
        $this->limpiarDiagnosticoIa();

        /*
         * Solo se recalculan promedios cuando Livewire recibe el cambio.
         * Con wire:model.blur ya no se ejecuta en cada tecla.
         */
        $this->calcularPromedios();

        /*
         * Solo se reaplica el filtro si realmente hay un filtro activo.
         * Esto evita recorrer toda la tabla innecesariamente.
         */
        if ($this->filtro_estado !== '' || $this->orden_promedio !== '') {
            $this->aplicarFiltroEstado();
        }
    }

    private function resetEstadoAcademico(array $camposExtra = []): void
    {
        $campos = array_merge($camposExtra, [
            'periodo_id',
            'ciclo_escolar_id',
            'periodoSeleccionado',
            'filtro_estado',
            'orden_promedio',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'archivo_calificaciones',
            'resumenImportacion',
            'diagnosticoIa',
            'diagnosticoIaGeneradoEn',
            'mensajeContexto',
        ]);

        $this->reset($campos);
    }

    private function puedeCargarDatos(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->semestre_id)
                && filled($this->grupo_id)
                && filled($this->parcial_bachillerato_id);
        }

        return filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && filled($this->periodo_basica_id);
    }

    private function cargarGrados(): void
    {
        if (blank($this->generacion_id)) {
            $this->grados = collect();
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->whereHas('grupos', function ($query) {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('generacion_id', $this->generacion_id);
            })
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        if ($this->grados->isEmpty()) {
            $this->mensajeContexto = 'La generación seleccionada no tiene grados o grupos configurados.';
        }
    }

    private function cargarSemestres(): void
    {
        if (
            !$this->esBachillerato
            || blank($this->generacion_id)
            || blank($this->grado_id)
        ) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->whereHas('grupos', function ($query) {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('generacion_id', $this->generacion_id)
                    ->where('grado_id', $this->grado_id);
            })
            ->whereHas('periodosBachillerato', function ($query) {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('generacion_id', $this->generacion_id);
            })
            ->orderBy('numero')
            ->get();

        if ($this->semestres->isEmpty()) {
            $this->mensajeContexto = 'No existen periodos de bachillerato configurados para esta generación y grado.';
        }
    }

    private function cargarParcialesDisponibles(): void
    {
        $this->parciales = collect();

        if (
            !$this->esBachillerato
            || blank($this->generacion_id)
            || blank($this->semestre_id)
        ) {
            return;
        }

        $this->parciales = Parcial::query()
            ->whereHas('periodos', function ($query) {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('generacion_id', $this->generacion_id)
                    ->where('semestre_id', $this->semestre_id);
            })
            ->orderBy('parcial')
            ->get();

        if ($this->parciales->isEmpty()) {
            $this->mensajeContexto = 'El semestre seleccionado no tiene parciales registrados en Periodos.';
        }
    }

    private function cargarGrupos(): void
    {
        $this->grupos = collect();

        if (blank($this->generacion_id) || blank($this->grado_id)) {
            return;
        }

        if ($this->esBachillerato && blank($this->semestre_id)) {
            return;
        }

        $this->grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->where('grupos.nivel_id', $this->nivel_id)
            ->where('grupos.generacion_id', $this->generacion_id)
            ->where('grupos.grado_id', $this->grado_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query->where('grupos.semestre_id', $this->semestre_id),
                fn($query) => $query->whereNull('grupos.semestre_id')
            )
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get();

        if ($this->grupos->isEmpty()) {
            $this->mensajeContexto = 'No existen grupos configurados para la generación, grado y semestre seleccionados.';
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'generacion_id',
            'grado_id',
            'grupo_id',
            'semestre_id',
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'periodo_id',
            'ciclo_escolar_id',
            'periodoSeleccionado',
            'busqueda',
            'filtro_estado',
            'orden_promedio',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'archivo_calificaciones',
            'resumenImportacion',
            'diagnosticoIa',
            'diagnosticoIaGeneradoEn',
            'boleta_inscripcion_id',
            'reconocimiento_inscripcion_id',
            'mensajeContexto',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
        $this->parciales = collect();
    }

    public function cargarDatos(): void
    {
        $this->reset([
            'periodo_id',
            'ciclo_escolar_id',
            'periodoSeleccionado',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'archivo_calificaciones',
            'resumenImportacion',
            'diagnosticoIa',
            'diagnosticoIaGeneradoEn',
            'mensajeContexto',
        ]);

        if (!$this->puedeCargarDatos()) {
            $this->mensajeContexto = $this->esBachillerato
                ? 'Selecciona una generación, grado, semestre, grupo y parcial válidos.'
                : 'Selecciona una generación, grado, grupo y periodo válidos.';
            return;
        }

        $this->cargarPeriodoSeleccionado();

        if (blank($this->periodo_id)) {
            $this->mensajeContexto = 'No existe un periodo registrado para la generación, semestre y parcial seleccionados.';
            return;
        }

        $this->cargarInscripciones();
        $this->cargarMaterias();

        if (empty($this->inscripciones)) {
            $this->mensajeContexto = 'El contexto seleccionado no tiene alumnos activos asignados.';
        } elseif (empty($this->materias)) {
            $this->mensajeContexto = 'El grupo seleccionado no tiene materias calificables asignadas para el ciclo escolar del periodo.';
        }

        $this->cargarCalificaciones();
        $this->calcularPromedios();
        $this->aplicarFiltroEstado();
    }

    public function cargarPeriodoSeleccionado(): void
    {
        $query = Periodos::query()
            ->with(['cicloEscolar', 'mesesBasica', 'periodoBasica', 'mesesBachillerato', 'parcialBachillerato'])
            ->where('nivel_id', $this->nivel_id);

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

        if (!$periodo) {
            $this->periodo_id = null;
            $this->ciclo_escolar_id = null;
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
        if (!blank($periodo->ciclo_escolar_id)) {
            return (int) $periodo->ciclo_escolar_id;
        }

        return CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->value('id');
    }

    private function obtenerGrupoIdsEquivalentes(): array
    {
        if (blank($this->grupo_id)) {
            return [];
        }

        $grupoSeleccionado = Grupo::query()
            ->select('id', 'nivel_id', 'grado_id', 'generacion_id', 'asignacion_grupo_id')
            ->find($this->grupo_id);

        if (!$grupoSeleccionado) {
            return [(int) $this->grupo_id];
        }

        /*
         * En bachillerato se buscan todos los grupos equivalentes.
         * Esto permite cargar alumnos del mismo grupo lógico, aunque estén
         * registrados en otro semestre.
         */
        if ($this->esBachillerato) {
            return Grupo::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('grado_id', $this->grado_id)
                ->where('asignacion_grupo_id', $grupoSeleccionado->asignacion_grupo_id)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->toArray();
        }

        return [(int) $this->grupo_id];
    }
    private function obtenerGrupoIdsParaAlumnos(): array
    {
        if (blank($this->grupo_id)) {
            return [];
        }

        $grupoSeleccionado = Grupo::query()
            ->select('id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'asignacion_grupo_id')
            ->find($this->grupo_id);

        if (!$grupoSeleccionado) {
            return [(int) $this->grupo_id];
        }

        /*
         * En bachillerato se toman todos los grupos equivalentes de la
         * generación, incluso si el alumno ya avanzó de grado o semestre.
         * La inscripción guarda la ubicación actual del alumno; por eso,
         * limitar por grado/semestre impediría consultar periodos históricos.
         */
        if ($this->esBachillerato) {
            return Grupo::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('generacion_id', $this->generacion_id)
                ->where('asignacion_grupo_id', $grupoSeleccionado->asignacion_grupo_id)
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->values()
                ->toArray();
        }

        return [(int) $this->grupo_id];
    }
    private function cargarInscripciones(): void
    {
        $grupoIds = $this->obtenerGrupoIdsParaAlumnos();

        if (empty($grupoIds) || blank($this->ciclo_escolar_id)) {
            $this->inscripciones = [];
            $this->inscripcionesTabla = [];
            return;
        }

        $fechaCorte = $this->periodoSeleccionado['fecha_fin']
            ?? $this->periodoSeleccionado['fecha_inicio']
            ?? now()->toDateString();

        $alumnos = app(ListaAcademicaService::class)->alumnosPorContexto(
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            grupoIds: $grupoIds,
            fechaCorte: $fechaCorte,
            nivelId: (int) $this->nivel_id,
            gradoId: $this->esBachillerato ? null : (int) $this->grado_id,
            generacionId: (int) $this->generacion_id,
            semestreId: null,
        );

        if (filled($this->busqueda)) {
            $buscar = mb_strtolower(trim($this->busqueda));
            $alumnos = $alumnos->filter(function ($alumno) use ($buscar) {
                $texto = mb_strtolower(trim(
                    ($alumno->matricula ?? '') . ' ' .
                    ($alumno->nombre ?? '') . ' ' .
                    ($alumno->apellido_paterno ?? '') . ' ' .
                    ($alumno->apellido_materno ?? '')
                ));

                return str_contains($texto, $buscar);
            })->values();
        }

        $this->inscripciones = $alumnos
            ->map(function ($inscripcion) {
                return [
                    'inscripcion_id' => (int) $inscripcion->id,
                    'matricula' => $inscripcion->matricula ?? 'SIN MATRÍCULA',
                    'alumno' => trim(
                        ($inscripcion->apellido_paterno ?? '') . ' ' .
                        ($inscripcion->apellido_materno ?? '') . ' ' .
                        ($inscripcion->nombre ?? '')
                    ),
                    'estatus_historico' => $inscripcion->getAttribute('estatus_historico') ?? 'activo',
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
            ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA)
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
        $this->resetErrorBag('calificaciones');

        if (!$this->puedeGuardar) {
            $this->addError('calificaciones', 'Selecciona todos los filtros requeridos antes de guardar.');
            return;
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

    public function guardarCalificaciones(CalificacionCorreccionService $correcciones, CicloNivelGateService $gate): void
    {
        $this->resetErrorBag('calificaciones');

        if (!$this->puedeGuardar) {
            $this->addError('calificaciones', 'Selecciona todos los filtros requeridos antes de guardar.');
            return;
        }

        if (blank($this->ciclo_escolar_id)) {
            $this->addError('calificaciones', 'No se pudo determinar el ciclo escolar para guardar las calificaciones.');
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);
        $gate->asegurar((int) $this->ciclo_escolar_id, (int) $this->nivel_id, 'calificaciones');

        $this->validate($this->reglasCalificaciones(), $this->mensajesCalificaciones());

        if ($ciclo?->cerrado_at) {
            $this->solicitarCorreccionesHistoricas($correcciones);
            return;
        }

        DB::transaction(function () {
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

                    $condiciones = [
                        'periodo_id' => $this->periodo_id,
                        'inscripcion_id' => (int) $inscripcionId,
                        'asignacion_materia_id' => (int) $asignacionMateriaId,
                    ];

                    if ($valorNuevo === null) {
                        $calificacion = ModelsCalificacion::query()
                            ->where($condiciones)
                            ->first();

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

                    $existe = ModelsCalificacion::query()
                        ->where($condiciones)
                        ->exists();

                    $accion = $existe ? 'editar' : 'crear';

                    ModelsCalificacion::query()->updateOrCreate(
                        $condiciones,
                        [
                            'nivel_id' => $this->nivel_id,
                            'grado_id' => $this->grado_id,
                            'grupo_id' => $this->grupo_id,
                            'ciclo_escolar_id' => $this->ciclo_escolar_id,
                            'generacion_id' => $this->generacion_id,
                            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
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
        });

        $this->calificacionesOriginales = $this->calificaciones;
        $this->observacionesOriginales = $this->observaciones;
        $this->mostrarModalRevision = false;
        $this->motivo_guardado = '';

        $this->calcularPromedios();
        $this->aplicarFiltroEstado();

        $this->dispatch('calificaciones-internas-guardadas');

        $this->dispatch('swal', [
            'title' => '¡Calificaciones guardadas correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function solicitarCorreccionesHistoricas(CalificacionCorreccionService $service): void
    {
        if (mb_strlen(trim($this->motivo_guardado)) < 10) {
            $this->addError('calificaciones', 'Para un ciclo cerrado debes escribir un motivo de corrección de al menos 10 caracteres.');
            return;
        }

        $periodo = Periodos::query()->findOrFail((int) $this->periodo_id);
        $solicitadas = 0;

        DB::transaction(function () use ($service, $periodo, &$solicitadas): void {
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

                    $alumno = Inscripcion::query()->findOrFail((int) $inscripcionId);
                    $calificacion = ModelsCalificacion::query()
                        ->where('periodo_id', $periodo->id)
                        ->where('inscripcion_id', $alumno->id)
                        ->where('asignacion_materia_id', (int) $asignacionMateriaId)
                        ->first();

                    $propuesto = [
                        'accion' => $valorNuevo === null ? 'eliminar' : ($calificacion ? 'actualizar' : 'crear'),
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

                    $service->solicitar(
                        $alumno,
                        $periodo,
                        $calificacion,
                        $propuesto,
                        trim($this->motivo_guardado),
                        Auth::id(),
                    );
                    $solicitadas++;
                }
            }
        });

        $this->mostrarModalRevision = false;
        $this->motivo_guardado = '';
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Correcciones enviadas a autorización',
            'text' => "Se registraron {$solicitadas} solicitud(es). Las calificaciones del ciclo cerrado no fueron modificadas todavía.",
            'position' => 'top-end',
        ]);
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
        return filled($this->periodo_id)
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
        return $this->puedeGuardar;
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

    /**
     * Construye un resumen grupal sin nombres, matrículas ni calificaciones individuales.
     *
     * @return array<string, mixed>
     */
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

    private function periodoSeleccionadoValido(): bool
    {
        if (blank($this->periodo_id) || blank($this->ciclo_escolar_id)) {
            return false;
        }

        $query = Periodos::query()
            ->whereKey((int) $this->periodo_id)
            ->where('nivel_id', (int) $this->nivel_id);

        if ($this->esBachillerato) {
            return $query
                ->where('generacion_id', (int) $this->generacion_id)
                ->where('semestre_id', (int) $this->semestre_id)
                ->where('parcial_bachillerato_id', (int) $this->parcial_bachillerato_id)
                ->exists();
        }

        return $query
            ->where('periodo_basica_id', (int) $this->periodo_basica_id)
            ->exists();
    }

    private function tipoPeriodoImportacion(): string
    {
        return $this->esBachillerato ? 'bachillerato' : 'basica';
    }

    private function periodoReferenciaIdImportacion(): int
    {
        return $this->esBachillerato
            ? (int) $this->parcial_bachillerato_id
            : (int) $this->periodo_basica_id;
    }

    private function etiquetaPeriodoParaArchivo(): string
    {
        if (!$this->periodoSeleccionado) {
            return $this->esBachillerato ? 'sin_parcial' : 'sin_periodo';
        }

        if ($this->esBachillerato) {
            return $this->periodoSeleccionado['parcial'] ?? 'sin_parcial';
        }

        return $this->periodoSeleccionado['parcial'] ?? 'sin_periodo';
    }

    private function nombreArchivoPlantillaImportacion(string $nivel, string $grado, string $grupo): string
    {
        $segmentoPeriodo = $this->esBachillerato ? 'PARCIAL' : 'PERIODO';

        return 'PLANTILLA_IMPORTAR_CALIFICACIONES_' .
            Str::slug($nivel, '_') .
            '_GRADO_' . Str::slug($grado, '_') .
            '_GRUPO_' . Str::slug($grupo, '_') .
            '_' . $segmentoPeriodo . '_' . Str::slug($this->etiquetaPeriodoParaArchivo(), '_') .
            '_P' . ((int) $this->periodo_id) .
            '.xlsx';
    }

    public function descargarPlantillaImportacion()
    {
        if (!$this->puedeUsarPlantillaImportacion) {
            $this->dispatch('swal', [
                'title' => 'Selecciona todos los filtros antes de descargar la plantilla.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        if (!$this->periodoSeleccionadoValido()) {
            $this->dispatch('swal', [
                'title' => 'El periodo seleccionado no coincide con los filtros actuales.',
                'text' => 'Vuelve a seleccionar el periodo o parcial y descarga nuevamente la plantilla.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        $nivel = Nivel::query()->find($this->nivel_id);
        $grado = Grado::query()->find($this->grado_id);
        $grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);
        $generacion = Generacion::query()->find($this->generacion_id);
        $semestre = $this->esBachillerato ? Semestre::query()->find($this->semestre_id) : null;

        $contexto = [
            'nivel_id' => (int) $this->nivel_id,
            'grado_id' => (int) $this->grado_id,
            'grupo_id' => (int) $this->grupo_id,
            'generacion_id' => (int) $this->generacion_id,
            'semestre_id' => $this->esBachillerato ? (int) $this->semestre_id : 0,
            'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
            'periodo_id' => (int) $this->periodo_id,
            'tipo_periodo' => $this->tipoPeriodoImportacion(),
            'periodo_referencia_id' => $this->periodoReferenciaIdImportacion(),
            'numero_materias_promediar' => $this->esBachillerato
                ? (int) ($this->obtenerNumeroMateriasPromediar() ?? 0)
                : null,

            'nivel' => $nivel?->nombre,
            'grado' => $grado?->nombre,
            'grupo' => $this->textoGrupo($grupo),
            'generacion' => $generacion
                ? trim(($generacion->anio_ingreso ?? '') . ' - ' . ($generacion->anio_egreso ?? ''))
                : null,
            'semestre' => $semestre?->numero,
            'periodo' => $this->etiquetaPeriodoParaArchivo(),
        ];

        $nombreArchivo = $this->nombreArchivoPlantillaImportacion(
            nivel: $nivel?->nombre ?? $this->slug_nivel,
            grado: $grado?->nombre ?? 'grado',
            grupo: $this->textoGrupo($grupo)
        );

        return Excel::download(
            new PlantillaCalificacionesImportExport(
                inscripciones: $this->inscripciones,
                materias: $this->materias,
                calificaciones: $this->calificaciones,
                observaciones: $this->observaciones,
                contexto: $contexto
            ),
            $nombreArchivo
        );
    }

    public function importarPlantillaCalificaciones(): void
    {
        if (!$this->puedeUsarPlantillaImportacion) {
            $this->addError('archivo_calificaciones', 'Selecciona todos los filtros antes de importar calificaciones.');
            return;
        }

        if (!$this->periodoSeleccionadoValido()) {
            $this->addError('archivo_calificaciones', 'El periodo seleccionado no coincide con los filtros actuales. Vuelve a seleccionar el periodo o parcial.');
            return;
        }

        $this->validate([
            'archivo_calificaciones' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'archivo_calificaciones.required' => 'Selecciona una plantilla de calificaciones.',
            'archivo_calificaciones.file' => 'El archivo seleccionado no es válido.',
            'archivo_calificaciones.mimes' => 'El archivo debe ser Excel: xlsx o xls.',
            'archivo_calificaciones.max' => 'El archivo no debe pesar más de 10 MB.',
        ]);

        try {
            $import = new CalificacionesImport(
                nivelId: (int) $this->nivel_id,
                gradoId: (int) $this->grado_id,
                grupoId: (int) $this->grupo_id,
                generacionId: (int) $this->generacion_id,
                semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                periodoId: (int) $this->periodo_id,
                esBachillerato: $this->esBachillerato,
                tipoPeriodo: $this->tipoPeriodoImportacion(),
                periodoReferenciaId: $this->periodoReferenciaIdImportacion(),
                inscripcionIdsPermitidas: collect($this->inscripciones)
                    ->pluck('inscripcion_id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all(),
                materiasPermitidas: $this->materias,
                userId: Auth::id(),
                ip: request()->ip(),
                motivo: 'Importación desde plantilla Excel'
            );

            Excel::import($import, $this->archivo_calificaciones);

            $this->resumenImportacion = $import->resumen;
            $this->reset('archivo_calificaciones');
            $this->cargarDatos();

            $this->dispatch('swal', [
                'title' => 'Calificaciones importadas correctamente.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (ValidationException $e) {
            $errores = collect($e->errors())
                ->flatten()
                ->values()
                ->all();

            $this->resumenImportacion = [
                'creadas' => 0,
                'editadas' => 0,
                'eliminadas' => 0,
                'sin_cambios' => 0,
                'errores' => $errores,
            ];

            $this->addError(
                'archivo_calificaciones',
                'La plantilla tiene errores. Revisa el resumen mostrado debajo del formulario.'
            );

            $this->dispatch('swal', [
                'title' => 'La plantilla tiene errores.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        } catch (Throwable $e) {
            report($e);

            $this->addError(
                'archivo_calificaciones',
                'Ocurrió un error al importar la plantilla. Verifica el archivo e inténtalo nuevamente.'
            );

            $this->dispatch('swal', [
                'title' => 'No se pudo importar la plantilla.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function exportarCalificaciones()
    {
        if (!$this->puedeExportarPdf) {
            $this->dispatch('swal', [
                'title' => 'Selecciona todos los filtros antes de exportar.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        $grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);

        $nombreNivel = mb_strtoupper(Nivel::query()->where('id', $this->nivel_id)->value('nombre') ?? $this->slug_nivel ?? 'NIVEL');
        $nombreGrado = Grado::query()->where('id', $this->grado_id)->value('nombre') ?? 'GRADO';
        $nombreGrupo = $this->textoGrupo($grupo);

        $nombreArchivo = 'CALIFICACIONES_' .
            Str::slug($nombreNivel, '_') .
            '_GRADO_' . Str::slug($nombreGrado, '_') .
            '_GRUPO_' . Str::slug($nombreGrupo, '_') .
            '_PERIODO_' . ($this->periodo_id ?? 'SIN_PERIODO') .
            '.xlsx';

        return Excel::download(
            new CalificacionExport(
                nivel_id: $this->nivel_id ? (int) $this->nivel_id : null,
                grado_id: $this->grado_id ? (int) $this->grado_id : null,
                grupo_id: $this->grupo_id ? (int) $this->grupo_id : null,
                periodo_id: $this->periodo_id ? (int) $this->periodo_id : null,
                semestre_id: $this->semestre_id ? (int) $this->semestre_id : null,
                generacion_id: $this->generacion_id ? (int) $this->generacion_id : null,
                esBachillerato: $this->esBachillerato,
                busqueda: $this->busqueda ?? ''
            ),
            $nombreArchivo
        );
    }


    private function alumnoTieneCalificacionesNumericas(int $inscripcionId): bool
    {
        $materiasOrdenadas = $this->obtenerMateriasOrdenadasParaPromedio();

        if ($inscripcionId <= 0 || $materiasOrdenadas->isEmpty()) {
            return false;
        }

        foreach ($materiasOrdenadas as $materia) {
            $asignacionMateriaId = (int) ($materia['id'] ?? 0);

            if ($asignacionMateriaId <= 0) {
                continue;
            }

            $valor = $this->calificaciones[$inscripcionId][$asignacionMateriaId] ?? null;

            if ($this->esCalificacionNumerica($valor)) {
                return true;
            }
        }

        return false;
    }


    public function render()
    {
        return view('livewire.accion.calificacion', [
            'hayCambios' => $this->hayCambios,
            'graficasCalificaciones' => $this->graficasCalificaciones,
        ]);
    }
}
