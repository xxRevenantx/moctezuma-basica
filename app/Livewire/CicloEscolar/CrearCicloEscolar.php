<?php

namespace App\Livewire\CicloEscolar;

use App\Models\CicloEscolar;
use Livewire\Component;

class CrearCicloEscolar extends Component
{
    public ?int $inicio_anio = null;
    public ?int $fin_anio = null;

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

                    // Regla sugerida (no obligatoria en muchos casos, pero útil)
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
        $data = $this->validate();

        $existe = CicloEscolar::query()
            ->where('inicio_anio', $data['inicio_anio'])
            ->where('fin_anio', $data['fin_anio'])
            ->exists();

        if ($existe) {
            $this->addError('inicio_anio', 'Este ciclo escolar ya existe.');
            $this->addError('fin_anio', 'Este ciclo escolar ya existe.');
            return;
        }

        CicloEscolar::create([
            'inicio_anio' => (int) $data['inicio_anio'],
            'fin_anio' => (int) $data['fin_anio'],
        ]);

        $this->reset(['inicio_anio', 'fin_anio']);

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Ciclo escolar creado',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('livewire.ciclo-escolar.crear-ciclo-escolar');
    }
}
