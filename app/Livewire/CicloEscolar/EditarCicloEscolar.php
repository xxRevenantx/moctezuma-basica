<?php

namespace App\Livewire\CicloEscolar;

use App\Models\CicloEscolar;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarCicloEscolar extends Component
{
    public ?int $ciclo_id = null;

    public ?int $inicio_anio = null;
    public ?int $fin_anio = null;


    public bool $open = false;

    #[On('editarModal')]
    public function editarModal(int $id): void
    {

        $ciclo = CicloEscolar::findOrFail($id);

        $this->ciclo_id = (int) $ciclo->id;
        $this->inicio_anio = (int) $ciclo->inicio_anio;
        $this->fin_anio = (int) $ciclo->fin_anio;

        // abre modal (Alpine)
        $this->dispatch('abrir-modal-editar');

        // quita loading del modal
        $this->dispatch('editar-cargado');
    }

    public function rules(): array
    {
        $min = 2000;
        $max = (int) now()->addYears(5)->format('Y');

        return [
            'inicio_anio' => [
                'required',
                'integer',
                "min:$min",
                "max:$max",
                'digits:4',
            ],
            'fin_anio' => [
                'required',
                'integer',
                "min:$min",
                "max:$max",
                'digits:4',
                function ($attribute, $value, $fail) {
                    if ($this->inicio_anio !== null && (int) $value < (int) $this->inicio_anio) {
                        $fail('El año final debe ser mayor o igual al año de inicio.');
                    }
                    if ($this->inicio_anio !== null && ((int) $value - (int) $this->inicio_anio) > 1) {
                        $fail('El ciclo escolar normalmente abarca 1 año (por ejemplo 2025–2026).');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'inicio_anio.required' => 'El año de inicio es obligatorio.',
            'inicio_anio.integer' => 'El año de inicio debe ser numérico.',
            'inicio_anio.digits' => 'El año de inicio debe tener 4 dígitos.',
            'inicio_anio.min' => 'El año de inicio es demasiado antiguo.',
            'inicio_anio.max' => 'El año de inicio es demasiado grande.',

            'fin_anio.required' => 'El año de fin es obligatorio.',
            'fin_anio.integer' => 'El año de fin debe ser numérico.',
            'fin_anio.digits' => 'El año de fin debe tener 4 dígitos.',
            'fin_anio.min' => 'El año de fin es demasiado antiguo.',
            'fin_anio.max' => 'El año de fin es demasiado grande.',
        ];
    }

    public function updated($property): void
    {
        $this->validateOnly($property);
    }

    public function guardar(): void
    {
        $this->resetValidation();

        if (!$this->ciclo_id) {
            $this->dispatch('notify', ['type' => 'error', 'message' => 'No hay ciclo seleccionado.']);
            return;
        }

        $data = $this->validate();

        // evita duplicados (mismo inicio-fin en otro registro)
        $existe = CicloEscolar::query()
            ->where('inicio_anio', (int) $data['inicio_anio'])
            ->where('fin_anio', (int) $data['fin_anio'])
            ->where('id', '!=', (int) $this->ciclo_id)
            ->exists();

        if ($existe) {
            $this->addError('inicio_anio', 'Este ciclo escolar ya existe.');
            $this->addError('fin_anio', 'Este ciclo escolar ya existe.');
            return;
        }

        $ciclo = CicloEscolar::findOrFail((int) $this->ciclo_id);

        $ciclo->update([
            'inicio_anio' => (int) $data['inicio_anio'],
            'fin_anio' => (int) $data['fin_anio'],
        ]);

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Ciclo escolar actualizado.']);

        // refrescar lista
        $this->dispatch('refreshCiclos');

        // cerrar modal
        $this->dispatch('cerrar-modal-editar');

        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset([
            'open',
            'ciclo_id',
            'inicio_anio',
            'fin_anio',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.ciclo-escolar.editar-ciclo-escolar');
    }
}
