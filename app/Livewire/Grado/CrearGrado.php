<?php

namespace App\Livewire\Grado;

use App\Models\Grado;
use App\Models\Nivel;
use Illuminate\Support\Str;
use Livewire\Component;

class CrearGrado extends Component
{
    public $nivel_id;
    public $nombre; // 1..6 (number)
    public $slug;

    // Mapea número -> texto (puedes ampliar si luego ocupas más)
    protected array $gradoMap = [
        1 => 'primero',
        2 => 'segundo',
        3 => 'tercero',
        4 => 'cuarto',
        5 => 'quinto',
        6 => 'sexto',
    ];

    public function updatedNombre(): void
    {
        $this->generarSlug();
    }

    public function updatedNivelId(): void
    {
        $this->generarSlug();
    }

    private function generarSlug(): void
    {
        // Si falta alguno, limpia slug
        if (blank($this->nombre) || blank($this->nivel_id)) {
            $this->slug = null;
            return;
        }

        $gradoNumero = (int) $this->nombre;

        // Si el número no está en el mapa (ej. 0 o 7), limpia slug
        if (!isset($this->gradoMap[$gradoNumero])) {
            $this->slug = null;
            return;
        }

        $nivel = Nivel::query()->select('id', 'nombre')->find($this->nivel_id);
        if (!$nivel) {
            $this->slug = null;
            return;
        }

        // "primero_preescolar"
        $gradoTxt = $this->gradoMap[$gradoNumero];
        $nivelTxt = $nivel->nombre;

        $this->slug = Str::slug($gradoTxt, '_') . '_' . Str::slug($nivelTxt, '_');
    }

    public function guardarGrado()
    {
        // Asegura que el slug esté generado antes de validar/guardar
        $this->generarSlug();

        $this->validate([
            'nivel_id' => 'required|exists:niveles,id',
            'nombre'   => 'required|numeric|min:1|max:6',
            'slug'     => 'required|string',
        ], [
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists'   => 'El nivel seleccionado no es válido.',
            'nombre.required'   => 'El grado es obligatorio.',
            'nombre.numeric'    => 'El grado debe ser un número.',
            'nombre.min'        => 'El grado debe ser al menos :min.',
            'nombre.max'        => 'El grado no debe ser mayor a :max.',
            'slug.required'     => 'El slug es obligatorio.',
            'slug.string'       => 'El slug debe ser una cadena de texto.',
        ]);

        // Lo normal es que el grado sea único por nivel (slug queda implícito)
        $existe = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('nombre', (int) $this->nombre)
            ->exists();

        if ($existe) {
            $this->addError('nombre', 'El grado ya existe en este nivel.');
            return;
        }

        Grado::create([
            'nivel_id' => $this->nivel_id,
            'nombre'   => (int) $this->nombre,
            'slug'     => $this->slug,
        ]);

        $this->dispatch('swal', [
            'title'    => '¡Grado creado correctamente!',
            'icon'     => 'success',
            'position' => 'top-end',
        ]);

        $this->reset(['nivel_id', 'nombre', 'slug']);

        $this->dispatch('refreshGrados');
    }

    public function render()
    {
        $niveles = Nivel::orderBy('id')->get();
        return view('livewire.grado.crear-grado', compact('niveles'));
    }
}
