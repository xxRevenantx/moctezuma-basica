<?php

namespace App\Livewire\Personas;

use App\Models\Persona;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarPersonal extends Component
{

    public $personaId;
     public $nombre;

    public $apellido_paterno;

    public $apellido_materno;

    public $foto_nueva;

    public $foto_actual;

    public $curp;

    public $rfc;

    public $correo;

    public $telefono_movil;

    public $telefono_fijo;

    public $fecha_nacimiento;

    public $genero;

    public $grado_estudios;

    public $especialidad;

    public $status = true;

    public $calle;

    public $numero_exterior;

    public $numero_interior;

    public $colonia;

    public $municipio;

    public $estado;

    public $codigo_postal;

    public $datosCurp = [];

    use WithFileUploads;
     public $open = false;

    #[On('editarModal')]
    public function editarModal($id)
    {
        $personal = Persona::findOrFail($id);

        $this->personaId = $personal->id;
        $this->nombre = $personal->nombre;
        $this->foto_actual = $personal->foto;
        $this->apellido_paterno = $personal->apellido_paterno;
        $this->apellido_materno = $personal->apellido_materno;
        $this->curp = $personal->curp;
        $this->rfc = $personal->rfc;
        $this->correo = $personal->correo;
        $this->telefono_movil = $personal->telefono_movil;
        $this->telefono_fijo = $personal->telefono_fijo;
        $this->fecha_nacimiento = $personal->fecha_nacimiento;
        $this->genero = $personal->genero;
        $this->grado_estudios = $personal->grado_estudios;
        $this->especialidad = $personal->especialidad;
        $this->calle = $personal->calle;;
        $this->numero_exterior = $personal->numero_exterior;
        $this->numero_interior = $personal->numero_interior;
        $this->colonia = $personal->colonia;
        $this->municipio = $personal->municipio;
        $this->estado = $personal->estado;
        $this->codigo_postal = $personal->codigo_postal;

        $this->status = $personal->status == 1 ? true : false;

        // dd($this->status);

        $this->open = true;

        $this->dispatch('editar-cargado');
    }



    // ACTUALIZAR PERSONAL
    public function actualizarPersonal()
    {
        $this->validate([
            'nombre'=> 'required|string|max:255',
            'apellido_paterno'=> 'required|string|max:255',
            'apellido_materno'=> 'nullable|string|max:255',
            'curp'=> 'nullable|string|max:18|unique:personas,curp,' . $this->personaId,
            'rfc'=> 'nullable|string|max:13|unique:personas,rfc,' . $this->personaId,
            'correo'=> 'nullable|email|max:255|unique:personas,correo,' . $this->personaId,
            'telefono_movil'=> 'nullable|string|max:15',
            'telefono_fijo'=> 'nullable|string|max:15',
            'fecha_nacimiento'=> 'required|date',
            'genero' => 'required|in:H,M',
            'grado_estudios'=> 'nullable|string|max:100',
            'especialidad'=> 'nullable|string|max:100',
            'calle'=> 'nullable|string|max:255',
            'numero_exterior'=> 'nullable|string|max:10',
            'numero_interior'=> 'nullable|string|max:10',
            'colonia'=> 'nullable|string|max:100',
            'municipio'=> 'nullable|string|max:100',
            'estado'=> 'nullable|string|max:100',
            'codigo_postal'=> 'nullable|string|max:10',
        ]);


        $personal = Persona::findOrFail($this->personaId);

         if ($this->foto_nueva   ) {
            if ($this->foto_actual) {
                Storage::disk('public')->delete('personal/' . $this->foto_actual);
            }
            $path = $this->foto_nueva->store('personal', 'public'); // storage/app/public/personal
            $this->foto_actual = basename($path);
        }





        $personal->update([
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'foto' => $this->foto_actual,
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'correo' => $this->correo,
            'telefono_movil' => $this->telefono_movil,
            'telefono_fijo' => $this->telefono_fijo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'grado_estudios' => $this->grado_estudios,
            'especialidad' => $this->especialidad,
            'calle' => $this->calle,
            'numero_exterior' => $this->numero_exterior,
            'numero_interior' => $this->numero_interior,
            'colonia' => $this->colonia,
            'municipio' => $this->municipio,
            'estado' => $this->estado,
            'codigo_postal' => $this->codigo_postal,
            'status' => $this->status ? 1 : 0,
        ]);


        $this->dispatch('swal', [
            'title' => 'Persona actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonal');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }




    public function cerrarModal()
    {
        $this->reset([
            'open',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'foto_nueva',
            'curp',
            'rfc',
            'correo',
            'telefono_movil',
            'telefono_fijo',
            'fecha_nacimiento',
            'genero',
            'grado_estudios',
            'especialidad',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'municipio',
            'estado',
            'codigo_postal',


        ]);
    }
    public function render()
    {
        return view('livewire.personas.editar-personal');
    }
}
