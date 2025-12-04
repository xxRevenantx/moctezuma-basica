<?php

namespace App\Livewire\Director;

use App\Models\Director;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarDirector extends Component
{
    public $directivoId;

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

    public $status;

    public $open = false;

    #[On('editarModal')]
    public function editarModal($id)
    {
        $directivo = Director::findOrFail($id);

        $this->directivoId = $directivo->id;
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

        $this->status = $directivo->status == 1 ? true : false;

        // dd($this->status);

        $this->open = true;

        $this->dispatch('editar-cargado');
    }



    // ACTUALIZAR DIRECTOR
    public function actualizarDirectivo()
    {
        $this->validate([
            'titulo' => 'required|string|max:10',
            'nombre' => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'curp' => 'nullable|string|max:18|unique:directores,curp,' . $this->directivoId,
            'rfc' => 'nullable|string|max:13|unique:directores,rfc,' . $this->directivoId,
            'cargo' => 'required|string|max:100',
            'identificador' => 'required|string|max:50',
            'zona_escolar' => 'nullable|string|max:50',
            'telefono' => 'nullable|string|max:15',
            'correo' => 'nullable|email|max:100|unique:directores,correo,' . $this->directivoId,
            'status' => 'boolean',
        ]);

        // ❌ Validar que no haya otro con el mismo identificador y status = true
        if ($this->status === 1 || $this->status === true) {
            $existeOtroActivo = Director::where('identificador', $this->identificador)
                ->where('id', '!=', $this->directivoId)
                ->where('status', 1)
                ->exists();

            if ($existeOtroActivo) {
                $this->dispatch('swal', [
                    'title' => 'Ya existe un directivo activo con este identificador. Solo puede haber uno activo',
                    'icon' => 'error',
                    'position' => 'top',
                ]);
                return;
            }
        }


        $directivo = Director::findOrFail($this->directivoId);
        $directivo->update([
            'titulo' => $this->titulo,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'cargo' => $this->cargo,
            'identificador' => $this->identificador,
            'zona_escolar' => $this->zona_escolar,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'status' => $this->status ? 1 : 0,
        ]);


        $this->dispatch('swal', [
            'title' => '¡Directivo actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshDirectivos');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }




    public function cerrarModal()
    {
        $this->reset([
            'open',
            'titulo',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'curp',
            'rfc',
            'cargo',
            'identificador',
            'zona_escolar',
            'telefono',
            'correo'
        ]);
    }

    public function render()
    {
        return view('livewire.director.editar-director');
    }
}
