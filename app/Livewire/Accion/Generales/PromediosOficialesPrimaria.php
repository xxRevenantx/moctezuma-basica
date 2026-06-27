<?php

namespace App\Livewire\Accion\Generales;

use App\Exports\CalificacionesOficialesPrimariaExport;
use App\Models\CicloEscolar;
use App\Models\DecisionPromocionOficial;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Services\CalificacionOficialPrimariaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PromediosOficialesPrimaria extends Component
{
    public string $slug_nivel = 'primaria';
    public int $nivelId;

    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $grupos;

    public string $ciclo_escolar_id = '';
    public string $generacion_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;
        $nivel = Nivel::query()->where('slug', 'primaria')->firstOrFail();
        $this->nivelId = (int) $nivel->id;

        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_escolar_id = (string) ($this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id
            ?? '');

        $this->generaciones = Generacion::query()
            ->where('nivel_id', $this->nivelId)
            ->orderByDesc('anio_ingreso')
            ->get(['id', 'anio_ingreso', 'anio_egreso', 'status']);

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivelId)
            ->orderBy('orden')
            ->get(['id', 'nombre', 'orden']);

        $this->grupos = collect();
        $this->cargarGrupos();
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

    public function limpiarFiltros(): void
    {
        $this->generacion_id = '';
        $this->grado_id = '';
        $this->grupo_id = '';
        $this->cargarGrupos();
    }

    private function cargarGrupos(): void
    {
        $this->grupos = Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $this->nivelId)
            ->when($this->generacion_id !== '', fn ($query) => $query->where('generacion_id', $this->generacion_id))
            ->when($this->grado_id !== '', fn ($query) => $query->where('grado_id', $this->grado_id))
            ->get(['id', 'asignacion_grupo_id', 'grado_id', 'generacion_id'])
            ->sortBy(fn (Grupo $grupo) => $grupo->asignacionGrupo?->nombre ?? '')
            ->values();
    }

    public function getReporteProperty(): array
    {
        if ($this->ciclo_escolar_id === '') {
            return app(CalificacionOficialPrimariaService::class)->reporteAnual(
                nivelId: $this->nivelId,
                cicloEscolarId: 0,
            );
        }

        return app(CalificacionOficialPrimariaService::class)->reporteAnual(
            nivelId: $this->nivelId,
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            generacionId: $this->generacion_id !== '' ? (int) $this->generacion_id : null,
            gradoId: $this->grado_id !== '' ? (int) $this->grado_id : null,
            grupoId: $this->grupo_id !== '' ? (int) $this->grupo_id : null,
        );
    }

    public function confirmarPromocion(int $inscripcionId, bool $promovido): void
    {
        $fila = collect($this->reporte['alumnos'])->firstWhere('inscripcion_id', $inscripcionId);

        if (! $fila) {
            $this->addError('promocion', 'No se encontró al alumno dentro del reporte actual.');
            return;
        }

        if ($fila['promedio_general_preciso'] === null && (int) $fila['grado_orden'] !== 1) {
            $this->addError('promocion', 'No se puede confirmar la promoción mientras falten campos o periodos oficiales.');
            return;
        }

        DecisionPromocionOficial::query()->updateOrCreate(
            [
                'inscripcion_id' => $inscripcionId,
                'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
                'grado_id' => (int) $fila['grado_id'],
            ],
            [
                'trayectoria_academica_id' => $fila['trayectoria_academica_id'],
                'nivel_id' => $this->nivelId,
                'grupo_id' => (int) $fila['grupo_id'],
                'generacion_id' => (int) $fila['generacion_id'],
                'promedio_final' => $fila['promedio_general_preciso'],
                'promocion_sugerida' => $fila['promocion_sugerida'],
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

    public function exportarExcel(): BinaryFileResponse
    {
        $ciclo = $this->ciclosEscolares->firstWhere('id', (int) $this->ciclo_escolar_id);
        $textoCiclo = $ciclo ? $ciclo->inicio_anio . '-' . $ciclo->fin_anio : 'SIN_CICLO';

        return Excel::download(
            new CalificacionesOficialesPrimariaExport($this->reporte, $textoCiclo),
            'EVALUACION_OFICIAL_PRIMARIA_' . Str::slug($textoCiclo, '_') . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function getPdfUrlProperty(): string
    {
        return route('generales.promedios-oficiales-primaria.pdf', [
            'ciclo_escolar_id' => $this->ciclo_escolar_id,
            'generacion_id' => $this->generacion_id ?: null,
            'grado_id' => $this->grado_id ?: null,
            'grupo_id' => $this->grupo_id ?: null,
        ]);
    }

    public function render()
    {
        return view('livewire.accion.generales.promedios-oficiales-primaria');
    }
}
