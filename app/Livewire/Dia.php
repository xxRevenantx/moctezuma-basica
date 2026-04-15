<?php

namespace App\Livewire;

use App\Models\Dia as DiaModel;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Dia extends Component
{
    public $nivel;

    public ?int $dia_id = null;
    public string $dia = '';

    protected function rules(): array
    {
        return [
            'dia' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dias', 'dia')
                    ->ignore($this->dia_id)
                    ->where(function ($query) {
                        return $query->where('nivel_id', $this->nivel->id);
                    }),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'dia.required' => 'Escribe el nombre del día.',
            'dia.string' => 'El nombre del día no es válido.',
            'dia.max' => 'El nombre del día es demasiado largo.',
            'dia.unique' => 'Ese día ya existe en este nivel.',
        ];
    }

    public function guardarDia(): void
    {
        $this->resetErrorBag();
        $this->validate();

        $esEdicion = filled($this->dia_id);

        DiaModel::updateOrCreate(
            ['id' => $this->dia_id],
            [
                'nivel_id' => $this->nivel->id,
                'dia' => trim($this->dia),
            ]
        );

        $this->limpiarDia();

        session()->flash(
            'success_dia',
            $esEdicion ? 'El día se actualizó correctamente.' : 'El día se guardó correctamente.'
        );
    }

    public function editarDia(int $id): void
    {
        $dia = DiaModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $this->dia_id = $dia->id;
        $this->dia = $dia->dia;
    }

    public function eliminarDia(int $id): void
    {
        $dia = DiaModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $dia->delete();

        if ($this->dia_id === $id) {
            $this->limpiarDia();
        }

        session()->flash('success_dia', 'El día se eliminó correctamente.');
    }

    public function cancelarDia(): void
    {
        $this->limpiarDia();
    }

    public function limpiarDia(): void
    {
        $this->reset([
            'dia_id',
            'dia',
        ]);

        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function render()
    {
        $dias = DiaModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('dia')
            ->get();

        return view('livewire.dia', [
            'dias' => $dias,
        ]);
    }
}
