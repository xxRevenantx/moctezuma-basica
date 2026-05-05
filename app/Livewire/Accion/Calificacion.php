<?php

namespace App\Livewire\Accion;

use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as ModelsCalificacion;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

use App\Exports\CalificacionExport;
use Maatwebsite\Excel\Facades\Excel;

class Calificacion extends Component
{
    public string $slug_nivel = '';

    public $boleta_inscripcion_id = '';

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

    public array $inscripciones = [];
    public array $inscripcionesTabla = [];
    public array $materias = [];
    public array $calificaciones = [];
    public array $calificacionesOriginales = [];
    public array $observaciones = [];
    public array $observacionesOriginales = [];
    public array $promedios = [];

    public bool $mostrarModalBitacora = false;
    public bool $mostrarModalRevision = false;
    public array $resumenRevision = [];
    public string $motivo_guardado = '';

    public $diploma_inscripcion_id = '';

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
    }

    public function getPuedeExportarDiplomaProperty()
    {
        return !empty($this->diploma_inscripcion_id)
            && !empty($this->periodo_id)
            && !empty($this->generacion_id)
            && !empty($this->grado_id)
            && !empty($this->grupo_id)
            && (!$this->esBachillerato || !empty($this->semestre_id));
    }

    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel_id === 4;
    }

    public function cargarCatalogos(): void
    {
        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->grados = collect();
        $this->grupos = collect();

        $this->semestres = Semestre::query()
            ->orderBy('numero')
            ->get();

        $this->parciales = Parcial::query()
            ->orderBy('parcial')
            ->get();

        $this->periodosBasica = PeriodosBasica::query()
            ->orderBy('periodo')
            ->get();
    }

    public function updatedGeneracionId(): void
    {
        $this->resetEstadoAcademico(['grado_id', 'grupo_id', 'semestre_id', 'parcial_bachillerato_id', 'periodo_basica_id']);
        $this->cargarGrados();
    }

    public function updatedGradoId(): void
    {
        $this->resetEstadoAcademico(['grupo_id', 'semestre_id', 'parcial_bachillerato_id', 'periodo_basica_id']);
        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->resetEstadoAcademico(['grupo_id', 'parcial_bachillerato_id']);
        $this->cargarGrupos();
    }

    public function updatedGrupoId(): void
    {
        $this->resetEstadoAcademico(['parcial_bachillerato_id', 'periodo_basica_id']);

        if (!$this->esBachillerato) {
            $this->cargarDatos();
        }
    }

    public function updatedParcialBachilleratoId(): void
    {
        $this->resetEstadoAcademico();
        $this->cargarDatos();
    }

    public function updatedPeriodoBasicaId(): void
    {
        $this->resetEstadoAcademico();
        $this->cargarDatos();
    }

    public function updatedBusqueda(): void
    {
        $this->cargarDatos();
    }

    public function updatedFiltroEstado(): void
    {
        $this->aplicarFiltroEstado();
    }

    private function resetEstadoAcademico(array $camposExtra = []): void
    {
        $campos = array_merge($camposExtra, [
            'periodo_id',
            'ciclo_escolar_id',
            'periodoSeleccionado',
            'busqueda',
            'filtro_estado',
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
        ]);

        $this->reset($campos);
    }

    private function cargarGrados(): void
    {
        if (blank($this->generacion_id)) {
            $this->grados = collect();
            return;
        }

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    private function cargarGrupos(): void
    {
        if (blank($this->generacion_id) || blank($this->grado_id)) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->when($this->esBachillerato, fn($query) => $query->where('semestre_id', $this->semestre_id))
            ->orderBy('nombre')
            ->get();
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
            'inscripciones',
            'inscripcionesTabla',
            'materias',
            'calificaciones',
            'calificacionesOriginales',
            'observaciones',
            'observacionesOriginales',
            'promedios',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'diploma_inscripcion_id',
        ]);

        $this->boleta_inscripcion_id = '';
        $this->diploma_inscripcion_id = '';

        $this->grados = collect();
        $this->grupos = collect();
    }

    public function getPuedeExportarBoletaProperty(): bool
    {
        return !empty($this->slug_nivel)
            && !empty($this->generacion_id)
            && !empty($this->grado_id)
            && !empty($this->grupo_id)
            && !empty($this->periodo_id)
            && !empty($this->boleta_inscripcion_id)
            && (!$this->esBachillerato || !empty($this->semestre_id));
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
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
        ]);

        if ($this->esBachillerato) {
            if (blank($this->generacion_id) || blank($this->grado_id) || blank($this->semestre_id) || blank($this->grupo_id) || blank($this->parcial_bachillerato_id)) {
                return;
            }
        } else {
            if (blank($this->generacion_id) || blank($this->grado_id) || blank($this->grupo_id) || blank($this->periodo_basica_id)) {
                return;
            }
        }

        $this->cargarPeriodoSeleccionado();

        if (blank($this->periodo_id)) {
            return;
        }

        $this->cargarInscripciones();
        $this->cargarMaterias();
        $this->cargarCalificaciones();
        $this->calcularPromedios();
        $this->aplicarFiltroEstado();
    }

    public function cargarPeriodoSeleccionado(): void
    {
        $query = Periodos::query()
            ->with(['cicloEscolar', 'mesesBasica', 'periodoBasica', 'mesesBachillerato', 'parcialBachillerato'])
            ->where('nivel_id', $this->nivel_id);

        if ($this->esBachillerato) {
            $query->where('generacion_id', $this->generacion_id)
                ->where('semestre_id', $this->semestre_id)
                ->where('parcial_bachillerato_id', $this->parcial_bachillerato_id);
        } else {
            // En básica el periodo es global.
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

        return \App\Models\cicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->value('id');
    }

    private function cargarInscripciones(): void
    {
        $query = Inscripcion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where(function ($q) {
                $q->where('activo', true)
                    ->orWhere('activo', 1);
            });

        if ($this->esBachillerato) {
            $query->where('semestre_id', $this->semestre_id);
        }

        if (filled($this->busqueda)) {
            $busqueda = '%' . trim($this->busqueda) . '%';

            $query->where(function ($q) use ($busqueda) {
                $q->where('matricula', 'like', $busqueda)
                    ->orWhere('nombre', 'like', $busqueda)
                    ->orWhere('apellido_paterno', 'like', $busqueda)
                    ->orWhere('apellido_materno', 'like', $busqueda)
                    ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', IFNULL(apellido_materno, '')) LIKE ?", [$busqueda])
                    ->orWhereRaw("CONCAT(apellido_paterno, ' ', IFNULL(apellido_materno, ''), ' ', nombre) LIKE ?", [$busqueda]);
            });
        }

        $this->inscripciones = $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get()
            ->map(function ($inscripcion) {
                return [
                    'inscripcion_id' => $inscripcion->id,
                    'matricula' => $inscripcion->matricula ?? 'SIN MATRÍCULA',
                    'alumno' => trim(($inscripcion->apellido_paterno ?? '') . ' ' . ($inscripcion->apellido_materno ?? '') . ' ' . ($inscripcion->nombre ?? '')),
                ];
            })
            ->values()
            ->toArray();

        $this->inscripcionesTabla = $this->inscripciones;
    }

    private function cargarMaterias(): void
    {
        $query = AsignacionMateria::query()
            ->with(['profesor'])
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('calificable', 1);

        if ($this->esBachillerato) {
            // La tabla asignacion_materias usa la columna semestre, no semestre_id.
            $query->where('semestre', $this->semestre_id);
        }

        $this->materias = $query
            ->orderBy('orden')
            ->orderBy('materia')
            ->get()
            ->map(function ($materia) {
                return [
                    'id' => $materia->id,
                    'materia' => $materia->materia ?? 'Materia',
                    'profesor' => $materia->profesor
                        ? trim(($materia->profesor->nombre ?? '') . ' ' . ($materia->profesor->apellido_paterno ?? '') . ' ' . ($materia->profesor->apellido_materno ?? ''))
                        : 'SIN PROFESOR ASIGNADO',
                ];
            })
            ->values()
            ->toArray();
    }

    private function cargarCalificaciones(): void
    {
        if (empty($this->inscripciones) || empty($this->materias) || blank($this->periodo_id) || blank($this->ciclo_escolar_id)) {
            return;
        }

        $inscripcionIds = collect($this->inscripciones)->pluck('inscripcion_id')->values()->all();
        $materiaIds = collect($this->materias)->pluck('id')->values()->all();

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
            ->whereIn('asignacion_materia_id', $materiaIds)
            ->get();

        foreach ($this->inscripciones as $fila) {
            $inscripcionId = (int) $fila['inscripcion_id'];

            foreach ($this->materias as $materia) {
                $materiaId = (int) $materia['id'];

                $calificacion = $calificacionesGuardadas
                    ->where('inscripcion_id', $inscripcionId)
                    ->where('asignacion_materia_id', $materiaId)
                    ->first();

                $valor = $calificacion?->calificacion;
                $observacion = $calificacion?->observacion;

                $this->calificaciones[$inscripcionId][$materiaId] = $valor !== null ? (string) $valor : '';
                $this->calificacionesOriginales[$inscripcionId][$materiaId] = $valor !== null ? (string) $valor : '';
                $this->observaciones[$inscripcionId][$materiaId] = $observacion !== null ? (string) $observacion : '';
                $this->observacionesOriginales[$inscripcionId][$materiaId] = $observacion !== null ? (string) $observacion : '';
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
        return $valor === '' ? null : $valor;
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

        $numero = (float) $valor;
        return $numero >= 0 && $numero <= 10;
    }

    private function obtenerValorNumerico($valor): ?float
    {
        return $this->esCalificacionNumerica($valor) ? (float) $this->normalizarCalificacion($valor) : null;
    }

    private function validarCalificacionPermitida($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null) {
            return true;
        }

        return $this->esCalificacionEspecial($valor) || $this->esCalificacionNumerica($valor);
    }

    private function tipoValorCalificacion($valor): string
    {
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null) {
            return 'vacio';
        }

        if ($this->esCalificacionEspecial($valor)) {
            return 'especial';
        }

        if ($this->esCalificacionNumerica($valor)) {
            return 'numerico';
        }

        return 'invalido';
    }

    public function abrirRevisionGuardado(): void
    {
        $this->resetErrorBag();

        if (!$this->puedeGuardar) {
            $this->addError('calificaciones', 'Selecciona todos los filtros requeridos antes de guardar.');
            return;
        }

        if (blank($this->ciclo_escolar_id)) {
            $this->addError('calificaciones', 'No se pudo determinar el ciclo escolar para guardar las calificaciones.');
            return;
        }

        foreach ($this->calificaciones as $materiasAlumno) {
            foreach ($materiasAlumno as $valor) {
                if (!$this->validarCalificacionPermitida($valor)) {
                    $this->addError('calificaciones', 'Hay calificaciones inválidas. Solo se permite 0 a 10 o claves: AC, ED, RA, NP, SD.');
                    return;
                }
            }
        }

        $this->resumenRevision = $this->generarResumenRevision();
        $this->mostrarModalRevision = true;
    }

    public function cerrarRevisionGuardado(): void
    {
        $this->mostrarModalRevision = false;
        $this->motivo_guardado = '';
    }

    private function generarResumenRevision(): array
    {
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

                $filaAlumno = collect($this->inscripciones)->firstWhere('inscripcion_id', (int) $inscripcionId);
                $filaMateria = collect($this->materias)->firstWhere('id', (int) $asignacionMateriaId);

                $cambios[] = [
                    'inscripcion_id' => (int) $inscripcionId,
                    'asignacion_materia_id' => (int) $asignacionMateriaId,
                    'alumno' => $filaAlumno['alumno'] ?? 'Sin alumno',
                    'matricula' => $filaAlumno['matricula'] ?? '—',
                    'materia' => $filaMateria['materia'] ?? 'Sin materia',
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo,
                    'tipo' => $this->tipoValorCalificacion($valorNuevo),
                    'observacion' => $observacionNueva,
                ];
            }
        }

        $coleccion = collect($cambios);

        return [
            'total' => $coleccion->count(),
            'numericas' => $coleccion->where('tipo', 'numerico')->count(),
            'especiales' => $coleccion->where('tipo', 'especial')->count(),
            'vacias' => $coleccion->where('tipo', 'vacio')->count(),
            'reprobatorias' => $coleccion->filter(fn($item) => is_numeric($item['nuevo']) && (float) $item['nuevo'] < 6)->count(),
            'alumnos_afectados' => $coleccion->pluck('inscripcion_id')->unique()->count(),
            'materias_afectadas' => $coleccion->pluck('asignacion_materia_id')->unique()->count(),
            'cambios' => $cambios,
        ];
    }

    public function guardarCalificaciones(): void
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

        $this->validate($this->reglasCalificaciones(), $this->mensajesCalificaciones());

        DB::transaction(function () {
            foreach ($this->calificaciones as $inscripcionId => $materias) {
                foreach ($materias as $materiaId => $valorNuevo) {
                    $valorNuevo = $this->normalizarCalificacion($valorNuevo);
                    $valorAnterior = $this->normalizarCalificacion($this->calificacionesOriginales[$inscripcionId][$materiaId] ?? null);

                    $observacionNueva = trim((string) ($this->observaciones[$inscripcionId][$materiaId] ?? ''));
                    $observacionAnterior = trim((string) ($this->observacionesOriginales[$inscripcionId][$materiaId] ?? ''));

                    if ($valorNuevo === $valorAnterior && $observacionNueva === $observacionAnterior) {
                        continue;
                    }

                    $condiciones = [
                        'periodo_id' => $this->periodo_id,
                        'inscripcion_id' => (int) $inscripcionId,
                        'asignacion_materia_id' => (int) $materiaId,
                        'ciclo_escolar_id' => $this->ciclo_escolar_id,
                        'grado_id' => $this->grado_id,
                        'grupo_id' => $this->grupo_id,
                    ];

                    if ($valorNuevo === null) {
                        $calificacion = ModelsCalificacion::query()->where($condiciones)->first();

                        if ($calificacion) {
                            $calificacion->delete();

                            $this->crearBitacoraCalificacion(
                                accion: 'eliminar',
                                inscripcionId: (int) $inscripcionId,
                                asignacionMateriaId: (int) $materiaId,
                                anterior: $valorAnterior,
                                nuevo: null,
                                observacion: $observacionNueva
                            );
                        }

                        continue;
                    }

                    $accion = ModelsCalificacion::query()->where($condiciones)->exists() ? 'editar' : 'crear';

                    ModelsCalificacion::query()->updateOrCreate(
                        $condiciones,
                        [
                            'nivel_id' => $this->nivel_id,
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
                        asignacionMateriaId: (int) $materiaId,
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

        $this->dispatch('swal', [
            'title' => '¡Calificaciones guardadas correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function crearBitacoraCalificacion(string $accion, int $inscripcionId, int $asignacionMateriaId, mixed $anterior, mixed $nuevo, ?string $observacion = null): void
    {
        BitacoraCalificacion::query()->create([
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
            'semestre_id' => $this->esBachillerato ? $this->semestre_id : null,
            'generacion_id' => $this->generacion_id,
            'periodo_id' => $this->periodo_id,
            'inscripcion_id' => $inscripcionId,
            'asignacion_materia_id' => $asignacionMateriaId,
            'user_id' => Auth::id(),
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

    private function reglasCalificaciones(): array
    {
        $reglas = [];

        foreach ($this->calificaciones as $inscripcionId => $materias) {
            foreach ($materias as $materiaId => $valor) {
                $reglas["calificaciones.$inscripcionId.$materiaId"] = [
                    'nullable',
                    'string',
                    'max:5',
                    'regex:/^(10(\.0)?|[0-9](\.[0-9])?|AC|ED|RA|NP|SD)$/i',
                ];
            }
        }

        return $reglas;
    }

    private function mensajesCalificaciones(): array
    {
        return [
            'calificaciones.*.*.string' => 'Debe ser texto válido.',
            'calificaciones.*.*.max' => 'Máximo 5 caracteres.',
            'calificaciones.*.*.regex' => 'Usa 0 a 10, AC, ED, RA, NP o SD.',
        ];
    }

    public function calcularPromedios(): void
    {
        $this->promedios = [];

        foreach ($this->calificaciones as $inscripcionId => $materias) {
            $valores = collect($materias)
                ->map(fn($valor) => $this->normalizarCalificacion($valor))
                ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                ->map(fn($valor) => (float) $valor)
                ->values();

            if ($valores->isEmpty()) {
                $this->promedios[$inscripcionId] = '—';
                continue;
            }

            $this->promedios[$inscripcionId] = $this->truncarDecimal($valores->avg(), 1);
        }
    }

    private function truncarDecimal(float $numero, int $decimales = 1): string
    {
        $factor = pow(10, $decimales);
        return number_format(floor($numero * $factor) / $factor, $decimales);
    }

    public function claseInputCalificacion($inscripcionId, $materiaId): string
    {
        $base = 'w-full rounded-xl border px-2 py-1.5 text-center text-sm font-semibold uppercase outline-none transition focus:ring-2';
        $valor = $this->calificaciones[$inscripcionId][$materiaId] ?? '';
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null) {
            return $base . ' border-neutral-200 bg-white text-neutral-800 focus:ring-sky-300 dark:border-neutral-700 dark:bg-neutral-950 dark:text-neutral-100';
        }

        if ($this->esCalificacionEspecial($valor)) {
            return $base . ' border-violet-300 bg-violet-50 text-violet-700 focus:ring-violet-300 dark:border-violet-800 dark:bg-violet-950/40 dark:text-violet-300';
        }

        if (!$this->validarCalificacionPermitida($valor)) {
            return $base . ' border-red-300 bg-red-50 text-red-700 focus:ring-red-300 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300';
        }

        $numero = (float) $valor;

        if ($numero < 6) {
            return $base . ' border-rose-300 bg-rose-50 text-rose-700 focus:ring-rose-300 dark:border-rose-800 dark:bg-rose-950/40 dark:text-rose-300';
        }

        if ($numero < 8) {
            return $base . ' border-amber-300 bg-amber-50 text-amber-700 focus:ring-amber-300 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
        }

        return $base . ' border-emerald-300 bg-emerald-50 text-emerald-700 focus:ring-emerald-300 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
    }

    public function getPuedeGuardarProperty(): bool
    {
        if ($this->esBachillerato) {
            return filled($this->generacion_id)
                && filled($this->grado_id)
                && filled($this->semestre_id)
                && filled($this->grupo_id)
                && filled($this->parcial_bachillerato_id)
                && filled($this->periodo_id)
                && filled($this->ciclo_escolar_id)
                && count($this->inscripciones) > 0
                && count($this->materias) > 0;
        }

        return filled($this->generacion_id)
            && filled($this->grado_id)
            && filled($this->grupo_id)
            && filled($this->periodo_basica_id)
            && filled($this->periodo_id)
            && filled($this->ciclo_escolar_id)
            && count($this->inscripciones) > 0
            && count($this->materias) > 0;
    }

    public function getPuedeExportarPdfProperty(): bool
    {
        return $this->puedeGuardar;
    }

    public function getHayCambiosProperty(): bool
    {
        return $this->calificaciones !== $this->calificacionesOriginales || $this->observaciones !== $this->observacionesOriginales;
    }

    public function getMensajeCambiosProperty(): string
    {
        return $this->hayCambios ? 'Cambios pendientes' : 'Sin cambios pendientes';
    }

    public function getClaseEstadoCambiosProperty(): string
    {
        return $this->hayCambios
            ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900'
            : 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900';
    }

    public function getClaseGuardarProperty(): string
    {
        if (!$this->puedeGuardar) {
            return 'inline-flex cursor-not-allowed items-center justify-center gap-2 rounded-2xl bg-neutral-300 px-5 py-3 text-sm font-semibold text-neutral-500 opacity-70 dark:bg-neutral-800 dark:text-neutral-500';
        }

        return 'inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow transition hover:opacity-95';
    }

    public function getCeldasCapturadasProperty(): int
    {
        return collect($this->calificaciones)
            ->flatMap(fn($materias) => $materias)
            ->filter(fn($valor) => $this->normalizarCalificacion($valor) !== null)
            ->count();
    }

    public function getTotalCeldasProperty(): int
    {
        return count($this->inscripciones) * count($this->materias);
    }

    public function getPorcentajeCapturaProperty(): int
    {
        if ($this->totalCeldas === 0) {
            return 0;
        }

        return (int) round(($this->celdasCapturadas / $this->totalCeldas) * 100);
    }

    public function getEstadisticasCalificacionesProperty(): array
    {
        $valores = collect($this->calificaciones)
            ->flatMap(fn($materiasAlumno) => $materiasAlumno)
            ->map(fn($valor) => $this->normalizarCalificacion($valor))
            ->filter(fn($valor) => $valor !== null)
            ->values();

        $numericas = $valores
            ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $especiales = $valores
            ->filter(fn($valor) => $this->esCalificacionEspecial($valor))
            ->values();

        $aprobadas = $numericas->filter(fn($valor) => $valor >= 6)->count();
        $reprobadas = $numericas->filter(fn($valor) => $valor < 6)->count();

        $promedioGlobal = $numericas->isNotEmpty()
            ? $this->truncarDecimal($numericas->avg(), 1)
            : null;

        $totalCeldas = $this->totalCeldas;
        $capturadas = $valores->count();
        $pendientes = max(0, $totalCeldas - $capturadas);

        return [
            'promedio_global' => $promedioGlobal,
            'total_celdas' => $totalCeldas,
            'capturadas' => $capturadas,
            'pendientes' => $pendientes,
            'numericas' => $numericas->count(),
            'especiales' => $especiales->count(),
            'aprobadas' => $aprobadas,
            'reprobadas' => $reprobadas,
            'porcentaje_captura' => $totalCeldas > 0 ? round(($capturadas / $totalCeldas) * 100) : 0,
            'porcentaje_aprobacion' => $numericas->count() > 0 ? round(($aprobadas / $numericas->count()) * 100) : 0,
        ];
    }

    public function aplicarFiltroEstado(): void
    {
        if ($this->filtro_estado === '') {
            $this->inscripcionesTabla = $this->inscripciones;
            return;
        }

        $this->inscripcionesTabla = collect($this->inscripciones)
            ->filter(function ($fila) {
                $inscripcionId = (int) $fila['inscripcion_id'];
                $valores = collect($this->calificaciones[$inscripcionId] ?? [])
                    ->map(fn($valor) => $this->normalizarCalificacion($valor));

                return match ($this->filtro_estado) {
                    'pendientes' => $valores->contains(fn($valor) => $valor === null),
                    'aprobados' => $valores->contains(fn($valor) => $this->esCalificacionNumerica($valor) && (float) $valor >= 6),
                    'reprobados' => $valores->contains(fn($valor) => $this->esCalificacionNumerica($valor) && (float) $valor < 6),
                    'especiales' => $valores->contains(fn($valor) => $this->esCalificacionEspecial($valor)),
                    'cambios' => collect($this->calificaciones[$inscripcionId] ?? [])
                        ->contains(function ($valor, $materiaId) use ($inscripcionId) {
                                $nuevo = $this->normalizarCalificacion($valor);
                                $anterior = $this->normalizarCalificacion($this->calificacionesOriginales[$inscripcionId][$materiaId] ?? null);
                                $obsNueva = trim((string) ($this->observaciones[$inscripcionId][$materiaId] ?? ''));
                                $obsAnterior = trim((string) ($this->observacionesOriginales[$inscripcionId][$materiaId] ?? ''));

                                return $nuevo !== $anterior || $obsNueva !== $obsAnterior;
                            }),
                    default => true,
                };
            })
            ->values()
            ->toArray();
    }

    public function getNombrePeriodoProperty(): string
    {
        return $this->periodoSeleccionado['periodo'] ?? 'Sin periodo';
    }

    public function getEstadoPeriodoProperty(): string
    {
        if (!$this->periodoSeleccionado || empty($this->periodoSeleccionado['fecha_inicio']) || empty($this->periodoSeleccionado['fecha_fin'])) {
            return 'Sin fechas';
        }

        $inicio = Carbon::parse($this->periodoSeleccionado['fecha_inicio'])->startOfDay();
        $fin = Carbon::parse($this->periodoSeleccionado['fecha_fin'])->endOfDay();
        $hoy = Carbon::today();

        if ($hoy->between($inicio, $fin)) {
            return 'Activo';
        }

        return $hoy->gt($fin) ? 'Finalizado' : 'Próximo';
    }

    public function getClaseEstadoPeriodoProperty(): string
    {
        return match ($this->estadoPeriodo) {
            'Activo' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900',
            'Finalizado' => 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900',
            'Próximo' => 'bg-sky-50 text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900',
            default => 'bg-neutral-50 text-neutral-700 ring-1 ring-neutral-200 dark:bg-neutral-900 dark:text-neutral-300 dark:ring-neutral-700',
        };
    }

    public function getPorcentajePeriodoProperty(): int
    {
        if (!$this->periodoSeleccionado || empty($this->periodoSeleccionado['fecha_inicio']) || empty($this->periodoSeleccionado['fecha_fin'])) {
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

        $nombreNivel = mb_strtoupper($this->nivel?->nombre ?? $this->slug_nivel ?? 'NIVEL');
        $nombreGrado = Grado::query()->where('id', $this->grado_id)->value('nombre') ?? 'GRADO';
        $nombreGrupo = Grupo::query()->where('id', $this->grupo_id)->value('nombre') ?? 'GRUPO';

        $nombreArchivo = 'CALIFICACIONES_' .
            str_replace(' ', '_', $nombreNivel) .
            '_GRADO_' . ($nombreGrado) .
            '_GRUPO_' . ($nombreGrupo) .
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

    public function getGraficasCalificacionesProperty(): array
    {
        $alumnos = collect($this->inscripciones)
            ->map(function ($fila) {
                $inscripcionId = (int) $fila['inscripcion_id'];
                $promedio = $this->promedios[$inscripcionId] ?? null;

                if (!is_numeric($promedio)) {
                    return null;
                }

                return [
                    'nombre' => $this->recortarTexto($fila['alumno'] ?? 'Alumno', 24),
                    'promedio' => (float) $promedio,
                ];
            })
            ->filter()
            ->values();

        $materias = collect($this->materias)
            ->map(function ($materia) {
                $materiaId = (int) $materia['id'];

                $valores = collect($this->calificaciones)
                    ->map(fn($materiasAlumno) => $materiasAlumno[$materiaId] ?? null)
                    ->map(fn($valor) => $this->normalizarCalificacion($valor))
                    ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                    ->map(fn($valor) => (float) $valor)
                    ->values();

                if ($valores->isEmpty()) {
                    return null;
                }

                return [
                    'materia' => $this->recortarTexto($materia['materia'] ?? 'Materia', 22),
                    'promedio' => (float) $this->truncarDecimal($valores->avg(), 1),
                ];
            })
            ->filter()
            ->values();

        $valoresGlobales = collect($this->calificaciones)
            ->flatMap(fn($materiasAlumno) => $materiasAlumno)
            ->map(fn($valor) => $this->normalizarCalificacion($valor))
            ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGlobal = $valoresGlobales->isNotEmpty()
            ? (float) $this->truncarDecimal($valoresGlobales->avg(), 1)
            : 0;

        $aprobadas = $valoresGlobales->filter(fn($valor) => $valor >= 6)->count();
        $reprobadas = $valoresGlobales->filter(fn($valor) => $valor < 6)->count();

        return [
            'alumnos' => [
                'labels' => $alumnos->pluck('nombre')->values()->all(),
                'series' => $alumnos->pluck('promedio')->values()->all(),
            ],
            'materias' => [
                'labels' => $materias->pluck('materia')->values()->all(),
                'series' => $materias->pluck('promedio')->values()->all(),
            ],
            'global' => [
                'promedio' => $promedioGlobal,
                'porcentaje' => min(100, $promedioGlobal * 10),
                'total_numericas' => $valoresGlobales->count(),
                'aprobadas' => $aprobadas,
                'reprobadas' => $reprobadas,
                'porcentaje_aprobacion' => $valoresGlobales->count() > 0 ? round(($aprobadas / $valoresGlobales->count()) * 100) : 0,
            ],
        ];
    }

    private function recortarTexto(string $texto, int $limite = 24): string
    {
        $texto = trim($texto);

        if (mb_strlen($texto) <= $limite) {
            return $texto;
        }

        return mb_substr($texto, 0, $limite) . '...';
    }

    public function render()
    {
        return view('livewire.accion.calificacion', [
            'hayCambios' => $this->hayCambios,
            'graficasCalificaciones' => $this->graficasCalificaciones,
        ]);
    }
}
