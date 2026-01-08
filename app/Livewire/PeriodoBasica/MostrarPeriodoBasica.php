<?php

namespace App\Livewire\PeriodoBasica;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Periodos_basico;
use Livewire\Attributes\On;

class MostrarPeriodoBasica extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    // Cuando cambia el buscador, regresamos a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }


    public function eliminar($periodoId)
    {
        $periodo = Periodos_basico::find($periodoId);

        if ($periodo) {
            $periodo->delete();
            $this->dispatch('swal', [
                'title' => 'Periodo eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } else {
            $this->dispatch('swal', [
                'title' => 'Error al eliminar el periodo.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    #[On('refreshPeriodosBasica')]
    public function render()
    {
        $periodosBasicos = Periodos_basico::with(['cicloEscolar', 'periodos'])
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';
                $query->where(function ($q) use ($search) {
                    $q->where('parcial_inicio', 'like', $search)
                        ->orWhere('parcial_fin', 'like', $search)
                        ->orWhereHas('cicloEscolar', function ($q2) use ($search) {
                            $q2->where('inicio_anio', 'like', $search)
                                ->orWhere('fin_anio', 'like', $search);
                        });
                });
            })
            ->paginate(10);


        return view('livewire.periodo-basica.mostrar-periodo-basica', compact('periodosBasicos'));
    }

}
