<?php

namespace App\Livewire\Periodo;

use App\Models\Periodos;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class MostrarPeriodos extends Component
{
    use WithPagination;

    public string $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
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

        $periodo->delete();

        $this->dispatch('swal', [
            'title' => '¡Periodo eliminado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->resetPage();
    }

    public function render()
    {
        $periodos = Periodos::query()
            ->with([
                'nivel',
                'generacion',
                'semestre',
                'cicloEscolar',

                // Relaciones para básica
                'mesesBasica',
                'periodoBasica',

                // Relaciones para bachillerato
                'mesesBachillerato',
                'parcialBachillerato',
            ])
            ->when(trim($this->search) !== '', function ($query) {
                $texto = trim($this->search);
                $search = '%' . $texto . '%';

                $query->where(function ($q) use ($search) {
                    $q->where('fecha_inicio', 'like', $search)
                        ->orWhere('fecha_fin', 'like', $search)

                        // Buscar por nivel
                        ->orWhereHas('nivel', function ($q1) use ($search) {
                            $q1->where('nombre', 'like', $search)
                                ->orWhere('slug', 'like', $search);
                        })

                        // Buscar por generación
                        ->orWhereHas('generacion', function ($q2) use ($search) {
                            $q2->where('anio_ingreso', 'like', $search)
                                ->orWhere('anio_egreso', 'like', $search);
                        })

                        // Buscar por semestre
                        ->orWhereHas('semestre', function ($q3) use ($search) {
                            $q3->where('numero', 'like', $search);
                        })

                        // Buscar por ciclo escolar
                        ->orWhereHas('cicloEscolar', function ($q4) use ($search) {
                            $q4->where('inicio_anio', 'like', $search)
                                ->orWhere('fin_anio', 'like', $search);
                        })

                        // Buscar por mes de básica
                        ->orWhereHas('mesesBasica', function ($q5) use ($search) {
                            $q5->where('meses', 'like', $search)
                                ->orWhere('meses_corto', 'like', $search);
                        })

                        // Buscar por periodo de básica
                        ->orWhereHas('periodoBasica', function ($q6) use ($search) {
                            $q6->where('periodo', 'like', $search)
                                ->orWhere('descripcion', 'like', $search);
                        })

                        // Buscar por mes de bachillerato
                        ->orWhereHas('mesesBachillerato', function ($q7) use ($search) {
                            $q7->where('meses', 'like', $search)
                                ->orWhere('meses_corto', 'like', $search);
                        })

                        // Buscar por parcial de bachillerato
                        ->orWhereHas('parcialBachillerato', function ($q8) use ($search) {
                            $q8->where('parcial', 'like', $search)
                                ->orWhere('descripcion', 'like', $search);
                        })

                        // Buscar por fechas con formato mexicano
                        ->orWhereRaw("DATE_FORMAT(fecha_inicio, '%d/%m/%Y') LIKE ?", [$search])
                        ->orWhereRaw("DATE_FORMAT(fecha_fin, '%d/%m/%Y') LIKE ?", [$search]);
                });
            })

            // Orden general
            ->orderBy('nivel_id')
            ->orderBy('ciclo_escolar_id')

            // Orden para bachillerato
            ->orderBy('generacion_id')
            ->orderBy('semestre_id')
            ->orderBy('mes_bachillerato_id')
            ->orderBy('parcial_bachillerato_id')

            // Orden para básica
            ->orderBy('mes_basica_id')
            ->orderBy('periodo_basica_id')

            ->paginate(10);

        return view('livewire.periodo.mostrar-periodos', [
            'periodos' => $periodos,
        ]);
    }
}
