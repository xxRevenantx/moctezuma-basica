<?php

namespace App\Livewire\PeriodoBachillerato;

use App\Models\PeriodosBachillerato;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarPeriodosBachillerato extends Component
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
        $periodo = PeriodosBachillerato::find($id);

        if ($periodo) {
            $periodo->delete();

            $this->dispatch('swal', [
                'title' => '¡Periodo eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        }
    }

    #[On('refreshPeriodosBachillerato')]
    public function render()
    {
        $periodosBachilleratos = \App\Models\PeriodosBachillerato::with([
            'generacion',
            'semestre',
            'cicloEscolar',
            'mesesBachillerato',
        ])
            ->when($this->search, function ($query) {
                $search = '%' . $this->search . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('fecha_inicio', 'like', $search)
                        ->orWhere('fecha_fin', 'like', $search)
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
                            $q5->where('meses', 'like', $search);
                        })
                        ->orWhereRaw("DATE_FORMAT(fecha_inicio, '%d/%m/%Y') LIKE ?", [$search])
                        ->orWhereRaw("DATE_FORMAT(fecha_fin, '%d/%m/%Y') LIKE ?", [$search]);
                });
            })

            // 👉 aquí forzamos el orden por generación, luego ciclo y luego por mes
            ->orderBy('generacion_id')
            ->orderBy('ciclo_escolar_id')
            ->paginate(10);

        return view('livewire.periodo-bachillerato.mostrar-periodos-bachillerato', [
            'periodosBachilleratos' => $periodosBachilleratos,
        ]);
    }
}
