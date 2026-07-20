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

class CrearGrupo extends Component
{
    public int|string|null $ciclo_escolar_id = '';
    public int|string|null $asignacion_grupo_id = '';
    public int|string|null $nivel_id = '';
    public int|string|null $grado_id = '';
    public int|string|null $generacion_id = '';
    public int|string|null $semestre_id = '';
    public string $estado = 'activo';
    public bool $usar_generacion_excepcional = false;
    public int|string|null $generacion_excepcional_id = '';
    public ?string $motivo_generacion_excepcional = null;

    public Collection $grados;
    public Collection $semestres;
    public ?string $generacionCalculada = null;
    public ?string $advertenciaAsignacion = null;
    public bool $esBachillerato = false;

    public function mount(): void
    {
        $this->grados = collect();
        $this->semestres = collect();
        $this->ciclo_escolar_id = CicloEscolar::query()
            ->where('es_actual', true)
            ->value('id') ?: '';
    }

    public function updatedCicloEscolarId(): void
    {
        $this->limpiarDependencias(false);
    }

    public function updatedNivelId(): void
    {
        $this->limpiarDependencias(true);
        $nivel = Nivel::query()->find($this->nivel_id);
        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        if (!$nivel) {
            return;
        }

        if ($this->esBachillerato) {
            $this->semestres = Semestre::query()
                ->whereHas('grado', fn ($query) => $query->where('nivel_id', $nivel->id))
                ->with('grado:id,nivel_id,nombre,orden')
                ->orderBy('numero')
                ->get();
        } else {
            $this->grados = Grado::query()
                ->where('nivel_id', $nivel->id)
                ->orderBy('orden')
                ->get();
        }
    }

    public function updatedGradoId(): void
    {
        $this->semestre_id = '';
        $this->resolverVistaPreviaGeneracion();
    }

    public function updatedSemestreId(): void
    {
        $semestre = Semestre::query()->find($this->semestre_id);
        $this->grado_id = $semestre?->grado_id ?: '';
        $this->resolverVistaPreviaGeneracion();
    }

    public function updatedUsarGeneracionExcepcional(bool $value): void
    {
        $this->generacion_excepcional_id = '';
        $this->motivo_generacion_excepcional = null;
        $this->resetValidation(['generacion_excepcional_id', 'motivo_generacion_excepcional']);

        if (! $value) {
            $this->resolverVistaPreviaGeneracion();
        }
    }

