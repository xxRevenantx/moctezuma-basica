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
    public bool $preparar_trayectorias = true;

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
            'preparar_trayectorias' => ['boolean'],
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

        [$nuevo, $anterior] = DB::transaction(function () use ($data) {
            $marcarActual = (bool) $data['marcar_como_actual'] || !CicloEscolar::query()->exists();
            $anterior = null;

            if ($marcarActual) {
                $actuales = CicloEscolar::query()->where('es_actual', true)->lockForUpdate()->get();
                $anterior = $actuales->first();

                foreach ($actuales as $actual) {
                    $actual->update([
                        'es_actual' => false,
                        'cerrado_at' => $data['cerrar_anterior'] ? now() : $actual->cerrado_at,
                        'cerrado_por' => $data['cerrar_anterior'] ? auth()->id() : $actual->cerrado_por,
                    ]);
                }
            }

            $nuevo = CicloEscolar::query()->create([
                'inicio_anio' => (int) $data['inicio_anio'],
                'fin_anio' => (int) $data['fin_anio'],
                'es_actual' => $marcarActual,
                'cerrado_at' => null,
                'cerrado_por' => null,
            ]);

            return [$nuevo, $anterior?->fresh()];
        });

        $texto = $nuevo->es_actual
            ? 'Quedó marcado como ciclo actual.'
            : 'Quedó registrado como ciclo histórico.';

        if ($nuevo->es_actual && $data['preparar_trayectorias'] && $anterior) {
            $resumen = app(\App\Services\PrepararCicloEscolarService::class)
                ->ejecutar($anterior, $nuevo, auth()->id());

            $texto = sprintf(
                'Trayectorias preparadas: %d promovidos, %d no promovidos, %d egresados, %d existentes y %d omitidos.',
                $resumen['promovidos'],
                $resumen['no_promovidos'],
                $resumen['egresados'],
                $resumen['existentes'],
                $resumen['omitidos'],
            );

            if ($resumen['errores'] !== []) {
                $texto .= ' Revisa Promoción masiva: ' . implode(' ', array_slice($resumen['errores'], 0, 2));
            }
        }

        $this->reset(['inicio_anio', 'fin_anio']);
        $this->marcar_como_actual = true;
        $this->cerrar_anterior = true;
        $this->preparar_trayectorias = true;

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => "Ciclo {$nuevo->nombre} creado",
            'text' => $texto,
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
