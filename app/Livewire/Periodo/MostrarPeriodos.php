<?php

namespace App\Livewire\Periodo;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarPeriodos extends Component
{
    use WithPagination;

    public string $search = '';
    public string $nivelFiltro = '';
    public string $cicloFiltro = '';
    public string $tipoFiltro = '';
    public string $generacionFiltro = '';
    public string $semestreFiltro = '';
    public string $periodoFiltro = '';
    public string $estadoFechasFiltro = '';
    public int $porPagina = 10;

    protected $paginationTheme = 'tailwind';

    public function updated(string $property): void
    {
        if (
            in_array($property, [
                'search',
                'nivelFiltro',
                'cicloFiltro',
                'tipoFiltro',
                'generacionFiltro',
                'semestreFiltro',
                'periodoFiltro',
                'estadoFechasFiltro',
                'porPagina',
            ], true)
        ) {
            $this->resetPage();
        }
    }

    /**
     * Los filtros de generación y semestre dependen del nivel.
     * Al cambiarlo se eliminan selecciones que pudieran pertenecer
     * al nivel anterior.
     */
    public function updatedNivelFiltro(): void
    {
        $this->generacionFiltro = '';
        $this->semestreFiltro = '';
        $this->resetPage();
    }

    /**
     * Evita aplicar manualmente una generación que no pertenezca
     * al nivel seleccionado o que no tenga periodos asociados.
     */
    public function updatedGeneracionFiltro($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->nivelFiltro === '') {
            $this->generacionFiltro = '';
            return;
        }

        $esValida = Generacion::query()
            ->whereKey($value)
            ->where('nivel_id', $this->nivelFiltro)
            ->whereNull('deleted_at')
            ->whereHas('periodosBachillerato', function (Builder $query) {
                $query->where('nivel_id', $this->nivelFiltro);
            })
            ->exists();

