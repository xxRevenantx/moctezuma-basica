<?php

namespace App\Livewire\Tutor;

use App\Models\Tutor;
use App\Rules\CurpMexicana;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CrearTutor extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.crear'), 403);
    }

    public bool $sin_curp = false;
    public ?string $curp = null;
    public ?string $identificador_alternativo = null;
    public ?string $motivo_sin_curp = null;
    public ?string $nombre = null;
    public ?string $apellido_paterno = null;
    public ?string $apellido_materno = null;
    public ?string $genero = null;
    public ?string $fecha_nacimiento = null;
    public ?string $ciudad_nacimiento = null;
    public ?string $estado_nacimiento = null;
    public ?string $municipio_nacimiento = null;
    public ?string $calle = null;
    public ?string $colonia = null;
    public ?string $ciudad = null;
    public ?string $municipio = null;
    public ?string $estado = null;
    public ?string $numero = null;
    public ?string $codigo_postal = null;
    public ?string $telefono_casa = null;
    public ?string $telefono_celular = null;
    public ?string $correo_electronico = null;

    protected function rules(): array
    {
        return [
            'sin_curp' => ['boolean'],
            'curp' => [Rule::requiredIf(! $this->sin_curp), 'nullable', 'string', 'size:18', new CurpMexicana(), 'unique:tutores,curp'],
            'identificador_alternativo' => [Rule::requiredIf($this->sin_curp), 'nullable', 'string', 'max:80', 'unique:tutores,identificador_alternativo'],
            'motivo_sin_curp' => [Rule::requiredIf($this->sin_curp), 'nullable', 'string', 'min:5', 'max:255'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido_paterno' => ['required', 'string', 'max:255'],
            'apellido_materno' => ['nullable', 'string', 'max:255'],
            'genero' => ['nullable', Rule::in(['M', 'F', 'O'])],
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
            'codigo_postal' => ['nullable', 'regex:/^[0-9]{5}$/'],
            'telefono_casa' => ['nullable', 'string', 'max:20'],
            'telefono_celular' => ['nullable', 'string', 'max:20'],
            'correo_electronico' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function updatedSinCurp(bool $sinCurp): void
    {
        if ($sinCurp) {
            $this->curp = null;
        } else {
            $this->identificador_alternativo = null;
            $this->motivo_sin_curp = null;
        }

        $this->resetValidation(['curp', 'identificador_alternativo', 'motivo_sin_curp']);
    }

    public function updatedCurp(): void
    {
        $this->curp = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->curp) ?: '');

        // La CURP se sincroniza al salir del campo. Mientras está incompleta no
        // mostramos un error ni provocamos una nueva renderización por cada tecla.
        if ($this->sin_curp || blank($this->curp) || mb_strlen($this->curp) < 18) {
            $this->resetValidation('curp');

            return;
        }

        $this->validateOnly('curp');
    }

    public function guardar(): void
    {
        $this->normalizar();
        $data = $this->validate();

        if (blank($data['telefono_celular']) && blank($data['telefono_casa']) && blank($data['correo_electronico'])) {
            throw ValidationException::withMessages([
                'telefono_celular' => 'Captura al menos teléfono celular, teléfono de casa o correo electrónico.',
            ]);
        }

        $tutor = DB::transaction(function () use ($data): Tutor {
            return Tutor::query()->create([
                ...$data,
                'curp' => blank($data['curp']) ? null : $data['curp'],
                'identificador_alternativo' => blank($data['identificador_alternativo']) ? null : $data['identificador_alternativo'],
                'motivo_sin_curp' => blank($data['motivo_sin_curp']) ? null : $data['motivo_sin_curp'],
                // Campo legado: el parentesco correcto se captura al relacionar al tutor con cada alumno.
                'parentesco' => 'NO ESPECIFICADO',
                'activo' => true,
            ]);
        });

        $this->dispatch('swal', [
            'title' => 'Responsable creado correctamente',
            'text' => 'Ahora puedes relacionarlo con uno o varios alumnos y definir el parentesco de cada relación.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('tutorRegistered', tutor: $tutor->id);
        $this->limpiar();
        $this->dispatch('refreshTutor');
    }

    public function limpiar(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    private function normalizar(): void
    {
        $this->curp = mb_strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $this->curp) ?: '');
        $this->identificador_alternativo = mb_strtoupper(trim((string) $this->identificador_alternativo));

        if ($this->sin_curp) {
            $this->curp = null;
        } else {
            $this->identificador_alternativo = null;
            $this->motivo_sin_curp = null;
        }

        foreach (['nombre', 'apellido_paterno', 'apellido_materno'] as $campo) {
            $this->{$campo} = mb_convert_case(trim((string) $this->{$campo}), MB_CASE_TITLE, 'UTF-8');
        }

        foreach (['motivo_sin_curp', 'ciudad_nacimiento', 'estado_nacimiento', 'municipio_nacimiento', 'calle', 'colonia', 'ciudad', 'municipio', 'estado', 'numero', 'codigo_postal', 'telefono_casa', 'telefono_celular', 'correo_electronico'] as $campo) {
            $valor = trim((string) $this->{$campo});
            $this->{$campo} = $valor === '' ? null : $valor;
        }
    }

    public function render()
    {
        return view('livewire.tutor.crear-tutor');
    }
}
