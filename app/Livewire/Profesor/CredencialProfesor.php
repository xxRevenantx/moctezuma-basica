<?php

namespace App\Livewire\Profesor;

use App\Models\Nivel;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CredencialProfesor extends Component
{
    public string $modo_descarga = 'seleccionados';

    public ?int $nivel_id = null;

    public ?int $persona_individual_id = null;

    public array $personas_seleccionadas = [];

    public string $buscar_persona = '';

    public string $vigencia = '';

    public string $cargo = 'PROFESOR';

    public function mount(): void
    {
        $this->vigencia = 'Agosto ' . now()->year;
    }

    public function updatedNivelId(): void
    {
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
        $this->buscar_persona = '';
    }

    public function updatedModoDescarga(): void
    {
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
        $this->buscar_persona = '';
    }

    public function updatedBuscarPersona(): void
    {
        $this->persona_individual_id = null;
    }

    #[Computed]
    public function niveles(): Collection
    {
        return Nivel::query()
            ->select('id', 'nombre', 'slug', 'cct', 'logo', 'color', 'director_id')
            ->orderBy('id')
            ->get();
    }

    #[Computed]
    public function nivelSeleccionado(): ?Nivel
    {
        if (!$this->nivel_id) {
            return null;
        }

        return Nivel::query()
            ->with('director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status')
            ->select('id', 'nombre', 'slug', 'cct', 'logo', 'color', 'director_id')
            ->find($this->nivel_id);
    }

    #[Computed]
    public function personas(): Collection
    {
        if (!$this->nivel_id) {
            return collect();
        }

        return $this->consultaPersonalDelNivel(trim($this->buscar_persona))
            ->limit(500)
            ->get();
    }

    private function consultaProfesores(string $busqueda = '')
    {
        return Persona::query()
            ->select('personas.*')
            ->with([
                'personaRoles.rolePersona:id,nombre,slug,status',
                'personaNiveles' => function ($consulta) {
                    $consulta->where('nivel_id', $this->nivel_id);
                },
                'personaNiveles.nivel:id,nombre,slug,cct,logo,color,director_id',
            ])
            ->where('personas.status', 1)
            ->whereHas('personaNiveles', function ($consulta) {
                $consulta->where('nivel_id', $this->nivel_id);
            })
            ->whereHas('personaRoles.rolePersona', function ($consulta) {
                $consulta->where(function ($rol) {
                    $rol->where('slug', 'like', '%docente%')
                        ->orWhere('slug', 'like', '%maestro%')
                        ->orWhere('slug', 'like', '%maestroa%')
                        ->orWhere('slug', 'like', '%profesor%')
                        ->orWhere('slug', 'like', '%tutor%')
                        ->orWhere('slug', 'director_con_grupo')
                        ->orWhere('nombre', 'like', '%Docente%')
                        ->orWhere('nombre', 'like', '%Maestro%')
                        ->orWhere('nombre', 'like', '%Maestra%')
                        ->orWhere('nombre', 'like', '%Profesor%')
                        ->orWhere('nombre', 'like', '%Tutora%')
                        ->orWhere('nombre', 'like', '%Tutor%');
                });
            })
            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                $consulta->where(function ($q) use ($busqueda) {
                    $q->where('personas.nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.apellido_paterno', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.apellido_materno', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.curp', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.rfc', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.correo', 'like', '%' . $busqueda . '%')
                        ->orWhereRaw(
                            "CONCAT_WS(' ', personas.apellido_paterno, personas.apellido_materno, personas.nombre) LIKE ?",
                            ['%' . $busqueda . '%']
                        )
                        ->orWhereRaw(
                            "CONCAT_WS(' ', personas.nombre, personas.apellido_paterno, personas.apellido_materno) LIKE ?",
                            ['%' . $busqueda . '%']
                        );
                });
            })
            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre');
    }

    #[Computed]
    public function personasSeleccionadasLista(): Collection
    {
        $ids = collect($this->personas_seleccionadas)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty() || !$this->nivel_id) {
            return collect();
        }

        $personas = $this->consultaPersonalDelNivel()
            ->whereIn('personas.id', $ids->all())
            ->get();

        return $personas
            ->sortBy(fn($persona) => $ids->search((int) $persona->id))
            ->values();
    }

    private function consultaPersonalDelNivel(string $busqueda = ''): Builder
    {
        return Persona::query()
            ->select('personas.*')
            ->with([
                'personaRoles.rolePersona:id,nombre,slug,status',
                'personaNiveles' => function ($consulta) {
                    $consulta->where('nivel_id', $this->nivel_id)
                        ->with([
                            'nivel:id,nombre,slug,cct,logo,color,director_id',
                            'detalles.personaRole.rolePersona:id,nombre,slug,status',
                        ]);
                },
            ])
            ->where('personas.status', 1)

            /*
         * Se trae todo el personal asignado al nivel seleccionado.
         * No se filtra por rol, porque en plantilla el conteo es por persona asignada al nivel,
         * no solamente por docentes.
         */
            ->whereHas('personaNiveles', function ($consulta) {
                $consulta->where('nivel_id', $this->nivel_id);
            })

            ->when($busqueda !== '', function ($consulta) use ($busqueda) {
                $consulta->where(function ($q) use ($busqueda) {
                    $q->where('personas.nombre', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.apellido_paterno', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.apellido_materno', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.curp', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.rfc', 'like', '%' . $busqueda . '%')
                        ->orWhere('personas.correo', 'like', '%' . $busqueda . '%')
                        ->orWhereRaw(
                            "CONCAT_WS(' ', personas.apellido_paterno, personas.apellido_materno, personas.nombre) LIKE ?",
                            ['%' . $busqueda . '%']
                        )
                        ->orWhereRaw(
                            "CONCAT_WS(' ', personas.nombre, personas.apellido_paterno, personas.apellido_materno) LIKE ?",
                            ['%' . $busqueda . '%']
                        );
                });
            })

            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre');
    }

    public function seleccionarTodosVisibles(): void
    {
        $idsVisibles = $this->personas
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $seleccionados = collect($this->personas_seleccionadas)
            ->map(fn($id) => (int) $id)
            ->values()
            ->toArray();

        $faltantes = array_values(array_diff($idsVisibles, $seleccionados));

        if (count($faltantes) === 0) {
            $this->personas_seleccionadas = array_values(array_diff($seleccionados, $idsVisibles));

            return;
        }

        $this->personas_seleccionadas = array_values(array_unique(array_merge($seleccionados, $idsVisibles)));
    }

    public function quitarPersonaSeleccionada(int $personaId): void
    {
        $this->personas_seleccionadas = collect($this->personas_seleccionadas)
            ->map(fn($id) => (int) $id)
            ->reject(fn($id) => $id === $personaId)
            ->values()
            ->toArray();
    }

    public function limpiarSeleccion(): void
    {
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
    }

    public function limpiarFiltros(): void
    {
        $this->modo_descarga = 'seleccionados';
        $this->nivel_id = null;
        $this->persona_individual_id = null;
        $this->personas_seleccionadas = [];
        $this->buscar_persona = '';
        $this->cargo = 'PROFESOR';
        $this->vigencia = 'Ciclo escolar ' . now()->year . ' - ' . now()->addYear()->year;
    }

    public function modosDescarga(): array
    {
        return [
            'todos' => 'Todo el personal docente del nivel',
            'individual' => 'Individual',
            'seleccionados' => 'Seleccionados',
        ];
    }

    #[Computed]
    public function puedeDescargar(): bool
    {
        if (!$this->nivel_id) {
            return false;
        }

        if ($this->modo_descarga === 'todos') {
            return true;
        }

        if ($this->modo_descarga === 'individual') {
            return filled($this->persona_individual_id);
        }

        if ($this->modo_descarga === 'seleccionados') {
            return count($this->personas_seleccionadas) > 0;
        }

        return false;
    }

    #[Computed]
    public function parametrosDescarga(): array
    {
        return [
            'nivel_id' => $this->nivel_id,
            'modo_descarga' => $this->modo_descarga,
            'persona_id' => $this->persona_individual_id,
            'personas' => implode(',', $this->personas_seleccionadas),
            'buscar' => trim($this->buscar_persona),
            'vigencia' => trim($this->vigencia),
            'cargo' => trim($this->cargo),
        ];
    }

    #[Computed]
    public function urlDescarga(): ?string
    {
        if (!$this->puedeDescargar) {
            return null;
        }

        return route('credenciales.profesores.pdf', $this->parametrosDescarga);
    }

    #[Computed]
    public function textoModoDescarga(): string
    {
        return $this->modosDescarga()[$this->modo_descarga] ?? 'Credenciales';
    }

    public function nombrePersona($persona): string
    {
        return trim(
            ($persona->titulo ? $persona->titulo . ' ' : '') .
                ($persona->nombre ?? '') . ' ' .
                ($persona->apellido_paterno ?? '') . ' ' .
                ($persona->apellido_materno ?? '')
        );
    }

    public function rolPrincipal($persona): string
    {
        $personaNivel = $persona->personaNiveles
            ->firstWhere('nivel_id', (int) $this->nivel_id);

        $rolDelNivel = $personaNivel?->detalles
            ?->map(fn($detalle) => $detalle->personaRole?->rolePersona?->nombre)
            ->filter()
            ->first();

        return $rolDelNivel ?? 'Personal asignado';
    }

    public function render()
    {
        return view('livewire.profesor.credencial-profesor');
    }
}
