<?php

namespace App\Livewire\Accion;

use App\Models\Dia;
use App\Models\Grado;
use App\Models\Hora;
use App\Models\Nivel;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Horario extends Component
{
    public $slug_nivel;
    public $nivel;
    public $niveles;
    public $grados;

    // =========================
    // Sección horas
    // =========================
    public ?int $hora_id = null;
    public ?int $grado_id_hora = null;
    public ?string $hora_inicio = null;
    public ?string $hora_fin = null;
    public int|string|null $orden_hora = null;

    // =========================
    // Sección días
    // =========================
    public ?int $dia_id = null;
    public ?int $grado_id_dia = null;
    public string $dia = '';
    public int|string|null $orden_dia = null;

    public function mount(): void
    {
        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->niveles = Nivel::query()
            ->select('id', 'nombre', 'slug')
            ->orderBy('nombre')
            ->get();

        $this->grados = Grado::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();
    }

    // =========================
    // Reglas para horas
    // =========================
    protected function rulesHora(): array
    {
        return [
            'grado_id_hora' => [
                'required',
                'integer',
                Rule::exists('grados', 'id')->where(function ($query) {
                    $query->where('nivel_id', $this->nivel->id);
                }),
            ],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'orden_hora' => ['required', 'integer', 'min:1'],
        ];
    }

    // =========================
    // Reglas para días
    // =========================
    protected function rulesDia(): array
    {
        return [
            'grado_id_dia' => [
                'required',
                'integer',
                Rule::exists('grados', 'id')->where(function ($query) {
                    $query->where('nivel_id', $this->nivel->id);
                }),
            ],
            'dia' => [
                'required',
                'string',
                'max:255',
                Rule::unique('dias', 'dia')
                    ->ignore($this->dia_id)
                    ->where(function ($query) {
                        return $query
                            ->where('nivel_id', $this->nivel->id)
                            ->where('grado_id', $this->grado_id_dia);
                    }),
            ],
            'orden_dia' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'grado_id_hora.required' => 'Selecciona el grado.',
            'grado_id_hora.exists' => 'El grado seleccionado no pertenece al nivel actual.',
            'hora_inicio.required' => 'Selecciona la hora de inicio.',
            'hora_inicio.date_format' => 'La hora de inicio no tiene un formato válido.',
            'hora_fin.required' => 'Selecciona la hora de fin.',
            'hora_fin.date_format' => 'La hora de fin no tiene un formato válido.',
            'hora_fin.after' => 'La hora de fin debe ser mayor que la hora de inicio.',
            'orden_hora.required' => 'Escribe el orden de la hora.',
            'orden_hora.integer' => 'El orden de la hora debe ser numérico.',
            'orden_hora.min' => 'El orden de la hora debe ser mayor a 0.',

            'grado_id_dia.required' => 'Selecciona el grado.',
            'grado_id_dia.exists' => 'El grado seleccionado no pertenece al nivel actual.',
            'dia.required' => 'Escribe el nombre del día.',
            'dia.unique' => 'Ese día ya existe en este grado.',
            'orden_dia.required' => 'Escribe el orden del día.',
            'orden_dia.integer' => 'El orden del día debe ser numérico.',
            'orden_dia.min' => 'El orden del día debe ser mayor a 0.',
        ];
    }

    // =========================
    // Guardar o actualizar hora
    // =========================
    public function guardarHora(): void
    {
        $this->validate($this->rulesHora());

        $existeTraslape = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->where('grado_id', $this->grado_id_hora)
            ->when($this->hora_id, function ($query) {
                $query->where('id', '!=', $this->hora_id);
            })
            ->where(function ($query) {
                $query->where('hora_inicio', '<', $this->hora_fin)
                    ->where('hora_fin', '>', $this->hora_inicio);
            })
            ->exists();

        if ($existeTraslape) {
            $this->addError('hora_inicio', 'Ese rango de hora se cruza con otro registro del mismo grado.');
            return;
        }

        Hora::updateOrCreate(
            ['id' => $this->hora_id],
            [
                'nivel_id' => $this->nivel->id,
                'grado_id' => $this->grado_id_hora,
                'hora_inicio' => $this->hora_inicio,
                'hora_fin' => $this->hora_fin,
                'orden' => (int) $this->orden_hora,
            ]
        );

        $this->limpiarHora();

        session()->flash('success_hora', 'La hora se guardó correctamente.');
    }

    public function editarHora(int $id): void
    {
        $hora = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $this->hora_id = $hora->id;
        $this->grado_id_hora = $hora->grado_id;
        $this->hora_inicio = $hora->hora_inicio;
        $this->hora_fin = $hora->hora_fin;
        $this->orden_hora = $hora->orden;
    }

    public function cancelarHora(): void
    {
        $this->limpiarHora();
    }

    public function eliminarHora(int $id): void
    {
        $hora = Hora::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $hora->delete();

        if ($this->hora_id === $id) {
            $this->limpiarHora();
        }

        session()->flash('success_hora', 'La hora se eliminó correctamente.');
    }

    public function limpiarHora(): void
    {
        $this->reset([
            'hora_id',
            'grado_id_hora',
            'hora_inicio',
            'hora_fin',
            'orden_hora',
        ]);

        $this->resetValidation();
    }

    // =========================
    // Guardar o actualizar día
    // =========================
    public function guardarDia(): void
    {
        $this->validate($this->rulesDia());

        Dia::updateOrCreate(
            ['id' => $this->dia_id],
            [
                'nivel_id' => $this->nivel->id,
                'grado_id' => $this->grado_id_dia,
                'dia' => trim($this->dia),
                'orden' => (int) $this->orden_dia,
            ]
        );

        $this->limpiarDia();

        session()->flash('success_dia', 'El día se guardó correctamente.');
    }

    public function editarDia(int $id): void
    {
        $dia = Dia::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $this->dia_id = $dia->id;
        $this->grado_id_dia = $dia->grado_id;
        $this->dia = $dia->dia;
        $this->orden_dia = $dia->orden;
    }

    public function cancelarDia(): void
    {
        $this->limpiarDia();
    }

    public function eliminarDia(int $id): void
    {
        $dia = Dia::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $dia->delete();

        if ($this->dia_id === $id) {
            $this->limpiarDia();
        }

        session()->flash('success_dia', 'El día se eliminó correctamente.');
    }

    public function limpiarDia(): void
    {
        $this->reset([
            'dia_id',
            'grado_id_dia',
            'dia',
            'orden_dia',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $horas = Hora::query()
            ->leftJoin('grados', 'grados.id', '=', 'horas.grado_id')
            ->where('horas.nivel_id', $this->nivel->id)
            ->select(
                'horas.*',
                'grados.nombre as grado_nombre'
            )
            ->orderBy('grados.orden')
            ->orderBy('grados.nombre')
            ->orderBy('horas.orden')
            ->orderBy('horas.hora_inicio')
            ->get();

        $dias = Dia::query()
            ->leftJoin('grados', 'grados.id', '=', 'dias.grado_id')
            ->where('dias.nivel_id', $this->nivel->id)
            ->select(
                'dias.*',
                'grados.nombre as grado_nombre'
            )
            ->orderBy('grados.orden')
            ->orderBy('grados.nombre')
            ->orderBy('dias.orden')
            ->get();

        return view('livewire.accion.horario', [
            'horas' => $horas,
            'dias' => $dias,
        ]);
    }
}
