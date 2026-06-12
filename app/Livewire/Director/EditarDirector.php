<?php

namespace App\Livewire\Director;

use App\Models\Director;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarDirector extends Component
{
    public $directivoId;

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
    public $status = true;

    public $open = false;

    #[On('editarModal')]
    public function editarModal($id)
    {
        $directivo = Director::findOrFail($id);

        $this->resetValidation();

        $this->directivoId = $directivo->id;
        $this->titulo = $directivo->titulo;
        $this->nombre = $directivo->nombre;
        $this->apellido_paterno = $directivo->apellido_paterno;
        $this->apellido_materno = $directivo->apellido_materno;
        $this->curp = $directivo->curp;
        $this->rfc = $directivo->rfc;
        $this->cargo = $directivo->cargo;
        $this->identificador = $directivo->identificador;
        $this->zona_escolar = $directivo->zona_escolar;
        $this->telefono = $directivo->telefono;
        $this->correo = $directivo->correo;
        $this->genero = $directivo->genero;
        $this->status = (bool) $directivo->status;

        $this->open = true;

        $this->dispatch('abrir-modal-editar');
        $this->dispatch('editar-cargado');
    }

    public function actualizarDirectivo()
    {
        $this->normalizarDatos();

        $this->validate([
            'titulo' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],

            'curp' => [
                'nullable',
                'string',
                'size:18',
                'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[A-Z0-9][0-9]$/',
                Rule::unique('directores', 'curp')->ignore($this->directivoId),
            ],

            'rfc' => [
                'nullable',
                'string',
                'min:12',
                'max:13',
                'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/',
                Rule::unique('directores', 'rfc')->ignore($this->directivoId),
            ],

            'cargo' => ['required', 'string', 'max:150'],
            'identificador' => ['required', 'string', 'max:100'],
            'zona_escolar' => ['nullable', 'string', 'max:50'],
            'telefono' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/'],

            'correo' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('directores', 'correo')->ignore($this->directivoId),
            ],

            'genero' => ['required', Rule::in(['M', 'F'])],
            'status' => ['boolean'],
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.max' => 'El título no debe exceder los 20 caracteres.',

            'nombre.required' => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',

            'curp.size' => 'La CURP debe tener exactamente 18 caracteres.',
            'curp.regex' => 'El formato de la CURP no es válido.',
            'curp.unique' => 'Ya existe un directivo registrado con esta CURP.',

            'rfc.min' => 'El RFC debe tener al menos 12 caracteres.',
            'rfc.max' => 'El RFC no debe exceder los 13 caracteres.',
            'rfc.regex' => 'El formato del RFC no es válido.',
            'rfc.unique' => 'Ya existe un directivo registrado con este RFC.',

            'cargo.required' => 'El cargo es obligatorio.',
            'identificador.required' => 'El identificador es obligatorio.',

            'telefono.regex' => 'El teléfono solo puede contener números, espacios, paréntesis, guiones o el signo +.',

            'correo.email' => 'El correo electrónico no tiene un formato válido.',
            'correo.unique' => 'Ya existe un directivo registrado con este correo.',

            'genero.required' => 'El género es obligatorio.',
            'genero.in' => 'El género seleccionado no es válido.',
        ]);

        if ($this->status) {
            $existeOtroActivo = Director::where('identificador', $this->identificador)
                ->where('id', '!=', $this->directivoId)
                ->where('status', 1)
                ->exists();

            if ($existeOtroActivo) {
                $this->dispatch('swal', [
                    'title' => 'Ya existe un directivo activo con este identificador. Solo puede haber uno activo.',
                    'icon' => 'error',
                    'position' => 'top',
                ]);

                return;
            }
        }

        $directivo = Director::findOrFail($this->directivoId);

        $directivo->update([
            'titulo' => $this->titulo,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'curp' => $this->curp,
            'rfc' => $this->rfc,
            'cargo' => $this->cargo,
            'identificador' => $this->identificador,
            'zona_escolar' => $this->zona_escolar,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'genero' => $this->genero,
            'status' => $this->status ? 1 : 0,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Directivo actualizado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshDirectivos');
        $this->dispatch('cerrar-modal-editar');

        $this->cerrarModal();
    }

    private function normalizarDatos(): void
    {
        $this->titulo = trim((string) $this->titulo);
        $this->nombre = trim((string) $this->nombre);
        $this->apellido_paterno = trim((string) $this->apellido_paterno);
        $this->apellido_materno = $this->apellido_materno ? trim((string) $this->apellido_materno) : null;

        $this->curp = $this->curp ? strtoupper(trim((string) $this->curp)) : null;
        $this->rfc = $this->rfc ? strtoupper(trim((string) $this->rfc)) : null;

        $this->cargo = trim((string) $this->cargo);
        $this->identificador = trim((string) $this->identificador);

        $this->zona_escolar = $this->zona_escolar ? trim((string) $this->zona_escolar) : null;
        $this->telefono = $this->telefono ? trim((string) $this->telefono) : null;
        $this->correo = $this->correo ? strtolower(trim((string) $this->correo)) : null;

        $this->genero = $this->genero ? strtoupper(trim((string) $this->genero)) : null;
        $this->status = (bool) $this->status;
    }

    public function cerrarModal()
    {
        $this->resetValidation();

        $this->reset([
            'open',
            'directivoId',
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
            'status',
        ]);

        $this->status = true;
    }

    public function render()
    {
        return view('livewire.director.editar-director');
    }
}
