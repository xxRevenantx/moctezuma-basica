<?php

namespace App\Livewire\Academico;

use App\Models\AnaliticaInstitucionalSnapshot;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Services\AnaliticaInstitucionalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class AnaliticaInstitucional extends Component
{
    public array $ciclos = [];
    public array $niveles = [];
    public array $generaciones = [];
    public array $grados = [];
    public array $grupos = [];

    public string $ciclo_escolar_id = '';
    public string $ciclo_comparacion_id = '';
    public string $nivel_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';

    public array $datos = [];
    public ?string $ultimo_snapshot_at = null;
    public bool $cargando = false;

    public function mount(AnaliticaInstitucionalService $service): void
    {
        abort_unless(auth()->user()?->canAccess('analitica.consultar'), 403);

        $this->ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get()
            ->map(fn (CicloEscolar $ciclo) => ['id' => $ciclo->id, 'nombre' => $ciclo->nombre, 'actual' => $ciclo->es_actual])->all();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre'])->toArray();
        $actual = collect($this->ciclos)->firstWhere('actual', true) ?? collect($this->ciclos)->first();
        $this->ciclo_escolar_id = (string) ($actual['id'] ?? '');
        $this->asignarComparacionPredeterminada();
        $this->cargarCatalogosDependientes();
        $this->cargarDatos($service);
    }

    public function updatedCicloEscolarId(): void
    {
        $this->asignarComparacionPredeterminada();
        $this->reset(['generacion_id', 'grado_id', 'grupo_id']);
        $this->cargarCatalogosDependientes();
    }

    public function updatedNivelId(): void
    {
        $this->reset(['generacion_id', 'grado_id', 'grupo_id']);
        $this->cargarCatalogosDependientes();
    }

    public function updatedGeneracionId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
    }

    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
        $this->cargarGrupos();
    }

    public function aplicarFiltros(AnaliticaInstitucionalService $service): void
    {
        $this->cargarDatos($service);
        $this->dispatch('notify', type: 'success', message: 'Indicadores institucionales actualizados.');
    }

    public function limpiarFiltros(AnaliticaInstitucionalService $service): void
    {
        $this->nivel_id = '';
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->asignarComparacionPredeterminada();
        $this->cargarCatalogosDependientes();
        $this->cargarDatos($service);
    }

    public function guardarSnapshot(AnaliticaInstitucionalService $service): void
    {
        abort_unless(auth()->user()?->canAccess('analitica.gestionar'), 403);
        if ($this->datos === []) {
            $this->cargarDatos($service);
        }
        $snapshot = $service->guardarSnapshot($this->datos, (int) auth()->id(), 'manual');
        $this->ultimo_snapshot_at = $snapshot->generado_at?->format('d/m/Y H:i');
        $this->dispatch('notify', type: 'success', message: 'Instantánea directiva guardada con firma de integridad.');
    }

    private function cargarDatos(AnaliticaInstitucionalService $service): void
    {
        $this->cargando = true;
        try {
            $this->datos = $service->generar($this->filtros());
            $ultimo = $service->ultimoSnapshot($this->filtros());
            $this->ultimo_snapshot_at = $ultimo?->generado_at?->format('d/m/Y H:i');
            $this->dispatch('analitica-actualizada', graficas: $this->graficas());
        } finally {
            $this->cargando = false;
        }
    }

    private function filtros(): array
    {
        return [
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'ciclo_comparacion_id' => $this->ciclo_comparacion_id,
            'nivel_id' => $this->nivel_id,
            'generacion_id' => $this->generacion_id,
            'grado_id' => $this->grado_id,
            'grupo_id' => $this->grupo_id,
        ];
    }

    public function getParametrosReporteProperty(): array
    {
        return array_filter($this->filtros(), fn ($valor) => filled($valor));
    }

    private function graficas(): array
    {
        return [
            'tendencia' => $this->datos['tendencia_ciclos'] ?? [],
            'niveles' => $this->datos['distribucion_niveles'] ?? [],
            'riesgo' => $this->datos['riesgo'] ?? [],
            'rendimiento' => $this->datos['rendimiento']['materias_reprobacion'] ?? [],
        ];
    }

    private function cargarCatalogosDependientes(): void
    {
        $this->generaciones = Generacion::query()
            ->when($this->nivel_id !== '', fn ($q) => $q->where('nivel_id', $this->nivel_id))
            ->orderByDesc('anio_ingreso')->get()
            ->map(fn (Generacion $g) => ['id' => $g->id, 'nombre' => $g->etiqueta])->all();

        $this->grados = Grado::query()
            ->when($this->nivel_id !== '', fn ($q) => $q->where('nivel_id', $this->nivel_id))
            ->orderBy('orden')->get(['id', 'nombre'])->toArray();

        $this->cargarGrupos();
    }

    private function cargarGrupos(): void
    {
        $query = Grupo::query()->with('asignacionGrupo')
            ->when($this->ciclo_escolar_id !== '' && Schema::hasColumn('grupos', 'ciclo_escolar_id'), fn ($q) => $q->where('ciclo_escolar_id', $this->ciclo_escolar_id))
            ->when($this->nivel_id !== '', fn ($q) => $q->where('nivel_id', $this->nivel_id))
            ->when($this->generacion_id !== '', fn ($q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn ($q) => $q->where('grado_id', $this->grado_id))
            ->orderBy('grado_id')->orderBy('asignacion_grupo_id');

        $this->grupos = $query->get()->map(fn (Grupo $g) => [
            'id' => $g->id,
            'nombre' => trim(($g->grado?->nombre ? $g->grado->nombre.' · ' : '').($g->asignacionGrupo?->nombre ?? $g->clave ?? 'Grupo')),
        ])->all();
    }

    private function asignarComparacionPredeterminada(): void
    {
        $ids = collect($this->ciclos)->pluck('id')->values();
        $indice = $ids->search((int) $this->ciclo_escolar_id);
        $this->ciclo_comparacion_id = $indice !== false && $ids->has($indice + 1) ? (string) $ids[$indice + 1] : '';
    }

    public function render()
    {
        return view('livewire.academico.analitica-institucional')
            ->layout('components.layouts.app');
    }
}
