<?php

namespace App\Livewire\Accion;

use App\Exports\CalificacionExport;
use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as ModelsCalificacion;
use App\Models\CicloEscolar;
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
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Calificacion extends Component
{
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

    public $boleta_inscripcion_id = '';
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

    public function getEsBachilleratoProperty(): bool
    {
        return (int) $this->nivel_id === 4;
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

        $this->parciales = Parcial::query()
            ->orderBy('parcial')
            ->get();

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
            'diploma_inscripcion_id',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();

        if (blank($value)) {
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
            'diploma_inscripcion_id',
        ]);

        $this->grupos = collect();
        $this->semestres = collect();

        if (blank($value)) {
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
            'diploma_inscripcion_id',
        ]);

        $this->grupos = collect();

        if (blank($value)) {
            return;
        }

        $this->cargarGrupos();
    }

    public function updatedGrupoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'parcial_bachillerato_id',
            'periodo_basica_id',
            'boleta_inscripcion_id',
            'diploma_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        if (!$this->esBachillerato) {
            return;
        }

        /*
         * En bachillerato no se cargan datos al seleccionar grupo.
         * Primero se debe seleccionar el parcial.
         */
    }

    public function updatedParcialBachilleratoId($value = null): void
    {
        $this->resetEstadoAcademico([
            'boleta_inscripcion_id',
            'diploma_inscripcion_id',
        ]);

        if (blank($value)) {
            return;
        }

        $this->cargarDatos();
    }

    public function updatedPeriodoBasicaId($value = null): void
    {
        $this->resetEstadoAcademico([
            'boleta_inscripcion_id',
            'diploma_inscripcion_id',
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

    private function resetEstadoAcademico(array $camposExtra = []): void
    {
        $campos = array_merge($camposExtra, [
            'periodo_id',
            'ciclo_escolar_id',
            'periodoSeleccionado',
            'filtro_estado',
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
            ->orderBy('orden')
            ->orderBy('id')
            ->get();
    }

    private function cargarSemestres(): void
    {
        if (!$this->esBachillerato || blank($this->grado_id)) {
            $this->semestres = collect();
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('numero')
            ->get();
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
            'mostrarModalBitacora',
            'mostrarModalRevision',
            'resumenRevision',
            'motivo_guardado',
            'boleta_inscripcion_id',
            'diploma_inscripcion_id',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
        $this->semestres = collect();
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
        ]);

        if (!$this->puedeCargarDatos()) {
            return;
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
         * En bachillerato se toman todos los grupos equivalentes.
         * Esto permite mostrar los alumnos del mismo grupo lógico,
         * aunque el alumno no tenga asignado el semestre seleccionado.
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
    private function cargarInscripciones(): void
    {
        $grupoIds = $this->obtenerGrupoIdsParaAlumnos();

        $query = Inscripcion::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_id)
            ->where(function ($q) {
                $q->where('activo', 1)
                    ->orWhere('activo', true)
                    ->orWhere('activo', '1')
                    ->orWhere('activo', 'true');
            });

        /*
         * No se filtra por semestre_id.
         * En bachillerato tampoco se usa solo el grupo_id seleccionado,
         * porque el mismo grupo A puede existir en varios semestres con ids diferentes.
         */
        if (!empty($grupoIds)) {
            $query->whereIn('grupo_id', $grupoIds);
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
                    'inscripcion_id' => (int) $inscripcion->id,
                    'matricula' => $inscripcion->matricula ?? 'SIN MATRÍCULA',
                    'alumno' => trim(
                        ($inscripcion->apellido_paterno ?? '') . ' ' .
                        ($inscripcion->apellido_materno ?? '') . ' ' .
                        ($inscripcion->nombre ?? '')
                    ),
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
                'materia:id,nivel_id,grado_id,semestre_id,materia,clave,slug,calificable,extra,orden',
            ])
            ->where('grupo_id', $this->grupo_id)
            ->whereHas('materia', function ($query) {
                $query->where('nivel_id', $this->nivel_id)
                    ->where('grado_id', $this->grado_id)
                    ->where('calificable', 1);

                if ($this->esBachillerato) {
                    $query->where('semestre_id', $this->semestre_id);
                } else {
                    $query->whereNull('semestre_id');
                }
            })

            // Se ordenan las materias por la columna orden de asignacion_materias.
            ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('orden')
            ->orderBy('id')
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
                    'calificable' => (bool) ($asignacion->materia?->calificable ?? false),

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
                            $fail('Usa una calificación de 0 a 10 o una clave válida: AC, ED, RA, NP, SD.');
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

    public function calcularPromedios(): void
    {
        $this->promedios = [];

        $materiasPromediables = collect($this->materias)
            ->filter(fn($materia) => empty($materia['extra']))
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        foreach ($this->calificaciones as $inscripcionId => $materiasAlumno) {
            $valores = collect($materiasAlumno)
                ->filter(fn($valor, $asignacionMateriaId) => in_array((int) $asignacionMateriaId, $materiasPromediables, true))
                ->map(fn($valor) => $this->normalizarCalificacion($valor))
                ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                ->map(fn($valor) => (float) $valor)
                ->values();

            $this->promedios[(int) $inscripcionId] = $valores->isEmpty()
                ? '—'
                : number_format($valores->avg(), 1);
        }
    }

    private function aplicarFiltroEstado(): void
    {
        $this->inscripcionesTabla = $this->inscripciones;

        if ($this->filtro_estado === '') {
            return;
        }

        $this->inscripcionesTabla = collect($this->inscripciones)
            ->filter(function ($fila) {
                $inscripcionId = (int) $fila['inscripcion_id'];
                $materiasAlumno = $this->calificaciones[$inscripcionId] ?? [];

                $valores = collect($materiasAlumno)
                    ->map(fn($valor) => $this->normalizarCalificacion($valor));

                return match ($this->filtro_estado) {
                    'pendientes' => $valores->contains(fn($valor) => $valor === null),
                    'aprobados' => $valores->filter(fn($valor) => $this->esCalificacionNumerica($valor))->every(fn($valor) => (float) $valor >= 6),
                    'reprobados' => $valores->contains(fn($valor) => $this->esCalificacionNumerica($valor) && (float) $valor < 6),
                    'especiales' => $valores->contains(fn($valor) => $this->esCalificacionEspecial($valor)),
                    'cambios' => $this->tieneCambiosInscripcion($inscripcionId),
                    default => true,
                };
            })
            ->values()
            ->toArray();
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

        $this->dispatch('swal', [
            'title' => '¡Calificaciones guardadas correctamente!',
            'icon' => 'success',
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

    public function getTotalCeldasProperty(): int
    {
        return count($this->inscripciones) * count($this->materias);
    }

    public function getCeldasCapturadasProperty(): int
    {
        return collect($this->calificaciones)
            ->flatten()
            ->filter(fn($valor) => $this->normalizarCalificacion($valor) !== null)
            ->count();
    }

    public function getPorcentajeCapturaProperty(): int
    {
        if ($this->totalCeldas === 0) {
            return 0;
        }

        return (int) round(($this->celdasCapturadas / $this->totalCeldas) * 100);
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

    public function getPuedeExportarDiplomaProperty(): bool
    {
        return $this->puedeExportarPdf && filled($this->diploma_inscripcion_id);
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
        $valores = collect($this->calificaciones)
            ->flatten()
            ->map(fn($valor) => $this->normalizarCalificacion($valor))
            ->values();

        $numericas = $valores
            ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $aprobadas = $numericas->filter(fn($valor) => $valor >= 6)->count();
        $reprobadas = $numericas->filter(fn($valor) => $valor < 6)->count();
        $especiales = $valores->filter(fn($valor) => $this->esCalificacionEspecial($valor))->count();
        $pendientes = max(0, $this->totalCeldas - $this->celdasCapturadas);

        return [
            'promedio_global' => $numericas->isEmpty() ? '—' : number_format($numericas->avg(), 1),
            'porcentaje_aprobacion' => $numericas->isEmpty() ? 0 : (int) round(($aprobadas / $numericas->count()) * 100),
            'pendientes' => $pendientes,
            'reprobadas' => $reprobadas,
            'especiales' => $especiales,
            'porcentaje_captura' => $this->porcentajeCaptura,
        ];
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
                $asignacionMateriaId = (int) $materia['id'];

                $valores = collect($this->calificaciones)
                    ->map(fn($materiasAlumno) => $materiasAlumno[$asignacionMateriaId] ?? null)
                    ->map(fn($valor) => $this->normalizarCalificacion($valor))
                    ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
                    ->map(fn($valor) => (float) $valor)
                    ->values();

                if ($valores->isEmpty()) {
                    return null;
                }

                return [
                    'materia' => $this->recortarTexto($materia['materia'] ?? 'Materia', 18),
                    'promedio' => round($valores->avg(), 1),
                ];
            })
            ->filter()
            ->values();

        $globalValores = collect($this->calificaciones)
            ->flatten()
            ->map(fn($valor) => $this->normalizarCalificacion($valor))
            ->filter(fn($valor) => $this->esCalificacionNumerica($valor))
            ->map(fn($valor) => (float) $valor)
            ->values();

        $promedioGlobal = $globalValores->isEmpty() ? 0 : round($globalValores->avg(), 1);
        $aprobadas = $globalValores->filter(fn($valor) => $valor >= 6)->count();
        $reprobadas = $globalValores->filter(fn($valor) => $valor < 6)->count();

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
                'promedio' => $promedioGlobal,
                'porcentaje' => min(100, round(($promedioGlobal / 10) * 100)),
                'total_numericas' => $globalValores->count(),
                'aprobadas' => $aprobadas,
                'reprobadas' => $reprobadas,
                'porcentaje_aprobacion' => $globalValores->isEmpty() ? 0 : (int) round(($aprobadas / $globalValores->count()) * 100),
            ],
        ];
    }

    private function recortarTexto(string $texto, int $limite): string
    {
        return mb_strlen($texto) > $limite
            ? mb_substr($texto, 0, $limite) . '...'
            : $texto;
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

    public function render()
    {
        return view('livewire.accion.calificacion', [
            'hayCambios' => $this->hayCambios,
            'graficasCalificaciones' => $this->graficasCalificaciones,
        ]);
    }
}
