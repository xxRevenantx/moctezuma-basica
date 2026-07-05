<?php

namespace App\Livewire\Grupo;

use App\Models\AsignacionGrupo;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGrupo extends Component
{
    public ?int $grupo_id = null;

    public int|string|null $asignacion_grupo_id = '';
    public int|string|null $nivel_id = '';
    public int|string|null $grado_id = '';
    public int|string|null $generacion_id = '';
    public int|string|null $semestre_id = '';

    public string $grupo_nombre = '';
    public string $nivel_nombre = '';
    public string $grado_nombre = '';

    public Collection $grados;
    public Collection $generaciones;
    public Collection $semestres;

    public bool $esBachillerato = false;

    public function mount(): void
    {
        $this->grados = collect();
        $this->generaciones = collect();
        $this->semestres = collect();
    }

    #[On('editarModal')]
    public function editarModal(int|array|null $id = null): void
    {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        $this->resetValidation();

        if (!$id) {
            $this->mostrarError('No se recibió el grupo que se desea editar.');
            $this->dispatch('cerrar-modal-editar');

            return;
        }

        $grupo = Grupo::query()
            ->with([
                'asignacionGrupo',
                'nivel',
                'grado',
                'generacion',
                'semestre',
            ])
            ->find($id);

        if (!$grupo) {
            $this->mostrarError('El grupo seleccionado ya no existe.');
            $this->dispatch('cerrar-modal-editar');

            return;
        }

        $this->grupo_id = $grupo->id;
        $this->asignacion_grupo_id = $grupo->asignacion_grupo_id;
        $this->nivel_id = $grupo->nivel_id;
        $this->grado_id = $grupo->grado_id ?: '';
        $this->generacion_id = $grupo->generacion_id ?: '';
        $this->semestre_id = $grupo->semestre_id ?: '';

        $this->esBachillerato = $this->nivelEsBachillerato(
            $grupo->nivel
        );

        $this->grupo_nombre = $grupo->asignacionGrupo?->nombre
            ?? 'No definido';

        $this->nivel_nombre = $grupo->nivel?->nombre
            ?? 'Nivel no seleccionado';

        $this->grado_nombre = $this->esBachillerato
            ? 'No aplica'
            : ($grupo->grado?->nombre ?? 'Grado no seleccionado');

        $this->cargarDatosPorNivel();

        $this->dispatch('editar-cargado');
    }

    public function updatedAsignacionGrupoId(
        int|string|null $value
    ): void {
        $this->resetValidation('asignacion_grupo_id');

        $this->grupo_nombre = AsignacionGrupo::query()
            ->whereKey($value)
            ->value('nombre') ?? 'No definido';
    }

    public function updatedNivelId(
        int|string|null $value
    ): void {
        $this->resetValidation([
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
        ]);

        $this->grado_id = '';
        $this->generacion_id = '';
        $this->semestre_id = '';
        $this->grado_nombre = '';

        $nivel = Nivel::query()->find($value);

        $this->nivel_nombre = $nivel?->nombre
            ?? 'Nivel no seleccionado';

        $this->esBachillerato = $this->nivelEsBachillerato($nivel);

        if ($this->esBachillerato) {
            $this->grado_nombre = 'No aplica';
        }

        $this->cargarDatosPorNivel();
    }

    public function updatedGradoId(
        int|string|null $value
    ): void {
        $this->resetValidation('grado_id');

        if ($this->esBachillerato) {
            $this->grado_nombre = 'No aplica';

            return;
        }

        $this->grado_nombre = Grado::query()
            ->whereKey($value)
            ->value('nombre') ?? 'Grado no seleccionado';
    }

    public function updatedGeneracionId(): void
    {
        $this->resetValidation('generacion_id');
    }

    public function updatedSemestreId(): void
    {
        $this->resetValidation('semestre_id');
    }

    public function cargarDatosPorNivel(): void
    {
        if (!$this->nivel_id) {
            $this->grados = collect();
            $this->generaciones = collect();
            $this->semestres = collect();
            $this->esBachillerato = false;

            return;
        }

        $nivel = Nivel::query()->find($this->nivel_id);

        $this->esBachillerato = $this->nivelEsBachillerato($nivel);

        /*
         * Bachillerato se administra por semestre,
         * por lo que no requiere grado.
         */
        $this->grados = $this->esBachillerato
            ? collect()
            : Grado::query()
                ->with('nivel')
                ->where('nivel_id', $this->nivel_id)
                ->orderBy('id')
                ->get();

        /*
         * Mostrar generaciones activas.
         *
         * También mantiene disponible la generación actual
         * cuando se está editando, aunque esté inactiva.
         */
        $this->generaciones = Generacion::query()
            ->with('nivel')
            ->where('nivel_id', $this->nivel_id)
            ->where(function ($query): void {
                $query->where('status', true);

                if (
                    $this->generacion_id !== null
                    && $this->generacion_id !== ''
                ) {
                    $query->orWhere(
                        'generaciones.id',
                        (int) $this->generacion_id
                    );
                }
            })
            ->orderByDesc('anio_ingreso')
            ->get();

        $this->semestres = $this->esBachillerato
            ? Semestre::query()
                ->orderBy('numero')
                ->get()
            : collect();

        if ($this->esBachillerato) {
            $this->grado_id = '';
            $this->grado_nombre = 'No aplica';
        } else {
            $this->semestre_id = '';
        }
    }
    public function actualizarGrupo(): void
    {
        /*
         * Se vuelve a calcular para evitar depender únicamente
         * del estado almacenado en el navegador.
         */
        $nivel = Nivel::query()->find($this->nivel_id);

        $this->esBachillerato = $this->nivelEsBachillerato($nivel);

        if ($this->esBachillerato) {
            $this->grado_id = '';
        } else {
            $this->semestre_id = '';
        }

        $this->validate([
            'grupo_id' => [
                'required',
                'integer',
                'exists:grupos,id',
            ],

            'asignacion_grupo_id' => [
                'required',
                'integer',
                'exists:asignacion_grupos,id',
            ],

            'nivel_id' => [
                'required',
                'integer',
                'exists:niveles,id',
            ],

            'grado_id' => $this->esBachillerato
                ? [
                    'nullable',
                ]
                : [
                    'required',
                    'integer',
                    Rule::exists('grados', 'id')
                        ->where(
                            fn($query) => $query->where(
                                'nivel_id',
                                $this->nivel_id
                            )
                        ),
                ],

            'generacion_id' => [
                'required',
                'integer',
                Rule::exists('generaciones', 'id')
                    ->where(
                        fn($query) => $query->where(
                            'nivel_id',
                            $this->nivel_id
                        )
                    ),
            ],

            'semestre_id' => $this->esBachillerato
                ? [
                    'required',
                    'integer',
                    'exists:semestres,id',
                ]
                : [
                    'nullable',
                ],
        ], [
            'grupo_id.required' => 'No se identificó el grupo que se desea editar.',
            'grupo_id.exists' => 'El grupo seleccionado ya no existe.',

            'asignacion_grupo_id.required' => 'Selecciona la letra o nombre del grupo.',
            'asignacion_grupo_id.integer' => 'El grupo seleccionado no es válido.',
            'asignacion_grupo_id.exists' => 'El grupo seleccionado no existe.',

            'nivel_id.required' => 'Selecciona un nivel educativo.',
            'nivel_id.integer' => 'El nivel seleccionado no es válido.',
            'nivel_id.exists' => 'El nivel seleccionado no existe.',

            'grado_id.required' => 'Selecciona un grado.',
            'grado_id.integer' => 'El grado seleccionado no es válido.',
            'grado_id.exists' => 'El grado no pertenece al nivel seleccionado.',

            'generacion_id.required' => 'Selecciona una generación.',
            'generacion_id.integer' => 'La generación seleccionada no es válida.',
            'generacion_id.exists' => 'La generación no pertenece al nivel seleccionado.',

            'semestre_id.required' => 'Selecciona un semestre para Bachillerato.',
            'semestre_id.integer' => 'El semestre seleccionado no es válido.',
            'semestre_id.exists' => 'El semestre seleccionado no existe.',
        ]);

        $existe = Grupo::query()
            ->whereKeyNot($this->grupo_id)
            ->where(
                'asignacion_grupo_id',
                $this->asignacion_grupo_id
            )
            ->where('nivel_id', $this->nivel_id)
            ->where('generacion_id', $this->generacion_id)
            ->when(
                $this->esBachillerato,
                fn($query) => $query
                    ->whereNull('grado_id')
                    ->where('semestre_id', $this->semestre_id),
                fn($query) => $query
                    ->where('grado_id', $this->grado_id)
                    ->whereNull('semestre_id')
            )
            ->exists();

        if ($existe) {
            $this->addError(
                'asignacion_grupo_id',
                'Ya existe un grupo con el mismo nivel, generación y periodo académico.'
            );

            return;
        }

        $grupo = Grupo::query()->find($this->grupo_id);

        if (!$grupo) {
            $this->mostrarError('El grupo seleccionado ya no existe.');
            $this->dispatch('cerrar-modal-editar');

            return;
        }

        $grupo->update([
            'asignacion_grupo_id' => $this->asignacion_grupo_id,
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->esBachillerato
                ? null
                : $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->esBachillerato
                ? $this->semestre_id
                : null,
        ]);

        $this->dispatch('refreshGrupos');
        $this->dispatch('grupoActualizado');
        $this->dispatch('cerrar-modal-editar');

        $this->dispatch('swal', [
            'title' => 'Grupo actualizado',
            'text' => 'Los cambios se guardaron correctamente.',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->limpiarFormulario();
    }

    #[On('refreshAsignacionGrupos')]
    public function refreshAsignacionGrupos(
        int|array|null $id = null
    ): void {
        if (is_array($id)) {
            $id = $id['id'] ?? null;
        }

        /*
         * Cuando el modal secundario envía el ID del grupo creado,
         * se selecciona automáticamente.
         */
        if ($id) {
            $asignacion = AsignacionGrupo::query()->find($id);

            if ($asignacion) {
                $this->asignacion_grupo_id = $asignacion->id;
                $this->grupo_nombre = $asignacion->nombre;

                return;
            }
        }

        /*
         * Si solamente se editó el nombre, se actualiza el resumen.
         */
        if ($this->asignacion_grupo_id) {
            $this->grupo_nombre = AsignacionGrupo::query()
                ->whereKey($this->asignacion_grupo_id)
                ->value('nombre') ?? 'No definido';
        }
    }

    public function cerrarModal(): void
    {
        $this->limpiarFormulario();
    }

    private function limpiarFormulario(): void
    {
        $this->reset([
            'grupo_id',
            'asignacion_grupo_id',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'grupo_nombre',
            'nivel_nombre',
            'grado_nombre',
            'esBachillerato',
        ]);

        $this->grados = collect();
        $this->generaciones = collect();
        $this->semestres = collect();

        $this->resetValidation();
    }

    private function nivelEsBachillerato(?Nivel $nivel): bool
    {
        if (!$nivel) {
            return false;
        }

        $nombre = Str::lower(
            Str::ascii((string) $nivel->nombre)
        );

        return Str::contains($nombre, 'bachillerato');
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
            'niveles' => Nivel::query()
                ->orderBy('id')
                ->get([
                    'id',
                    'nombre',
                ]),

            'asignacionGrupos' => AsignacionGrupo::query()
                ->orderBy('nombre')
                ->get([
                    'id',
                    'nombre',
                ]),
        ]);
    }
}
