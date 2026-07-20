<?php

namespace App\Livewire\Generacion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use App\Services\AsignacionEscolarService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CrearGeneracion extends Component
{
    public ?int $nivel_id = null;
    public ?int $anio_ingreso = null;
    public ?int $anio_egreso = null;
    public string $nombre = '';
    public ?int $ciclo_escolar_inicio_id = null;
    public ?int $ciclo_escolar_fin_id = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_termino = null;
    public ?string $detalleDuracion = null;

    public function updatedNivelId(): void
    {
        $this->sincronizarGeneracion();
    }

    public function updatedAnioIngreso(): void
    {
        $this->sincronizarGeneracion();
    }

    public function guardarGeneracion(AsignacionEscolarService $asignaciones): void
    {
        $this->sincronizarGeneracion($asignaciones);

        $data = $this->validate([
            'nivel_id' => ['required', Rule::exists('niveles', 'id')],
            'anio_ingreso' => ['required', 'integer', 'digits:4', 'between:1900,2200'],
            'anio_egreso' => ['required', 'integer', 'digits:4', 'gt:anio_ingreso'],
            'nombre' => ['required', 'string', 'max:50'],
            'ciclo_escolar_inicio_id' => ['nullable', Rule::exists('ciclo_escolares', 'id')],
            'ciclo_escolar_fin_id' => ['nullable', Rule::exists('ciclo_escolares', 'id')],
            'fecha_inicio' => ['required', 'date'],
            'fecha_termino' => ['required', 'date', 'after:fecha_inicio'],
        ]);

        $nivel = Nivel::query()->findOrFail((int) $data['nivel_id']);
        $anioEgresoEsperado = (int) $data['anio_ingreso'] + $asignaciones->duracionNivel($nivel);

        if ((int) $data['anio_egreso'] !== $anioEgresoEsperado) {
            $this->addError(
                'anio_egreso',
                "Para {$nivel->nombre}, la generación debe terminar en {$anioEgresoEsperado}."
            );
            return;
        }

        $existe = Generacion::query()
            ->where('nivel_id', $data['nivel_id'])
            ->where('anio_ingreso', $data['anio_ingreso'])
            ->where('anio_egreso', $data['anio_egreso'])
            ->exists();

        if ($existe) {
            $this->addError('anio_ingreso', 'La generación ya existe en este nivel.');
            return;
        }

        Generacion::query()->create($data + ['status' => true]);

        $this->reset();
        $this->dispatch('refreshGeneraciones');
        $this->dispatch('swal', [
            'title' => 'Generación creada',
            'text' => 'Los años y ciclos se calcularon según la duración oficial del nivel.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
    }

    private function sincronizarGeneracion(?AsignacionEscolarService $asignaciones = null): void
    {
        $this->resetValidation([
            'anio_ingreso',
            'anio_egreso',
            'ciclo_escolar_inicio_id',
            'ciclo_escolar_fin_id',
        ]);

        if (!$this->nivel_id || !$this->anio_ingreso) {
            $this->anio_egreso = null;
            $this->nombre = '';
            $this->ciclo_escolar_inicio_id = null;
            $this->ciclo_escolar_fin_id = null;
            $this->fecha_inicio = null;
            $this->fecha_termino = null;
            $this->detalleDuracion = null;
            return;
        }

        $nivel = Nivel::query()->find($this->nivel_id);

        if (!$nivel) {
            return;
        }

        $asignaciones ??= app(AsignacionEscolarService::class);
        $duracion = $asignaciones->duracionNivel($nivel);
        $this->anio_egreso = (int) $this->anio_ingreso + $duracion;
        $this->nombre = $this->anio_ingreso . '-' . $this->anio_egreso;
        $this->fecha_inicio = sprintf('%d-08-01', $this->anio_ingreso);
        $this->fecha_termino = sprintf('%d-07-31', $this->anio_egreso);
        $this->ciclo_escolar_inicio_id = CicloEscolar::query()
            ->where('inicio_anio', $this->anio_ingreso)
            ->value('id');
        $this->ciclo_escolar_fin_id = CicloEscolar::query()
            ->where('fin_anio', $this->anio_egreso)
            ->value('id');
        $this->detalleDuracion = "Duración calculada: {$duracion} ciclos escolares.";
    }

    public function render()
    {
        return view('livewire.generacion.crear-generacion', [
            'niveles' => Nivel::query()->orderBy('id')->get(),
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
        ]);
    }
}
