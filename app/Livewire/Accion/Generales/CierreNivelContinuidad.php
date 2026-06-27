<?php

namespace App\Livewire\Accion\Generales;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\TrayectoriaAcademica;
use App\Services\CierreNivelReingresoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CierreNivelContinuidad extends Component
{
    public ?string $slug_nivel = null;
    public ?int $nivel_id = null;
    public ?int $ciclo_escolar_origen_id = null;
    public ?int $ciclo_id_origen = null;
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

    public string $accion_global = 'continua_institucion';
    public array $acciones = [];
    public array $seleccionados = [];
    public bool $seleccionar_todos = false;
    public string $search = '';
    public string $fecha = '';
    public string $motivo = '';
    public string $observaciones = '';
    public string $usuario_acceso = '';
    public bool $confirmar = false;
    public ?int $generacion_sugerida_cierre_id = null;

    public Collection $niveles;
    public Collection $ciclosEscolares;
    public Collection $ciclos;

    public function mount(?string $slug_nivel = null): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->nivel_id = $nivel->id;
        $this->nivel_destino_id = $nivel->id;
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclos = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);

        $actual = $this->ciclosEscolares->firstWhere('es_actual', true) ?: $this->ciclosEscolares->first();
        $this->ciclo_escolar_origen_id = $actual?->id;
        $this->ciclo_escolar_destino_id = $actual?->id;
        $this->ciclo_id_origen = $this->ciclos->last()?->id;
        $this->ciclo_id_destino = $this->ciclos->first()?->id;
        $this->fecha = now()->toDateString();

        $ultimoGrado = Grado::query()->where('nivel_id', $nivel->id)->orderByDesc('orden')->orderByDesc('id')->first();
        $this->grado_origen_id = $ultimoGrado?->id;
    }

    public function updated($property): void
    {
        if (str_contains($property, '_origen_') || in_array($property, ['search', 'grado_origen_id', 'grupo_origen_id'], true)) {
            $this->limpiarSeleccion();
        }
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
        $this->grupo_destino_id = null;
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->grupo_destino_id = null;
    }

    public function updatedSeleccionarTodos(bool $valor): void
    {
        $this->seleccionados = $valor
            ? $this->alumnos->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
        foreach ($this->seleccionados as $id) {
            $this->acciones[$id] ??= $this->accion_global;
        }
    }

    public function aplicarAccionGlobal(): void
    {
        foreach ($this->seleccionados as $id) {
            $this->acciones[$id] = $this->accion_global;
        }
    }

    public function getEsBachilleratoOrigenProperty(): bool
    {
        return $this->esBachillerato($this->nivel_id);
    }

    public function getEsBachilleratoDestinoProperty(): bool
    {
        return $this->esBachillerato($this->nivel_destino_id);
    }

    public function getGradosOrigenProperty(): Collection
    {
        return $this->grados($this->nivel_id);
    }

    public function getGradosDestinoProperty(): Collection
    {
        return $this->grados($this->nivel_destino_id);
    }

    public function getGeneracionesOrigenProperty(): Collection
    {
        return $this->generaciones($this->nivel_id, true);
    }

    public function getGeneracionesDestinoProperty(): Collection
    {
        return $this->generaciones($this->nivel_destino_id, false);
    }

    public function getSemestresOrigenProperty(): Collection
    {
        return $this->semestres($this->nivel_id, $this->grado_origen_id);
    }

    public function getSemestresDestinoProperty(): Collection
    {
        return $this->semestres($this->nivel_destino_id, $this->grado_destino_id);
    }

    public function getGruposOrigenProperty(): Collection
    {
        return $this->grupos($this->nivel_id, $this->grado_origen_id, $this->generacion_origen_id, $this->semestre_origen_id);
    }

    public function getGruposDestinoProperty(): Collection
    {
        return $this->grupos($this->nivel_destino_id, $this->grado_destino_id, $this->generacion_destino_id, $this->semestre_destino_id);
    }

    public function getAlumnosProperty(): Collection
    {
        if (!$this->filtrosOrigenCompletos()) {
            return collect();
        }

        return TrayectoriaAcademica::query()
            ->with([
                'inscripcion.matriculasAlumno', 'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso,status', 'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
            ])
            ->where('ciclo_escolar_id', $this->ciclo_escolar_origen_id)
            ->where('ciclo_id', $this->ciclo_id_origen)
            ->where('nivel_id', $this->nivel_id)
            ->where('grado_id', $this->grado_origen_id)
            ->where('generacion_id', $this->generacion_origen_id)
            ->where('grupo_id', $this->grupo_origen_id)
            ->when($this->es_bachillerato_origen, fn (Builder $q) => $q->where('semestre_id', $this->semestre_origen_id))
            ->where('activo', true)
            ->where('vigente_en_corte', true)
            ->whereNotIn('estatus', ['egresado', 'traslado', 'baja_definitiva', 'archivado'])
            ->when(trim($this->search) !== '', function (Builder $q) {
                $like = '%' . trim($this->search) . '%';
                $q->whereHas('inscripcion', function (Builder $alumno) use ($like) {
                    $alumno->where('matricula', 'like', $like)
                        ->orWhere('curp', 'like', $like)
                        ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$like]);
                });
            })
            ->join('inscripciones', 'inscripciones.id', '=', 'trayectorias_academicas.inscripcion_id')
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->select('trayectorias_academicas.*')
            ->get();
    }

    public function procesar(): void
    {
        $this->validate([
            'fecha' => ['required', 'date'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:trayectorias_academicas,id'],
        ]);

        if (!$this->confirmar) {
            $this->addError('confirmar', 'Confirma que revisaste las decisiones.');
            return;
        }

        $trayectorias = $this->alumnos->whereIn('id', collect($this->seleccionados)->map(fn ($id) => (int) $id));
        $service = app(CierreNivelReingresoService::class);
        $procesados = 0;
        $omitidos = 0;
        $errores = [];

        foreach ($trayectorias as $trayectoria) {
            $accion = $this->acciones[(string) $trayectoria->id] ?? $this->accion_global;
            try {
                $datos = [
                    'fecha' => $this->fecha,
                    'motivo' => $this->motivo,
                    'observaciones' => $this->observaciones,
                    'usuario_acceso_activo' => $this->usuario_acceso === '' ? null : $this->usuario_acceso === '1',
                ];

                if (in_array($accion, ['continua_institucion', 'repite'], true)) {
                    $datos = array_merge($datos, [
                        'ciclo_escolar_id' => $this->ciclo_escolar_destino_id,
                        'ciclo_id' => $this->ciclo_id_destino,
                        'nivel_id' => $accion === 'repite' ? $trayectoria->nivel_id : $this->nivel_destino_id,
                        'grado_id' => $accion === 'repite' ? $trayectoria->grado_id : $this->grado_destino_id,
                        'generacion_id' => $accion === 'repite' ? $trayectoria->generacion_id : $this->generacion_destino_id,
                        'semestre_id' => $accion === 'repite' ? $trayectoria->semestre_id : $this->semestre_destino_id,
                        'grupo_id' => $accion === 'repite' ? $trayectoria->grupo_id : $this->grupo_destino_id,
                    ]);
                }

                $service->procesarCierre($trayectoria, $accion, $datos, auth()->id());
                $procesados++;
            } catch (ValidationException $e) {
                $omitidos++;
                $errores[] = collect($e->errors())->flatten()->first();
            } catch (\Throwable $e) {
                report($e);
                $omitidos++;
                $errores[] = $e->getMessage();
            }
        }

        if ($this->generacion_origen_id) {
            $generacion = Generacion::query()->find($this->generacion_origen_id);
            $this->generacion_sugerida_cierre_id = $generacion && $service->generacionPuedeCerrar($generacion)
                ? $generacion->id : null;
        }

        $this->limpiarSeleccion();
        $this->confirmar = false;
        $this->dispatch('swal', [
            'title' => "Cierre terminado: {$procesados} procesado(s), {$omitidos} omitido(s).",
            'text' => collect($errores)->filter()->unique()->take(3)->implode(' '),
            'icon' => $procesados ? 'success' : 'info',
            'position' => 'top-end',
        ]);
    }

    public function cerrarGeneracionSugerida(): void
    {
        abort_unless($this->generacion_sugerida_cierre_id, 422);
        try {
            app(CierreNivelReingresoService::class)->cerrarGeneracion(
                Generacion::query()->findOrFail($this->generacion_sugerida_cierre_id),
                auth()->id()
            );
            $this->generacion_sugerida_cierre_id = null;
            $this->dispatch('swal', ['title' => 'Generación cerrada.', 'icon' => 'success', 'position' => 'top-end']);
        } catch (ValidationException $e) {
            $this->addError('generacion', collect($e->errors())->flatten()->first());
        }
    }

    public function nombreAlumno($alumno): string
    {
        return trim(($alumno?->apellido_paterno ?? '') . ' ' . ($alumno?->apellido_materno ?? '') . ' ' . ($alumno?->nombre ?? ''));
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre ?? $grupo?->grupo ?? $grupo?->nombre ?? '—';
    }

    private function limpiarSeleccion(): void
    {
        $this->seleccionados = [];
        $this->seleccionar_todos = false;
        $this->acciones = [];
    }

    private function filtrosOrigenCompletos(): bool
    {
        return filled($this->ciclo_escolar_origen_id) && filled($this->ciclo_id_origen)
            && filled($this->nivel_id) && filled($this->grado_origen_id)
            && filled($this->generacion_origen_id) && filled($this->grupo_origen_id)
            && (!$this->es_bachillerato_origen || filled($this->semestre_origen_id));
    }

    private function esBachillerato(?int $nivelId): bool
    {
        $nivel = $this->niveles->firstWhere('id', $nivelId);
        return str_contains(mb_strtolower(($nivel?->slug ?? '') . ' ' . ($nivel?->nombre ?? '')), 'bachillerato');
    }

    private function grados(?int $nivelId): Collection
    {
        return $nivelId ? Grado::query()->where('nivel_id', $nivelId)->orderBy('orden')->get(['id', 'nivel_id', 'nombre', 'orden']) : collect();
    }

    private function generaciones(?int $nivelId, bool $incluirCerradas): Collection
    {
        return $nivelId ? Generacion::query()->where('nivel_id', $nivelId)
            ->when(!$incluirCerradas, fn (Builder $q) => $q->where('status', true))
            ->orderByDesc('anio_ingreso')->get(['id', 'nivel_id', 'anio_ingreso', 'anio_egreso', 'status']) : collect();
    }

    private function semestres(?int $nivelId, ?int $gradoId): Collection
    {
        return $this->esBachillerato($nivelId) && $gradoId
            ? Semestre::query()->where('grado_id', $gradoId)->orderBy('numero')->get(['id', 'grado_id', 'numero'])
            : collect();
    }

    private function grupos(?int $nivelId, ?int $gradoId, ?int $generacionId, ?int $semestreId): Collection
    {
        if (!$nivelId || !$gradoId || !$generacionId) return collect();
        return Grupo::query()->with('asignacionGrupo:id,nombre')
            ->where('nivel_id', $nivelId)->where('grado_id', $gradoId)->where('generacion_id', $generacionId)
            ->when($this->esBachillerato($nivelId), fn (Builder $q) => $q->where('semestre_id', $semestreId))
            ->when(!$this->esBachillerato($nivelId), fn (Builder $q) => $q->whereNull('semestre_id'))
            ->orderBy('asignacion_grupo_id')->get();
    }

    public function render()
    {
        return view('livewire.accion.generales.cierre-nivel-continuidad');
    }
}
