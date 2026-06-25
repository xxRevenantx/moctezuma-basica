<?php

namespace App\Livewire\Personas;

use App\Exports\PersonasExport;
use App\Imports\PersonasImport;
use App\Models\MovimientoPersonal;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class MostrarPersonal extends Component
{
    use WithPagination;
    use WithFileUploads;

    public $search = '';
    public $archivo;
    public $erroresImportacion = [];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function importarPersonal()
    {
        $this->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ], [
            'archivo.required' => 'Selecciona un archivo de Excel.',
            'archivo.file' => 'El archivo no es válido.',
            'archivo.mimes' => 'El archivo debe ser xlsx, xls o csv.',
            'archivo.max' => 'El archivo no debe pesar más de 10 MB.',
        ]);

        try {
            Excel::import(new PersonasImport, $this->archivo);

            $this->reset('archivo', 'erroresImportacion');
            $this->resetPage();

            $this->dispatch('swal', [
                'title' => '¡Personal importado correctamente!',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (\Throwable $e) {
            $this->erroresImportacion = [
                'No se pudo importar el archivo.',
                $e->getMessage(),
            ];

            $this->dispatch('swal', [
                'title' => 'Error al importar',
                'text' => 'Revisa que el archivo tenga las columnas correctas.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function exportarPersonal()
    {
        $nombreArchivo = 'personal_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(
            new PersonasExport($this->search),
            $nombreArchivo
        );
    }

    public function eliminarPersonal($id)
    {
        $personal = Persona::find($id);

        if (!$personal) {
            return;
        }

        DB::transaction(function () use ($personal) {
            if ($personal->estado_laboral !== 'baja' || $personal->status) {
                $personal->update([
                    'status' => false,
                    'estado_laboral' => 'baja',
                ]);

                MovimientoPersonal::query()->create([
                    'persona_id' => $personal->id,
                    'tipo' => 'baja',
                    'fecha' => now()->toDateString(),
                    'motivo' => 'Baja registrada desde el listado general de personal.',
                    'observaciones' => 'El registro y su expediente digital se conservaron.',
                    'registrado_por' => auth()->id(),
                ]);
            }
        });

        $this->dispatch('swal', [
            'title' => 'Personal marcado como baja. Su expediente se conservó.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    #[On('refreshPersonal')]
    public function render()
    {
        $personal = Persona::query()
            ->where(function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('titulo', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $this->search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $this->search . '%')
                    ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) LIKE ?", ['%' . $this->search . '%'])
                    ->orWhereRaw("CONCAT(nombre, ' ', apellido_paterno) LIKE ?", ['%' . $this->search . '%'])
                    ->orWhere('telefono_movil', 'like', '%' . $this->search . '%')
                    ->orWhere('correo', 'like', '%' . $this->search . '%')
                    ->orWhere('curp', 'like', '%' . $this->search . '%')
                    ->orWhere('rfc', 'like', '%' . $this->search . '%');
            })
            ->orderBy('nombre', 'asc')
            ->orderBy('apellido_paterno', 'asc')
            ->orderBy('apellido_materno', 'asc')
            ->paginate(10);

        return view('livewire.personas.mostrar-personal', compact('personal'));
    }
}
