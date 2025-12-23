<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Nivel;
use App\Models\PersonaRole;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\PersonaNivel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CrearPersonaNivel extends Component
{
    public ?int $persona_id = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    public $PersonasRoles = [];
    public $niveles = [];

    public $grados = [];
    public $grupos = [];

    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;

    public function mount(): void
    {
        // Personas únicas (por persona_id) que tienen al menos un rol
        $this->PersonasRoles = PersonaRole::with('persona')
            ->get()
            ->unique('persona_id')
            ->values();

        $this->niveles = Nivel::orderBy('id')->get();

        $this->grados = collect();
        $this->grupos = collect();
    }

    public function updatedNivelId($value): void
    {
        // Reset cascada
        $this->grado_id = null;
        $this->grupo_id = null;
        $this->grupos = collect();

        if (! $value) {
            $this->grados = collect();
            return;
        }

        $this->grados = Grado::where('nivel_id', $value)
            ->orderBy('nombre')
            ->get();
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;

        if (! $value) {
            $this->grupos = collect();
            return;
        }

        $this->grupos = Grupo::where('grado_id', $value)
            ->orderBy('nombre')
            ->get();
    }

    public function asignarPersonalNivel(): void
    {
        $this->validate([
            'persona_id'  => ['required', 'integer', 'exists:personas,id'],
            'nivel_id'    => ['required', 'integer', 'exists:niveles,id'],
            'grado_id'    => ['required', 'integer', 'exists:grados,id'],
            'grupo_id'    => ['required', 'integer', 'exists:grupos,id'],
            'ingreso_seg' => ['nullable', 'date'],
            'ingreso_sep' => ['nullable', 'date'],
        ], [
            'persona_id.required' => 'El campo Personal es obligatorio.',
            'persona_id.exists'   => 'El personal seleccionado no es válido.',
            'nivel_id.required'   => 'El campo Nivel es obligatorio.',
            'nivel_id.exists'     => 'El nivel seleccionado no es válido.',
            'grado_id.required'   => 'El campo Grado es obligatorio.',
            'grado_id.exists'     => 'El grado seleccionado no es válido.',
            'grupo_id.required'   => 'El campo Grupo es obligatorio.',
            'grupo_id.exists'     => 'El grupo seleccionado no es válido.',
            'ingreso_seg.date'    => 'La Fecha de Ingreso SEG debe ser una fecha válida.',
            'ingreso_sep.date'    => 'La Fecha de Ingreso SEP debe ser una fecha válida.',
        ]);

        // VERIFICAR SI YA EXISTE LA ASIGNACIÓN (misma persona en mismo nivel+grado+grupo)
        $existeAsignacion = PersonaNivel::query()
            ->where('persona_id', $this->persona_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->exists();

        if ($existeAsignacion) {
            $this->dispatch('swal', [
                'title' => 'La asignación ya existe.',
                'icon' => 'warning',
                'position' => 'top',
            ]);
            return;
        }

        // ✅ ORDEN INCLUSIVO POR NIVEL+GRUPO
        // Cada (nivel_id, grupo_id) tiene su propia secuencia 1..N
        DB::transaction(function () {
            $maxOrden = PersonaNivel::query()
                ->where('nivel_id', $this->nivel_id)
                ->where('grupo_id', $this->grupo_id)
                ->max('orden');

            $nuevoOrden = ((int) ($maxOrden ?? 0)) + 1;

            PersonaNivel::create([
                'persona_id'   => $this->persona_id,
                'nivel_id'     => $this->nivel_id,
                'grado_id'     => $this->grado_id,
                'grupo_id'     => $this->grupo_id,
                'ingreso_seg'  => $this->ingreso_seg,
                'ingreso_sep'  => $this->ingreso_sep,
                'orden'        => $nuevoOrden, // ✅ aquí
            ]);
        });

        $this->dispatch('swal', [
            'title' => 'Asignación exitosa',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonaNivelList');

        // reset campos
        $this->reset(['persona_id', 'nivel_id', 'grado_id', 'grupo_id', 'ingreso_seg', 'ingreso_sep']);
        $this->grados = collect();
        $this->grupos = collect();
    }

    public function render()
    {
        return view('livewire.persona-nivel.crear-persona-nivel');
    }
}
