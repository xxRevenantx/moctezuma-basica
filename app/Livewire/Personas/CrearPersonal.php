<?php

namespace App\Livewire\Personas;

use App\Services\CurpService;
use Livewire\Component;
use Livewire\WithFileUploads;

class CrearPersonal extends Component
{
    public $nombre;

    public $apellido_paterno;

    public $apellido_materno;

    public $foto;

    public $curp;

    public $rfc;

    public $correo;

    public $telefono_movil;

    public $telefono_fijo;

    public $fecha_nacimiento;

    public $genero;

    public $grado_estudios;

    public $especialidad;

    public $status = true;

    public $calle;

    public $numero_exterior;

    public $numero_interior;

    public $colonia;

    public $municipio;

    public $estado;

    public $codigo_postal;

    public $datosCurp = [];

    use WithFileUploads;

    public function updatedCurp($value)
    {
        $curp = strtoupper(trim($value));
        $this->curp = $curp;

        if (! $curp || strlen($curp) < 18) {
            $this->reset([
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'datosCurp',
            ]);

            return;
        }

        // Dispara una acción para que wire:loading sea consistente
        $this->consultarCurp();
    }

    public function consultarCurp()
    {
        $servicio = new CurpService;
        $this->datosCurp = $servicio->obtenerDatosPorCurp($this->curp);



        if (! ($this->datosCurp['error'] ?? true) && isset($this->datosCurp['response'])) {
            $info = $this->datosCurp['response']['Solicitante'] ?? [];

           // dd($info);

            $this->nombre = $info['Nombres'] ?? '';
            $this->apellido_paterno = $info['ApellidoPaterno'] ?? '';
            $this->apellido_materno = $info['ApellidoMaterno'] ?? '';
            $this->fecha_nacimiento = isset($info['FechaNacimiento']) ? date('Y-m-d', strtotime($info['FechaNacimiento'])) : '';
            // $this->genero = $info['ClaveSexo'] ?? '';

        } else {
            $this->dispatch('swal', [
                'title' => 'Este CURP no se encuentra en RENAPO.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    // CREAR PERSONA
    public function crearPersonal()
    {
        $this->validate([
            'nombre' => 'required|string|max:255',
            'apellido_paterno' => 'required|string|max:255',
            'apellido_materno' => 'nullable|string|max:255',
            'foto' => 'nullable|image|max:2048',

            'curp' => 'required|string|size:18|unique:personas,curp',
            'rfc' => 'nullable|string|size:13|unique:personas,rfc',

            'correo' => 'nullable|email|max:150|unique:personas,correo',
            'telefono_movil' => 'nullable|string|size:10',
            'telefono_fijo' => 'nullable|string|size:10',

            'fecha_nacimiento' => 'required|date',
            'genero' => 'required|in:H,M',

            'grado_estudios' => 'nullable|string|max:255',
            'especialidad' => 'nullable|string|max:255',

            'calle' => 'nullable|string|max:255',
            'numero_exterior' => 'nullable|string|max:20',
            'numero_interior' => 'nullable|string|max:20',
            'colonia' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:255',
            'estado' => 'nullable|string|max:255',
            'codigo_postal' => 'nullable|string|max:10',
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'curp.required' => 'La CURP es obligatoria.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',
            'curp.unique' => 'La CURP ya está registrada.',
            'rfc.unique' => 'El RFC ya está registrado.',
            'correo.unique' => 'El correo ya está registrado.',
            'correo.email' => 'El correo no es válido.',
            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe superar los 2MB.',
            'telefono_movil.size' => 'El teléfono móvil debe tener 10 dígitos.',
            'telefono_fijo.size' => 'El teléfono fijo debe tener 10 dígitos.',
            'genero.in' => 'El género seleccionado no es válido.',
            'curp.size' => 'La CURP debe tener 18 caracteres.',

            'rfc.size' => 'El RFC debe tener 13 caracteres.',
            'codigo_postal.max' => 'El código postal no debe superar los 10 caracteres.',
            'fecha_nacimiento.date' => 'La fecha de nacimiento no es una fecha válida.',
            'grado_estudios.max' => 'El grado de estudios no debe superar los 255 caracteres.',
            'especialidad.max' => 'La especialidad no debe superar los 255 caracteres.',
            'calle.max' => 'La calle no debe superar los 255 caracteres.',
            'numero_exterior.max' => 'El número exterior no debe superar los 20 caracteres.',
            'numero_interior.max' => 'El número interior no debe superar los 20 caracteres.',
            'colonia.max' => 'La colonia no debe superar los 255 caracteres.',
            'municipio.max' => 'El municipio no debe superar los 255 caracteres.',
            'estado.max' => 'El estado no debe superar los 255 caracteres.',
            'codigo_postal.max' => 'El código postal no debe superar los 10 caracteres.',

        ]);

        // Lógica para crear la persona en la base de datos

    }

    public function render()
    {
        return view('livewire.personas.crear-personal');
    }
}
