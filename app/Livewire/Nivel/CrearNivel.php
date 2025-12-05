<?php

namespace App\Livewire\Nivel;

use App\Models\Director;
use App\Models\Nivel;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;


class CrearNivel extends Component
{
      use WithFileUploads;

    public $logo;
    public $nombre;
    public $slug;
    public $cct;

    public $color;

    public $director_id;
    public $supervisor_id;

       public $directores = [];

    public function mount()
    {
        $this->directores = Director::orderBy('nombre')->get();
    }

        // Cuando se actualice "nombre", se genera el slug
    public function updatedNombre($value)
    {
        $this->slug = Str::slug($value);
    }

    public function guardarNivel()
    {
        $this->validate([
            'logo'          => ['nullable', 'image', 'max:2048'],
            'nombre'        => ['required', 'string', 'max:255'],
            'slug'          => ['required', 'string', 'max:255', 'unique:niveles,slug'],
            'cct'           => ['required', 'string', 'max:50'],
            'color'         => ['required', 'string', 'max:10'],
            'director_id'   => ['nullable', 'exists:directores,id'],
            'supervisor_id' => ['nullable', 'exists:directores,id'],
        ],[
            'slug.unique' => 'El slug ya está en uso. Por favor, elige otro.',
            'cct' => 'El campo C.C.T. es obligatorio.',
            'color' => 'El campo Color es obligatorio.',
        ]);



        // Si se sube una nueva foto...
        $logoPath = null;
        if ($this->logo) {
            // Guarda la nueva imagen
            $path = $this->logo->store('logos');
            $logoPath = str_replace('logos/', '', $path);
        }

        Nivel::create([
            'logo'          => $logoPath,
            'nombre'        => $this->nombre,
            'slug'          => $this->slug,
            'cct'           => $this->cct,
            'color'         => $this->color,
            'director_id'   => $this->director_id,
            'supervisor_id' => $this->supervisor_id,
        ]);

         $this->dispatch('swal', [
            'title' => '¡Nivel creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'logo',
            'nombre',
            'slug',
            'cct',
            'color',
            'director_id',
            'supervisor_id',
        ]);

        $this->dispatch('refreshNiveles');
    }



    public function render()
    {
        return view('livewire.nivel.crear-nivel');
    }
}
