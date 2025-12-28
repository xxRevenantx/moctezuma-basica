<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\PersonaRole;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPersonaNivel extends Component
{
    public ?int $personaId = null;   // id persona_nivel
    public ?int $persona_id = null;

    public ?int $nivel_id = null;
    public ?int $grado_id = null;
    public ?int $grupo_id = null;

    public Collection $grados;
    public Collection $grupos;

    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct = null;


    public ?string $nombrePersona = null;

    public ?string $nombreNivel = null;
    public ?string $nombreGrado = null;
    public ?string $nombreGrupo = null;

    public bool $open = false;

    public function mount(): void
    {
        $this->grados = collect();
        $this->grupos = collect();
    }

    #[On('editarModal')]
    public function editarModal(int $id): void
    {
        // abre modal + loader (Alpine)
        $this->dispatch('abrir-modal-editar');

        $personal = PersonaNivel::findOrFail($id);

        $this->personaId   = $personal->id;
        $this->persona_id  = $personal->persona_id;
        $this->nivel_id    = $personal->nivel_id;
        $this->grado_id    = $personal->grado_id;
        $this->grupo_id    = $personal->grupo_id;
        $this->ingreso_seg = $personal->ingreso_seg;
        $this->ingreso_sep = $personal->ingreso_sep;
        $this->ingreso_ct = $personal->ingreso_ct;


        // nombre persona (una sola consulta)
        $p = Persona::find($this->persona_id);
        $this->nombrePersona = $p
            ? trim($p->nombre . ' ' . $p->apellido_paterno . ' ' . $p->apellido_materno)
            : null;

        // nombres dependientes
        $n = Nivel::find($this->nivel_id);
        $this->nombreNivel = $n ? $n->nombre : null;

        $g = Grado::find($this->grado_id);
        $this->nombreGrado = $g ? $g->nombre : null;

        $gr = Grupo::find($this->grupo_id);
        $this->nombreGrupo = $gr ? $gr->nombre : null;

        // ✅ Cargar dependientes para que el edit muestre seleccionado
        $this->cargarGrados();
        $this->cargarGrupos();

        $this->open = true;

        // quita loader
        $this->dispatch('editar-cargado');
    }

    // ====== Dependientes ======
    public function updatedNivelId($value): void
    {
        $this->grado_id = null;
        $this->grupo_id = null;

        $this->cargarGrados();
        $this->grupos = collect();
    }

    public function updatedGradoId($value): void
    {
        $this->grupo_id = null;

        $this->cargarGrupos();
    }

    private function cargarGrados(): void
    {
        $this->grados = $this->nivel_id
            ? Grado::query()
                ->where('nivel_id', $this->nivel_id)
                ->orderBy('nombre')
                ->get()
            : collect();
    }

    private function cargarGrupos(): void
    {
        $this->grupos = $this->grado_id
            ? Grupo::query()
                ->where('grado_id', $this->grado_id)
                ->orderBy('nombre')
                ->get()
            : collect();
    }

    // ====== Guardar ======
    public function actualizarPersonal(): void
    {
        $this->validate([
            'persona_id'  => 'required|exists:personas,id',
            'nivel_id'    => 'required|exists:niveles,id',
            'grado_id'    => 'required|exists:grados,id',
            'grupo_id'    => 'required|exists:grupos,id',
            'ingreso_seg' => 'nullable|date',
            'ingreso_sep' => 'nullable|date',
            'ingreso_ct'  => 'nullable|date',
        ]);

        $personal = PersonaNivel::findOrFail($this->personaId);


        // VERIFICA SI YA EXISTE ASIGNACIÓN PARA ESA PERSONA EN ESE NIVEL, GRADO Y GRUPO
        $existeAsignacion = PersonaNivel::query()
            ->where('persona_id', $this->persona_id)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('id', '!=', $this->personaId) // Excluir el registro actual
            ->exists();

        if ($existeAsignacion) {
            $this->dispatch('swal', [
            'title' => 'Error: La asignación ya existe para esta persona en el nivel, grado y grupo seleccionados.',
            'icon'  => 'error',
            'position' => 'top',
            ]);
            return;
        }

        // VERIFICA SI YA HAY OTRA PERSONA ASIGNADA EN ESE NIVEL, GRADO Y GRUPO
        $existeOtraPersona = PersonaNivel::query()
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_id)
            ->where('grupo_id', $this->grupo_id)
            ->where('id', '!=', $this->personaId) // Excluir el registro actual
            ->exists();

        if ($existeOtraPersona) {
            $this->dispatch('swal', [
            'title' => 'Error: Ya existe otra persona asignada a este nivel, grado y grupo.',
            'icon'  => 'error',
            'position' => 'top',
            ]);
            return;
        }

        $personal->update([
            'persona_id'  => $this->persona_id,
            'nivel_id'    => $this->nivel_id,
            'grado_id'    => $this->grado_id,
            'grupo_id'    => $this->grupo_id,
            'ingreso_seg' => $this->ingreso_seg,
            'ingreso_sep' => $this->ingreso_sep,
            'ingreso_ct'  => $this->ingreso_ct,
        ]);

        $this->dispatch('swal', [
            'title' => 'Asignación actualizada correctamente!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('cerrar-modal-editar');
        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset([
            'open',
            'personaId',
            'persona_id',
            'nivel_id',
            'grado_id',
            'grupo_id',
            'ingreso_seg',
            'ingreso_sep',
            'ingreso_ct',
            'nombrePersona',
        ]);

        $this->grados = collect();
        $this->grupos = collect();
    }

    public function render()
    {

         $personas = PersonaRole::with('persona')
            ->get()
            ->unique('persona_id')
            ->sortBy(fn ($pr) => $pr->persona->nombre ?? '')
            ->values();
        $niveles  = Nivel::orderBy('id')->get();

        return view('livewire.persona-nivel.editar-persona-nivel', compact('personas', 'niveles'));
    }
}
