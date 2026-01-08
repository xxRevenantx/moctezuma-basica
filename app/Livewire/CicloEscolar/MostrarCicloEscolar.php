<?php

namespace App\Livewire\CicloEscolar;

use App\Models\CicloEscolar;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarCicloEscolar extends Component
{
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('refreshCiclos')]
    public function refreshCiclos(): void
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function eliminar(int $cicloId): void
    {
        $ciclo = CicloEscolar::findOrFail($cicloId);
        $ciclo->delete();

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Ciclo escolar eliminado',
            'position' => 'top-end',
        ]);

        $this->refreshCiclos();
    }

    public function render()
    {
        $query = CicloEscolar::query()
            ->when($this->search !== '', function ($q) {
                $s = trim($this->search);

                // Permite buscar por: "2025", "2026", "2025-2026", "2025 2026"
                $nums = preg_split('/\D+/', $s, -1, PREG_SPLIT_NO_EMPTY);

                $q->where(function ($qq) use ($s, $nums) {
                    if (count($nums) >= 1) {
                        $qq->orWhere('inicio_anio', (int) $nums[0])
                            ->orWhere('fin_anio', (int) $nums[0]);
                    }

                    if (count($nums) >= 2) {
                        $qq->orWhere(function ($qq2) use ($nums) {
                            $qq2->where('inicio_anio', (int) $nums[0])
                                ->where('fin_anio', (int) $nums[1]);
                        });
                    }

                    // fallback por si escriben raro
                    $qq->orWhereRaw("CONCAT(inicio_anio,'-',fin_anio) LIKE ?", ["%{$s}%"]);
                });
            })
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio');

        $ciclos = $query->paginate($this->perPage);

        return view('livewire.ciclo-escolar.mostrar-ciclo-escolar', [
            'ciclos' => $ciclos,
        ]);
    }
}
