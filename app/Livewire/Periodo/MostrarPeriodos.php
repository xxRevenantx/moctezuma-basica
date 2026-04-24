<?php

namespace App\Livewire\Periodo;

use App\Models\Periodos;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarPeriodos extends Component
{
    use WithPagination;

    public $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function eliminar($id)
    {
        $periodo = Periodos::find($id);

        if ($periodo) {
            $periodo->delete();

            $this->dispatch('swal', [
                'title' => '¡Periodo eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        }
    }

    #[On('refreshPeriodos')]
    public function render()
    {
        $periodos = Periodos::with([
            'nivel',
            'generacion',
            'semestre',
            'cicloEscolar',
            'mesesBachillerato',
            'parcialBachillerato',
        ])
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('fecha_inicio', 'like', $search)
                        ->orWhere('fecha_fin', 'like', $search)

                        ->orWhereHas('nivel', function ($q1) use ($search) {
                            $q1->where('nombre', 'like', $search);
                        })

                        ->orWhereHas('generacion', function ($q2) use ($search) {
                            $q2->where('anio_ingreso', 'like', $search)
                                ->orWhere('anio_egreso', 'like', $search);
                        })

                        ->orWhereHas('semestre', function ($q3) use ($search) {
                            $q3->where('numero', 'like', $search);
                        })

                        ->orWhereHas('cicloEscolar', function ($q4) use ($search) {
                            $q4->where('inicio_anio', 'like', $search)
                                ->orWhere('fin_anio', 'like', $search);
                        })

                        ->orWhereHas('mesesBachillerato', function ($q5) use ($search) {
                            $q5->where('meses', 'like', $search)
                                ->orWhere('meses_corto', 'like', $search);
                        })

                        ->orWhereHas('parcialBachillerato', function ($q6) use ($search) {
                            $q6->where('parcial', 'like', $search)
                                ->orWhere('descripcion', 'like', $search);
                        })

                        ->orWhereRaw("DATE_FORMAT(fecha_inicio, '%d/%m/%Y') LIKE ?", [$search])
                        ->orWhereRaw("DATE_FORMAT(fecha_fin, '%d/%m/%Y') LIKE ?", [$search]);
                });
            })

            // Ordeno por nivel, generación, ciclo, semestre, mes y parcial.
            ->orderBy('nivel_id')
            ->orderBy('generacion_id')
            ->orderBy('ciclo_escolar_id')
            ->orderBy('semestre_id')
            ->orderBy('mes_bachillerato_id')
            ->orderBy('parcial_bachillerato_id')
            ->paginate(10);

        return view('livewire.periodo.mostrar-periodos', [
            'periodos' => $periodos,
        ]);
    }
}
