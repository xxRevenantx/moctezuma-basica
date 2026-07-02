<?php

namespace App\Livewire\Accion;

use App\Exports\MatriculaExport;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class Generales extends Component
{
    public ?Nivel $nivel = null;
    public Collection $niveles;
    public Collection $grados;
    public Collection $generaciones;
    public string $slug_nivel = '';
    public string $generacion_id = '';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->with('director')->where('slug', $slug_nivel)->firstOrFail();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->grados = Grado::query()->where('nivel_id', $this->nivel->id)->orderBy('orden')->get();
        $this->generaciones = Generacion::query()->where('nivel_id', $this->nivel->id)
            ->orderByDesc('status')->orderByDesc('anio_ingreso')->get();
    }

    public function limpiarFiltroEstadistica(): void
    {
        $this->generacion_id = '';
    }

    private function alumnosQuery(): Builder
    {
        return Inscripcion::query()
            ->with(['generacion', 'grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->generacion_id !== '', fn (Builder $q) => $q->where('generacion_id', $this->generacion_id));
    }

    public function getResumenProperty(): array
    {
        $base = $this->alumnosQuery();
        return [
            'total' => (clone $base)->count(),
            'hombres' => (clone $base)->where('genero', 'H')->count(),
            'mujeres' => (clone $base)->where('genero', 'M')->count(),
            'activos' => (clone $base)->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])->count(),
            'bajas' => (clone $base)->whereIn('estatus', ['baja_temporal', 'baja_definitiva'])->count(),
            'trasladados' => (clone $base)->where('estatus', 'trasladado')->count(),
            'suspendidos' => (clone $base)->where('estatus', 'suspendido')->count(),
            'egresados' => (clone $base)->where('estatus', 'egresado')->count(),
            'inactivos' => (clone $base)->where('estatus', 'inactivo')->count(),
            'reingresos' => (clone $base)->where('estatus', 'reingreso')->count(),
        ];
    }

    public function getDistribucionEscolarProperty(): Collection
    {
        return $this->alumnosQuery()->get()
            ->groupBy(fn (Inscripcion $a) => ($a->grado_id ?: 0) . '|' . ($a->semestre_id ?: 0) . '|' . ($a->grupo_id ?: 0))
            ->map(function (Collection $grupo): array {
                /** @var Inscripcion $primero */
                $primero = $grupo->first();
                return [
                    'grado' => $primero->grado?->nombre ?? 'Sin grado',
                    'semestre' => $primero->semestre?->numero,
                    'grupo' => $primero->grupo?->asignacionGrupo?->nombre ?? '—',
                    'hombres' => $grupo->where('genero', 'H')->count(),
                    'mujeres' => $grupo->where('genero', 'M')->count(),
                    'total' => $grupo->count(),
                    'activos' => $grupo->whereIn('estatus', ['activo', 'reingreso', 'no_promovido'])->count(),
                    'bajas' => $grupo->whereIn('estatus', ['baja_temporal', 'baja_definitiva'])->count(),
                    'egresados' => $grupo->where('estatus', 'egresado')->count(),
                    'orden' => (int) ($primero->grado?->orden ?? 999),
                    'semestre_orden' => (int) ($primero->semestre?->orden_global ?? 0),
                ];
            })->sortBy(fn (array $fila) => sprintf('%04d|%04d|%s', $fila['orden'], $fila['semestre_orden'], $fila['grupo']))->values();
    }

    public function exportarEstadisticaExcel()
    {
        $rows = $this->alumnosQuery()->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')->get();
        return Excel::download(
            new MatriculaExport($rows, $this->nivel->nombre, $this->slug_nivel === 'bachillerato'),
            'padron_generacion_' . $this->slug_nivel . '_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function render()
    {
        return view('livewire.accion.generales');
    }
}
