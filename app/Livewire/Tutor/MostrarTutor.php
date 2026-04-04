<?php

namespace App\Livewire\Tutor;

use App\Exports\TutorsExport;
use App\Models\Tutor;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MostrarTutor extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $buscar = '';
    public string $ordenCampo = 'id';
    public string $ordenDireccion = 'desc';

    protected $queryString = [
        'buscar' => ['except' => ''],
    ];

    // Este método reinicia la paginación cuando cambia la búsqueda
    public function updatingBuscar(): void
    {
        $this->resetPage();
    }

    // Este método permite ordenar la tabla
    public function ordenarPor(string $campo): void
    {
        if ($this->ordenCampo === $campo) {
            $this->ordenDireccion = $this->ordenDireccion === 'asc' ? 'desc' : 'asc';
        } else {
            $this->ordenCampo = $campo;
            $this->ordenDireccion = 'asc';
        }

        $this->resetPage();
    }

    // Este método emite el evento para abrir el modal de edición
    public function editar(int $id): void
    {
        $this->dispatch('editarModal', id: $id);
    }

    // Este método emite el evento para abrir el modal de eliminación
    public function eliminar(int $id): void
    {
        //Eliminar tutor
        $tutor = Tutor::find($id);

        if ($tutor) {
            $tutor->delete();

            $this->dispatch('swal', [
                'title' => '¡Tutor eliminado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        }

    }

    public function exportarTutores()
    {
        $tutoresFiltrados = Tutor::query()
            ->where(function ($query) {
                $query->where('nombre', 'like', '%' . $this->buscar . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $this->buscar . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $this->buscar . '%')
                    ->orWhere('curp', 'like', '%' . $this->buscar . '%')
                    ->orWhere('parentesco', 'like', '%' . $this->buscar . '%')
                    ->orWhere('telefono_celular', 'like', '%' . $this->buscar . '%')
                    ->orWhere('telefono_casa', 'like', '%' . $this->buscar . '%')
                    ->orWhere('correo_electronico', 'like', '%' . $this->buscar . '%')
                    ->orWhere('ciudad', 'like', '%' . $this->buscar . '%')
                    ->orWhere('municipio', 'like', '%' . $this->buscar . '%')
                    ->orWhere('estado', 'like', '%' . $this->buscar . '%');
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        return Excel::download(
            new TutorsExport($tutoresFiltrados),
            'tutores_' . now()->format('Y_m_d_H_i_s') . '.xlsx'
        );
    }



    #[On('refreshTutor')]
    public function render()
    {
        $tutores = Tutor::query()
            ->when($this->buscar, function ($query) {
                $query->where(function ($q) {
                    $q->where('curp', 'like', '%' . $this->buscar . '%')
                        ->orWhere('parentesco', 'like', '%' . $this->buscar . '%')
                        ->orWhere('nombre', 'like', '%' . $this->buscar . '%')
                        ->orWhere('apellido_paterno', 'like', '%' . $this->buscar . '%')
                        ->orWhere('apellido_materno', 'like', '%' . $this->buscar . '%')
                        ->orWhere('telefono_celular', 'like', '%' . $this->buscar . '%')
                        ->orWhere('telefono_casa', 'like', '%' . $this->buscar . '%')
                        ->orWhere('correo_electronico', 'like', '%' . $this->buscar . '%')
                        ->orWhere('ciudad', 'like', '%' . $this->buscar . '%')
                        ->orWhere('municipio', 'like', '%' . $this->buscar . '%')
                        ->orWhere('estado', 'like', '%' . $this->buscar . '%');
                });
            })
            ->orderBy($this->ordenCampo, $this->ordenDireccion)
            ->paginate(10);

        return view('livewire.tutor.mostrar-tutor', compact('tutores'));
    }
}
