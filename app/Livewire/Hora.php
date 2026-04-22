<?php

namespace App\Livewire;

use App\Models\Hora as HoraModel;
use Livewire\Component;

class Hora extends Component
{
    public $nivel;

    public ?int $hora_id = null;
    public ?string $hora_inicio = null;
    public ?string $hora_fin = null;

    protected function rules(): array
    {
        return [
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
        ];
    }

    protected function messages(): array
    {
        return [
            'hora_inicio.required' => 'Selecciona la hora de inicio.',
            'hora_inicio.date_format' => 'La hora de inicio no tiene un formato válido.',
            'hora_fin.required' => 'Selecciona la hora de fin.',
            'hora_fin.date_format' => 'La hora de fin no tiene un formato válido.',
            'hora_fin.after' => 'La hora de fin debe ser mayor que la hora de inicio.',
        ];
    }

    public function guardarHora(): void
    {
        $this->resetErrorBag();
        $this->validate();

        $esEdicion = filled($this->hora_id);

        $existeTraslape = HoraModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($this->hora_id, function ($query) {
                $query->where('id', '!=', $this->hora_id);
            })
            ->where(function ($query) {
                $query->where('hora_inicio', '<', $this->hora_fin)
                    ->where('hora_fin', '>', $this->hora_inicio);
            })
            ->exists();

        if ($existeTraslape) {
            $this->addError('hora_inicio', 'Ese rango de hora se cruza con otro registro del mismo nivel.');
            return;
        }

        HoraModel::updateOrCreate(
            ['id' => $this->hora_id],
            [
                'nivel_id' => $this->nivel->id,
                'hora_inicio' => $this->hora_inicio,
                'hora_fin' => $this->hora_fin,
            ]
        );

        $this->limpiarHora();

        $this->dispatch('refrescarHorasDias');

        session()->flash(
            'success_hora',
            $esEdicion ? 'La hora se actualizó correctamente.' : 'La hora se guardó correctamente.'
        );
    }

    public function editarHora(int $id): void
    {
        $hora = HoraModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $this->hora_id = $hora->id;
        $this->hora_inicio = substr((string) $hora->hora_inicio, 0, 5);
        $this->hora_fin = substr((string) $hora->hora_fin, 0, 5);
        $this->dispatch('refrescarHorasDias');
    }

    public function eliminarHora(int $id): void
    {
        $hora = HoraModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->findOrFail($id);

        $hora->delete();

        if ($this->hora_id === $id) {
            $this->limpiarHora();
        }

        session()->flash('success_hora', 'La hora se eliminó correctamente.');

        $this->dispatch('refrescarHorasDias');
    }

    public function cancelarHora(): void
    {
        $this->limpiarHora();
    }

    public function limpiarHora(): void
    {
        $this->reset([
            'hora_id',
            'hora_inicio',
            'hora_fin',
        ]);

        $this->resetValidation();
        $this->resetErrorBag();
    }

    public function render()
    {
        $horas = HoraModel::query()
            ->where('nivel_id', $this->nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->get();

        return view('livewire.hora', [
            'horas' => $horas,
        ]);
    }
}
