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

    // Limpia mientras escribe o si está incompleto
    if (! $curp || strlen($curp) < 18) {
        $this->reset([
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'rfc',
            'datosCurp',
        ]);
        return;
    }

    // Si quieres: evita llamadas repetidas si pega el mismo valor
    // if ($this->datosCurp && ($this->datosCurp['_curp'] ?? null) === $curp) return;

    $this->consultarCurp();
}

public function consultarCurp()
{
    // ✅ Resetea estado previo (opcional)
    $this->datosCurp = [];

    /** @var CurpService $servicio */
    $servicio = app(CurpService::class);

    $data = $servicio->obtenerDatosPorCurp($this->curp);

    // Guarda respuesta completa por si quieres debug
    $this->datosCurp = $data;
    // $this->datosCurp['_curp'] = $this->curp;

    if (($data['error'] ?? false) === true) {
        // Limpia campos si falló
        $this->reset([
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'rfc',
        ]);

        $this->dispatch('swal', [
            'title' => $data['message'] ?? 'No se pudo consultar el CURP',
            'text'  => $data['detail'] ?? null,
            'icon'  => 'error',
            'position' => 'top-end',
        ]);

        return;
    }

    // ✅ Según tu API, tú esperas: $data['response']['Solicitante']
    $info = data_get($data, 'response.Solicitante', []);

    // Si la API respondió "ok" pero no trajo solicitante
    if (empty($info)) {
        $this->dispatch('swal', [
            'title' => 'Este CURP no se encuentra en RENAPO.',
            'icon' => 'warning',
            'position' => 'top-end',
        ]);
        return;
    }

    $this->nombre = $info['Nombres'] ?? '';
    $this->apellido_paterno = $info['ApellidoPaterno'] ?? '';
    $this->apellido_materno = $info['ApellidoMaterno'] ?? '';

    $fecha = $info['FechaNacimiento'] ?? null;
    $this->fecha_nacimiento = $fecha ? date('Y-m-d', strtotime($fecha)) : '';

    $sexo = $info['ClaveSexo'] ?? null;
    $this->genero = ($sexo === 'H' || $sexo === 'M') ? $sexo : null;

    $this->rfc = substr($this->curp, 0, 10);

    $this->dispatch('swal', [
        'title' => 'CURP consultado correctamente',
        'icon' => 'success',
        'position' => 'top-end',
        'timer' => 1200,
        'showConfirmButton' => false,
    ]);
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
            'curp.size' => 'La CURP debe tener 18 caracteres.',
            'curp.unique' => 'La CURP ya está registrada.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required' => 'El género es obligatorio.',
            'rfc.unique' => 'El RFC ya está registrado.',
            'correo.unique' => 'El correo ya está registrado.',
            'correo.email' => 'El correo no es válido.',
            'foto.image' => 'La foto debe ser una imagen válida.',
            'foto.max' => 'La foto no debe superar los 2MB.',
            'telefono_movil.size' => 'El teléfono móvil debe tener 10 dígitos.',
            'telefono_fijo.size' => 'El teléfono fijo debe tener 10 dígitos.',
            'genero.in' => 'El género seleccionado no es válido.',

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

        ], []);


        // Código para guardar la persona en la base de datos aquí...
        $fotoPath = null;
        if ($this->foto) {
            $path = $this->foto->store('personal', 'public');
            $fotoPath = basename($path);
        }

        \App\Models\Persona::create([
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'foto' => $fotoPath,
            'curp' => strtoupper(trim($this->curp)),
            'rfc' => strtoupper(trim($this->rfc)),
            'correo' => $this->correo,
            'telefono_movil' => $this->telefono_movil,
            'telefono_fijo' => $this->telefono_fijo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'genero' => $this->genero,
            'grado_estudios' => $this->grado_estudios,
            'especialidad' => $this->especialidad,
            'status' => $this->status,
            'calle' => $this->calle,
            'numero_exterior' => $this->numero_exterior,
            'numero_interior' => $this->numero_interior,
            'colonia' => $this->colonia,
            'municipio' => $this->municipio,
            'estado' => $this->estado,
            'codigo_postal' => $this->codigo_postal,
        ]);



        $this->dispatch('swal', [
            'title' => 'Personal creado exitosamente.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'foto',
            'curp',
            'rfc',
            'correo',
            'telefono_movil',
            'telefono_fijo',
            'fecha_nacimiento',
            'genero',
            'grado_estudios',
            'especialidad',
            'status',
            'calle',
            'numero_exterior',
            'numero_interior',
            'colonia',
            'municipio',
            'estado',
            'codigo_postal',
        ]);

        $this->dispatch('refreshPersonal');
    }

    public function render()
    {
        return view('livewire.personas.crear-personal');
    }
}
