<?php

namespace App\Livewire\Accion\Generales;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use App\Services\GestionAcademicaService;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class PromocionAlumnos extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $ciclosEscolares;
    public Collection $generaciones;
    public Collection $grados;
    public Collection $semestresOrigen;
    public Collection $gruposOrigen;
    public Collection $semestresDestino;
    public Collection $gruposDestino;

    public ?int $ciclo_origen_id = null;
    public ?int $ciclo_destino_id = null;
    public ?int $generacion_id = null;
    public ?int $grado_origen_id = null;
    public ?int $semestre_origen_id = null;
    public ?int $grupo_origen_id = null;
    public ?int $grado_destino_id = null;
    public ?int $semestre_destino_id = null;
    public ?int $grupo_destino_id = null;
    public string $motivo = '';
    public array $seleccionados = [];
    public bool $seleccionarPagina = false;
    public bool $mostrarVistaPrevia = false;
    public array $vistaPrevia = [];
    public string $hashVistaPrevia = '';
    public string $confirmacion = '';
    public string $fecha_efectiva = '';
    public string $tipoResultado = 'promovido';
    public bool $autorizarPromocionConPendientes = false;
    public int $pendientesCalificacionesTotal = 0;
    public array $resultadoPromocion = [];

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->ciclo_origen_id = $this->ciclosEscolares->firstWhere('es_actual', true)?->id
            ?? $this->ciclosEscolares->first()?->id;
        $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_origen_id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->grados = Grado::query()->where('nivel_id', $this->nivel->id)->orderBy('orden')->get();
        $this->semestresOrigen = collect();
        $this->gruposOrigen = collect();
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
        $this->fecha_efectiva = now()->toDateString();
    }

    private function cargarGeneraciones(): Collection
    {
        return Generacion::query()
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_origen_id, fn (Builder $query) => $query->whereHas(
                'grupos',
                fn (Builder $grupos) => $grupos
                    ->where('ciclo_escolar_id', $this->ciclo_origen_id)
                    ->where('estado', 'activo')
            ))
            ->orderByDesc('status')
            ->orderByDesc('anio_ingreso')
            ->get();
    }

    private function resolverSiguienteCicloId(?int $cicloId): ?int
    {
        $origen = $this->ciclosEscolares->firstWhere('id', $cicloId);

        if (! $origen) {
            return null;
        }

        return $this->ciclosEscolares
            ->first(fn ($ciclo) => (int) $ciclo->inicio_anio === (int) $origen->inicio_anio + 1)?->id;
    }

    public function esBachillerato(): bool
    {
        return str_contains(mb_strtolower(($this->nivel?->slug ?? '') . ' ' . ($this->nivel?->nombre ?? '')), 'bachillerato');
    }

    public function updatedCicloOrigenId(): void
    {
        $this->ciclo_origen_id = $this->ciclo_origen_id ? (int) $this->ciclo_origen_id : null;
        $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_origen_id;
        $this->generaciones = $this->cargarGeneraciones();
        $this->limpiarContexto();
    }

    public function updatedCicloDestinoId(): void
    {
        $this->grupo_destino_id = null;
        $this->prepararDestinoAutomatico();
    }

    public function updatedGeneracionId(): void
    {
        $this->limpiarContexto(false);
    }

    public function updatedGradoOrigenId(): void
    {
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->semestresOrigen = $this->grado_origen_id
            ? Semestre::query()->where('grado_id', $this->grado_origen_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->gruposOrigen = $this->esBachillerato() ? collect() : $this->cargarGrupos($this->grado_origen_id, null, $this->ciclo_origen_id);
        $this->prepararDestinoAutomatico();
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function updatedSemestreOrigenId(): void
    {
        $this->grupo_origen_id = null;
        $this->gruposOrigen = $this->cargarGrupos($this->grado_origen_id, $this->semestre_origen_id, $this->ciclo_origen_id);
        $this->prepararDestinoAutomatico();
        $this->seleccionados = [];
        $this->resetPage();
    }

    public function updatedGrupoOrigenId(): void
    {
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->resetPage();
    }

    public function updatedGradoDestinoId(): void
    {
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresDestino = $this->grado_destino_id
            ? Semestre::query()->where('grado_id', $this->grado_destino_id)->orderBy('orden_global')->orderBy('numero')->get()
            : collect();
        $this->gruposDestino = $this->esBachillerato() ? collect() : $this->cargarGrupos($this->grado_destino_id, null, $this->ciclo_destino_id);
    }

    public function updatedSemestreDestinoId(): void
    {
        $this->grupo_destino_id = null;
        $this->gruposDestino = $this->cargarGrupos($this->grado_destino_id, $this->semestre_destino_id, $this->ciclo_destino_id);
    }

    public function updatedSeleccionarPagina(bool $valor): void
    {
        $this->seleccionados = $valor
            ? $this->alumnosQuery()->limit(25)->pluck('id')->map(fn ($id) => (string) $id)->all()
            : [];
    }

    public function promoverSeleccionados(): void
    {
        $this->prepararPromocion();
    }

    public function prepararPromocion(): void
    {
        $this->tipoResultado = 'promovido';
        $this->validarPromocion();
        $this->vistaPrevia = $this->construirVistaPrevia();
        $this->pendientesCalificacionesTotal = (int) collect($this->vistaPrevia)->sum('pendientes_calificaciones');
        $this->autorizarPromocionConPendientes = false;
        $this->hashVistaPrevia = $this->calcularHashVistaPrevia($this->vistaPrevia);
        $this->confirmacion = '';
        $this->mostrarVistaPrevia = true;
    }

    public function confirmarPromocion(GestionAcademicaService $service): void
    {
        $this->validarPromocion();
        $this->validate([
            'fecha_efectiva' => ['required', 'date'],
            'confirmacion' => ['required', 'in:'.($this->tipoResultado === 'no_promovido' ? 'NO PROMOVER' : 'PROMOVER')],
        ]);

        $vistaActual = $this->construirVistaPrevia();
        if (! hash_equals($this->hashVistaPrevia, $this->calcularHashVistaPrevia($vistaActual))) {
            $this->addError('confirmacion', 'La información cambió después de generar la vista previa. Revísala nuevamente.');
            $this->vistaPrevia = $vistaActual;
            $this->pendientesCalificacionesTotal = (int) collect($vistaActual)->sum('pendientes_calificaciones');
            $this->hashVistaPrevia = $this->calcularHashVistaPrevia($vistaActual);
            return;
        }

        $pendientesActuales = (int) collect($vistaActual)->sum('pendientes_calificaciones');
        if ($pendientesActuales > 0 && ! $this->autorizarPromocionConPendientes) {
            $this->addError(
                'autorizarPromocionConPendientes',
                'Hay calificaciones pendientes. Revisa la advertencia y autoriza expresamente continuar con la promoción.'
            );
            return;
        }

        $alumnos = $this->alumnosQuery()
            ->whereIn('id', array_map('intval', $this->seleccionados))
            ->lockForUpdate()
            ->get();

        foreach ($alumnos as $alumno) {
            $destino = [
                'ciclo_escolar_id' => $this->ciclo_destino_id,
                'nivel_id' => $this->nivel->id,
                'generacion_id' => $this->generacion_id,
                'grado_id' => $this->grado_destino_id,
                'semestre_id' => $this->esBachillerato() ? $this->semestre_destino_id : null,
                'grupo_id' => $this->grupo_destino_id,
                'matricula' => $alumno->matricula,
            ];

            if ($this->tipoResultado === 'no_promovido') {
                $service->continuarNoPromovido($alumno, $destino, trim($this->motivo), auth()->id(), $this->fecha_efectiva);
            } else {
                $service->promoverAlumno($alumno, $destino, trim($this->motivo), auth()->id(), $this->fecha_efectiva);
            }
        }

        $total = $alumnos->count();
        $primerPeriodoPendienteId = collect($vistaActual)
            ->pluck('primer_periodo_pendiente_id')
            ->filter()
            ->first();

        $parametrosRevision = [
            'origen' => 'busqueda-global',
            'ciclo_escolar_id' => $this->ciclo_origen_id,
            'generacion' => $this->generacion_id,
            'grado' => $this->grado_origen_id,
            'grupo' => $this->grupo_origen_id,
            'semestre' => $this->esBachillerato() ? $this->semestre_origen_id : null,
            'periodo' => $primerPeriodoPendienteId,
        ];

        $this->resultadoPromocion = [
            'total' => $total,
            'pendientes' => $pendientesActuales,
            'url_calificaciones' => route('submodulos.accion', [
                'slug_nivel' => $this->slug_nivel,
                'accion' => 'calificaciones',
            ]).'?'.http_build_query(array_filter($parametrosRevision, fn ($valor) => filled($valor))),
        ];

        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->autorizarPromocionConPendientes = false;
        $this->motivo = '';
        $this->confirmacion = '';
        $this->vistaPrevia = [];
        $this->hashVistaPrevia = '';
        $this->mostrarVistaPrevia = false;
        $this->resetPage();
        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $this->tipoResultado === 'no_promovido' ? 'Continuidad aplicada' : 'Promoción aplicada',
            'text' => $this->tipoResultado === 'no_promovido'
                ? "{$total} alumno(s) continuarán en el mismo grado o semestre. El ciclo anterior quedó conservado como evidencia."
                : "Se promovieron {$total} alumno(s). El ciclo anterior quedó conservado como evidencia y se creó el registro del ciclo destino.".($pendientesActuales > 0 ? " Permanecen {$pendientesActuales} calificaciones pendientes que pueden completarse desde el historial." : ''),
            'position' => 'top-end',
        ]);
    }

    public function cancelarVistaPrevia(): void
    {
        $this->mostrarVistaPrevia = false;
        $this->vistaPrevia = [];
        $this->hashVistaPrevia = '';
        $this->confirmacion = '';
        $this->autorizarPromocionConPendientes = false;
        $this->pendientesCalificacionesTotal = 0;
    }

    private function validarPromocion(): void
    {
        $reglas = [
            'ciclo_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_destino_id' => ['required', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'grado_destino_id' => ['required', 'exists:grados,id'],
            'grupo_destino_id' => ['required', 'exists:grupos,id'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:inscripciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ];
        if ($this->esBachillerato()) {
            $reglas['semestre_origen_id'] = ['required', 'exists:semestres,id'];
            $reglas['semestre_destino_id'] = ['required', 'exists:semestres,id'];
        }
        $this->validate($reglas);
        $this->validarDestinoSeleccionado();
    }

    private function validarDestinoSeleccionado(): void
    {
        $grupoOrigen = Grupo::query()->find($this->grupo_origen_id);
        $grupoDestino = Grupo::query()->find($this->grupo_destino_id);

        if (! $grupoOrigen || ! $grupoDestino) {
            throw ValidationException::withMessages([
                'grupo_destino_id' => 'No fue posible validar los grupos de origen y destino.',
            ]);
        }

        if ((int) $grupoDestino->nivel_id !== (int) $this->nivel->id
            || (int) $grupoDestino->generacion_id !== (int) $this->generacion_id
            || (int) $grupoDestino->ciclo_escolar_id !== (int) $this->ciclo_destino_id
            || (int) $grupoDestino->grado_id !== (int) $this->grado_destino_id
            || (int) ($grupoDestino->semestre_id ?? 0) !== (int) ($this->semestre_destino_id ?? 0)) {
            throw ValidationException::withMessages([
                'grupo_destino_id' => 'El grupo destino no corresponde exactamente al ciclo, nivel, generación, grado o semestre seleccionados.',
            ]);
        }

        if ($this->tipoResultado === 'no_promovido') {
            if ((int) $this->ciclo_destino_id === (int) $this->ciclo_origen_id) {
                throw ValidationException::withMessages([
                    'ciclo_destino_id' => 'La repetición debe registrarse en un ciclo escolar posterior.',
                ]);
            }

            if ((int) $this->grado_destino_id !== (int) $this->grado_origen_id
                || (int) ($this->semestre_destino_id ?? 0) !== (int) ($this->semestre_origen_id ?? 0)) {
                throw ValidationException::withMessages([
                    'grado_destino_id' => 'Un alumno no promovido debe continuar en el mismo grado o semestre durante el ciclo siguiente.',
                ]);
            }

            return;
        }

        if ($this->esBachillerato()) {
            $origen = Semestre::query()->find($this->semestre_origen_id);
            $siguiente = $origen
                ? Semestre::query()
                    ->whereHas('grado', fn (Builder $query) => $query->where('nivel_id', $this->nivel->id))
                    ->where('orden_global', '>', (int) $origen->orden_global)
                    ->orderBy('orden_global')
                    ->first()
                : null;

            if (! $siguiente) {
                throw ValidationException::withMessages([
                    'semestre_origen_id' => 'Este es el último semestre del nivel. Procesa a los alumnos desde “Cierre de nivel y continuidad”.',
                ]);
            }

            $cicloEsperado = (int) $siguiente->numero % 2 === 0
                ? (int) $this->ciclo_origen_id
                : (int) ($this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? 0);

            if ((int) $this->semestre_destino_id !== (int) $siguiente->id
                || (int) $this->grado_destino_id !== (int) $siguiente->grado_id
                || (int) $this->ciclo_destino_id !== $cicloEsperado) {
                throw ValidationException::withMessages([
                    'semestre_destino_id' => 'La promoción ordinaria únicamente puede enviarse al semestre inmediato siguiente dentro del mismo nivel.',
                ]);
            }

            return;
        }

        $origen = $this->grados->firstWhere('id', $this->grado_origen_id);
        $siguiente = $this->grados
            ->filter(fn ($grado) => (int) $grado->orden > (int) ($origen?->orden ?? 0))
            ->sortBy('orden')
            ->first();

        if (! $siguiente) {
            throw ValidationException::withMessages([
                'grado_origen_id' => 'Este es el último grado del nivel. Procesa a los alumnos desde “Cierre de nivel y continuidad”.',
            ]);
        }

        $cicloEsperado = (int) ($this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? 0);

        if ((int) $this->grado_destino_id !== (int) $siguiente->id
            || (int) $this->ciclo_destino_id !== $cicloEsperado) {
            throw ValidationException::withMessages([
                'grado_destino_id' => 'La promoción ordinaria únicamente puede enviarse al grado inmediato siguiente y al ciclo escolar posterior.',
            ]);
        }
    }

    private function construirVistaPrevia(): array
    {
        $grupoDestino = Grupo::query()->with(['asignacionGrupo', 'grado', 'semestre', 'generacion', 'cicloEscolar'])->findOrFail($this->grupo_destino_id);
        $alumnos = $this->alumnosQuery()
            ->whereIn('id', array_map('intval', $this->seleccionados))
            ->get();
        $pendientes = $this->resumenCalificacionesPendientes($alumnos);

        return $alumnos
            ->map(fn (Inscripcion $alumno): array => [
                'id' => $alumno->id,
                'alumno' => trim($alumno->apellido_paterno . ' ' . $alumno->apellido_materno . ' ' . $alumno->nombre),
                'matricula' => $alumno->matricula,
                'pendientes_calificaciones' => (int) ($pendientes['por_alumno'][$alumno->id] ?? 0),
                'primer_periodo_pendiente_id' => $pendientes['primer_periodo_id'] ?? null,
                'origen' => [
                    'ciclo_id' => (int) $alumno->ciclo_escolar_id,
                    'grado_id' => (int) $alumno->grado_id,
                    'semestre_id' => (int) ($alumno->semestre_id ?? 0),
                    'grupo_id' => (int) $alumno->grupo_id,
                    'texto' => trim(($alumno->grado?->nombre ?? '—') . ($alumno->semestre ? ' · Sem. ' . $alumno->semestre->numero : '') . ' · ' . ($alumno->grupo?->asignacionGrupo?->nombre ?? '—')),
                ],
                'destino' => [
                    'ciclo_id' => (int) $this->ciclo_destino_id,
                    'grado_id' => (int) $grupoDestino->grado_id,
                    'semestre_id' => (int) ($grupoDestino->semestre_id ?? 0),
                    'grupo_id' => (int) $grupoDestino->id,
                    'texto' => trim(($grupoDestino->grado?->nombre ?? '—') . ($grupoDestino->semestre ? ' · Sem. ' . $grupoDestino->semestre->numero : '') . ' · ' . ($grupoDestino->asignacionGrupo?->nombre ?? '—')),
                ],
            ])
            ->values()
            ->all();
    }

    private function resumenCalificacionesPendientes(Collection $alumnos): array
    {
        if (! $this->esBachillerato() || $alumnos->isEmpty()) {
            return ['por_alumno' => [], 'primer_periodo_id' => null];
        }

        $asignacionIds = AsignacionMateria::query()
            ->where('ciclo_escolar_id', $this->ciclo_origen_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $this->grado_origen_id)
            ->where('semestre_id', $this->semestre_origen_id)
            ->where('grupo_id', $this->grupo_origen_id)
            ->whereHas('materia', function (Builder $query): void {
                ReglasMateriaBachillerato::aplicarPromediables($query, '');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $periodoIds = Periodos::query()
            ->where('ciclo_escolar_id', $this->ciclo_origen_id)
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('semestre_id', $this->semestre_origen_id)
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($asignacionIds->isEmpty() || $periodoIds->isEmpty()) {
            return [
                'por_alumno' => $alumnos->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => 0])->all(),
                'primer_periodo_id' => null,
            ];
        }

        $inscripcionIds = $alumnos->pluck('id')->map(fn ($id) => (int) $id)->values();
        $capturadas = Calificacion::query()
            ->whereIn('inscripcion_id', $inscripcionIds)
            ->whereIn('periodo_id', $periodoIds)
            ->whereIn('asignacion_materia_id', $asignacionIds)
            ->whereNotNull('calificacion')
            ->where('calificacion', '<>', '')
            ->get(['inscripcion_id', 'periodo_id', 'asignacion_materia_id'])
            ->mapWithKeys(fn (Calificacion $fila): array => [
                $fila->inscripcion_id.'-'.$fila->periodo_id.'-'.$fila->asignacion_materia_id => true,
            ]);

        $porAlumno = [];
        $primerPeriodoPendienteId = null;

        foreach ($inscripcionIds as $inscripcionId) {
            $pendientesAlumno = 0;

            foreach ($periodoIds as $periodoId) {
                foreach ($asignacionIds as $asignacionId) {
                    $clave = $inscripcionId.'-'.$periodoId.'-'.$asignacionId;

                    if ($capturadas->has($clave)) {
                        continue;
                    }

                    $pendientesAlumno++;
                    $primerPeriodoPendienteId ??= (int) $periodoId;
                }
            }

            $porAlumno[(int) $inscripcionId] = $pendientesAlumno;
        }

        return [
            'por_alumno' => $porAlumno,
            'primer_periodo_id' => $primerPeriodoPendienteId,
        ];
    }

    private function calcularHashVistaPrevia(array $vista): string
    {
        return hash('sha256', json_encode([
            'ciclo_origen_id' => $this->ciclo_origen_id,
            'ciclo_destino_id' => $this->ciclo_destino_id,
            'generacion_id' => $this->generacion_id,
            'grupo_destino_id' => $this->grupo_destino_id,
            'fecha_efectiva' => $this->fecha_efectiva,
            'tipo_resultado' => $this->tipoResultado,
            'filas' => $vista,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function marcarNoPromovidos(): void
    {
        $this->validate([
            'ciclo_origen_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_destino_id' => ['required', 'different:ciclo_origen_id', 'exists:ciclo_escolares,id'],
            'generacion_id' => ['required', 'exists:generaciones,id'],
            'grado_origen_id' => ['required', 'exists:grados,id'],
            'grupo_origen_id' => ['required', 'exists:grupos,id'],
            'seleccionados' => ['required', 'array', 'min:1'],
            'seleccionados.*' => ['integer', 'exists:inscripciones,id'],
            'motivo' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->tipoResultado = 'no_promovido';
        $this->grado_destino_id = $this->grado_origen_id;
        $this->semestre_destino_id = $this->esBachillerato() ? $this->semestre_origen_id : null;
        $this->gruposDestino = $this->cargarGrupos($this->grado_destino_id, $this->semestre_destino_id, $this->ciclo_destino_id);

        $grupoOrigen = Grupo::query()->find($this->grupo_origen_id);
        $coincidente = $this->gruposDestino->first(fn (Grupo $grupo) => (int) $grupo->asignacion_grupo_id === (int) $grupoOrigen?->asignacion_grupo_id);
        $this->grupo_destino_id = $coincidente?->id ?? $this->gruposDestino->first()?->id;

        if (! $this->grupo_destino_id) {
            $this->addError('grupo_destino_id', 'No existe un grupo del mismo grado o semestre en el ciclo destino. Créalo antes de continuar.');
            return;
        }

        $this->validarPromocion();
        $this->vistaPrevia = $this->construirVistaPrevia();
        $this->pendientesCalificacionesTotal = (int) collect($this->vistaPrevia)->sum('pendientes_calificaciones');
        $this->autorizarPromocionConPendientes = false;
        $this->hashVistaPrevia = $this->calcularHashVistaPrevia($this->vistaPrevia);
        $this->confirmacion = '';
        $this->mostrarVistaPrevia = true;
    }

    private function prepararDestinoAutomatico(): void
    {
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();

        if ($this->esBachillerato() && $this->semestre_origen_id) {
            $origen = Semestre::query()->find($this->semestre_origen_id);
            $siguiente = Semestre::query()
                ->whereHas('grado', fn (Builder $q) => $q->where('nivel_id', $this->nivel->id))
                ->where('orden_global', '>', (int) ($origen?->orden_global ?? 0))
                ->orderBy('orden_global')
                ->first();
            if ($siguiente) {
                $this->ciclo_destino_id = (int) $siguiente->numero % 2 === 0
                    ? $this->ciclo_origen_id
                    : ($this->resolverSiguienteCicloId($this->ciclo_origen_id) ?? $this->ciclo_destino_id);
                $this->grado_destino_id = $siguiente->grado_id;
                $this->semestre_destino_id = $siguiente->id;
                $this->semestresDestino = Semestre::query()->where('grado_id', $siguiente->grado_id)->orderBy('orden_global')->get();
                $this->gruposDestino = $this->cargarGrupos($siguiente->grado_id, $siguiente->id, $this->ciclo_destino_id);
            }
            return;
        }

        if ($this->grado_origen_id) {
            $origen = $this->grados->firstWhere('id', $this->grado_origen_id);
            $siguiente = $this->grados->first(fn ($g) => (int) $g->orden > (int) ($origen?->orden ?? 0));
            if ($siguiente) {
                $this->ciclo_destino_id = $this->resolverSiguienteCicloId($this->ciclo_origen_id)
                    ?? $this->ciclo_destino_id;
                $this->grado_destino_id = $siguiente->id;
                $this->gruposDestino = $this->cargarGrupos($siguiente->id, null, $this->ciclo_destino_id);
            }
        }
    }

    private function cargarGrupos(?int $gradoId, ?int $semestreId, ?int $cicloEscolarId): Collection
    {
        if (! $this->generacion_id || ! $gradoId) {
            return collect();
        }
        return Grupo::query()
            ->with('asignacionGrupo')
            ->withCount(['inscripciones as alumnos_activos_count' => fn (Builder $alumnos) => $alumnos
                ->visiblesEnListas()])
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('estado', 'activo')
            ->where('nivel_id', $this->nivel->id)
            ->where('generacion_id', $this->generacion_id)
            ->where('grado_id', $gradoId)
            ->when($this->esBachillerato(), fn (Builder $q) => $q->where('semestre_id', $semestreId), fn (Builder $q) => $q->whereNull('semestre_id'))
            ->get()->sortBy(fn ($g) => $g->asignacionGrupo?->nombre ?? $g->id)->values();
    }

    private function alumnosQuery(): Builder
    {
        return Inscripcion::query()
            ->visiblesEnListas()
            ->with(['grado', 'semestre', 'grupo.asignacionGrupo'])
            ->where('nivel_id', $this->nivel->id)
            ->when($this->ciclo_origen_id, fn (Builder $q) => $q->where('ciclo_escolar_id', $this->ciclo_origen_id))
            ->when($this->generacion_id, fn (Builder $q) => $q->where('generacion_id', $this->generacion_id))
            ->when($this->grado_origen_id, fn (Builder $q) => $q->where('grado_id', $this->grado_origen_id))
            ->when($this->esBachillerato() && $this->semestre_origen_id, fn (Builder $q) => $q->where('semestre_id', $this->semestre_origen_id))
            ->when($this->grupo_origen_id, fn (Builder $q) => $q->where('grupo_id', $this->grupo_origen_id))
            ->orderBy('apellido_paterno')->orderBy('apellido_materno')->orderBy('nombre');
    }

    private function limpiarContexto(bool $limpiarGeneracion = true): void
    {
        if ($limpiarGeneracion) $this->generacion_id = null;
        $this->grado_origen_id = null;
        $this->semestre_origen_id = null;
        $this->grupo_origen_id = null;
        $this->grado_destino_id = null;
        $this->semestre_destino_id = null;
        $this->grupo_destino_id = null;
        $this->semestresOrigen = collect();
        $this->gruposOrigen = collect();
        $this->semestresDestino = collect();
        $this->gruposDestino = collect();
        $this->seleccionados = [];
        $this->seleccionarPagina = false;
        $this->autorizarPromocionConPendientes = false;
        $this->pendientesCalificacionesTotal = 0;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.accion.generales.promocion-alumnos', [
            'alumnos' => $this->alumnosQuery()->paginate(25),
        ]);
    }
}