    public function guardarGrupo(AsignacionEscolarService $asignaciones): void
    {
        $nivel = Nivel::query()->find($this->nivel_id);
        $this->esBachillerato = $nivel?->slug === 'bachillerato';

        $data = $this->validate([
            'ciclo_escolar_id' => ['required', 'integer', Rule::exists('ciclo_escolares', 'id')],
            'asignacion_grupo_id' => ['required', 'integer', Rule::exists('asignacion_grupos', 'id')],
            'nivel_id' => ['required', 'integer', Rule::exists('niveles', 'id')],
            'grado_id' => ['required', 'integer', Rule::exists('grados', 'id')->where('nivel_id', $this->nivel_id)],
            'semestre_id' => $this->esBachillerato
                ? ['required', 'integer', Rule::exists('semestres', 'id')->where('grado_id', $this->grado_id)]
                : ['nullable'],
            'estado' => ['required', Rule::in(['activo', 'inactivo'])],
            'usar_generacion_excepcional' => ['boolean'],
            'generacion_excepcional_id' => $this->usar_generacion_excepcional
                ? ['required', 'integer', Rule::exists('generaciones', 'id')->where(fn ($query) => $query->where('nivel_id', $this->nivel_id)->where('status', true))]
                : ['nullable'],
            'motivo_generacion_excepcional' => $this->usar_generacion_excepcional
                ? ['required', 'string', 'min:10', 'max:500']
                : ['nullable'],
        ], [
            'ciclo_escolar_id.required' => 'Selecciona el ciclo escolar del grupo.',
            'asignacion_grupo_id.required' => 'Selecciona la letra o nombre del grupo.',
            'nivel_id.required' => 'Selecciona un nivel educativo.',
            'grado_id.required' => 'Selecciona un grado.',
            'grado_id.exists' => 'El grado no pertenece al nivel seleccionado.',
            'semestre_id.required' => 'Selecciona un semestre para Bachillerato.',
            'semestre_id.exists' => 'El semestre no corresponde al grado interno.',
            'generacion_excepcional_id.required' => 'Selecciona la generación excepcional.',
            'generacion_excepcional_id.exists' => 'La generación excepcional no pertenece al nivel o está inactiva.',
            'motivo_generacion_excepcional.required' => 'Explica por qué el grupo utilizará una generación diferente a la calculada.',
            'motivo_generacion_excepcional.min' => 'El motivo debe tener al menos 10 caracteres.',
        ]);

        $ciclo = CicloEscolar::query()->findOrFail((int) $data['ciclo_escolar_id']);
        $nivel = Nivel::query()->findOrFail((int) $data['nivel_id']);
        $grado = Grado::query()->findOrFail((int) $data['grado_id']);
        $semestre = $this->esBachillerato
            ? Semestre::query()->findOrFail((int) $data['semestre_id'])
            : null;

        $generacion = $this->usar_generacion_excepcional
            ? Generacion::query()
                ->where('nivel_id', $nivel->id)
                ->where('status', true)
                ->findOrFail((int) $data['generacion_excepcional_id'])
            : $asignaciones->resolverOCrearGeneracion($ciclo, $nivel, $grado, $semestre);
        $clave = $asignaciones->claveGrupo(
            $ciclo,
            $nivel,
            $grado,
            $generacion,
            (int) $data['asignacion_grupo_id'],
            $semestre,
        );

        $existe = Grupo::withTrashed()
            ->where('clave', $clave)
            ->first();

        if ($existe && !$existe->trashed()) {
            $this->addError('asignacion_grupo_id', 'Este grupo ya existe para el ciclo y la asignación académica seleccionados.');
            return;
        }

        if ($existe?->trashed()) {
            $existe->restore();
            $existe->update([
                'ciclo_escolar_id' => $ciclo->id,
                'clave' => $clave,
                'estado' => $data['estado'],
                'motivo_generacion_excepcional' => $this->usar_generacion_excepcional
                    ? trim((string) $data['motivo_generacion_excepcional'])
                    : null,
                'asignacion_grupo_id' => (int) $data['asignacion_grupo_id'],
                'nivel_id' => $nivel->id,
                'grado_id' => $grado->id,
                'generacion_id' => $generacion->id,
                'semestre_id' => $semestre?->id,
            ]);
        } else {
            Grupo::query()->create([
                'ciclo_escolar_id' => $ciclo->id,
                'clave' => $clave,
                'estado' => $data['estado'],
                'motivo_generacion_excepcional' => $this->usar_generacion_excepcional
                    ? trim((string) $data['motivo_generacion_excepcional'])
                    : null,
                'asignacion_grupo_id' => (int) $data['asignacion_grupo_id'],
                'nivel_id' => $nivel->id,
                'grado_id' => $grado->id,
                'generacion_id' => $generacion->id,
                'semestre_id' => $semestre?->id,
            ]);
        }

        $this->dispatch('swal', [
            'title' => 'Grupo creado correctamente',
            'text' => "Generación {$generacion->anio_ingreso}-{$generacion->anio_egreso} · cupo ilimitado."
                . ($this->usar_generacion_excepcional ? ' Excepción documentada.' : ''),
            'icon' => 'success',
            'position' => 'top-end',
        ]);
        $this->dispatch('refreshGrupos');
        $this->limpiarFormulario();
    }

    private function resolverVistaPreviaGeneracion(): void
    {
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
            $this->advertenciaAsignacion = 'La generación todavía no existe. Se creará automáticamente al guardar el grupo.';
        }
    }

    private function limpiarDependencias(bool $conservarNivel): void
    {
        if (!$conservarNivel) {
            $this->nivel_id = '';
            $this->esBachillerato = false;
        }

        $this->grado_id = '';
        $this->generacion_id = '';
        $this->semestre_id = '';
        $this->grados = collect();
        $this->semestres = collect();
        $this->generacionCalculada = null;
        $this->advertenciaAsignacion = null;
        $this->resetValidation();
    }

    private function limpiarFormulario(): void
    {
        $cicloActual = $this->ciclo_escolar_id;
        $this->reset([
            'asignacion_grupo_id',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'estado',
            'esBachillerato',
            'generacionCalculada',
            'advertenciaAsignacion',
            'usar_generacion_excepcional',
            'generacion_excepcional_id',
            'motivo_generacion_excepcional',
        ]);
        $this->ciclo_escolar_id = $cicloActual;
        $this->estado = 'activo';
        $this->grados = collect();
        $this->semestres = collect();
        $this->resetValidation();
    }

    #[On('refreshAsignacionGrupos')]
    public function render()
    {
        return view('livewire.grupo.crear-grupo', [
            'ciclosEscolares' => CicloEscolar::query()
                ->orderByDesc('inicio_anio')
                ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']),
            'niveles' => Nivel::query()->orderBy('id')->get(),
            'asignacionGrupos' => AsignacionGrupo::query()->orderBy('nombre')->get(),
            'generacionesExcepcionales' => $this->nivel_id
                ? Generacion::query()
                    ->where('nivel_id', $this->nivel_id)
                    ->where('status', true)
                    ->orderByDesc('anio_ingreso')
                    ->get(['id', 'anio_ingreso', 'anio_egreso'])
                : collect(),
        ]);
    }
}
