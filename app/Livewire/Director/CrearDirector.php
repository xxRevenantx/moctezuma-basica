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
    public $telefono;
    public $correo;
    public $genero;



    protected $rules = [
        'titulo' => 'required|string|max:255',
        'nombre' => 'required|string|max:255',
        'apellido_paterno' => 'required|string|max:255',
        'apellido_materno' => 'nullable|string|max:255',
        'curp' => 'nullable|string|size:18|unique:directores,curp',
        'rfc' => 'nullable|string|unique:directores,rfc',
        'cargo' => 'required|string|max:255',
        'identificador' => 'required|string|max:255',
        'zona_escolar' => 'nullable|string|max:255',
        'telefono' => 'nullable|string|max:10',
        'correo' => 'nullable|email|max:255',
        'genero' => 'required|in:M,F',

    ];

    protected $messages = [
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
