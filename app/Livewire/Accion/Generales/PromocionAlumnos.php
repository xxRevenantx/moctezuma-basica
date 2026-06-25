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
use App\Services\TrayectoriaAcademicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class PromocionAlumnos extends Component
{
    public ?string $slug_nivel = null;
    public ?Nivel $nivel = null;
    public Collection $niveles;
    public Collection $cicloEscolares;
    public Collection $ciclos;

    public ?int $ciclo_escolar_origen_id = null;
    public ?int $ciclo_id_origen = null;
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

    public string $resultado_promocion = 'promovido';
    public ?string $fecha_promocion = null;
    public string $search = '';
    public bool $seleccionarTodos = false;
    public bool $ocultarPromovidos = true;
    public bool $confirmarPromocion = false;
    public array $seleccionados = [];

    public function mount(?string $slug_nivel = null): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->first();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);
        $this->ciclos = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);

        $actual = $this->cicloEscolares->firstWhere('es_actual', true) ?: $this->cicloEscolares->first();
        $anterior = $this->cicloEscolares->first(fn($item) => $actual && $item->id !== $actual->id) ?: $actual;

        $this->ciclo_escolar_origen_id = $anterior?->id;
        $this->ciclo_escolar_destino_id = $actual?->id;
        $this->ciclo_id_origen = $this->ciclos->last()?->id;
        $this->ciclo_id_destino = $this->ciclos->first()?->id;
        $this->nivel_origen_id = $this->nivel?->id;
        $this->nivel_destino_id = $this->nivel?->id;
        $this->fecha_promocion = now()->toDateString();
    }

    public function updated($property): void
    {
        if (str_starts_with($property, 'ciclo_escolar_origen') || $property === 'ciclo_id_origen') {
            $this->limpiarSeleccion();
        }
    }

    public function updatedNivelOrigenId(): void
    {
        $this->grado_origen_id = $this->generacion_origen_id = $this->semestre_origen_id = $this->grupo_origen_id = null;
        $this->limpiarSeleccion();
    }

    public function updatedGradoOrigenId(): void
    {
        $this->semestre_origen_id = $this->grupo_origen_id = null;
        $this->limpiarSeleccion();
    }

    public function updatedGeneracionOrigenId(): void
    {
        $this->semestre_origen_id = $this->grupo_origen_id = null;
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

    public function updatedNivelDestinoId(): void
    {
        $this->grado_destino_id = $this->generacion_destino_id = $this->semestre_destino_id = $this->grupo_destino_id = null;
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = $this->grupo_destino_id = null;
    }

    public function updatedGeneracionDestinoId(): void
    {
        $this->semestre_destino_id = $this->grupo_destino_id = null;
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
        $this->seleccionados = $valor
            ? $this->alumnosDisponibles->pluck('id')->map(fn($id) => (string) $id)->values()->all()
            : [];
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
        return $this->consultarSemestres($this->nivel_origen_id, $this->grado_origen_id);
    }

    public function getSemestresDestinoProperty(): Collection
    {
        return $this->consultarSemestres($this->nivel_destino_id, $this->grado_destino_id);
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
                'inscripcion' => fn($q) => $q->with('matriculasAlumno'),
                'nivel:id,nombre,slug',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'ciclo:id,ciclo',
            ])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_origen_id)
            ->where('ciclo_id', $this->ciclo_id_origen)
            ->where('nivel_id', $this->nivel_origen_id)
            ->where('grado_id', $this->grado_origen_id)
            ->where('generacion_id', $this->generacion_origen_id)
            ->where('grupo_id', $this->grupo_origen_id)
            ->where('vigente_en_corte', true)
            ->where('activo', true)
            ->whereNotIn('estatus', ['baja_temporal', 'baja_definitiva', 'traslado', 'egresado'])
            ->when($this->es_bachillerato_origen, fn(Builder $q) => $q->where('semestre_id', $this->semestre_origen_id))
            ->when(!$this->es_bachillerato_origen, fn(Builder $q) => $q->whereNull('semestre_id'))
            ->when($this->ocultarPromovidos, fn(Builder $q) => $q->whereNull('fecha_promocion'))
            ->whereHas('inscripcion', function (Builder $q) {
                $q->whereNull('deleted_at');
                $termino = preg_replace('/\s+/', ' ', trim($this->search));
                if ($termino !== '') {
                    $like = "%{$termino}%";
                    $q->where(function (Builder $buscar) use ($like) {
                        $buscar->where('matricula', 'like', $like)
                            ->orWhere('folio', 'like', $like)
                            ->orWhere('curp', 'like', $like)
                            ->orWhere('nombre', 'like', $like)
                            ->orWhere('apellido_paterno', 'like', $like)
                            ->orWhere('apellido_materno', 'like', $like)
                            ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$like])
                            ->orWhereHas('matriculasAlumno', fn(Builder $m) => $m->where('matricula', 'like', $like));
                    });
                }
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
            'hombres' => $alumnos->filter(fn($t) => $t->inscripcion?->genero === 'H')->count(),
            'mujeres' => $alumnos->filter(fn($t) => $t->inscripcion?->genero === 'M')->count(),
            'procesados' => $alumnos->filter(fn($t) => filled($t->fecha_promocion))->count(),
        ];
    }

    public function aplicarPromocion(): void
    {
        $this->validate($this->reglas(), [], $this->atributos());

        if (!$this->confirmarPromocion) {
            $this->addError('confirmarPromocion', 'Confirma la operación antes de continuar.');
            return;
        }

        $grupoDestino = Grupo::query()
            ->whereKey($this->grupo_destino_id)
            ->where('nivel_id', $this->nivel_destino_id)
            ->where('grado_id', $this->grado_destino_id)
            ->where('generacion_id', $this->generacion_destino_id)
            ->when($this->es_bachillerato_destino, fn(Builder $q) => $q->where('semestre_id', $this->semestre_destino_id))
            ->when(!$this->es_bachillerato_destino, fn(Builder $q) => $q->whereNull('semestre_id'))
            ->exists();

        if (!$grupoDestino) {
            $this->addError('grupo_destino_id', 'El grupo no corresponde al nivel, generación, grado y semestre de destino.');
            return;
        }

        $trayectorias = $this->alumnosDisponibles
            ->whereIn('id', collect($this->seleccionados)->map(fn($id) => (int) $id));

        if ($trayectorias->isEmpty()) {
            $this->addError('seleccionados', 'Selecciona al menos un alumno disponible.');
            return;
        }

        $service = app(TrayectoriaAcademicaService::class);
        $creados = 0;
        $omitidos = 0;
        $errores = [];

        foreach ($trayectorias as $origen) {
            try {
                $service->promover(
                    $origen,
                    [
                        'ciclo_escolar_id' => (int) $this->ciclo_escolar_destino_id,
                        'ciclo_id' => (int) $this->ciclo_id_destino,
                        'nivel_id' => (int) $this->nivel_destino_id,
                        'grado_id' => (int) $this->grado_destino_id,
                        'generacion_id' => (int) $this->generacion_destino_id,
                        'grupo_id' => (int) $this->grupo_destino_id,
                        'semestre_id' => $this->es_bachillerato_destino ? (int) $this->semestre_destino_id : null,
                    ],
                    $this->resultado_promocion,
                    $this->fecha_promocion,
                    auth()->id()
                );
                $creados++;
            } catch (ValidationException $exception) {
                $omitidos++;
                $errores[] = collect($exception->errors())->flatten()->first();
            }
        }

        $this->limpiarSeleccion();
        $this->confirmarPromocion = false;

        $this->dispatch('swal', [
            'title' => "Proceso terminado: {$creados} creado(s), {$omitidos} omitido(s).",
            'text' => collect($errores)->filter()->unique()->take(3)->implode(' '),
            'icon' => $creados > 0 ? 'success' : 'info',
            'position' => 'top-end',
        ]);
    }

    public function limpiarFormulario(): void
    {
        $this->grado_origen_id = $this->generacion_origen_id = $this->semestre_origen_id = $this->grupo_origen_id = null;
        $this->grado_destino_id = $this->generacion_destino_id = $this->semestre_destino_id = $this->grupo_destino_id = null;
        $this->search = '';
        $this->resultado_promocion = 'promovido';
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
        return $grupo?->asignacionGrupo?->nombre ?? $grupo?->grupo ?? $grupo?->nombre ?? '—';
    }

    public function nombreAlumno(?Inscripcion $alumno): string
    {
        return trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? '')) ?: 'Alumno sin nombre';
    }

    private function filtrosOrigenCompletos(): bool
    {
        return filled($this->ciclo_escolar_origen_id)
            && filled($this->ciclo_id_origen)
            && filled($this->nivel_origen_id)
            && filled($this->grado_origen_id)
            && filled($this->generacion_origen_id)
            && filled($this->grupo_origen_id)
            && (!$this->es_bachillerato_origen || filled($this->semestre_origen_id));
    }

    private function esBachillerato(?int $nivelId): bool
    {
        $nivel = $this->niveles->firstWhere('id', $nivelId);
        return str_contains(mb_strtolower(($nivel?->slug ?? '') . ' ' . ($nivel?->nombre ?? '')), 'bachillerato');
    }

    private function consultarGrados(?int $nivelId): Collection
    {
        return $nivelId
            ? Grado::query()->where('nivel_id', $nivelId)->orderBy('orden')->orderBy('nombre')->get(['id', 'nivel_id', 'nombre', 'orden'])
            : collect();
    }

    private function consultarGeneraciones(?int $nivelId): Collection
    {
        return $nivelId
            ? Generacion::query()->where('nivel_id', $nivelId)->orderByDesc('anio_ingreso')->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso'])
            : collect();
    }

    private function consultarSemestres(?int $nivelId, ?int $gradoId): Collection
    {
        if (!$this->esBachillerato($nivelId) || !$gradoId) {
            return collect();
        }

        return Semestre::query()->where('grado_id', $gradoId)->orderBy('numero')->get(['id', 'grado_id', 'numero']);
    }

    private function consultarGrupos(?int $nivelId, ?int $gradoId, ?int $generacionId, ?int $semestreId, bool $bachillerato): Collection
    {
        if (!$nivelId || !$gradoId || !$generacionId || ($bachillerato && !$semestreId)) {
            return collect();
        }

        return Grupo::query()
            ->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $nivelId)
            ->where('grado_id', $gradoId)
            ->where('generacion_id', $generacionId)
            ->when($bachillerato, fn(Builder $q) => $q->where('semestre_id', $semestreId))
            ->when(!$bachillerato, fn(Builder $q) => $q->whereNull('semestre_id'))
            ->orderBy('asignacion_grupo_id')
            ->orderBy('id')
            ->get();
    }

    private function reglas(): array
    {
        return [
            'ciclo_escolar_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id_origen' => ['required', 'exists:ciclos,id'],
            'nivel_origen_id' => ['required', 'exists:niveles,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'generacion_origen_id' => ['required', 'exists:generaciones,id'],
            'semestre_origen_id' => [$this->es_bachillerato_origen ? 'required' : 'nullable', 'exists:semestres,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'ciclo_escolar_destino_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id_destino' => ['required', 'exists:ciclos,id'],
            'nivel_destino_id' => ['required', 'exists:niveles,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'generacion_destino_id' => ['required', 'exists:generaciones,id'],
            'semestre_destino_id' => [$this->es_bachillerato_destino ? 'required' : 'nullable', 'exists:semestres,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'resultado_promocion' => ['required', 'in:promovido,no_promovido'],
            'fecha_promocion' => ['required', 'date'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:trayectorias_academicas,id'],
        ];
    }

    private function atributos(): array
    {
        return [
            'ciclo_escolar_origen_id' => 'ciclo escolar de origen',
            'ciclo_id_origen' => 'corte de origen',
            'grupo_origen_id' => 'grupo de origen',
            'ciclo_escolar_destino_id' => 'ciclo escolar de destino',
            'ciclo_id_destino' => 'corte de destino',
            'grupo_destino_id' => 'grupo de destino',
            'seleccionados' => 'alumnos seleccionados',
        ];
    }

    public function render()
    {
        return view('livewire.accion.generales.promocion-alumnos');
    }
}
