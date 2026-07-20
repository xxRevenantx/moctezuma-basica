<?php

namespace App\Livewire\Generacion;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Nivel;
use App\Services\AsignacionEscolarService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGeneracion extends Component
{
    public ?int $generacionId = null;
    public ?int $nivel_id = null;
    public ?int $anio_ingreso = null;
    public ?int $anio_egreso = null;
    public string $nombre = '';
    public ?int $ciclo_escolar_inicio_id = null;
    public ?int $ciclo_escolar_fin_id = null;
    public ?string $fecha_inicio = null;
    public ?string $fecha_termino = null;
    public bool $tieneGrupos = false;
    public ?string $detalleDuracion = null;

    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        $this->resetValidation();

        $generacion = Generacion::query()->findOrFail($id);

        $this->fill([
            'generacionId' => $generacion->id,
            'nivel_id' => $generacion->nivel_id,
            'anio_ingreso' => (int) $generacion->anio_ingreso,
            'anio_egreso' => (int) $generacion->anio_egreso,
            'nombre' => $generacion->etiqueta,
            'ciclo_escolar_inicio_id' => $generacion->ciclo_escolar_inicio_id,
            'ciclo_escolar_fin_id' => $generacion->ciclo_escolar_fin_id,
            'fecha_inicio' => optional($generacion->fecha_inicio)->format('Y-m-d'),
            'fecha_termino' => optional($generacion->fecha_termino)->format('Y-m-d'),
            'tieneGrupos' => $generacion->grupos()->withTrashed()->exists(),
        ]);

        $nivel = Nivel::query()->find($this->nivel_id);
        $duracion = $nivel
            ? app(AsignacionEscolarService::class)->duracionNivel($nivel)
            : null;
        $this->detalleDuracion = $duracion
            ? "Duración oficial: {$duracion} ciclos escolares."
            : null;

        $this->dispatch('editar-cargado');
    }

    public function updatedNivelId(): void
    {
        $this->sincronizarGeneracion();
    }

    public function updatedAnioIngreso(): void
    {
        $this->sincronizarGeneracion();
    }

    public function actualizarGeneracion(AsignacionEscolarService $asignaciones): void
    {
        if (!$this->generacionId) {
            $this->addError('nombre', 'No se pudo identificar la generación seleccionada.');
            return;
        }

        $generacion = Generacion::query()->findOrFail($this->generacionId);
        $nivelOriginal = (int) $generacion->nivel_id;
        $ingresoOriginal = (int) $generacion->anio_ingreso;
        $egresoOriginal = (int) $generacion->anio_egreso;

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

        $cambioEstructural = $nivelOriginal !== (int) $data['nivel_id']
            || $ingresoOriginal !== (int) $data['anio_ingreso']
            || $egresoOriginal !== (int) $data['anio_egreso'];

        if ($cambioEstructural && $generacion->grupos()->withTrashed()->exists()) {
            $this->addError(
                'anio_ingreso',
                'No puedes cambiar el nivel o los años porque la generación ya está relacionada con grupos. Crea una generación nueva.'
            );
            return;
        }

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
            ->whereKeyNot($this->generacionId)
            ->exists();

        if ($existe) {
            $this->addError('anio_ingreso', 'Ya existe esa generación en el nivel seleccionado.');
            return;
        }

        $generacion->update($data);

        $this->dispatch('refreshGeneraciones');
        $this->dispatch('cerrar-modal-editar');
        $this->dispatch('swal', [
            'title' => 'Generación actualizada',
            'text' => 'Los años y ciclos quedaron validados con el nivel educativo.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->limpiarFormulario();
    }

    public function cerrarModal(): void
    {
        $this->limpiarFormulario();
    }

    private function sincronizarGeneracion(?AsignacionEscolarService $asignaciones = null): void
    {
        if (!$this->nivel_id || !$this->anio_ingreso) {
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
        $this->detalleDuracion = "Duración oficial: {$duracion} ciclos escolares.";
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'generacionId',
            'nivel_id',
            'anio_ingreso',
            'anio_egreso',
            'nombre',
            'ciclo_escolar_inicio_id',
            'ciclo_escolar_fin_id',
            'fecha_inicio',
            'fecha_termino',
            'tieneGrupos',
            'detalleDuracion',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.generacion.editar-generacion', [
            'niveles' => Nivel::query()->orderBy('id')->get(['id', 'nombre']),
            'ciclosEscolares' => CicloEscolar::query()
                ->orderByDesc('inicio_anio')
                ->get(['id', 'inicio_anio', 'fin_anio']),
        ]);
    }
}
