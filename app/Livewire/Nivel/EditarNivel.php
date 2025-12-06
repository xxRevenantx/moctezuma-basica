<?php

namespace App\Livewire\Nivel;

use App\Models\Nivel;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class EditarNivel extends Component
{
    use WithFileUploads;

    public $nivelId;
    public $nombre;
    public $slug;
    public $logo_actual;   // ← logo guardado en BD
    public $logo_nuevo;    // ← archivo nuevo
    public $cct;
    public $color;
    public $director_id;
    public $supervisor_id;

    public $open = false;



    #[On('editarModal')]
    public function editarModal($id)
    {
        $nivel = Nivel::findOrFail($id);

        $this->nivelId = $nivel->id;
        $this->nombre = $nivel->nombre;
        $this->slug = $nivel->slug;
        $this->logo_actual  = $nivel->logo; // solo nombre/archivo
        $this->cct = $nivel->cct;
        $this->color = $nivel->color;
        $this->director_id = $nivel->director_id;
        $this->supervisor_id = $nivel->supervisor_id;

        // dd($this->status);

        $this->open = true;

        $this->dispatch('editar-cargado');
    }

    public function actualizarNivel()
    {
        $this->validate([
            'nombre'      => 'required|string|max:255',
            'slug'        => 'required|string|max:255',
            'cct'         => 'required|string|max:50',
            'color'       => 'required|string|max:10',
            'logo_nuevo'  => 'nullable|image|max:2048',
            'director_id' => 'nullable|exists:directores,id',
            'supervisor_id' => 'nullable|exists:directores,id',

            // director_id, supervisor_id...
        ], [
            'logo_nuevo.image' => 'El archivo debe ser una imagen (jpeg, png, jpg, webp).',
            'logo_nuevo.max' => 'El tamaño máximo de la imagen es 2MB.',

            'director_id.exists' => 'El director seleccionado no es válido.',
            'supervisor_id.exists' => 'El supervisor seleccionado no es válido.',

        ]);

        $nivel = Nivel::findOrFail($this->nivelId);


        if ($this->logo_nuevo   ) {
            if ($this->logo_actual) {
                Storage::disk('public')->delete('logos/' . $this->logo_actual);
            }
            $path = $this->logo_nuevo->store('logos', 'public'); // storage/app/public/logos
            $this->logo_actual = basename($path);
        }



        $nivel->update([
            'nombre'      => $this->nombre,
            'logo'        => $this->logo_actual,
            'slug'        => $this->slug,
            'cct'         => $this->cct,
            'color'       => $this->color,
            'director_id' => $this->director_id,
            'supervisor_id' => $this->supervisor_id,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Nivel actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshNiveles');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }



    public function cerrarModal()
    {
        $this->reset([
            'open',
            'nivelId',
            'nombre',
            'slug',
            'logo_actual',
            'logo_nuevo',
            'cct',
            'color',
            'director_id',
            'supervisor_id',

        ]);
    }


    public function render()
    {
        $directores = \App\Models\Director::orderBy('nombre')->get();
        return view('livewire.nivel.editar-nivel', compact('directores'));
    }
}
