<?php

namespace App\Livewire\Tutor;

use App\Models\Tutor;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarTutor extends Component
{


    public $tutorId;

    public ?string $curp = null;

    // GENERALES
    public ?string $parentesco = null;
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;
    public ?string $genero = null; // M|F|O
    public ?string $fecha_nacimiento = null;
    public ?string $ciudad_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $municipio_nacimiento = null;

    // DOMICILIO
    public ?string $calle = null;
    public ?string $colonia = null;
    public ?string $ciudad = null;
    public ?string $municipio = null;
    public ?string $estado = null;
    public ?string $numero = null;
    public ?string $codigo_postal = null;

    // CONTACTO
    public ?string $telefono_casa = null;
    public ?string $telefono_celular = null;
    public ?string $correo_electronico = null;

    public bool $open = false;

    #[On('editarModal')]
    public function editarModal($id)
    {
        $tutor = Tutor::findOrFail($id);

        $this->tutorId = $tutor->id;
        $this->curp = $tutor->curp;
        $this->parentesco = $tutor->parentesco;
        $this->nombre = $tutor->nombre;
        $this->apellido_paterno = $tutor->apellido_paterno;
        $this->apellido_materno = $tutor->apellido_materno;
        $this->genero = $tutor->genero;
        $this->fecha_nacimiento = $tutor->fecha_nacimiento;
        $this->ciudad_nacimiento = $tutor->ciudad_nacimiento;
        $this->estado_nacimiento = $tutor->estado_nacimiento;
        $this->municipio_nacimiento = $tutor->municipio_nacimiento;

        $this->calle = $tutor->calle;
        $this->colonia = $tutor->colonia;
        $this->ciudad = $tutor->ciudad;
        $this->municipio = $tutor->municipio;
        $this->estado = $tutor->estado;
        $this->numero = $tutor->numero;
        $this->codigo_postal = $tutor->codigo_postal;

        $this->telefono_casa = $tutor->telefono_casa;
        $this->telefono_celular = $tutor->telefono_celular;
        $this->correo_electronico = $tutor->correo_electronico;



        // dd($this->status);

        $this->open = true;

        $this->dispatch('editar-cargado');
    }

    // Actualiza el tutor
    public function actualizarTutor()
    {

        $this->validate([
            'curp' => 'required|string|max:18|unique:tutores,curp,' . $this->tutorId,
            'parentesco' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'genero' => 'required|in:M,F,O',
            'fecha_nacimiento' => 'required|date',
            'ciudad_nacimiento' => 'nullable|string|max:255',
            'estado_nacimiento' => 'nullable|string|max:255',
            'municipio_nacimiento' => 'nullable|string|max:255',

            'calle' => 'nullable|string|max:255',
            'colonia' => 'nullable|string|max:255',
            'ciudad' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'codigo_postal' => 'nullable|string|max:20',

            'telefono_casa' => 'nullable|string|max:20',
            'telefono_celular' => 'nullable|string|max:20',
            'correo_electronico' => 'nullable|email|max:255',
        ]);

        $tutor = Tutor::findOrFail($this->tutorId);
        $tutor->update([
            'curp' => $this->curp,
            'parentesco' => $this->parentesco,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'genero' => $this->genero,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'ciudad_nacimiento' => $this->ciudad_nacimiento,
            'estado_nacimiento' => $this->estado_nacimiento,
            'municipio_nacimiento' => $this->municipio_nacimiento,

            'calle' => $this->calle,
            'colonia' => $this->colonia,
            'ciudad' => $this->ciudad,
            'municipio' => $this->municipio,
            'estado' => $this->estado,
            'numero' => $this->numero,
            'codigo_postal' => $this->codigo_postal,

            'telefono_casa' => $this->telefono_casa,
            'telefono_celular' => $this->telefono_celular,
            'correo_electronico' => $this->correo_electronico,
        ]);

        $this->dispatch('swal', [
            'title' => 'Tutor actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshTutor');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();

    }
    public function cerrarModal()
    {
        $this->reset([
            'tutorId',
            'curp',
            'parentesco',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'genero',
            'fecha_nacimiento',
            'ciudad_nacimiento',
            'estado_nacimiento',
            'municipio_nacimiento',

            'calle',
            'colonia',
            'ciudad',
            'municipio',
            'estado',
            'numero',
            'codigo_postal',

            'telefono_casa',
            'telefono_celular',
            'correo_electronico',
        ]);
    }
    public function render()
    {
        return view('livewire.tutor.editar-tutor');
    }
}
