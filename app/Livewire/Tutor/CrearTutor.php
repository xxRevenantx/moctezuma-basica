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
            'genero' => ['required', 'in:M,F,O'],
            'fecha_nacimiento' => ['required', 'date'],
            'ciudad_nacimiento' => ['required', 'string', 'max:255'],
            'estado_nacimiento' => ['required', 'string', 'max:255'],
            'municipio_nacimiento' => ['required', 'string', 'max:255'],

            'calle' => ['required', 'string', 'max:255'],
            'colonia' => ['required', 'string', 'max:255'],
            'ciudad' => ['required', 'string', 'max:255'],
            'municipio' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'codigo_postal' => ['required', 'string', 'max:10'],

            'telefono_casa' => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
        ];
    }

    protected array $messages = [
        'curp.required' => 'La CURP es obligatoria.',
        'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
        'curp.unique' => 'La CURP ya está registrada.',

        'parentesco.required' => 'El parentesco es obligatorio.',

        'nombre.required' => 'El nombre es obligatorio.',

        'apellido_paterno.required' => 'El apellido paterno es obligatorio.',

        'genero.required' => 'El género es obligatorio.',
        'genero.in' => 'El género seleccionado no es válido.',

        'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
        'fecha_nacimiento.date' => 'La fecha de nacimiento no es una fecha válida.',

        'correo_electronico.email' => 'El correo electrónico no es válido.',

        'codigo_postal.required' => 'El código postal es obligatorio.',
        'calle.required' => 'La calle es obligatoria.',
        'colonia.required' => 'La colonia es obligatoria.',
        'ciudad.required' => 'La ciudad es obligatoria.',
        'municipio.required' => 'El municipio es obligatorio.',
        'estado.required' => 'El estado es obligatorio.',
        'municipio_nacimiento.required' => 'El municipio de nacimiento es obligatorio.',
        'ciudad_nacimiento.required' => 'La ciudad de nacimiento es obligatoria.',
        'estado_nacimiento.required' => 'El estado de nacimiento es obligatorio.',

    ];

    public function guardar(): void
    {
        $this->validate();

        // Aquí guarda tu modelo Tutor (ajusta el namespace/modelo según tu app)
        \App\Models\Tutor::create([
            'curp' => $this->curp,
            'parentesco' => $this->parentesco,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'genero' => $this->genero,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'ciudad_nacimiento' => $this->ciudad_nacimiento,
            'estado_nacimiento' => $this->estado_nacimiento,
            'municipio_nacimiento' => $this->municipio_nacimiento,
            'calle' => $this->calle,
            'colonia' => $this->colonia,
            'ciudad' => $this->ciudad,
            'municipio' => $this->municipio,
            'estado' => $this->estado,
            'numero' => $this->numero,
            'codigo_postal' => $this->codigo_postal,
            'telefono_casa' => $this->telefono_casa,
            'telefono_celular' => $this->telefono_celular,
            'correo_electronico' => $this->correo_electronico,
        ]);

        $this->dispatch('swal', [
            'title' => 'Tutor creado correctamente',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset();
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }



    public function render()
    {
        return view('livewire.tutor.crear-tutor');
    }
}