        if (!$esValida) {
            $this->generacionFiltro = '';
        }
    }

    public function limpiarFiltros(): void
    {
        $this->reset([
            'search',
            'nivelFiltro',
            'cicloFiltro',
            'tipoFiltro',
            'generacionFiltro',
            'semestreFiltro',
            'periodoFiltro',
            'estadoFechasFiltro',
        ]);

        $this->porPagina = 10;
        $this->resetPage();
    }

    #[On('refreshPeriodos')]
    public function refrescarPeriodos(): void
    {
        $this->resetPage();
    }

    public function eliminar($id): void
    {
        $periodo = Periodos::find($id);

        if (!$periodo) {
            return;
        }

        if ($periodo->calificaciones()->exists() || $periodo->bitacoraCalificaciones()->exists()) {
            $this->dispatch('swal', [
                'title' => 'No se puede eliminar el periodo porque tiene calificaciones o historial de cambios asociado.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return;
        }

        $periodo->delete();

        $this->dispatch('swal', [
            'title' => '¡Periodo eliminado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->resetPage();
    }

    private function consultaFiltrada(): Builder
    {
        return Periodos::query()
            ->with([
                'nivel',
                'generacion',
                'semestre',
                'cicloEscolar',
                'mesesBasica',
                'periodoBasica',
                'mesesBachillerato',
                'parcialBachillerato',
            ])
            ->when(trim($this->search) !== '', function (Builder $query) {
                $search = '%' . trim($this->search) . '%';

                $query->where(function (Builder $q) use ($search) {
                    $q->where('fecha_inicio', 'like', $search)
                        ->orWhere('fecha_fin', 'like', $search)
                        ->orWhereHas('nivel', fn(Builder $nivel) => $nivel
                            ->where('nombre', 'like', $search)
                            ->orWhere('slug', 'like', $search))
                        ->orWhereHas('generacion', fn(Builder $generacion) => $generacion
                            ->where('anio_ingreso', 'like', $search)
                            ->orWhere('anio_egreso', 'like', $search))
                        ->orWhereHas('semestre', fn(Builder $semestre) => $semestre
                            ->where('numero', 'like', $search))
                        ->orWhereHas('cicloEscolar', fn(Builder $ciclo) => $ciclo
                            ->where('inicio_anio', 'like', $search)
                            ->orWhere('fin_anio', 'like', $search))
                        ->orWhereHas('mesesBasica', fn(Builder $mes) => $mes
                            ->where('meses', 'like', $search)
                            ->orWhere('meses_corto', 'like', $search))
                        ->orWhereHas('periodoBasica', fn(Builder $periodo) => $periodo
                            ->where('periodo', 'like', $search)
                            ->orWhere('descripcion', 'like', $search))
                        ->orWhereHas('mesesBachillerato', fn(Builder $mes) => $mes
                            ->where('meses', 'like', $search)
                            ->orWhere('meses_corto', 'like', $search))
                        ->orWhereHas('parcialBachillerato', fn(Builder $parcial) => $parcial
                            ->where('parcial', 'like', $search)
                            ->orWhere('descripcion', 'like', $search))
                        ->orWhereRaw("DATE_FORMAT(fecha_inicio, '%d/%m/%Y') LIKE ?", [$search])
                        ->orWhereRaw("DATE_FORMAT(fecha_fin, '%d/%m/%Y') LIKE ?", [$search]);
                });
            })
            ->when($this->nivelFiltro !== '', fn(Builder $query) => $query->where('nivel_id', $this->nivelFiltro))
            ->when($this->cicloFiltro !== '', fn(Builder $query) => $query->where('ciclo_escolar_id', $this->cicloFiltro))
            ->when(
                $this->nivelFiltro !== '' && $this->generacionFiltro !== '',
                fn(Builder $query) => $query
                    ->where('nivel_id', $this->nivelFiltro)
                    ->where('generacion_id', $this->generacionFiltro)
            )
            ->when($this->semestreFiltro !== '', fn(Builder $query) => $query->where('semestre_id', $this->semestreFiltro))
            ->when($this->tipoFiltro === 'basica', fn(Builder $query) => $query->whereHas('nivel', fn(Builder $nivel) => $nivel->where('slug', '!=', 'bachillerato')))
            ->when($this->tipoFiltro === 'bachillerato', fn(Builder $query) => $query->whereHas('nivel', fn(Builder $nivel) => $nivel->where('slug', 'bachillerato')))
            ->when($this->periodoFiltro !== '', function (Builder $query) {
                [$tipo, $id] = array_pad(explode(':', $this->periodoFiltro, 2), 2, null);

                if ($tipo === 'basica' && $id) {
                    $query->where('periodo_basica_id', $id);
                }

                if ($tipo === 'bachillerato' && $id) {
                    $query->where('parcial_bachillerato_id', $id);
                }
            })
            ->when($this->estadoFechasFiltro === 'vigente', fn(Builder $query) => $query
                ->whereDate('fecha_inicio', '<=', now()->toDateString())
                ->whereDate('fecha_fin', '>=', now()->toDateString()))
            ->when($this->estadoFechasFiltro === 'proximo', fn(Builder $query) => $query
                ->whereDate('fecha_inicio', '>', now()->toDateString()))
            ->when($this->estadoFechasFiltro === 'finalizado', fn(Builder $query) => $query
                ->whereDate('fecha_fin', '<', now()->toDateString()))
            ->when($this->estadoFechasFiltro === 'sin_fechas', fn(Builder $query) => $query
                ->where(function (Builder $q) {
                    $q->whereNull('fecha_inicio')->orWhereNull('fecha_fin');
                }));
    }

    /**
     * En la tabla periodos, generacion_id se utiliza para bachillerato.
     * Por eso el filtro solo se habilita para ese nivel y únicamente
     * lista generaciones del nivel que ya tienen periodos registrados.
     */
    private function generacionesDisponibles(?Nivel $nivelSeleccionado): Collection
    {
        if (!$nivelSeleccionado || $nivelSeleccionado->slug !== 'bachillerato') {
            return collect();
        }

        return Generacion::query()
            ->where('nivel_id', $nivelSeleccionado->id)
            ->whereNull('deleted_at')
            ->whereHas('periodosBachillerato', function (Builder $query) use ($nivelSeleccionado) {
                $query->where('nivel_id', $nivelSeleccionado->id);
            })
            ->withCount([
                'periodosBachillerato as periodos_count' => function (Builder $query) use ($nivelSeleccionado) {
                    $query->where('nivel_id', $nivelSeleccionado->id);
                },
            ])
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('anio_egreso')
            ->get();
    }

    public function render()
    {
        $niveles = Nivel::query()
            ->orderBy('nombre')
            ->get();

        $nivelSeleccionado = $this->nivelFiltro !== ''
            ? $niveles->firstWhere('id', (int) $this->nivelFiltro)
            : null;

        $generaciones = $this->generacionesDisponibles($nivelSeleccionado);
        $consulta = $this->consultaFiltrada();

        $resumen = [
            'total' => (clone $consulta)->count(),
            'basica' => (clone $consulta)->whereHas('nivel', fn(Builder $q) => $q->where('slug', '!=', 'bachillerato'))->count(),
            'bachillerato' => (clone $consulta)->whereHas('nivel', fn(Builder $q) => $q->where('slug', 'bachillerato'))->count(),
            'vigentes' => (clone $consulta)
                ->whereDate('fecha_inicio', '<=', now()->toDateString())
                ->whereDate('fecha_fin', '>=', now()->toDateString())
                ->count(),
        ];

        $periodos = $consulta
            ->orderBy('nivel_id')
            ->orderByDesc('ciclo_escolar_id')
            ->orderBy('generacion_id')
            ->orderBy('semestre_id')
            ->orderBy('mes_bachillerato_id')
            ->orderBy('parcial_bachillerato_id')
            ->orderBy('mes_basica_id')
            ->orderBy('periodo_basica_id')
            ->paginate($this->porPagina);

        return view('livewire.periodo.mostrar-periodos', [
            'periodos' => $periodos,
            'resumen' => $resumen,
            'niveles' => $niveles,
            'nivelSeleccionado' => $nivelSeleccionado,
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
            'generaciones' => $generaciones,
            'semestres' => Semestre::query()->orderBy('numero')->get(),
            'periodosBasica' => PeriodosBasica::query()->orderBy('periodo')->get(),
            'parciales' => Parcial::query()->orderBy('parcial')->get(),
        ]);
    }
}
