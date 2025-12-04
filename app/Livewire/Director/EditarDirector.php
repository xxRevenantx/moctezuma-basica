<?php

namespace App\Livewire\Director;

use App\Models\Director;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarDirector extends Component
{
    public $titulo;
    public $nombre;
    public $apellido_paterno;
    public $apellido_materno;
    public $curp;
    public $rfc;
    public $cargo;
    public $identificador;
    public $zona_escolar;
    public $telefono;
    public $correo;

    public $open = false;

    #[On('editarModal')]
    public function editarModal($id)
    {
        $directivo = Director::findOrFail($id);

        $this->titulo = $directivo->titulo;
        $this->nombre = $directivo->nombre;
        $this->apellido_paterno = $directivo->apellido_paterno;
        $this->apellido_materno = $directivo->apellido_materno;
        $this->curp = $directivo->curp;
        $this->rfc = $directivo->rfc;
        $this->cargo = $directivo->cargo;
        $this->identificador = $directivo->identificador;
        $this->zona_escolar = $directivo->zona_escolar;
        $this->telefono = $directivo->telefono;
        $this->correo = $directivo->correo;




        $this->open = true;

        $this->dispatch('editar-cargado');
    }

      public function cerrarModal()
    {
        $this->reset([
            'open', 'titulo', 'nombre', 'apellido_paterno', 'apellido_materno',
            'curp', 'rfc', 'cargo', 'identificador', 'zona_escolar', 'telefono', 'correo'        ]);
    }

    public function render()
    {
        return view('livewire.director.editar-director');
    }
}
