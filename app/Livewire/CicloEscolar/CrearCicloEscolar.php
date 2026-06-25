<?php

namespace App\Livewire\CicloEscolar;

use App\Models\CicloEscolar;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class CrearCicloEscolar extends Component
{
    public ?int $inicio_anio = null;
    public ?int $fin_anio = null;
    public bool $marcar_como_actual = true;
    public bool $cerrar_anterior = true;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    public function rules(): array
    {
        $max = (int) now()->addYears(5)->format('Y');

        return [
            'inicio_anio' => ['required', 'integer', 'digits:4', 'min:2000', "max:{$max}"],
            'fin_anio' => [
                'required',
                'integer',
                'digits:4',
                'min:2000',
                "max:{$max}",
                function ($attribute, $value, $fail) {
                    if ($this->inicio_anio !== null && (int) $value !== (int) $this->inicio_anio + 1) {
                        $fail('El ciclo debe abarcar un año, por ejemplo 2026-2027.');
                    }
                },
            ],
            'marcar_como_actual' => ['boolean'],
            'cerrar_anterior' => ['boolean'],
        ];
    }

    public function updatedInicioAnio($value): void
    {
        if (filled($value) && is_numeric($value)) {
            $this->fin_anio = (int) $value + 1;
        }
    }

    public function guardar(): void
    {
        $data = $this->validate();

        if (CicloEscolar::query()
            ->where('inicio_anio', $data['inicio_anio'])
            ->where('fin_anio', $data['fin_anio'])
            ->exists()) {
            $this->addError('inicio_anio', 'Este ciclo escolar ya existe.');
            return;
        }

        $nuevo = DB::transaction(function () use ($data) {
            $marcarActual = (bool) $data['marcar_como_actual'] || !CicloEscolar::query()->exists();

            if ($marcarActual) {
                $actuales = CicloEscolar::query()->where('es_actual', true)->lockForUpdate()->get();

                foreach ($actuales as $actual) {
                    $actual->update([
                        'es_actual' => false,
                        'cerrado_at' => $data['cerrar_anterior'] ? now() : $actual->cerrado_at,
                        'cerrado_por' => $data['cerrar_anterior'] ? auth()->id() : $actual->cerrado_por,
                    ]);
                }
            }

            return CicloEscolar::query()->create([
                'inicio_anio' => (int) $data['inicio_anio'],
                'fin_anio' => (int) $data['fin_anio'],
                'es_actual' => $marcarActual,
                'cerrado_at' => null,
                'cerrado_por' => null,
            ]);
        });

        $this->reset(['inicio_anio', 'fin_anio']);
        $this->marcar_como_actual = true;
        $this->cerrar_anterior = true;

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => "Ciclo {$nuevo->nombre} creado",
            'text' => $nuevo->es_actual
                ? 'Quedó marcado como ciclo actual.'
                : 'Quedó registrado como ciclo histórico.',
            'position' => 'top-end',
        ]);
        $this->dispatch('refreshHeader');
        $this->dispatch('refreshCiclos');
    }

    public function render()
    {
        return view('livewire.ciclo-escolar.crear-ciclo-escolar');
    }
}
