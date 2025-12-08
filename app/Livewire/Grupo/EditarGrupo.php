<?php

namespace App\Livewire\Grupo;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarGrupo extends Component
{
    public $grupoId;
    public $nombre;
    public $nivel_id;
    public $grado_id;
    public $generacion_id;
    public $semestre_id;

    public $open = false;

    // Para el badge
    public $nivel_nombre;
    public $grado_nombre;

    public $grados = [];

    // Flag para NO resetear al cargar desde editarModal
    public $skipResetNivel = false;

    #[On('editarModal')]
    public function editarModal($id)
    {

        $grupo = Grupo::findOrFail($id);

        // dd($id);

        $this->grupoId       = $grupo->id;
        $this->nombre        = $grupo->nombre;

        // Evitar limpiar dependientes en esta carga inicial
        $this->skipResetNivel = true;

        $this->nivel_id      = $grupo->nivel_id;
        $this->grado_id      = $grupo->grado_id;
        $this->generacion_id = $grupo->generacion_id;
        $this->semestre_id   = $grupo->semestre_id;

        $this->skipResetNivel = false;

        // Datos para el badge
        $nivel = Nivel::find($this->nivel_id);
        $this->nivel_nombre = $nivel?->nombre;

        $grado = Grado::find($this->grado_id);
        $this->grado_nombre = $grado?->nombre;

        // Cargar grados del nivel del grupo
        $this->grados = Grado::where('nivel_id', $this->nivel_id)
            ->orderBy('nombre')
            ->get();

        $this->open = true;

        // Notificar a Alpine que ya se cargaron los datos
        $this->dispatch('editar-cargado');
    }

    /**
     * Se ejecuta cuando cambia nivel_id (desde el select).
     */
    public function updatedNivelId($value)
    {
        // Si venimos de editarModal, no limpiamos
        if ($this->skipResetNivel) {
            return;
        }

        $nivel = Nivel::find($value);
        $this->nivel_nombre = $nivel?->nombre;

        // Cargar grados del nivel seleccionado
        $this->grados = Grado::where('nivel_id', $this->nivel_id)
            ->orderBy('nombre')
            ->get();

        // Limpiar dependientes al cambiar manualmente el nivel
        $this->reset(['grado_id', 'generacion_id', 'semestre_id']);
        $this->grado_nombre = null;
    }

    public function updatedGradoId($value)
    {
        $grado = Grado::find($value);
        $this->grado_nombre = $grado?->nombre;
    }

    public function actualizarGrupo()
    {
        $rules = [
            'nombre'         => 'required|string|max:255',
            'nivel_id'       => 'required|exists:niveles,id',
            'grado_id'       => 'required|exists:grados,id',
            'generacion_id'  => 'required|exists:generaciones,id',
            'semestre_id'    => 'nullable|exists:semestres,id',
        ];

        // Si el nivel es Bachillerato, semestre_id es obligatorio
        $nivelSeleccionado = Nivel::find($this->nivel_id);
        if ($nivelSeleccionado && $nivelSeleccionado->slug === 'bachillerato') {
            $rules['semestre_id'] = 'required|exists:semestres,id';
        }

        $this->validate($rules, [
            'nombre.required'         => 'El nombre es obligatorio.',
            'nivel_id.required'       => 'El nivel es obligatorio.',
            'nivel_id.exists'         => 'El nivel seleccionado no es válido.',
            'grado_id.required'       => 'El grado es obligatorio.',
            'grado_id.exists'         => 'El grado seleccionado no es válido.',
            'generacion_id.required'  => 'La generación es obligatoria.',
            'generacion_id.exists'    => 'La generación seleccionada no es válida.',
            'semestre_id.required'    => 'El semestre es obligatorio para Bachillerato.',
            'semestre_id.exists'      => 'El semestre seleccionado no es válido.',
        ]);

        // -------------------------------
        // VALIDACIONES DE CONSISTENCIA
        // -------------------------------

        // 1) Grado pertenece al nivel
        $grado = Grado::find($this->grado_id);
        if (!$grado || $grado->nivel_id != $this->nivel_id) {
            $this->addError('grado_id', 'El grado no pertenece al nivel seleccionado.');
            return;
        }

        // 2) Generación pertenece al nivel
        $generacion = Generacion::find($this->generacion_id);
        if (!$generacion || $generacion->nivel_id != $this->nivel_id) {
            $this->addError('generacion_id', 'La generación no pertenece al nivel seleccionado.');
            return;
        }

        // 3) El semestre (si existe) pertenece al grado (y por cadena al nivel)
        if ($this->semestre_id) {
            $semestre = Semestre::find($this->semestre_id);

            if (!$semestre) {
                $this->addError('semestre_id', 'El semestre seleccionado no es válido.');
                return;
            }

            if ($semestre->grado_id != $this->grado_id) {
                $this->addError('semestre_id', 'El semestre no pertenece al grado seleccionado.');
                return;
            }
        }

        // Si todo es consistente, ahora sí actualizamos
        $grupo = Grupo::findOrFail($this->grupoId);

        $grupo->update([
            'nombre'        => strtoupper($this->nombre),
            'nivel_id'      => $this->nivel_id,
            'grado_id'      => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id'   => $this->semestre_id ?: null,
        ]);

        $this->dispatch('swal', [
            'title'    => '¡Grupo actualizado correctamente!',
            'icon'     => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshGrupos');
        $this->dispatch('cerrar-modal-editar');
    }

    public function cerrarModal()
    {
        $this->reset([
            'open',
            'grupoId',
            'nombre',
            'nivel_id',
            'grado_id',
            'generacion_id',
            'semestre_id',
            'nivel_nombre',
            'grado_nombre',
            'skipResetNivel',
            'grados',
        ]);
        $this->resetValidation();
    }

    public function render()
    {
        $niveles        = Nivel::orderBy('id')->get();
        $generaciones   = collect();
        $semestres      = collect();
        $esBachillerato = false;


        $generaciones = Generacion::all();

        $nivelSeleccionado = $niveles->firstWhere('id', $this->nivel_id);

        if ($nivelSeleccionado && $nivelSeleccionado->slug === 'bachillerato') {
            $esBachillerato = true;
            $semestres = Semestre::orderBy('id')->get();
        }


        // Aseguramos que $grados siempre esté sincronizado si hay nivel

        $this->grados = Grado::all();


        return view('livewire.grupo.editar-grupo', [
            'niveles'        => $niveles,
            'generaciones'   => $generaciones,
            'semestres'      => $semestres,
            'esBachillerato' => $esBachillerato,
            'grados'         => $this->grados,
        ]);
    }
}
