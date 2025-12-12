<?php

namespace App\Livewire\Director;

use App\Models\Director;
use Livewire\Component;

class CrearDirector extends Component
{

    public $titulo;
    public $nombre;
    public $apellido_paterno;
    public $apellido_materno;
    public $curp;
    public $rfc;
    public $cargo;
    public $identificador;
    public $zona_escolar;
    public $sector;
    public $telefono;
    public $correo;
    public $genero;



    protected $rules = [
        'titulo' => 'required|string|max:100',
        'nombre' => 'required|string|max:100',
        'apellido_paterno' => 'required|string|max:100',
        'apellido_materno' => 'nullable|string|max:100',
        'curp' => 'nullable|string|size:18|unique:directores,curp',
        'rfc' => 'nullable|string|unique:directores,rfc',
        'cargo' => 'required|string|max:100',
        'identificador' => 'required|string|max:20',
        'zona_escolar' => 'nullable|string|max:20',
        'sector' => 'nullable|string|max:10',
        'telefono' => 'nullable|string|max:10',
        'correo' => 'nullable|email|max:100',
        'genero' => 'required|in:M,F',

    ];

    protected $messages = [
        'titulo.required' => 'El campo título es obligatorio.',
        'titulo.string' => 'El campo título debe ser una cadena de texto.',
        'titulo.max' => 'El campo título no debe exceder los 100 caracteres.',
        'nombre.required' => 'El campo nombre es obligatorio.',
        'nombre.string' => 'El campo nombre debe ser una cadena de texto.',
        'nombre.max' => 'El campo nombre no debe exceder los 100 caracteres.',
        'apellido_paterno.required' => 'El campo apellido paterno es obligatorio.',
        'apellido_paterno.string' => 'El campo apellido paterno debe ser una cadena de texto.',
        'apellido_paterno.max' => 'El campo apellido paterno no debe exceder los 100 caracteres.',
        'apellido_materno.string' => 'El campo apellido materno debe ser una cadena de texto.',
        'apellido_materno.max' => 'El campo apellido materno no debe exceder los 100 caracteres.',
        'curp.string' => 'El campo CURP debe ser una cadena de texto.',
        'curp.size' => 'El campo CURP debe tener exactamente 18 caracteres.',
        'curp.unique' => 'El CURP ya está registrado.',
        'rfc.string' => 'El campo RFC debe ser una cadena de texto.',
        'rfc.unique' => 'El RFC ya está registrado.',
        'cargo.required' => 'El campo cargo es obligatorio.',
        'cargo.string' => 'El campo cargo debe ser una cadena de texto.',
        'cargo.max' => 'El campo cargo no debe exceder los 100 caracteres.',
        'identificador.required' => 'El campo identificador es obligatorio.',
        'identificador.string' => 'El campo identificador debe ser una cadena de texto.',
        'identificador.max' => 'El campo identificador no debe exceder los 20 caracteres.',
        'zona_escolar.string' => 'El campo zona escolar debe ser una cadena de texto.',
        'zona_escolar.max' => 'El campo zona escolar no debe exceder los 20 caracteres.',
        'sector.string' => 'El campo sector debe ser una cadena de texto.',
        'sector.max' => 'El campo sector no debe exceder los 10 caracteres.',
        'telefono.string' => 'El campo teléfono debe ser una cadena de texto.',
        'telefono.max' => 'El campo teléfono no debe exceder los 10 caracteres.',
        'correo.email' => 'El campo correo debe ser una dirección de correo electrónico válida.',
        'correo.max' => 'El campo correo no debe exceder los 100 caracteres.',
        'genero.required' => 'El campo género es obligatorio.',
        'genero.in' => 'El campo género debe ser "M" para masculino o "F" para femenino.',
    ];


    public function crearDirectivo()
    {
        $this->validate();

        $identificadorNormalizado = strtolower(trim($this->identificador));
        $statusStr = true; // Siempre será true por defecto

        // 🚫 Validar que no haya otro activo con este identificador
        $yaActivo = Director::where('identificador', $identificadorNormalizado)
            ->where('status', 1)
            ->exists();

        if ($yaActivo) {
            $this->dispatch('swal', [
                'title' => 'Ya existe un directivo activo con este identificador. Solo puede haber uno activo',
                'icon' => 'error',
                'position' => 'top',
            ]);
            return;
        }

        // ✅ Crear directivo
        Director::create([
            'titulo' => $this->titulo,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'cargo' => $this->cargo,
            'identificador' => $identificadorNormalizado,
            'zona_escolar' => $this->zona_escolar,
            'sector' => $this->sector,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'genero' => $this->genero,
            'status' => $statusStr,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Directivo creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->reset([
            'titulo',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'curp',
            'rfc',
            'cargo',
            'identificador',
            'zona_escolar',
            'sector',
            'telefono',
            'correo',
            'genero',
        ]);

        $this->dispatch('refreshDirectivos');
    }


    public function render()
    {
        return view('livewire.director.crear-director');
    }
}
