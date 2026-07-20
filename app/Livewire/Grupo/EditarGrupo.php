<?php

namespace App\Livewire\Grupo;

use App\Models\AsignacionGrupo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Services\AsignacionEscolarService;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGrupo extends Component
{
    public ?int $grupo_id = null;
    public int|string|null $ciclo_escolar_id = '';
    public int|string|null $asignacion_grupo_id = '';
    public int|string|null $nivel_id = '';
    public int|string|null $grado_id = '';
    public int|string|null $generacion_id = '';
    public int|string|null $semestre_id = '';
    public string $estado = 'activo';

    public Collection $grados;
    public Collection $semestres;
    public bool $esBachillerato = false;
    public ?string $generacionCalculada = null;
    public ?string $advertenciaAsignacion = null;
    public bool $tieneInscripciones = false;
    public bool $esGeneracionExcepcional = false;
    public ?string $motivoGeneracionExcepcional = null;

    public function mount(): void
    {
        $this->grados = collect();
        $this->semestres = collect();
    }

    #[On('editarModal')]
    public function editarModal(int|array|null $id = null): void
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        $this->resetValidation();
        $grupo = $id
            ? Grupo::query()->with(['nivel', 'grado', 'semestre', 'generacion'])->find($id)
            : null;

        if (!$grupo) {
            $this->mostrarError('El grupo seleccionado no existe.');
            $this->dispatch('cerrar-modal-editar');
            return;
        }

        $this->grupo_id = (int) $grupo->id;
        $this->ciclo_escolar_id = $grupo->ciclo_escolar_id ?: '';
        $this->asignacion_grupo_id = $grupo->asignacion_grupo_id;
        $this->nivel_id = $grupo->nivel_id;
        $this->grado_id = $grupo->grado_id;
        $this->generacion_id = $grupo->generacion_id;
        $this->semestre_id = $grupo->semestre_id ?: '';
        $this->estado = $grupo->estado ?: 'activo';
        $this->esBachillerato = $grupo->nivel?->slug === 'bachillerato';
        $this->generacionCalculada = $grupo->generacion
            ? $grupo->generacion->anio_ingreso . '-' . $grupo->generacion->anio_egreso
            : null;
        $this->tieneInscripciones = $grupo->inscripciones()->withTrashed()->exists();
        $this->esGeneracionExcepcional = filled($grupo->motivo_generacion_excepcional);
        $this->motivoGeneracionExcepcional = $grupo->motivo_generacion_excepcional;
        $this->cargarOpcionesNivel();
        $this->dispatch('editar-cargado');
    }

    public function updatedCicloEscolarId(): void
    {
        $this->resolverVistaPreviaGeneracion();
    }

    public function updatedNivelId(): void
    {
        $this->grado_id = '';
        $this->semestre_id = '';
        $this->generacion_id = '';
        $this->generacionCalculada = null;
        $this->cargarOpcionesNivel();
    }

    public function updatedGradoId(): void
    {
        if (!$this->esBachillerato) {
            $this->semestre_id = '';
        }
        $this->resolverVistaPreviaGeneracion();
    }

    public function updatedSemestreId(): void
    {
        $semestre = Semestre::query()->find($this->semestre_id);
        $this->grado_id = $semestre?->grado_id ?: '';
        $this->resolverVistaPreviaGeneracion();
    }

    public function actualizarGrupo(AsignacionEscolarService $asignaciones): void
    {
        $grupo = Grupo::query()->find($this->grupo_id);

        if (!$grupo) {
            $this->mostrarError('El grupo seleccionado ya no existe.');
            $this->dispatch('cerrar-modal-editar');
            return;
        }

        $nivel = Nivel::query()->find($this->nivel_id);
        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        $data = $this->validate([
            'grupo_id' => ['required', 'integer', Rule::exists('grupos', 'id')],
            'ciclo_escolar_id' => ['required', 'integer', Rule::exists('ciclo_escolares', 'id')],
            'asignacion_grupo_id' => ['required', 'integer', Rule::exists('asignacion_grupos', 'id')],
            'nivel_id' => ['required', 'integer', Rule::exists('niveles', 'id')],
            'grado_id' => ['required', 'integer', Rule::exists('grados', 'id')->where('nivel_id', $this->nivel_id)],
            'semestre_id' => $this->esBachillerato
                ? ['required', 'integer', Rule::exists('semestres', 'id')->where('grado_id', $this->grado_id)]
                : ['nullable'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
        ]);

        $ciclo = CicloEscolar::query()->findOrFail((int) $data['ciclo_escolar_id']);
        $nivel = Nivel::query()->findOrFail((int) $data['nivel_id']);
        $grado = Grado::query()->findOrFail((int) $data['grado_id']);
        $semestre = $this->esBachillerato
            ? Semestre::query()->findOrFail((int) $data['semestre_id'])
            : null;
        $generacion = $this->esGeneracionExcepcional
            ? Generacion::query()->findOrFail((int) $grupo->generacion_id)
            : $asignaciones->resolverOCrearGeneracion($ciclo, $nivel, $grado, $semestre);

        $cambioAcademico = (int) $grupo->ciclo_escolar_id !== (int) $ciclo->id
            || (int) $grupo->nivel_id !== (int) $nivel->id
            || (int) $grupo->grado_id !== (int) $grado->id
            || (int) $grupo->generacion_id !== (int) $generacion->id
            || ($grupo->semestre_id ? (int) $grupo->semestre_id : null) !== ($semestre?->id ? (int) $semestre->id : null);

        if ($cambioAcademico && ($grupo->inscripciones()->withTrashed()->exists() || $this->esGeneracionExcepcional)) {
            $mensaje = $this->esGeneracionExcepcional
                ? 'La estructura de un grupo con generación excepcional está protegida. Crea otro grupo si necesitas una combinación distinta.'
                : 'No puedes cambiar la estructura académica porque el grupo ya tiene alumnos relacionados. Crea un grupo nuevo y usa “Cambiar asignación escolar” en los alumnos.';
            $this->addError('ciclo_escolar_id', $mensaje);
            return;
        }

        $clave = $asignaciones->claveGrupo(
            $ciclo,
            $nivel,
            $grado,
            $generacion,
            (int) $data['asignacion_grupo_id'],
            $semestre,
        );

        if (Grupo::withTrashed()->where('clave', $clave)->whereKeyNot($grupo->id)->exists()) {
            $this->addError('asignacion_grupo_id', 'Ya existe otro grupo con la misma combinación académica.');
            return;
        }

        $grupo->update([
            'ciclo_escolar_id' => $ciclo->id,
            'clave' => $clave,
            'estado' => $data['estado'],
            'motivo_generacion_excepcional' => $this->esGeneracionExcepcional
                ? $this->motivoGeneracionExcepcional
                : null,
            'asignacion_grupo_id' => (int) $data['asignacion_grupo_id'],
            'nivel_id' => $nivel->id,
            'grado_id' => $grado->id,
            'generacion_id' => $generacion->id,
            'semestre_id' => $semestre?->id,
        ]);

        $this->dispatch('refreshGrupos');
        $this->dispatch('grupoActualizado');
        $this->dispatch('cerrar-modal-editar');
        $this->dispatch('swal', [
            'title' => 'Grupo actualizado',
            'text' => 'La relación con ciclo, generación y periodo quedó validada.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
        $this->limpiarFormulario();
    }

    #[On('refreshAsignacionGrupos')]
    public function refreshAsignacionGrupos(int|array|null $id = null): void
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        if ($id && AsignacionGrupo::query()->whereKey($id)->exists()) {
            $this->asignacion_grupo_id = $id;
        }
    }

    public function cerrarModal(): void
    {
        $this->limpiarFormulario();
    }

    private function cargarOpcionesNivel(): void
    {
        $nivel = Nivel::query()->find($this->nivel_id);
        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        $this->grados = $this->esBachillerato || !$nivel
            ? collect()
            : Grado::query()->where('nivel_id', $nivel->id)->orderBy('orden')->get();

        $this->semestres = $this->esBachillerato && $nivel
            ? Semestre::query()
                ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivel->id))
                ->with('grado:id,nivel_id,nombre,orden')
                ->orderBy('numero')
                ->get()
            : collect();
    }

    private function resolverVistaPreviaGeneracion(): void
    {
        if ($this->esGeneracionExcepcional) {
            return;
        }

        $this->generacion_id = '';
        $this->generacionCalculada = null;
        $this->advertenciaAsignacion = null;

        if (!$this->ciclo_escolar_id || !$this->nivel_id || !$this->grado_id) {
            return;
        }

        $ciclo = CicloEscolar::query()->find($this->ciclo_escolar_id);
        $nivel = Nivel::query()->find($this->nivel_id);
        $grado = Grado::query()->find($this->grado_id);
        $semestre = $this->semestre_id ? Semestre::query()->find($this->semestre_id) : null;

        if (!$ciclo || !$nivel || !$grado) {
            return;
        }

        $servicio = app(AsignacionEscolarService::class);
        $this->generacionCalculada = $servicio->etiquetaGeneracionEsperada($ciclo, $nivel, $grado, $semestre);
        $generacion = $servicio->resolverGeneracion($ciclo, $nivel, $grado, $semestre);
        $this->generacion_id = $generacion?->id ?: '';

        if (!$generacion) {
            $this->advertenciaAsignacion = 'La generación se creará automáticamente cuando guardes.';
        }
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'grupo_id', 'ciclo_escolar_id', 'asignacion_grupo_id', 'nivel_id', 'grado_id',
            'generacion_id', 'semestre_id', 'estado', 'esBachillerato', 'generacionCalculada',
            'advertenciaAsignacion', 'tieneInscripciones', 'esGeneracionExcepcional',
            'motivoGeneracionExcepcional',
        ]);
        $this->grados = collect();
        $this->semestres = collect();
        $this->estado = 'activo';
        $this->resetValidation();
    }

    private function mostrarError(string $mensaje): void
    {
        $this->dispatch('swal', [
            'title' => 'No fue posible continuar',
            'text' => $mensaje,
            'icon' => 'error',
            'position' => 'top-end',
        ]);
    }

    public function render()
    {
        return view('livewire.grupo.editar-grupo', [
            'ciclosEscolares' => CicloEscolar::query()->orderByDesc('inicio_anio')->get(),
            'niveles' => Nivel::query()->orderBy('id')->get(),
            'asignacionGrupos' => AsignacionGrupo::query()->orderBy('nombre')->get(),
        ]);
    }
}
