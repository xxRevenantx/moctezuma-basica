<?php

namespace App\Livewire\Grado;

use App\Models\Grado;
use App\Models\Nivel;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGrados extends Component
{
    public $open = false;
    public $gradoId;
    public $nombre;   // 1..6
    public $nivel_id; // id nivel
    public $slug;

    protected array $gradoMap = [
        1 => 'primero',
        2 => 'segundo',
        3 => 'tercero',
        4 => 'cuarto',
        5 => 'quinto',
        6 => 'sexto',
    ];

    #[On('editarModal')]
    public function editarModal($id)
    {
        $grado = Grado::findOrFail($id);

        $this->gradoId  = $grado->id;
        $this->nombre   = $grado->nombre;
        $this->nivel_id = $grado->nivel_id;

        // Genera el slug con la lógica nueva (por si antes estaba distinto)
        $this->generarSlug();

        $this->open = true;

        $this->dispatch('editar-cargado');
    }

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
        if (blank($this->nombre) || blank($this->nivel_id)) {
            $this->slug = null;
            return;
        }

        $gradoNumero = (int) $this->nombre;

        if (!isset($this->gradoMap[$gradoNumero])) {
            $this->slug = null;
            return;
        }

        $nivel = Nivel::query()->select('id', 'nombre')->find($this->nivel_id);
        if (!$nivel) {
            $this->slug = null;
            return;
        }

        $gradoTxt = $this->gradoMap[$gradoNumero];
        $nivelTxt = $nivel->nombre;

        // Formato requerido: "primero_preescolar"
        $this->slug = Str::slug($gradoTxt, '_') . '_' . Str::slug($nivelTxt, '_');
    }

    public function actualizarGrado()
    {
        // Asegura slug correcto antes de validar/guardar
        $this->generarSlug();

        $this->validate([
            'nivel_id' => 'required|exists:niveles,id',
            'nombre'   => 'required|numeric|min:1|max:6',
            'slug'     => 'required|string',
        ], [
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists'   => 'El nivel seleccionado no es válido.',
            'nombre.required'   => 'El nombre del grado es obligatorio.',
            'nombre.numeric'    => 'El nombre del grado debe ser un número.',
            'nombre.min'        => 'El nombre del grado debe ser al menos :min.',
            'nombre.max'        => 'El nombre del grado no debe ser mayor que :max.',
            'slug.required'     => 'El slug es obligatorio.',
            'slug.string'       => 'El slug debe ser una cadena de texto.',
        ]);

        // ✅ Evita falso positivo: excluye el mismo grado que estás editando
        $existe = Grado::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('nombre', (int) $this->nombre)
            ->where('id', '!=', $this->gradoId)
            ->exists();

        if ($existe) {
            $this->addError('nombre', 'El grado ya existe en este nivel.');
            return;
        }

        $grado = Grado::findOrFail($this->gradoId);

        $grado->update([
            'nombre'   => (int) $this->nombre,
            'nivel_id' => (int) $this->nivel_id,
            'slug'     => $this->slug,
        ]);

        $this->dispatch('swal', [
            'title'    => '¡Grado actualizado correctamente!',
            'icon'     => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshGrados');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }

    public function cerrarModal()
    {
        $this->reset([
            'open',
            'gradoId',
            'nombre',
            'slug',
            'nivel_id'
        ]);
    }

    public function render()
    {
        $niveles = Nivel::all();
        return view('livewire.grado.editar-grados', compact('niveles'));
    }
}
