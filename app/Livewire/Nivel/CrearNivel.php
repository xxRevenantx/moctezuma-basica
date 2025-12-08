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

        dd($this->logo);
        $this->validate([
            'logo'          => ['nullable', 'image', 'max:2048'],
            'nombre'        => ['required', 'string', 'max:255'],
            'slug'          => ['required', 'string', 'max:255', 'unique:niveles,slug'],
            'cct'           => ['required', 'string', 'max:50'],
            'color'         => ['required', 'string', 'max:10'],
            'director_id'   => ['nullable', 'exists:directores,id'],
            'supervisor_id' => ['nullable', 'exists:directores,id'],
        ], [
            'logo_nivel.image' => 'El archivo debe ser una imagen.',
            'logo_nivel.max'   => 'La imagen no debe pesar más de 2MB.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max'      => 'El nombre no debe exceder los 255 caracteres.',
            'slug.required'   => 'El slug es obligatorio.',
            'slug.max'        => 'El slug no debe exceder los 255 caracteres.',
            'slug.unique'     => 'El slug ya está en uso. Por favor, elige otro.',
            'cct.required'    => 'El CCT es obligatorio.',
            'cct.max'         => 'El CCT no debe exceder los 50 caracteres.',
            'color.required'  => 'El color es obligatorio.',
            'color.max'       => 'El color no debe exceder los 10 caracteres.',
            'director_id.exists'   => 'El director seleccionado no es válido.',
            'supervisor_id.exists' => 'El supervisor seleccionado no es válido.',
        ]);

        $logoPath = null;
        if ($this->logo) {
            $path = $this->logo->store('logos', 'public');
            $logoPath = basename($path);
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
            'logo_nivel',
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
