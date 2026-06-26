<?php

namespace App\Livewire\Accion\Generales;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Services\DistribucionEscolarService;
use Illuminate\Support\Collection;
use Livewire\Component;

class DistribucionHistorial extends Component
{
    public string $slug_nivel = '';
    public ?Nivel $nivel = null;

    public Collection $cicloEscolares;
    public Collection $generaciones;
    public Collection $grados;

    public string $modo = 'ciclo';
    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';
    public string $estado = 'todos';
    public bool $solo_ya_no_estan = false;

    public bool $modalDetalle = false;
    public array $contextoDetalle = [];
    public string $buscar_detalle = '';
    public string $estado_detalle = 'todos';

    public bool $modalTrayectoria = false;
    public ?int $inscripcionSeleccionadaId = null;

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403, 'Solo administración puede consultar el historial escolar.');

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()
            ->with('director')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('anio_ingreso')
            ->orderBy('anio_egreso')
            ->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get(['id', 'nivel_id', 'nombre', 'orden']);

        $cicloActual = $this->cicloEscolares->firstWhere('es_actual', true)
            ?: $this->cicloEscolares->first();

        $this->ciclo_escolar_id = (string) ($cicloActual?->id ?? '');
    }

    public function updatedModo(string $valor): void
    {
        if (!in_array($valor, ['ciclo', 'historico'], true)) {
            $this->modo = 'ciclo';
        }

        $this->cerrarDetalle();
    }

    public function updatedCicloEscolarId(): void
    {
        $this->cerrarDetalle();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = '';
        $this->cerrarDetalle();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
        $this->cerrarDetalle();
    }

    public function updatedGrupoId(): void
    {
        $this->cerrarDetalle();
    }

    public function updatedEstado(): void
    {
        $this->cerrarDetalle();
    }

    public function updatedSoloYaNoEstan(): void
    {
        $this->cerrarDetalle();
    }

    public function getBloquesProperty(): Collection
    {
        if (!$this->nivel) {
            return collect();
        }

        return app(DistribucionEscolarService::class)->bloques($this->nivel, $this->filtrosReporte());
    }

    public function getCategoriasProperty(): array
    {
        return app(DistribucionEscolarService::class)->categorias();
    }

    public function getGruposFiltroProperty(): Collection
    {
        if (!$this->nivel) {
            return collect();
        }

        return Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->grado_id !== '', fn ($query) => $query->where('grado_id', (int) $this->grado_id))
            ->when($this->generacion_id !== '', fn ($query) => $query->where('generacion_id', (int) $this->generacion_id))
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('asignacion_grupo_id')
            ->get(['id', 'asignacion_grupo_id', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id']);
    }

    public function abrirDetalle(
        int $cicloEscolarId,
        ?int $gradoId,
        ?int $grupoId,
        ?int $generacionId,
        ?int $semestreId = null
    ): void {
        $this->contextoDetalle = [
            'ciclo_escolar_id' => $cicloEscolarId,
            'grado_id' => $gradoId,
            'grupo_id' => $grupoId,
            'generacion_id' => $generacionId,
            'semestre_id' => $semestreId,
        ];

        $this->buscar_detalle = '';
        $this->estado_detalle = 'todos';
        $this->modalDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        $this->modalDetalle = false;
        $this->contextoDetalle = [];
        $this->buscar_detalle = '';
        $this->estado_detalle = 'todos';
        $this->cerrarTrayectoria();
    }

    public function getDetalleAlumnosProperty(): Collection
    {
        if (!$this->nivel || $this->contextoDetalle === []) {
            return collect();
        }

        return app(DistribucionEscolarService::class)->detalleFila(
            $this->nivel,
            $this->contextoDetalle,
            array_merge($this->filtrosReporte(), [
                'buscar' => $this->buscar_detalle,
                'estado_detalle' => $this->estado_detalle,
            ])
        );
    }

    public function getTituloDetalleProperty(): string
    {
        if ($this->contextoDetalle === []) {
            return 'Listado nominal';
        }

        $ciclo = $this->cicloEscolares->firstWhere('id', (int) ($this->contextoDetalle['ciclo_escolar_id'] ?? 0));
        $grado = $this->grados->firstWhere('id', (int) ($this->contextoDetalle['grado_id'] ?? 0));
        $generacion = $this->generaciones->firstWhere('id', (int) ($this->contextoDetalle['generacion_id'] ?? 0));
        $grupo = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->contextoDetalle['grupo_id'] ?? null);

        $partes = array_filter([
            $ciclo?->nombre,
            $grado ? $grado->nombre . '°' : null,
            $grupo?->asignacionGrupo?->nombre ? 'Grupo ' . $grupo->asignacionGrupo->nombre : null,
            $generacion ? 'Generación ' . $generacion->anio_ingreso . '-' . $generacion->anio_egreso : null,
        ]);

        return $partes !== [] ? implode(' · ', $partes) : 'Listado nominal';
    }

    public function abrirTrayectoria(int $inscripcionId): void
    {
        $this->inscripcionSeleccionadaId = $inscripcionId;
        $this->modalTrayectoria = true;
    }

    public function cerrarTrayectoria(): void
    {
        $this->modalTrayectoria = false;
        $this->inscripcionSeleccionadaId = null;
    }

    public function getTrayectoriaSeleccionadaProperty(): array
    {
        if (!$this->nivel || !$this->inscripcionSeleccionadaId) {
            return [
                'alumno' => [],
                'trayectorias' => collect(),
                'movimientos' => collect(),
            ];
        }

        return app(DistribucionEscolarService::class)
            ->trayectoriaAlumno($this->nivel, $this->inscripcionSeleccionadaId);
    }

    public function limpiarFiltros(): void
    {
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->estado = 'todos';
        $this->solo_ya_no_estan = false;

        $cicloActual = $this->cicloEscolares->firstWhere('es_actual', true)
            ?: $this->cicloEscolares->first();

        $this->ciclo_escolar_id = (string) ($cicloActual?->id ?? '');
        $this->cerrarDetalle();
    }

    public function claseBadgeEstado(string $categoria, bool $estadoActual = false): string
    {
        if ($estadoActual) {
            return $categoria === 'activo'
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200'
                : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200';
        }

        return match ($categoria) {
            'activo' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200',
            'baja' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-200',
            'traslado' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-200',
            'suspendido' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-200',
            'egresado' => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-200',
            default => 'bg-slate-200 text-slate-700 dark:bg-neutral-700 dark:text-slate-200',
        };
    }

    public function claseTarjetaEtapa(string $categoria): string
    {
        return match ($categoria) {
            'activo' => 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/40 dark:bg-emerald-950/20',
            'baja' => 'border-rose-200 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/20',
            'traslado' => 'border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20',
            'egresado' => 'border-violet-200 bg-violet-50 dark:border-violet-900/40 dark:bg-violet-950/20',
            default => 'border-slate-200 bg-slate-50 dark:border-neutral-700 dark:bg-neutral-800',
        };
    }

    public function getUrlPdfProperty(): string
    {
        return route('generales.distribucion.pdf', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->parametrosExportacion()
        ));
    }

    public function getUrlExcelProperty(): string
    {
        return route('generales.distribucion.excel', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->parametrosExportacion()
        ));
    }

    public function getUrlZipProperty(): string
    {
        return route('generales.distribucion.zip', array_merge(
            ['slug_nivel' => $this->slug_nivel],
            $this->parametrosExportacion()
        ));
    }

    private function filtrosReporte(): array
    {
        return [
            'ciclo_escolar_id' => $this->modo === 'ciclo' && $this->ciclo_escolar_id !== ''
                ? (int) $this->ciclo_escolar_id
                : null,
            'generacion_id' => $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            'grado_id' => $this->grado_id !== '' ? (int) $this->grado_id : null,
            'grupo_id' => $this->grupo_id !== '' ? (int) $this->grupo_id : null,
            'estado' => $this->estado,
            'solo_ya_no_estan' => $this->solo_ya_no_estan,
        ];
    }

    private function parametrosExportacion(): array
    {
        return array_filter([
            'modo' => $this->modo,
            'ciclo_escolar_id' => $this->modo === 'ciclo' ? $this->ciclo_escolar_id : null,
            'generacion_id' => $this->generacion_id ?: null,
            'grado_id' => $this->grado_id ?: null,
            'grupo_id' => $this->grupo_id ?: null,
            'estado' => $this->estado !== 'todos' ? $this->estado : null,
            'solo_ya_no_estan' => $this->solo_ya_no_estan ? 1 : null,
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    public function render()
    {
        return view('livewire.accion.generales.distribucion-historial', [
            'historial' => $this->trayectoriaSeleccionada,
        ]);
    }
}
