<?php

namespace App\Livewire\Grupo;

use Livewire\Component;
use App\Models\Nivel;
use App\Models\Grado;
use App\Models\Generacion;
use App\Models\Grupo;
use App\Models\Semestre;

class CrearGrupo extends Component
{
    public $nombre;
    public $nivel_id = '';
    public $grado_id = '';
    public $generacion_id = '';
    public $semestre_id = '';

    public function updatedNivelId()
    {
        // Cuando cambie el nivel, limpiamos dependientes
        $this->reset(['grado_id', 'generacion_id', 'semestre_id']);
    }

    public function guardarGrupo()
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'nivel_id' => 'required|exists:niveles,id',
            'grado_id' => 'required|exists:grados,id',
            'generacion_id' => 'required|exists:generaciones,id',
            'semestre_id' => 'nullable|exists:semestres,id',
        ];

        // Si el nivel es Bachillerato, semestre_id es obligatorio
        $nivelSeleccionado = Nivel::find($this->nivel_id);
        if ($nivelSeleccionado && $nivelSeleccionado->slug === 'bachillerato') {
            $rules['semestre_id'] = 'required|exists:semestres,id';
        }

        $this->validate($rules, [
            'nombre.required' => 'El nombre es obligatorio.',
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists' => 'El nivel seleccionado no es válido.',
            'grado_id.required' => 'El grado es obligatorio.',
            'grado_id.exists' => 'El grado seleccionado no es válido.',
            'generacion_id.required' => 'La generación es obligatoria.',
            'generacion_id.exists' => 'La generación seleccionada no es válida.',
            'semestre_id.required' => 'El semestre es obligatorio para Bachillerato.',
            'semestre_id.exists' => 'El semestre seleccionado no es válido.',
        ]);

        // VERIFICA QUE EL GRUPO NO EXISTA YA EN EN EL NIVEL, GRADO Y GENERACIÓN
        $queryGrupo = Grupo::where('nombre', strtoupper($this->nombre))
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('generacion_id', $this->generacion_id);

        // Si es bachillerato, también validar por semestre
        $nivelSeleccionado = Nivel::find($this->nivel_id);
        if ($nivelSeleccionado && $nivelSeleccionado->slug === 'bachillerato') {
            $queryGrupo->where('semestre_id', $this->semestre_id);
        }

        $existeGrupo = $queryGrupo->first();

        if ($existeGrupo) {
            $this->addError('nombre', 'Ya existe un grupo con este nombre en el nivel, grado, generación' . ($nivelSeleccionado && $nivelSeleccionado->slug === 'bachillerato' ? ' y semestre' : '') . ' seleccionados.');
            return;
        }

        Grupo::create([
            'nombre' => strtoupper($this->nombre),
            'nivel_id' => $this->nivel_id,
            'grado_id' => $this->grado_id,
            'generacion_id' => $this->generacion_id,
            'semestre_id' => $this->semestre_id ?: NULL,
        ]);

        $this->dispatch('swal', [
            'title' => '¡Grupo creado correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);
        $this->dispatch('refreshGrupos');

        $this->reset(['nombre', 'nivel_id', 'grado_id', 'generacion_id', 'semestre_id']);


    }

    public function render()
    {
        $niveles = Nivel::orderBy('id')->get();

        // Por defecto, colecciones vacías
        $grados = collect();
        $generaciones = collect();
        $semestres = collect();
        $esBachillerato = false;

        if ($this->nivel_id) {
            // Filtrar grados y generaciones por nivel
            $grados = Grado::where('nivel_id', $this->nivel_id)
                ->orderBy('nombre')
                ->get();

            $generaciones = Generacion::where('nivel_id', $this->nivel_id)
                ->where('status',1)
                ->orderBy('anio_ingreso', 'desc')
                ->orderBy('anio_egreso', 'asc')
                ->get();

            // Detectar si el nivel seleccionado es Bachillerato (por slug)
            $nivelSeleccionado = $niveles->firstWhere('id', $this->nivel_id);

            if ($nivelSeleccionado && $nivelSeleccionado->slug === 'bachillerato') {
                $esBachillerato = true;

                // Solo para bachillerato cargamos semestres
                $semestres = Semestre::orderBy('id')->get();
                // Si tus semestres también tienen nivel_id, podrías usar:
                // $semestres = Semestre::where('nivel_id', $this->nivel_id)->orderBy('id')->get();
            }
        }

        return view('livewire.grupo.crear-grupo', compact(
            'niveles',
            'grados',
            'generaciones',
            'semestres',
            'esBachillerato',
        ));
    }


}
