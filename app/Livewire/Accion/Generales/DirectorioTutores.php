<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\Tutor;
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

    public string $slug_nivel = '';

    public ?int $nivel_id = null;
    public ?int $generacion_id = null;
    public ?int $ciclo_escolar_id = null;
    public ?int $grado_id = null;
    public ?int $semestre_id = null;
    public ?int $grupo_id = null;

    public string $modo_responsables = 'principal';
    public string $parentesco = '';
    public string $buscar = '';
    public string $orden = 'academico_alumno';
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

    public function mount(string $slug_nivel): void
    {
        $this->autorizar();
        $this->slug_nivel = $slug_nivel;

        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();

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

        $this->nivel_id = (int) $nivel->id;
        $this->ciclo_escolar_id = collect($this->ciclosEscolares)->firstWhere('es_actual', true)['id']
            ?? collect($this->ciclosEscolares)->first()['id']
            ?? null;

        $this->cargarCatalogosDependientes();
    }

    public function updatedNivelId(mixed $value): void
    {
        $this->nivel_id = $this->enteroValido($value)
            && collect($this->niveles)->contains('id', (int) $value)
                ? (int) $value
                : collect($this->niveles)->firstWhere('slug', $this->slug_nivel)['id'] ?? null;

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
        $this->grupo_id = null;
        $this->cargarGrupos();
        $this->resetPage('directorioPage');
    }

    public function updatedCicloEscolarId(mixed $value): void
    {
        $this->ciclo_escolar_id = $this->enteroValido($value) ? (int) $value : null;
        $this->grupo_id = null;
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

    public function updatedPerPage(mixed $value): void
    {
        $valor = (int) $value;
        $this->perPage = in_array($valor, [20, 40, 80], true) ? $valor : 20;
        $this->resetPage('directorioPage');
    }

    public function limpiarFiltros(): void
    {
        $nivel = collect($this->niveles)->firstWhere('slug', $this->slug_nivel);
        $ciclo = collect($this->ciclosEscolares)->firstWhere('es_actual', true)
            ?? collect($this->ciclosEscolares)->first();

        $this->nivel_id = isset($nivel['id']) ? (int) $nivel['id'] : null;
        $this->generacion_id = null;
        $this->ciclo_escolar_id = isset($ciclo['id']) ? (int) $ciclo['id'] : null;
        $this->grado_id = null;
        $this->semestre_id = null;
        $this->grupo_id = null;
        $this->modo_responsables = 'principal';
        $this->parentesco = '';
        $this->buscar = '';
        $this->orden = 'academico_alumno';
        $this->salto_grupo = true;
        $this->perPage = 20;
        $this->cargarCatalogosDependientes();
        $this->resetPage('directorioPage');
    }

    /**
     * Restaura preferencias provenientes exclusivamente del localStorage del
     * navegador. Todas las llaves vuelven a validarse contra la base de datos.
     */
    public function restaurarVistaGuardada(array $estado): void
    {
        $this->autorizar();

        $nivelId = $this->enteroValido($estado['nivel_id'] ?? null) ? (int) $estado['nivel_id'] : null;
        if (! collect($this->niveles)->contains('id', $nivelId)) {
            $nivelId = collect($this->niveles)->firstWhere('slug', $this->slug_nivel)['id'] ?? null;
        }

        $this->nivel_id = $nivelId;
        $this->generacion_id = $this->idRelacionadoValido(
            Generacion::class,
            $estado['generacion_id'] ?? null,
            ['nivel_id' => $this->nivel_id]
        );
        $this->ciclo_escolar_id = $this->idRelacionadoValido(CicloEscolar::class, $estado['ciclo_escolar_id'] ?? null);
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

        $modo = (string) ($estado['modo_responsables'] ?? 'principal');
        $orden = (string) ($estado['orden'] ?? 'academico_alumno');
        $porPagina = (int) ($estado['perPage'] ?? 20);

        $this->modo_responsables = in_array($modo, DirectorioTutoresService::MODOS_RESPONSABLES, true) ? $modo : 'principal';
        $this->parentesco = Str::upper(Str::limit(trim((string) ($estado['parentesco'] ?? '')), 50, ''));
        $this->buscar = Str::limit(trim((string) ($estado['buscar'] ?? '')), 120, '');
        $this->orden = in_array($orden, DirectorioTutoresService::ORDENES, true) ? $orden : 'academico_alumno';
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
        $filtros = $this->filtros();
        $filas = $servicio->filas($filtros);
        $metricas = $servicio->metricas($filas);
        $secciones = $servicio->secciones($filas);
        $paginadas = $this->paginar($filas);

        return view('livewire.accion.generales.directorio-tutores', [
            'filas' => $paginadas,
            'metricas' => $metricas,
            'secciones' => $secciones,
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
        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'nivel_id', 'nombre', 'anio_ingreso', 'anio_egreso', 'status'])
            ->map(fn (Generacion $generacion): array => [
                'id' => (int) $generacion->id,
                'nombre' => $generacion->etiqueta,
                'status' => (bool) $generacion->status,
            ])->all();

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden'])
            ->map(fn (Grado $grado): array => [
                'id' => (int) $grado->id,
                'nombre' => $grado->nombre,
            ])->all();

        $this->cargarSemestres();
        $this->cargarGrupos();
        $this->cargarParentescos();
    }

    private function cargarSemestres(): void
    {
        if (! $this->grado_id) {
            $this->semestres = [];
            return;
        }

        $this->semestres = Semestre::query()
            ->where('grado_id', $this->grado_id)
            ->orderBy('orden_global')
            ->orderBy('numero')
            ->get(['id', 'grado_id', 'numero', 'orden_global'])
            ->map(fn (Semestre $semestre): array => [
                'id' => (int) $semestre->id,
                'nombre' => 'Semestre ' . $semestre->numero,
            ])->all();
    }

    private function cargarGrupos(): void
    {
        $query = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'semestre:id,numero,orden_global',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'cicloEscolar:id,inicio_anio,fin_anio',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->when($this->generacion_id, fn ($q, int $id) => $q->where('generacion_id', $id))
            ->when($this->ciclo_escolar_id, fn ($q, int $id) => $q->where('ciclo_escolar_id', $id))
            ->when($this->grado_id, fn ($q, int $id) => $q->where('grado_id', $id))
            ->when($this->semestre_id, fn ($q, int $id) => $q->where('semestre_id', $id));

        $this->grupos = $query
            ->get(['id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id', 'ciclo_escolar_id', 'asignacion_grupo_id'])
            ->sortBy(fn (Grupo $grupo): string => Str::lower(implode('|', [
                str_pad((string) ($grupo->grado?->orden ?? 999), 4, '0', STR_PAD_LEFT),
                str_pad((string) ($grupo->semestre?->orden_global ?? 999), 4, '0', STR_PAD_LEFT),
                $grupo->asignacionGrupo?->nombre ?? '',
                $grupo->generacion?->etiqueta ?? '',
            ])))
            ->map(function (Grupo $grupo): array {
                $nombreGrupo = $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
                $contexto = collect([
                    ! $this->grado_id ? $grupo->grado?->nombre : null,
                    ! $this->semestre_id && $grupo->semestre?->numero
                        ? 'Semestre ' . $grupo->semestre->numero
                        : null,
                    ! $this->generacion_id ? $grupo->generacion?->etiqueta : null,
                    ! $this->ciclo_escolar_id && $grupo->cicloEscolar
                        ? $grupo->cicloEscolar->inicio_anio . '-' . $grupo->cicloEscolar->fin_anio
                        : null,
                ])->filter()->join(' · ');

                return [
                    'id' => (int) $grupo->id,
                    'nombre' => $contexto !== '' ? $nombreGrupo . ' · ' . $contexto : $nombreGrupo,
                ];
            })->values()->all();

        if ($this->grupo_id && ! collect($this->grupos)->contains('id', $this->grupo_id)) {
            $this->grupo_id = null;
        }
    }

    private function cargarParentescos(): void
    {
        $parentescos = collect();

        if (Schema::hasTable('inscripcion_tutor')) {
            $parentescos = DB::table('inscripcion_tutor')
                ->join('inscripciones', 'inscripciones.id', '=', 'inscripcion_tutor.inscripcion_id')
                ->where('inscripcion_tutor.activo', true)
                ->whereNull('inscripcion_tutor.fecha_fin')
                ->where('inscripciones.nivel_id', $this->nivel_id)
                ->where('inscripciones.activo', true)
                ->where('inscripciones.estatus', 'activo')
                ->whereNull('inscripciones.deleted_at')
                ->whereNotNull('inscripcion_tutor.parentesco')
                ->pluck('inscripcion_tutor.parentesco');
        }

        $legados = Tutor::query()
            ->whereHas('inscripciones', fn ($query) => $query
                ->where('nivel_id', $this->nivel_id)
                ->visiblesEnListas())
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
            'modo_responsables' => $this->modo_responsables,
            'parentesco' => $this->parentesco,
            'buscar' => $this->buscar,
            'orden' => $this->orden,
            'salto_grupo' => $this->salto_grupo,
        ];
    }

    private function paginar(Collection $filas): LengthAwarePaginator
    {
        $paginaActual = LengthAwarePaginator::resolveCurrentPage('directorioPage');
        $total = $filas->count();
        $ultimaPagina = max((int) ceil($total / max($this->perPage, 1)), 1);
        $paginaActual = min(max($paginaActual, 1), $ultimaPagina);

        return new LengthAwarePaginator(
            $filas->forPage($paginaActual, $this->perPage)->values(),
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
        return route('generales.directorio-tutores.descargar', ['formato' => $formato])
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
