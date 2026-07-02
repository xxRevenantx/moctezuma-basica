<?php

namespace App\Livewire\CicloEscolar;

use App\Models\CicloEscolar;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarCicloEscolar extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;
    protected $queryString = ['search' => ['except' => '']];

    public function mount(): void { abort_unless(auth()->user()?->is_admin, 403); }
    public function updatingSearch(): void { $this->resetPage(); }
    #[On('refreshCiclos')] public function refreshCiclos(): void { $this->resetPage(); }

    public function marcarActual(int $cicloId): void
    {
        DB::transaction(function () use ($cicloId): void {
            $nuevo = CicloEscolar::query()->lockForUpdate()->findOrFail($cicloId);
            CicloEscolar::query()->where('es_actual', true)->whereKeyNot($nuevo->id)->lockForUpdate()->get()->each(function (CicloEscolar $anterior): void {
                $anterior->update(['es_actual' => false, 'cerrado_at' => $anterior->cerrado_at ?: now(), 'cerrado_por' => auth()->id()]);
            });
            $nuevo->update(['es_actual' => true, 'cerrado_at' => null, 'cerrado_por' => null]);
        });
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Ciclo actual actualizado', 'text' => 'Los alumnos no fueron movidos. Su ubicación se administra por generación.', 'position' => 'top-end']);
        $this->dispatch('refreshHeader');
        $this->dispatch('refreshCiclos');
    }

    public function alternarCierre(int $cicloId): void
    {
        $ciclo = CicloEscolar::query()->findOrFail($cicloId);
        if ($ciclo->es_actual && ! $ciclo->cerrado_at) {
            $this->dispatch('swal', ['icon' => 'warning', 'title' => 'No se puede cerrar el ciclo actual', 'text' => 'Marca primero otro ciclo como actual.', 'position' => 'top-end']);
            return;
        }
        $ciclo->update($ciclo->cerrado_at ? ['cerrado_at' => null, 'cerrado_por' => null] : ['cerrado_at' => now(), 'cerrado_por' => auth()->id()]);
        $this->dispatch('swal', ['icon' => 'success', 'title' => $ciclo->fresh()->cerrado_at ? 'Ciclo cerrado' : 'Ciclo reabierto', 'position' => 'top-end']);
    }

    public function eliminar(int $cicloId): void
    {
        $ciclo = CicloEscolar::query()->withCount(['calificaciones', 'periodos', 'asignacionMaterias', 'horarios', 'tallerSesiones'])->findOrFail($cicloId);
        if ($ciclo->es_actual) { $this->addError('eliminar', 'El ciclo actual no puede eliminarse.'); return; }
        if ($ciclo->calificaciones_count || $ciclo->periodos_count || $ciclo->asignacion_materias_count || $ciclo->horarios_count || $ciclo->taller_sesiones_count) {
            $this->addError('eliminar', 'Este ciclo contiene periodos, cargas, talleres, horarios o calificaciones. Puedes cerrarlo, pero no eliminarlo.');
            return;
        }
        $ciclo->delete();
        $this->dispatch('swal', ['icon' => 'success', 'title' => 'Ciclo eliminado', 'position' => 'top-end']);
    }

    public function render()
    {
        $ciclos = CicloEscolar::query()
            ->withCount(['calificaciones', 'periodos', 'asignacionMaterias', 'horarios'])
            ->when(trim($this->search) !== '', fn ($q) => $q->whereRaw("CONCAT(inicio_anio, '-', fin_anio) LIKE ?", ['%' . trim($this->search) . '%']))
            ->orderByDesc('es_actual')->orderByDesc('inicio_anio')->paginate($this->perPage);
        return view('livewire.ciclo-escolar.mostrar-ciclo-escolar', compact('ciclos'));
    }
}
