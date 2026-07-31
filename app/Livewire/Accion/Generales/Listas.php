<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Semestre;
use App\Services\ListaAcademicaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Listas extends Component
{
    public string $slug_nivel = '';

    public $nivel;

    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;
    public Collection $semestres;
    public Collection $parciales;

    public ?int $generacion_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public ?int $ciclo_escolar_id = null;

    /** @var array<int, int|string> */
    public array $alumnos_seleccionados = [];

    public string $buscar_alumno = '';

    public bool $mostrar_motivo = false;

    public string $modo_descarga = 'grupo';

    public string $tipo_descarga = 'evaluacion';
    public string $opcion_descarga = 'primer_periodo';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('status', 1)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $this->semestres = $this->cargarSemestresIniciales();
        $this->parciales = $this->cargarParciales();
        $this->grupos = collect();

        $cicloEscolar = CicloEscolar::query()
            ->where('es_actual', true)
            ->first()
            ?? CicloEscolar::query()
                ->orderByDesc('inicio_anio')
                ->orderByDesc('fin_anio')
                ->first();

        $this->ciclo_escolar_id = $cicloEscolar?->id;

        $this->tipo_descarga = $this->esBachillerato() ? 'grupo' : 'evaluacion';

        $opciones = $this->opcionesDescarga();

        $this->opcion_descarga = array_key_first($opciones) ?? '';
    }

    private function cargarSemestresIniciales(): Collection
    {
        if (!$this->esBachillerato()) {
            return collect();
        }

        $columnas = ['id'];

        if (Schema::hasColumn('semestres', 'numero')) {
            $columnas[] = 'numero';
        }

        if (Schema::hasColumn('semestres', 'semestre')) {
            $columnas[] = 'semestre';
        }

        if (Schema::hasColumn('semestres', 'grado_id')) {
            $columnas[] = 'grado_id';
        }

        return Semestre::query()
            ->orderBy('id')
            ->get($columnas);
    }

    private function cargarParciales(): Collection
    {
        if (!$this->esBachillerato()) {
            return collect();
        }

        return Parcial::query()
            ->orderBy('id')
            ->get(['id', 'parcial', 'descripcion']);
    }

    public function updatedModoDescarga(): void
    {
        if (!in_array($this->modo_descarga, ['grupo', 'seleccionados', 'nivel'], true)) {
            $this->modo_descarga = 'grupo';
        }

        $this->limpiarSeleccionAlumnos();
        $this->mostrar_motivo = false;

        if ($this->modo_descarga === 'nivel') {
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
            $this->grupos = collect();
            $this->semestres = $this->cargarSemestresIniciales();
        }
    }

    public function updatedGeneracionId(): void
    {
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->grupos = collect();
        $this->semestres = $this->cargarSemestresIniciales();
        $this->limpiarSeleccionAlumnos();
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->limpiarSeleccionAlumnos();

        $this->cargarSemestresPorGrado();
        $this->cargarGrupos();
    }

    public function updatedSemestreId(): void
    {
        $this->grupo_id = null;
        $this->limpiarSeleccionAlumnos();

        $this->cargarGrupos();
    }

    public function updatedGrupoId(): void
    {
        $this->limpiarSeleccionAlumnos();
    }

    public function updatedBuscarAlumno(): void
    {
        // El buscador solo filtra visualmente; la selección previa se conserva.
    }

    public function updatedAlumnosSeleccionados(mixed $value = null, mixed $key = null): void
    {
        if ($this->modo_descarga !== 'seleccionados') {
            $this->alumnos_seleccionados = [];

            return;
        }

        $this->alumnos_seleccionados = $this->idsAlumnosSeleccionadosValidos;
    }

    public function updatedTipoDescarga(): void
    {
        if (($this->esBachillerato() || $this->esSecundaria()) && in_array($this->tipo_descarga, ['evaluacion', 'asistencia'])) {
            $this->tipo_descarga = 'grupo';
        }

        if ($this->tipo_descarga !== 'grupo') {
            $this->mostrar_motivo = false;
        }

        $this->opcion_descarga = array_key_first($this->opcionesDescarga()) ?? '';
    }

    public function cargarSemestresPorGrado(): void
    {
        if (!$this->esBachillerato()) {
            $this->semestres = collect();

            return;
        }

        $columnas = ['id'];

        if (Schema::hasColumn('semestres', 'numero')) {
            $columnas[] = 'numero';
        }

        if (Schema::hasColumn('semestres', 'semestre')) {
            $columnas[] = 'semestre';
        }

        if (Schema::hasColumn('semestres', 'grado_id')) {
            $columnas[] = 'grado_id';
        }

        $consulta = Semestre::query();

        if ($this->grado_id && Schema::hasColumn('semestres', 'grado_id')) {
            $consulta->where('grado_id', $this->grado_id);
        }

        $this->semestres = $consulta
            ->orderBy('id')
            ->get($columnas);
    }

    public function cargarGrupos(): void
    {
        $this->grupos = collect();

        if ($this->modo_descarga === 'nivel') {
            return;
        }

        if (!$this->generacion_id || !$this->grado_id) {
            return;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return;
        }

        $columnas = [
            'grupos.id',
            'grupos.nivel_id',
            'grupos.asignacion_grupo_id',
        ];

        if (Schema::hasColumn('grupos', 'generacion_id')) {
            $columnas[] = 'grupos.generacion_id';
        }

        if (Schema::hasColumn('grupos', 'grado_id')) {
            $columnas[] = 'grupos.grado_id';
        }

        if (Schema::hasColumn('grupos', 'semestre_id')) {
            $columnas[] = 'grupos.semestre_id';
        }

        $consulta = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
            ])
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select($columnas)
            ->where('grupos.nivel_id', $this->nivel->id);

        if (Schema::hasColumn('grupos', 'generacion_id')) {
            $consulta->where('grupos.generacion_id', $this->generacion_id);
        }

        if (Schema::hasColumn('grupos', 'grado_id')) {
            $consulta->where('grupos.grado_id', $this->grado_id);
        }

        if ($this->esBachillerato() && Schema::hasColumn('grupos', 'semestre_id')) {
            $consulta->where('grupos.semestre_id', $this->semestre_id);
        }

        if (!$this->esBachillerato() && Schema::hasColumn('grupos', 'semestre_id')) {
            $consulta->whereNull('grupos.semestre_id');
        }

        $this->grupos = $consulta
            ->orderBy('asignacion_grupos.nombre')
            ->get();
    }

    public function limpiarFiltros(): void
    {
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;

        $this->modo_descarga = 'grupo';

        $this->tipo_descarga = $this->esBachillerato() ? 'grupo' : 'evaluacion';

        $opciones = $this->opcionesDescarga();

        $this->opcion_descarga = array_key_first($opciones) ?? '';

        $this->grupos = collect();
        $this->mostrar_motivo = false;
        $this->semestres = $this->cargarSemestresIniciales();
        $this->parciales = $this->cargarParciales();
        $this->limpiarSeleccionAlumnos();
    }

    public function seleccionarTodos(): void
    {
        $this->alumnos_seleccionados = $this->alumnosDisponibles
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function limpiarSeleccion(): void
    {
        $this->alumnos_seleccionados = [];
    }

    public function quitarAlumnoSeleccionado(int $alumnoId): void
    {
        $this->alumnos_seleccionados = collect($this->alumnos_seleccionados)
            ->map(fn ($id): int => (int) $id)
            ->reject(fn (int $id): bool => $id === $alumnoId)
            ->unique()
            ->values()
            ->all();
    }

    private function limpiarSeleccionAlumnos(): void
    {
        $this->alumnos_seleccionados = [];
        $this->buscar_alumno = '';
        $this->resetErrorBag('alumnos_seleccionados');
    }

    public function tiposDescarga(): array
    {
        $tipos = [
            'evaluacion' => 'Lista de evaluación',
            'asistencia' => 'Lista de asistencia',
            'grupo' => 'Lista de grupo',
            'formatos' => 'Formatos',
        ];

        if ($this->esBachillerato() || $this->esSecundaria()) {
            unset($tipos['evaluacion'], $tipos['asistencia']);
        }

        return $tipos;
    }

    public function opcionesDescarga(): array
    {
        if ($this->esBachillerato() && $this->tipo_descarga !== 'formatos') {
            return $this->parciales
                ->mapWithKeys(function ($parcial) {
                    return [
                        'parcial_' . $parcial->id => $this->textoParcial($parcial),
                    ];
                })
                ->toArray();
        }

        return match ($this->tipo_descarga) {
            'evaluacion' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'asistencia' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'grupo' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'boletas' => [
                'primer_periodo' => 'PRIMER PERIODO',
                'segundo_periodo' => 'SEGUNDO PERIODO',
                'tercer_periodo' => 'TERCER PERIODO',
            ],

            'formatos' => [
                'sece' => 'SECE',
                'sece_interna' => 'SECE interna',
                'personalizadores' => 'Personalizadores',
                'etiquetas' => 'Etiquetas',
            ],

            default => [],
        };
    }

    #[Computed]
    public function alumnosDisponibles(): Collection
    {
        if (
            $this->modo_descarga !== 'seleccionados'
            || !$this->ciclo_escolar_id
            || !$this->generacion_id
            || !$this->grado_id
            || !$this->grupo_id
            || ($this->esBachillerato() && !$this->semestre_id)
        ) {
            return collect();
        }

        $grupo = $this->grupoSeleccionado;

        if (!$grupo) {
            return collect();
        }

        return app(ListaAcademicaService::class)
            ->alumnosPorContexto(
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                grupoIds: [(int) $grupo->id],
                fechaCorte: now(),
                nivelId: (int) $this->nivel->id,
                gradoId: (int) $this->grado_id,
                generacionId: (int) $this->generacion_id,
                semestreId: $this->esBachillerato() ? (int) $this->semestre_id : null,
                usarHistorialCiclo: true,
                incluirNoActivos: false,
                usarActualComoRespaldo: true,
                incluirTodaGeneracionBachillerato: $this->esBachillerato(),
            )
            ->filter(fn ($alumno): bool => $alumno->visibleEnListas())
            ->unique(fn ($alumno): int => (int) $alumno->id)
            ->sortBy(fn ($alumno): string => mb_strtolower(trim(
                (string) $alumno->apellido_paterno . ' ' .
                (string) $alumno->apellido_materno . ' ' .
                (string) $alumno->nombre
            )))
            ->values();
    }

    #[Computed]
    public function alumnosFiltrados(): Collection
    {
        $busqueda = mb_strtolower(trim($this->buscar_alumno));

        if ($busqueda === '') {
            return $this->alumnosDisponibles;
        }

        return $this->alumnosDisponibles
            ->filter(function ($alumno) use ($busqueda): bool {
                $texto = mb_strtolower(implode(' ', [
                    (string) ($alumno->matricula ?? ''),
                    (string) ($alumno->apellido_paterno ?? ''),
                    (string) ($alumno->apellido_materno ?? ''),
                    (string) ($alumno->nombre ?? ''),
                ]));

                return str_contains($texto, $busqueda);
            })
            ->values();
    }

    #[Computed]
    public function idsAlumnosSeleccionadosValidos(): array
    {
        $idsDisponibles = $this->alumnosDisponibles
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->unique();

        return collect($this->alumnos_seleccionados)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0 && $idsDisponibles->contains($id))
            ->unique()
            ->values()
            ->all();
    }

    #[Computed]
    public function totalAlumnosDisponibles(): int
    {
        return $this->alumnosDisponibles->count();
    }

    #[Computed]
    public function totalAlumnosSeleccionados(): int
    {
        return count($this->idsAlumnosSeleccionadosValidos);
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (!$this->tipo_descarga || !$this->opcion_descarga) {
            return false;
        }

        if ($this->modo_descarga === 'nivel') {
            return true;
        }

        if (!in_array($this->modo_descarga, ['grupo', 'seleccionados'], true)) {
            return false;
        }

        if (!$this->generacion_id || !$this->grado_id || !$this->grupo_id) {
            return false;
        }

        if ($this->esBachillerato() && !$this->semestre_id) {
            return false;
        }

        if ($this->modo_descarga === 'seleccionados') {
            return $this->totalAlumnosSeleccionados > 0;
        }

        return true;
    }

    #[Computed]
    public function parametrosDescarga(): array
    {
        $usaContextoGrupo = in_array($this->modo_descarga, ['grupo', 'seleccionados'], true);

        return [
            'modo_descarga' => $this->modo_descarga,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'generacion_id' => $usaContextoGrupo ? $this->generacion_id : null,
            'grado_id' => $usaContextoGrupo ? $this->grado_id : null,
            'semestre_id' => $usaContextoGrupo ? $this->semestre_id : null,
            'grupo_id' => $usaContextoGrupo ? $this->grupo_id : null,
            'alumnos' => $this->modo_descarga === 'seleccionados'
                ? implode(',', $this->idsAlumnosSeleccionadosValidos)
                : null,
            'tipo_descarga' => $this->tipo_descarga,
            'opcion_descarga' => $this->opcion_descarga,
            'mostrar_motivo' => $this->tipo_descarga === 'grupo'
                && $usaContextoGrupo
                && $this->mostrar_motivo ? 1 : 0,
        ];
    }

    #[Computed]
    public function urlPdf(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('accion.generales.listas.pdf', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->parametrosDescarga
        ));
    }

    #[Computed]
    public function urlDescarga(): ?string
    {
        return $this->urlPdf;
    }


    #[Computed]
    public function extensionDescarga(): string
    {
        return 'PDF';
    }

    #[Computed]
    public function textoBotonDescarga(): string
    {
        return match ($this->modo_descarga) {
            'nivel' => 'Descargar PDF por nivel',
            'seleccionados' => 'Descargar PDF (' . $this->totalAlumnosSeleccionados . ' alumnos)',
            default => 'Descargar PDF',
        };
    }

    #[Computed]
    public function textoModoDescarga(): string
    {
        if ($this->modo_descarga === 'nivel') {
            return 'Se generará un PDF con todas las listas del nivel seleccionado, sin necesidad de elegir generación, grado ni grupo.';
        }

        if ($this->modo_descarga === 'seleccionados') {
            return 'Se incluirán únicamente los ' . $this->totalAlumnosSeleccionados . ' alumnos seleccionados.';
        }

        return 'Se incluirán todos los alumnos activos del grupo seleccionado.';
    }

    #[Computed]
    public function textoAlcanceDescarga(): string
    {
        return match ($this->modo_descarga) {
            'nivel' => 'Todas las listas del nivel',
            'seleccionados' => 'Alumnos seleccionados',
            default => 'Grupo seleccionado',
        };
    }

    #[Computed]
    public function mensajeEstadoDescarga(): string
    {
        if ($this->modo_descarga === 'nivel') {
            return $this->puedeDescargar
                ? 'Se generarán todas las listas disponibles del nivel seleccionado.'
                : 'Selecciona el tipo de documento y el periodo.';
        }

        if (!$this->generacion_id || !$this->grado_id || !$this->grupo_id || ($this->esBachillerato() && !$this->semestre_id)) {
            return $this->esBachillerato()
                ? 'Selecciona generación, grado, semestre y grupo.'
                : 'Selecciona generación, grado y grupo.';
        }

        if ($this->modo_descarga === 'seleccionados') {
            if ($this->totalAlumnosDisponibles === 0) {
                return 'No hay alumnos activos disponibles para los filtros seleccionados.';
            }

            if ($this->totalAlumnosSeleccionados === 0) {
                return 'Selecciona al menos un alumno para generar el documento.';
            }

            return 'Se incluirán ' . $this->totalAlumnosSeleccionados . ' alumnos seleccionados.';
        }

        return 'Se incluirán todos los alumnos activos del grupo seleccionado.';
    }

    public function nombreAlumno($alumno): string
    {
        return trim(implode(' ', array_filter([
            $alumno?->apellido_paterno,
            $alumno?->apellido_materno,
            $alumno?->nombre,
        ])));
    }

    #[Computed]
    public function generacionSeleccionada(): ?Generacion
    {
        if (!$this->generacion_id) {
            return null;
        }

        return $this->generaciones->firstWhere('id', (int) $this->generacion_id);
    }

    #[Computed]
    public function gradoSeleccionado(): ?Grado
    {
        if (!$this->grado_id) {
            return null;
        }

        return $this->grados->firstWhere('id', (int) $this->grado_id);
    }

    #[Computed]
    public function semestreSeleccionado()
    {
        if (!$this->semestre_id) {
            return null;
        }

        return $this->semestres->firstWhere('id', (int) $this->semestre_id);
    }

    #[Computed]
    public function grupoSeleccionado(): ?Grupo
    {
        if (!$this->grupo_id) {
            return null;
        }

        return $this->grupos->firstWhere('id', (int) $this->grupo_id);
    }

    #[Computed]
    public function textoTipoDescarga(): string
    {
        return $this->tiposDescarga()[$this->tipo_descarga] ?? 'Documento';
    }

    #[Computed]
    public function textoOpcionDescarga(): string
    {
        return $this->opcionesDescarga()[$this->opcion_descarga] ?? '—';
    }

    public function etiquetaOpcionDescarga(): string
    {
        if ($this->tipo_descarga === 'formatos') {
            return 'Formato';
        }

        return $this->esBachillerato() ? 'Parcial' : 'Periodo';
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    public function textoSemestre($semestre): string
    {
        if (!$semestre) {
            return '—';
        }

        if (isset($semestre->numero)) {
            return 'Semestre ' . $semestre->numero;
        }

        if (isset($semestre->semestre)) {
            return $semestre->semestre;
        }

        return 'Semestre ' . $semestre->id;
    }

    public function textoParcial($parcial): string
    {
        if (!$parcial) {
            return '—';
        }

        if (!empty($parcial->parcial)) {
            return mb_strtoupper($parcial->parcial);
        }

        if (!empty($parcial->descripcion)) {
            return mb_strtoupper($parcial->descripcion);
        }

        return 'PARCIAL ' . $parcial->id;
    }

    public function esPreescolar(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 1)
            || ($this->nivel?->slug === 'preescolar');
    }

    public function esBachillerato(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 4)
            || ($this->nivel?->slug === 'bachillerato');
    }

    public function esSecundaria(): bool
    {
        return ((int) ($this->nivel?->id ?? 0) === 3)
            || ($this->nivel?->slug === 'secundaria');
    }

    public function render()
    {
        return view('livewire.accion.generales.listas');
    }
}
