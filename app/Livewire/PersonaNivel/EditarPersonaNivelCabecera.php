<?php

namespace App\Livewire\PersonaNivel;

use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelDetalle;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class EditarPersonaNivelCabecera extends Component
{
    // ✅ id de CABECERA (persona_nivel.id)
    public ?int $cabeceraId = null;

    public ?int $persona_id = null;
    public ?int $nivel_id   = null;

    // Fechas de cabecera
    public ?string $ingreso_seg = null;
    public ?string $ingreso_sep = null;
    public ?string $ingreso_ct  = null;

    // Informativos
    public ?string $nombrePersona = null;
    public ?string $nombreNivel   = null;

    #[On('editarCabeceraModal')]
    public function editarCabeceraModal(int $id): void
    {
        $this->dispatch('abrir-modal-editar-cabecera');

        $cab = PersonaNivel::query()
            ->with(['persona:id,nombre,apellido_paterno,apellido_materno', 'nivel:id,nombre'])
            ->findOrFail($id);

        $this->cabeceraId = (int) $cab->id;

        $this->persona_id = (int) $cab->persona_id;
        $this->nivel_id   = (int) $cab->nivel_id;

        $this->ingreso_seg = $cab->ingreso_seg;
        $this->ingreso_sep = $cab->ingreso_sep;
        $this->ingreso_ct  = $cab->ingreso_ct;

        $p = $cab->persona;
        $this->nombrePersona = $p
            ? trim(($p->nombre ?? '').' '.($p->apellido_paterno ?? '').' '.($p->apellido_materno ?? ''))
            : null;

        $this->nombreNivel = $cab->nivel?->nombre;

        $this->dispatch('editar-cabecera-cargado');
    }

    public function actualizarCabecera(): void
    {
        $this->validate([
            'cabeceraId' => ['required', 'integer', 'exists:persona_nivel,id'],
            'persona_id' => ['required', 'integer', 'exists:personas,id'],
            'nivel_id'   => ['required', 'integer', 'exists:niveles,id'],
            'ingreso_seg' => ['nullable', 'date'],
            'ingreso_sep' => ['nullable', 'date'],
            'ingreso_ct'  => ['nullable', 'date'],
        ], [
            'persona_id.required' => 'Selecciona una persona.',
            'nivel_id.required'   => 'Selecciona un nivel.',
        ]);

        DB::transaction(function () {
            $cab = PersonaNivel::lockForUpdate()->findOrFail($this->cabeceraId);

            $oldPersonaId = (int) $cab->persona_id;
            $oldNivelId   = (int) $cab->nivel_id;

            $newPersonaId = (int) $this->persona_id;
            $newNivelId   = (int) $this->nivel_id;

            // ✅ Si NO cambia persona/nivel → solo actualiza fechas
            if ($oldPersonaId === $newPersonaId && $oldNivelId === $newNivelId) {
                $cab->update([
                    'ingreso_seg' => $this->ingreso_seg,
                    'ingreso_sep' => $this->ingreso_sep,
                    'ingreso_ct'  => $this->ingreso_ct,
                ]);
                return;
            }

            /**
             * ✅ Si cambia persona/nivel:
             * - Buscar/crear cabecera destino (persona+nivel)
             * - Mover TODOS los detalles a esa cabecera
             * - Actualizar fechas en cabecera destino
             * - Borrar cabecera vieja si se queda sin detalles
             */
            $dest = PersonaNivel::firstOrCreate(
                ['persona_id' => $newPersonaId, 'nivel_id' => $newNivelId],
                ['ingreso_seg' => $this->ingreso_seg, 'ingreso_sep' => $this->ingreso_sep, 'ingreso_ct' => $this->ingreso_ct]
            );

            $dest->update([
                'ingreso_seg' => $this->ingreso_seg,
                'ingreso_sep' => $this->ingreso_sep,
                'ingreso_ct'  => $this->ingreso_ct,
            ]);

            // mover detalles
            PersonaNivelDetalle::where('persona_nivel_id', $cab->id)
                ->update(['persona_nivel_id' => $dest->id]);

            // si vieja queda vacía, borrar
            $tieneMas = PersonaNivelDetalle::where('persona_nivel_id', $cab->id)->exists();
            if (! $tieneMas) {
                $cab->delete();
            }
        });

        $this->dispatch('swal', [
            'title' => 'Personal/Nivel actualizado!',
            'icon' => 'success',
            'position' => 'top-end',
        ]);

        $this->dispatch('refreshPersonaNivelList');
        $this->dispatch('cerrar-modal-editar-cabecera');

        $this->cerrarModal();
    }

    public function cerrarModal(): void
    {
        $this->reset([
            'cabeceraId',
            'persona_id',
            'nivel_id',
            'ingreso_seg',
            'ingreso_sep',
            'ingreso_ct',
            'nombrePersona',
            'nombreNivel',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $personas = Persona::query()
            ->select('id','titulo','nombre','apellido_paterno','apellido_materno')
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre')
            ->get();

        $niveles = Nivel::query()
            ->select('id','nombre','slug')
            ->orderBy('id')
            ->get();

        return view('livewire.persona-nivel.editar-persona-nivel-cabecera', compact('personas','niveles'));
    }
}
