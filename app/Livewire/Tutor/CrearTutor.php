<?php

namespace App\Livewire\Tutor;

use Livewire\Component;

class CrearTutor extends Component
{
    // =========================
    // Campos (según tu tabla)
    // =========================
    public ?string $curp = null;

    // GENERALES
    public ?string $parentesco = null;
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;
    public ?string $genero = null; // M|F|O
    public ?string $fecha_nacimiento = null;
    public ?string $ciudad_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $municipio_nacimiento = null;

    // DOMICILIO
    public ?string $calle = null;
    public ?string $colonia = null;
    public ?string $ciudad = null;
    public ?string $municipio = null;
    public ?string $estado = null;
    public ?string $numero = null;
    public ?string $codigo_postal = null;

    // CONTACTO
    public ?string $telefono_casa = null;
    public ?string $telefono_celular = null;
    public ?string $correo_electronico = null;

    protected function rules(): array
    {
        return [
            'curp' => ['required', 'string', 'size:18', 'unique:tutores,curp'],
            'parentesco' => ['required', 'string', 'max:50'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'genero' => ['nullable', 'in:M,F,O'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'ciudad_nacimiento' => ['nullable', 'string', 'max:255'],
            'estado_nacimiento' => ['nullable', 'string', 'max:255'],
            'municipio_nacimiento' => ['nullable', 'string', 'max:255'],

            'calle' => ['nullable', 'string', 'max:255'],
            'colonia' => ['nullable', 'string', 'max:255'],
            'ciudad' => ['nullable', 'string', 'max:255'],
            'municipio' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'codigo_postal' => ['nullable', 'string', 'max:10'],

            'telefono_casa' => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
        ];
    }

    protected array $messages = [
        'curp.required' => 'La CURP es obligatoria.',
        'curp.size' => 'La CURP debe tener 18 caracteres.',
        'curp.unique' => 'Esta CURP ya está registrada.',
        'parentesco.required' => 'El parentesco es obligatorio.',
        'correo_electronico.email' => 'El correo no tiene un formato válido.',
    ];

    public function guardar(): void
    {
        $this->validate();

        // Aquí guarda tu modelo Tutor (ajusta el namespace/modelo según tu app)
        // \App\Models\Tutor::create([...]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tutor registrado correctamente.'
        ]);

        $this->reset();
        $this->dispatch('close-crear-tutor'); // opcional si lo usas en modal
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    public function autocompletarDemo(): void
    {
        // Solo para probar UI rápidamente
        $this->parentesco = 'PADRE';
        $this->nombre = 'JUAN';
        $this->apellido_paterno = 'PÉREZ';
        $this->apellido_materno = 'GARCÍA';
        $this->genero = 'M';
        $this->fecha_nacimiento = '1985-03-12';
        $this->correo_electronico = 'juan.perez@email.com';
        $this->telefono_celular = '7670000000';
    }

    public function render()
    {
        return view('livewire.tutor.crear-tutor');
    }
}
