<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\TrayectoriaAcademica;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class PromocionAlumnos extends Component
{
    public ?string $slug_nivel = null;

    public ?Nivel $nivel = null;

    public Collection $niveles;
    public Collection $cicloEscolares;
    public Collection $ciclos;

    public ?int $ciclo_escolar_origen_id = null;
    public ?int $nivel_origen_id = null;
    public ?int $grado_origen_id = null;
    public ?int $generacion_origen_id = null;
    public ?int $semestre_origen_id = null;
    public ?int $grupo_origen_id = null;

    public ?int $ciclo_escolar_destino_id = null;
    public ?int $ciclo_id_destino = null;
    public ?int $nivel_destino_id = null;
    public ?int $grado_destino_id = null;
    public ?int $generacion_destino_id = null;
    public ?int $semestre_destino_id = null;
    public ?int $grupo_destino_id = null;

    public string $search = '';
    public bool $seleccionarTodos = false;
    public bool $ocultarPromovidos = true;
    public bool $confirmarPromocion = false;

    public array $seleccionados = [];

    public function mount(?string $slug_nivel = null): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->nivel = Nivel::query()
            ->where('slug', $slug_nivel)
            ->first();

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug']);

        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('id')
            ->get(['id', 'inicio_anio', 'fin_anio']);

        $this->ciclos = Ciclo::query()
            ->orderBy('id')
            ->get(['id', 'ciclo']);

        $cicloActual = $this->cicloEscolares->first();
        $cicloDestino = $this->cicloEscolares->skip(1)->first() ?: $cicloActual;

        $this->ciclo_escolar_origen_id = $cicloDestino?->id;
        $this->ciclo_escolar_destino_id = $cicloActual?->id;

        $this->nivel_origen_id = $this->nivel?->id;
        $this->nivel_destino_id = $this->nivel?->id;

        $this->ciclo_id_destino = $this->obtenerCicloInicioId();
    }

    public function updatedCicloEscolarOrigenId(): void
    {
        $this->limpiarSeleccion();
    }

    public function updatedNivelOrigenId(): void
    {
        $this->grado_origen_id = null;
        $this->generacion_origen_id = null;
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->limpiarSeleccion();
    }

    public function updatedGradoOrigenId(): void
    {
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->limpiarSeleccion();
    }

    public function updatedGeneracionOrigenId(): void
    {
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->limpiarSeleccion();
    }

    public function updatedSemestreOrigenId(): void
    {
        $this->grupo_origen_id = null;
        $this->limpiarSeleccion();
    }

    public function updatedGrupoOrigenId(): void
    {
        $this->limpiarSeleccion();
    }

    public function updatedCicloEscolarDestinoId(): void
    {
        $this->limpiarSeleccion();
    }

    public function updatedNivelDestinoId(): void
    {
        $this->grado_destino_id = null;
        $this->generacion_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
    }

    public function updatedGeneracionDestinoId(): void
    {
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->grupo_destino_id = null;
    }

    public function updatedSearch(): void
    {
        $this->limpiarSeleccion();
    }

    public function updatedOcultarPromovidos(): void
    {
        $this->limpiarSeleccion();
    }

    public function updatedSeleccionarTodos(bool $valor): void
    {
        if (!$valor) {
            $this->seleccionados = [];
            return;
        }

        $this->seleccionados = $this->alumnosDisponibles
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->values()
            ->all();
    }

    public function getEsBachilleratoOrigenProperty(): bool
    {
        return $this->esBachillerato($this->nivel_origen_id);
    }

    public function getEsBachilleratoDestinoProperty(): bool
    {
        return $this->esBachillerato($this->nivel_destino_id);
    }

    public function getGradosOrigenProperty(): Collection
    {
        return $this->consultarGrados($this->nivel_origen_id);
    }

    public function getGradosDestinoProperty(): Collection
    {
        return $this->consultarGrados($this->nivel_destino_id);
    }

    public function getGeneracionesOrigenProperty(): Collection
    {
        return $this->consultarGeneraciones($this->nivel_origen_id);
    }

    public function getGeneracionesDestinoProperty(): Collection
    {
        return $this->consultarGeneraciones($this->nivel_destino_id);
    }

    public function getSemestresOrigenProperty(): Collection
    {
        return $this->consultarSemestres(
            $this->nivel_origen_id,
            $this->grado_origen_id,
            $this->generacion_origen_id
        );
    }

    public function getSemestresDestinoProperty(): Collection
    {
        return $this->consultarSemestres(
            $this->nivel_destino_id,
            $this->grado_destino_id,
            $this->generacion_destino_id
        );
    }

    public function getGruposOrigenProperty(): Collection
    {
        return $this->consultarGrupos(
            $this->nivel_origen_id,
            $this->grado_origen_id,
            $this->generacion_origen_id,
            $this->semestre_origen_id,
            $this->es_bachillerato_origen
        );
    }

    public function getGruposDestinoProperty(): Collection
    {
        return $this->consultarGrupos(
            $this->nivel_destino_id,
            $this->grado_destino_id,
            $this->generacion_destino_id,
            $this->semestre_destino_id,
            $this->es_bachillerato_destino
        );
    }

    public function getAlumnosDisponiblesProperty(): Collection
    {
        if (!$this->filtrosOrigenCompletos()) {
            return collect();
        }

        return TrayectoriaAcademica::query()
            ->with([
                'inscripcion:id,matricula,folio,curp,nombre,apellido_paterno,apellido_materno,genero,foto_path,activo',
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo:id,nombre,grupo',
                'semestre:id,numero',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'ciclo:id,ciclo',
            ])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_origen_id)
            ->where('nivel_id', $this->nivel_origen_id)
            ->where('grado_id', $this->grado_origen_id)
            ->where('generacion_id', $this->generacion_origen_id)
            ->where('grupo_id', $this->grupo_origen_id)
            ->when($this->es_bachillerato_origen, function (Builder $query) {
                $query->where('semestre_id', $this->semestre_origen_id);
            })
            ->when(!$this->es_bachillerato_origen, function (Builder $query) {
                $query->whereNull('semestre_id');
            })
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->when($this->ocultarPromovidos, function (Builder $query) {
                $query->where(function (Builder $subconsulta) {
                    $subconsulta->whereNull('promovido')
                        ->orWhere('promovido', false);
                });
            })
            ->whereHas('inscripcion', function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->when($this->search !== '', function (Builder $subconsulta) {
                        $busqueda = '%' . trim($this->search) . '%';

                        $subconsulta->where(function (Builder $buscador) use ($busqueda) {
                            $buscador->where('matricula', 'like', $busqueda)
                                ->orWhere('folio', 'like', $busqueda)
                                ->orWhere('curp', 'like', $busqueda)
                                ->orWhere('nombre', 'like', $busqueda)
                                ->orWhere('apellido_paterno', 'like', $busqueda)
                                ->orWhere('apellido_materno', 'like', $busqueda)
                                ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$busqueda])
                                ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$busqueda]);
                        });
                    });
            })
            ->join('inscripciones', 'inscripciones.id', '=', 'trayectorias_academicas.inscripcion_id')
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->select('trayectorias_academicas.*')
            ->get();
    }

    public function getTotalSeleccionadosProperty(): int
    {
        return count($this->seleccionados);
    }

    public function getResumenOrigenProperty(): array
    {
        $alumnos = $this->alumnosDisponibles;

        return [
            'total' => $alumnos->count(),
            'hombres' => $alumnos->filter(fn($trayectoria) => $trayectoria->inscripcion?->genero === 'H')->count(),
            'mujeres' => $alumnos->filter(fn($trayectoria) => $trayectoria->inscripcion?->genero === 'M')->count(),
            'promovidos' => $alumnos->filter(fn($trayectoria) => (bool) $trayectoria->promovido)->count(),
        ];
    }

    public function aplicarPromocion(): void
    {
        $this->validate($this->reglas(), [], $this->atributos());

        if (!$this->confirmarPromocion) {
            $this->dispatch('swal', [
                'title' => 'Confirma la promoción antes de continuar.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        if ((int) $this->ciclo_escolar_origen_id === (int) $this->ciclo_escolar_destino_id) {
            $this->dispatch('swal', [
                'title' => 'El ciclo escolar destino debe ser diferente al origen.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $idsSeleccionados = collect($this->seleccionados)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($idsSeleccionados->isEmpty()) {
            $this->dispatch('swal', [
                'title' => 'Selecciona al menos un alumno.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $trayectoriasOrigen = TrayectoriaAcademica::query()
            ->with('inscripcion')
            ->whereIn('id', $idsSeleccionados)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_origen_id)
            ->where('nivel_id', $this->nivel_origen_id)
            ->where('grado_id', $this->grado_origen_id)
            ->where('generacion_id', $this->generacion_origen_id)
            ->where('grupo_id', $this->grupo_origen_id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->get();

        if ($trayectoriasOrigen->isEmpty()) {
            $this->dispatch('swal', [
                'title' => 'No se encontraron alumnos válidos para promover.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return;
        }

        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($trayectoriasOrigen, &$creados, &$omitidos) {
            foreach ($trayectoriasOrigen as $trayectoriaOrigen) {
                $yaExisteDestino = TrayectoriaAcademica::query()
                    ->where('inscripcion_id', $trayectoriaOrigen->inscripcion_id)
                    ->where('ciclo_escolar_id', $this->ciclo_escolar_destino_id)
                    ->exists();

                if ($yaExisteDestino) {
                    $omitidos++;
                    continue;
                }

                $trayectoriaDestino = TrayectoriaAcademica::create([
                    'inscripcion_id' => $trayectoriaOrigen->inscripcion_id,
                    'ciclo_escolar_id' => $this->ciclo_escolar_destino_id,
                    'ciclo_id' => $this->ciclo_id_destino,
                    'nivel_id' => $this->nivel_destino_id,
                    'grado_id' => $this->grado_destino_id,
                    'generacion_id' => $this->generacion_destino_id,
                    'grupo_id' => $this->grupo_destino_id,
                    'semestre_id' => $this->es_bachillerato_destino ? $this->semestre_destino_id : null,
                    'activo' => true,
                    'promovido' => null,
                    'fecha_promocion' => null,
                    'trayectoria_origen_id' => $trayectoriaOrigen->id,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'observaciones_baja' => null,
                    'fecha_inscripcion' => now(),
                ]);

                $trayectoriaOrigen->update([
                    'promovido' => true,
                    'fecha_promocion' => now(),
                ]);

                Inscripcion::query()
                    ->where('id', $trayectoriaOrigen->inscripcion_id)
                    ->update([
                        'nivel_id' => $trayectoriaDestino->nivel_id,
                        'grado_id' => $trayectoriaDestino->grado_id,
                        'generacion_id' => $trayectoriaDestino->generacion_id,
                        'grupo_id' => $trayectoriaDestino->grupo_id,
                        'semestre_id' => $trayectoriaDestino->semestre_id,
                        'ciclo_id' => $trayectoriaDestino->ciclo_id,
                        'activo' => true,
                        'fecha_baja' => null,
                        'motivo_baja' => null,
                        'observaciones_baja' => null,
                    ]);

                $creados++;
            }
        });

        $this->limpiarSeleccion();
        $this->confirmarPromocion = false;

        $this->dispatch('swal', [
            'title' => "Promoción aplicada. Creados: {$creados}. Omitidos: {$omitidos}.",
            'icon' => $creados > 0 ? 'success' : 'info',
            'position' => 'top-end',
        ]);
    }

    public function limpiarFormulario(): void
    {
        $this->grado_origen_id = null;
        $this->generacion_origen_id = null;
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;

        $this->grado_destino_id = null;
        $this->generacion_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;

        $this->search = '';
        $this->confirmarPromocion = false;
        $this->limpiarSeleccion();
    }

    public function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
        $this->seleccionarTodos = false;
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->grupo
            ?? $grupo->nombre
            ?? ('Grupo ' . $grupo->id);
    }

    public function nombreAlumno(?Inscripcion $alumno): string
    {
        if (!$alumno) {
            return 'Alumno sin datos';
        }

        return trim(
            ($alumno->apellido_paterno ?? '') . ' ' .
                ($alumno->apellido_materno ?? '') . ' ' .
                ($alumno->nombre ?? '')
        ) ?: 'Alumno sin nombre';
    }

    private function filtrosOrigenCompletos(): bool
    {
        return filled($this->ciclo_escolar_origen_id)
            && filled($this->nivel_origen_id)
            && filled($this->grado_origen_id)
            && filled($this->generacion_origen_id)
            && filled($this->grupo_origen_id)
            && (!$this->es_bachillerato_origen || filled($this->semestre_origen_id));
    }

    private function esBachillerato(?int $nivelId): bool
    {
        if (!$nivelId) {
            return false;
        }

        $nivel = $this->niveles->firstWhere('id', $nivelId) ?: Nivel::find($nivelId);

        if (!$nivel) {
            return false;
        }

        return str_contains(strtolower($nivel->slug ?? ''), 'bachillerato')
            || str_contains(strtolower($nivel->nombre ?? ''), 'bachillerato');
    }

    private function consultarGrados(?int $nivelId): Collection
    {
        if (!$nivelId) {
            return collect();
        }

        return Grado::query()
            ->where('nivel_id', $nivelId)
            ->when(Schema::hasColumn('grados', 'orden'), fn($query) => $query->orderBy('orden'))
            ->orderBy('id')
            ->get(['id', 'nivel_id', 'nombre']);
    }

    private function consultarGeneraciones(?int $nivelId): Collection
    {
        if (!$nivelId) {
            return collect();
        }

        return Generacion::query()
            ->when(Schema::hasColumn('generaciones', 'nivel_id'), fn($query) => $query->where('nivel_id', $nivelId))
            ->orderByDesc('anio_ingreso')
            ->orderByDesc('id')
            ->get(['id', 'anio_ingreso', 'anio_egreso']);
    }

    private function consultarSemestres(?int $nivelId, ?int $gradoId, ?int $generacionId): Collection
    {
        if (!$nivelId || !$gradoId) {
            return collect();
        }

        if (!$this->esBachillerato($nivelId)) {
            return collect();
        }

        $semestresDesdeGrupos = Grupo::query()
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->when($generacionId, fn($query) => $query->where('generacion_id', $generacionId))
            ->whereNotNull('semestre_id')
            ->pluck('semestre_id')
            ->unique()
            ->values();

        return Semestre::query()
            ->when($semestresDesdeGrupos->isNotEmpty(), fn($query) => $query->whereIn('id', $semestresDesdeGrupos))
            ->when($semestresDesdeGrupos->isEmpty() && Schema::hasColumn('semestres', 'grado_id'), fn($query) => $query->where('grado_id', $gradoId))
            ->orderBy('numero')
            ->orderBy('id')
            ->get(['id', 'numero']);
    }

    private function consultarGrupos(?int $nivelId, ?int $gradoId, ?int $generacionId, ?int $semestreId, bool $esBachillerato): Collection
    {
        if (!$nivelId || !$gradoId || !$generacionId) {
            return collect();
        }

        if ($esBachillerato && !$semestreId) {
            return collect();
        }

        return Grupo::query()
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->where('generacion_id', $generacionId)
            ->when($esBachillerato, fn($query) => $query->where('semestre_id', $semestreId))
            ->when(!$esBachillerato && Schema::hasColumn('grupos', 'semestre_id'), fn($query) => $query->whereNull('semestre_id'))
            ->when(Schema::hasColumn('grupos', 'orden'), fn($query) => $query->orderBy('orden'))
            ->orderBy('id')
            ->get();
    }

    private function obtenerCicloInicioId(): ?int
    {
        return Ciclo::query()
            ->where('ciclo', 'like', '%inicio%')
            ->value('id')
            ?: Ciclo::query()->orderBy('id')->value('id');
    }

    private function reglas(): array
    {
        return [
            'ciclo_escolar_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'nivel_origen_id' => ['required', 'exists:niveles,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'generacion_origen_id' => ['required', 'exists:generaciones,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'semestre_origen_id' => [$this->es_bachillerato_origen ? 'required' : 'nullable', 'nullable', 'exists:semestres,id'],

            'ciclo_escolar_destino_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id_destino' => ['required', 'exists:ciclos,id'],
            'nivel_destino_id' => ['required', 'exists:niveles,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'generacion_destino_id' => ['required', 'exists:generaciones,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'semestre_destino_id' => [$this->es_bachillerato_destino ? 'required' : 'nullable', 'nullable', 'exists:semestres,id'],

            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:trayectorias_academicas,id'],
        ];
    }

    private function atributos(): array
    {
        return [
            'ciclo_escolar_origen_id' => 'ciclo escolar origen',
            'nivel_origen_id' => 'nivel origen',
            'grado_origen_id' => 'grado origen',
            'generacion_origen_id' => 'generación origen',
            'grupo_origen_id' => 'grupo origen',
            'semestre_origen_id' => 'semestre origen',
            'ciclo_escolar_destino_id' => 'ciclo escolar destino',
            'ciclo_id_destino' => 'periodo de inscripción destino',
            'nivel_destino_id' => 'nivel destino',
            'grado_destino_id' => 'grado destino',
            'generacion_destino_id' => 'generación destino',
            'grupo_destino_id' => 'grupo destino',
            'semestre_destino_id' => 'semestre destino',
            'seleccionados' => 'alumnos seleccionados',
        ];
    }

    public function render()
    {
        return view('livewire.accion.generales.promocion-alumnos');
    }
}
