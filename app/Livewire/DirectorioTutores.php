<?php

namespace App\Livewire;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\Tutor;
use App\Services\ContextoEscolarService;
use App\Services\DirectorioTutoresService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class DirectorioTutores extends Component
{
    use WithPagination;

    public ?int $nivel_id = null;
    public ?int $generacion_id = null;
    public ?int $ciclo_escolar_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $estado_alumno = 'activos';
    public string $modo_responsables = 'principal';
    public string $parentesco = '';
    public string $buscar = '';
    public string $orden = 'academico_alumno';
    public string $vista = 'familias';
    public string $pestana = 'todos';
    public string $tipo_familia = 'todas';
    public string $filtro_rapido = '';
    public bool $salto_grupo = true;
    public int $perPage = 20;

    public array $niveles = [];
    public array $generaciones = [];
    public array $ciclosEscolares = [];
    public array $grados = [];
    public array $semestres = [];
    public array $grupos = [];
    public array $parentescos = [];

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->autorizar();

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug'])
            ->map(fn (Nivel $item): array => [
                'id' => (int) $item->id,
                'nombre' => $item->nombre,
                'slug' => $item->slug,
            ])->all();

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual'])
            ->map(fn (CicloEscolar $ciclo): array => [
                'id' => (int) $ciclo->id,
                'nombre' => $ciclo->inicio_anio . '-' . $ciclo->fin_anio,
                'es_actual' => (bool) $ciclo->es_actual,
            ])->all();

        $ciclo = collect($this->ciclosEscolares)->firstWhere('es_actual', true)
            ?? collect($this->ciclosEscolares)->first();
        $this->ciclo_escolar_id = isset($ciclo['id']) ? (int) $ciclo['id'] : null;

        // El módulo inicia en modo institucional: todos los niveles, ciclo actual.
        $this->nivel_id = null;
        $this->cargarCatalogosDependientes();
    }

    public function updatedNivelId(mixed $value): void
    {
        $id = $this->enteroValido($value) ? (int) $value : null;
        $this->nivel_id = $id && collect($this->niveles)->contains('id', $id) ? $id : null;
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->cargarCatalogosDependientes();
        $this->resetPage('directorioPage');
    }

    public function updatedCicloEscolarId(mixed $value): void
    {
        $this->ciclo_escolar_id = $this->enteroValido($value) ? (int) $value : null;
        $this->generacion_id = null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->cargarCatalogosDependientes();
        $this->resetPage('directorioPage');
    }

    public function updatedGeneracionId(mixed $value): void
    {
        $this->generacion_id = $this->enteroValido($value) ? (int) $value : null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->resetPage('directorioPage');
    }

    public function updatedGradoId(mixed $value): void
    {
        $this->grado_id = $this->enteroValido($value) ? (int) $value : null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->resetPage('directorioPage');
    }

    public function updatedSemestreId(mixed $value): void
    {
        $this->semestre_id = $this->enteroValido($value) ? (int) $value : null;
        $this->grupo_id = null;
        $this->cargarGrupos();
        $this->resetPage('directorioPage');
    }

    public function updatedGrupoId(mixed $value): void
    {
        $this->grupo_id = $this->enteroValido($value) ? (int) $value : null;
        $this->resetPage('directorioPage');
    }

    public function updatedEstadoAlumno(): void
    {
        if (! in_array($this->estado_alumno, DirectorioTutoresService::ESTADOS_ALUMNO, true)) {
            $this->estado_alumno = 'activos';
        }

        $this->cargarParentescos();
        $this->resetPage('directorioPage');
    }

    public function updatedModoResponsables(): void
    {
        if (! in_array($this->modo_responsables, DirectorioTutoresService::MODOS_RESPONSABLES, true)) {
            $this->modo_responsables = 'principal';
        }

        $this->resetPage('directorioPage');
    }

    public function updatedParentesco(): void
    {
        $this->parentesco = Str::upper(trim($this->parentesco));
        $this->resetPage('directorioPage');
    }

    public function updatedBuscar(): void
    {
        $this->buscar = Str::limit(trim($this->buscar), 120, '');
        $this->resetPage('directorioPage');
    }

    public function updatedOrden(): void
    {
        if (! in_array($this->orden, DirectorioTutoresService::ORDENES, true)) {
            $this->orden = 'academico_alumno';
        }

        $this->resetPage('directorioPage');
    }

    public function updatedVista(): void
    {
        if (! in_array($this->vista, DirectorioTutoresService::VISTAS, true)) {
            $this->vista = 'familias';
        }

        $this->resetPage('directorioPage');
    }

    public function updatedPestana(): void
    {
        if (! in_array($this->pestana, DirectorioTutoresService::PESTANAS, true)) {
            $this->pestana = 'todos';
        }

        $this->filtro_rapido = '';
        $this->resetPage('directorioPage');
    }

    public function updatedTipoFamilia(): void
    {
        if (! in_array($this->tipo_familia, DirectorioTutoresService::TIPOS_FAMILIA, true)) {
            $this->tipo_familia = 'todas';
        }

        $this->resetPage('directorioPage');
    }

    public function updatedPerPage(mixed $value): void
    {
        $valor = (int) $value;
        $this->perPage = in_array($valor, [20, 40, 80], true) ? $valor : 20;
        $this->resetPage('directorioPage');
    }

    public function cambiarPestana(string $pestana): void
    {
        if (! in_array($pestana, DirectorioTutoresService::PESTANAS, true)) {
            return;
        }

        $this->pestana = $pestana;
        $this->filtro_rapido = '';
        $this->resetPage('directorioPage');
    }

    public function cambiarVista(string $vista): void
    {
        if (! in_array($vista, DirectorioTutoresService::VISTAS, true)) {
            return;
        }

        $this->vista = $vista;
        $this->resetPage('directorioPage');
    }

    public function aplicarFiltroRapido(string $filtro): void
    {
        if (! in_array($filtro, DirectorioTutoresService::FILTROS_RAPIDOS, true)) {
            return;
        }

        $this->pestana = 'todos';
        $this->filtro_rapido = $this->filtro_rapido === $filtro ? '' : $filtro;
        $this->resetPage('directorioPage');
    }

    public function limpiarFiltros(): void
    {
        $ciclo = collect($this->ciclosEscolares)->firstWhere('es_actual', true)
            ?? collect($this->ciclosEscolares)->first();

        $this->nivel_id = null;
        $this->generacion_id = null;
        $this->ciclo_escolar_id = isset($ciclo['id']) ? (int) $ciclo['id'] : null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->estado_alumno = 'activos';
        $this->modo_responsables = 'principal';
        $this->parentesco = '';
        $this->buscar = '';
        $this->orden = 'academico_alumno';
        $this->vista = 'familias';
        $this->pestana = 'todos';
        $this->tipo_familia = 'todas';
        $this->filtro_rapido = '';
        $this->salto_grupo = true;
        $this->perPage = 20;
        $this->cargarCatalogosDependientes();
        $this->resetPage('directorioPage');
    }

    /**
     * Restaura únicamente valores válidos provenientes de localStorage.
     */
    public function restaurarVistaGuardada(array $estado): void
    {
        $this->autorizar();

        $nivelId = $this->enteroValido($estado['nivel_id'] ?? null) ? (int) $estado['nivel_id'] : null;
        $this->nivel_id = $nivelId && collect($this->niveles)->contains('id', $nivelId) ? $nivelId : null;
        $this->ciclo_escolar_id = $this->idRelacionadoValido(CicloEscolar::class, $estado['ciclo_escolar_id'] ?? null);

        if ($this->nivel_id) {
            $this->generacion_id = $this->idRelacionadoValido(
                Generacion::class,
                $estado['generacion_id'] ?? null,
                ['nivel_id' => $this->nivel_id]
            );
            $this->grado_id = $this->idRelacionadoValido(
                Grado::class,
                $estado['grado_id'] ?? null,
                ['nivel_id' => $this->nivel_id]
            );
            $this->semestre_id = $this->idRelacionadoValido(
                Semestre::class,
                $estado['semestre_id'] ?? null,
                $this->grado_id ? ['grado_id' => $this->grado_id] : []
            );
            $this->grupo_id = $this->idRelacionadoValido(
                Grupo::class,
                $estado['grupo_id'] ?? null,
                collect([
                    'nivel_id' => $this->nivel_id,
                    'generacion_id' => $this->generacion_id,
                    'grado_id' => $this->grado_id,
                    'semestre_id' => $this->semestre_id,
                    'ciclo_escolar_id' => $this->ciclo_escolar_id,
                ])->filter(fn ($valor): bool => $valor !== null)->all()
            );
        } else {
            $this->generacion_id = null;
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
        }

        $servicio = DirectorioTutoresService::class;
        $estadoAlumno = (string) ($estado['estado_alumno'] ?? 'activos');
        $modo = (string) ($estado['modo_responsables'] ?? 'principal');
        $orden = (string) ($estado['orden'] ?? 'academico_alumno');
        $vista = (string) ($estado['vista'] ?? 'familias');
        $pestana = (string) ($estado['pestana'] ?? 'todos');
        $tipoFamilia = (string) ($estado['tipo_familia'] ?? 'todas');
        $filtroRapido = (string) ($estado['filtro_rapido'] ?? '');
        $porPagina = (int) ($estado['perPage'] ?? 20);

        $this->estado_alumno = in_array($estadoAlumno, $servicio::ESTADOS_ALUMNO, true) ? $estadoAlumno : 'activos';
        $this->modo_responsables = in_array($modo, $servicio::MODOS_RESPONSABLES, true) ? $modo : 'principal';
        $this->parentesco = Str::upper(Str::limit(trim((string) ($estado['parentesco'] ?? '')), 50, ''));
        $this->buscar = Str::limit(trim((string) ($estado['buscar'] ?? '')), 120, '');
        $this->orden = in_array($orden, $servicio::ORDENES, true) ? $orden : 'academico_alumno';
        $this->vista = in_array($vista, $servicio::VISTAS, true) ? $vista : 'familias';
        $this->pestana = in_array($pestana, $servicio::PESTANAS, true) ? $pestana : 'todos';
        $this->tipo_familia = in_array($tipoFamilia, $servicio::TIPOS_FAMILIA, true) ? $tipoFamilia : 'todas';
        $this->filtro_rapido = in_array($filtroRapido, $servicio::FILTROS_RAPIDOS, true) ? $filtroRapido : '';
        $this->salto_grupo = filter_var($estado['salto_grupo'] ?? true, FILTER_VALIDATE_BOOL);
        $this->perPage = in_array($porPagina, [20, 40, 80], true) ? $porPagina : 20;

        $this->cargarCatalogosDependientes();

        $pagina = max(1, min((int) ($estado['page'] ?? 1), 10000));
        $this->setPage($pagina, 'directorioPage');
    }

    public function render()
    {
        $this->autorizar();

        $servicio = app(DirectorioTutoresService::class);
        $resultado = $servicio->directorio($this->filtros());
        $coleccion = $this->vista === 'familias' ? $resultado['familias'] : $resultado['filas'];
        $registros = $this->paginar($coleccion);

        return view('livewire.directorio-tutores', [
            'registros' => $registros,
            'metricas' => $resultado['metricas'],
            'duplicados' => $resultado['duplicados'],
            'urlsDescarga' => [
                'pdf' => $this->urlDescarga('pdf'),
                'word' => $this->urlDescarga('word'),
                'zip_pdf' => $this->urlDescarga('zip-pdf'),
                'zip_word' => $this->urlDescarga('zip-word'),
            ],
        ]);
    }

    private function cargarCatalogosDependientes(): void
    {
        if (! $this->nivel_id || ! $this->ciclo_escolar_id) {
            $this->generaciones = [];
            $this->grados = [];
            $this->semestres = [];
            $this->grupos = [];
            $this->cargarParentescos();
            return;
        }

        $contexto = app(ContextoEscolarService::class);

        $this->generaciones = $contexto
            ->generaciones((int) $this->nivel_id, (int) $this->ciclo_escolar_id)
            ->map(fn (Generacion $generacion): array => [
                'id' => (int) $generacion->id,
                'nombre' => $generacion->etiqueta,
                'status' => (bool) $generacion->status,
            ])->all();

        if ($this->generacion_id && ! collect($this->generaciones)->contains('id', (int) $this->generacion_id)) {
            $this->generacion_id = null;
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
        }

        $this->cargarGrados();
        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarParentescos();
    }

    private function cargarGrados(): void
    {
        if (! $this->nivel_id || ! $this->ciclo_escolar_id) {
            $this->grados = [];
            return;
        }

        $this->grados = app(ContextoEscolarService::class)
            ->grados(
                nivelId: (int) $this->nivel_id,
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                generacionId: $this->generacion_id,
            )
            ->map(fn (Grado $grado): array => [
                'id' => (int) $grado->id,
                'nombre' => $grado->nombre,
            ])->all();

        if ($this->grado_id && ! collect($this->grados)->contains('id', (int) $this->grado_id)) {
            $this->grado_id = null;
            $this->semestre_id = null;
            $this->grupo_id = null;
        }
    }

    private function cargarSemestres(): void
    {
        if (! $this->nivel_id || ! $this->ciclo_escolar_id || ! $this->grado_id || ! $this->esBachillerato()) {
            $this->semestres = [];
            $this->semestre_id = null;
            return;
        }

        $this->semestres = app(ContextoEscolarService::class)
            ->semestres(
                nivelId: (int) $this->nivel_id,
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                generacionId: $this->generacion_id,
                gradoId: $this->grado_id,
            )
            ->map(fn (Semestre $semestre): array => [
                'id' => (int) $semestre->id,
                'nombre' => 'Semestre ' . $semestre->numero,
            ])->all();

        if ($this->semestre_id && ! collect($this->semestres)->contains('id', (int) $this->semestre_id)) {
            $this->semestre_id = null;
            $this->grupo_id = null;
        }
    }

    private function cargarGrupos(): void
    {
        if (! $this->nivel_id || ! $this->ciclo_escolar_id) {
            $this->grupos = [];
            return;
        }

        $this->grupos = app(ContextoEscolarService::class)
            ->grupos(
                nivelId: (int) $this->nivel_id,
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                generacionId: $this->generacion_id,
                gradoId: $this->grado_id,
                semestreId: $this->semestre_id,
                bachillerato: $this->esBachillerato(),
            )
            ->map(function (Grupo $grupo): array {
                $nombreGrupo = $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
                $contexto = collect([
                    ! $this->grado_id ? $grupo->grado?->nombre : null,
                    ! $this->semestre_id && $grupo->semestre?->numero
                        ? 'Semestre ' . $grupo->semestre->numero
                        : null,
                    ! $this->generacion_id ? $grupo->generacion?->etiqueta : null,
                ])->filter()->join(' · ');

                return [
                    'id' => (int) $grupo->id,
                    'nombre' => $contexto !== '' ? $nombreGrupo . ' · ' . $contexto : $nombreGrupo,
                ];
            })->values()->all();

        if ($this->grupo_id && ! collect($this->grupos)->contains('id', (int) $this->grupo_id)) {
            $this->grupo_id = null;
        }
    }

    private function esBachillerato(): bool
    {
        $nivel = collect($this->niveles)->firstWhere('id', (int) $this->nivel_id);
        $texto = Str::lower(trim(($nivel['slug'] ?? '') . ' ' . ($nivel['nombre'] ?? '')));

        return Str::contains($texto, 'bachillerato');
    }

    private function cargarParentescos(): void
    {
        $parentescos = collect();

        if (Schema::hasTable('inscripcion_tutor')) {
            $query = DB::table('inscripcion_tutor')
                ->join('inscripciones', 'inscripciones.id', '=', 'inscripcion_tutor.inscripcion_id')
                ->where('inscripcion_tutor.activo', true)
                ->whereNull('inscripcion_tutor.fecha_fin')
                ->whereNull('inscripciones.deleted_at')
                ->whereNotNull('inscripcion_tutor.parentesco');

            $query->when($this->nivel_id, fn ($q, int $id) => $q->where('inscripciones.nivel_id', $id));
            $query->when($this->ciclo_escolar_id, fn ($q, int $id) => $q->where('inscripciones.ciclo_escolar_id', $id));

            match ($this->estado_alumno) {
                'activos' => $query->where('inscripciones.activo', true)->where('inscripciones.estatus', 'activo'),
                'egresados' => $query->where('inscripciones.estatus', 'egresado'),
                'no_reinscritos' => $query->whereIn('inscripciones.estatus', ['no_reinscrito', 'pendiente_reinscripcion']),
                default => null,
            };

            $parentescos = $query->pluck('inscripcion_tutor.parentesco');
        }

        $legados = Tutor::query()
            ->whereHas('inscripciones', function ($query): void {
                $query->whereNull('deleted_at')
                    ->when($this->nivel_id, fn ($q, int $id) => $q->where('nivel_id', $id))
                    ->when($this->ciclo_escolar_id, fn ($q, int $id) => $q->where('ciclo_escolar_id', $id));

                match ($this->estado_alumno) {
                    'activos' => $query->where('activo', true)->where('estatus', 'activo'),
                    'egresados' => $query->where('estatus', 'egresado'),
                    'no_reinscritos' => $query->whereIn('estatus', ['no_reinscrito', 'pendiente_reinscripcion']),
                    default => null,
                };
            })
            ->whereNotNull('parentesco')
            ->pluck('parentesco');

        $this->parentescos = $parentescos
            ->merge($legados)
            ->map(fn ($valor): string => Str::upper(trim((string) $valor)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($this->parentesco !== '' && ! in_array($this->parentesco, $this->parentescos, true)) {
            $this->parentesco = '';
        }
    }

    private function filtros(): array
    {
        return [
            'nivel_id' => $this->nivel_id,
            'generacion_id' => $this->generacion_id,
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'grado_id' => $this->grado_id,
            'semestre_id' => $this->semestre_id,
            'grupo_id' => $this->grupo_id,
            'estado_alumno' => $this->estado_alumno,
            'modo_responsables' => $this->modo_responsables,
            'parentesco' => $this->parentesco,
            'buscar' => $this->buscar,
            'orden' => $this->orden,
            'vista' => $this->vista,
            'pestana' => $this->pestana,
            'tipo_familia' => $this->tipo_familia,
            'filtro_rapido' => $this->filtro_rapido,
            'salto_grupo' => $this->salto_grupo,
        ];
    }

    private function paginar(Collection $registros): LengthAwarePaginator
    {
        $paginaActual = LengthAwarePaginator::resolveCurrentPage('directorioPage');
        $total = $registros->count();
        $ultimaPagina = max((int) ceil($total / max($this->perPage, 1)), 1);
        $paginaActual = min(max($paginaActual, 1), $ultimaPagina);

        return new LengthAwarePaginator(
            $registros->forPage($paginaActual, $this->perPage)->values(),
            $total,
            $this->perPage,
            $paginaActual,
            [
                'path' => request()->url(),
                'query' => request()->query(),
                'pageName' => 'directorioPage',
            ]
        );
    }

    private function urlDescarga(string $formato): string
    {
        return route('directorio-tutores.descargar', ['formato' => $formato])
            . '?' . http_build_query($this->filtros());
    }

    private function idRelacionadoValido(string $modelo, mixed $valor, array $condiciones = []): ?int
    {
        if (! $this->enteroValido($valor)) {
            return null;
        }

        $query = $modelo::query()->whereKey((int) $valor);

        foreach ($condiciones as $campo => $esperado) {
            $query->where($campo, $esperado);
        }

        return $query->exists() ? (int) $valor : null;
    }

    private function enteroValido(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_INT) !== false && (int) $valor > 0;
    }

    private function autorizar(): void
    {
        abort_unless(auth()->check() && auth()->user()->is_admin, 403, 'No tienes permiso para consultar este directorio.');
    }
}
