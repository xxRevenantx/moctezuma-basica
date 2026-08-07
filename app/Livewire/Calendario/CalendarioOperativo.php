<?php

namespace App\Livewire\Calendario;

use App\Models\CalendarioEvento;
use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\User;
use App\Services\CalendarioOperativoService;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CalendarioOperativo extends Component
{
    public Collection $ciclos;
    public Collection $niveles;
    public Collection $grados;
    public Collection $grupos;
    public Collection $usuarios;

    public string $mesActual = '';
    public string $vista = 'mes';
    public string $buscar = '';
    public string $filtroTipo = 'todos';
    public string $filtroEstado = 'todos';
    public string $filtroNivelId = '';
    public string $filtroCicloId = '';
    public bool $mostrarSistema = true;

    public bool $modalFormulario = false;
    public bool $modalDetalle = false;
    public ?int $eventoEditandoId = null;
    /** @var array<string, mixed>|null */
    public ?array $detalleEvento = null;

    public string $titulo = '';
    public string $descripcion = '';
    public string $tipo = 'academico';
    public string $estado = 'programado';
    public string $prioridad = 'normal';
    public string $audiencia = 'todos';
    public string $inicia_at = '';
    public string $termina_at = '';
    public bool $todo_el_dia = true;
    public string $ubicacion = '';
    public string $enlace = '';
    public string $recurrencia = 'ninguna';
    public string $recurrencia_hasta = '';
    public string $recordatorio_dias = '0';
    public string $ciclo_escolar_id = '';
    public string $nivel_id = '';
    public string $grado_id = '';
    public string $grupo_id = '';
    public string $responsable_id = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAccess('calendario.consultar'), 403);

        $this->mesActual = now()->format('Y-m');
        $this->ciclos = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual']);
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug', 'color']);
        $this->grados = Grado::query()->orderBy('nivel_id')->orderBy('orden')->get(['id', 'nivel_id', 'nombre']);
        $this->grupos = Grupo::query()
            ->whereNull('deleted_at')
            ->orderByDesc('ciclo_escolar_id')
            ->orderBy('clave')
            ->get(['id', 'clave', 'nivel_id', 'grado_id', 'ciclo_escolar_id']);
        $this->usuarios = User::query()->where('activo', true)->orderBy('name')->get(['id', 'name']);
        $this->filtroCicloId = (string) ($this->ciclos->firstWhere('es_actual', true)?->id ?? '');
    }

    public function mesAnterior(): void
    {
        $this->mesActual = $this->mes()->subMonthNoOverflow()->format('Y-m');
    }

    public function mesSiguiente(): void
    {
        $this->mesActual = $this->mes()->addMonthNoOverflow()->format('Y-m');
    }

    public function irHoy(): void
    {
        $this->mesActual = now()->format('Y-m');
    }

    public function limpiarFiltros(): void
    {
        $this->buscar = '';
        $this->filtroTipo = 'todos';
        $this->filtroEstado = 'todos';
        $this->filtroNivelId = '';
        $this->filtroCicloId = (string) ($this->ciclos->firstWhere('es_actual', true)?->id ?? '');
        $this->mostrarSistema = true;
    }

    public function abrirCrear(?string $fecha = null): void
    {
        $this->autorizarGestion();
        $this->reiniciarFormulario();

        $base = $fecha
            ? CarbonImmutable::parse($fecha)->setTime(8, 0)
            : CarbonImmutable::now()->addHour()->startOfHour();

        $this->inicia_at = $base->format('Y-m-d\TH:i');
        $this->termina_at = $base->addHour()->format('Y-m-d\TH:i');
        $this->ciclo_escolar_id = (string) ($this->ciclos->firstWhere('es_actual', true)?->id ?? '');
        $this->modalFormulario = true;
    }

    public function editarEvento(int $eventoId): void
    {
        $this->autorizarGestion();

        $evento = CalendarioEvento::query()->findOrFail($eventoId);
        $this->eventoEditandoId = $evento->id;
        $this->titulo = (string) $evento->titulo;
        $this->descripcion = (string) ($evento->descripcion ?? '');
        $this->tipo = (string) $evento->tipo;
        $this->estado = (string) $evento->estado;
        $this->prioridad = (string) $evento->prioridad;
        $this->audiencia = (string) $evento->audiencia;
        $this->inicia_at = $evento->inicia_at?->format('Y-m-d\TH:i') ?? '';
        $this->termina_at = $evento->termina_at?->format('Y-m-d\TH:i') ?? '';
        $this->todo_el_dia = (bool) $evento->todo_el_dia;
        $this->ubicacion = (string) ($evento->ubicacion ?? '');
        $this->enlace = (string) ($evento->enlace ?? '');
        $this->recurrencia = (string) $evento->recurrencia;
        $this->recurrencia_hasta = $evento->recurrencia_hasta?->format('Y-m-d') ?? '';
        $this->recordatorio_dias = (string) $evento->recordatorio_dias;
        $this->ciclo_escolar_id = (string) ($evento->ciclo_escolar_id ?? '');
        $this->nivel_id = (string) ($evento->nivel_id ?? '');
        $this->grado_id = (string) ($evento->grado_id ?? '');
        $this->grupo_id = (string) ($evento->grupo_id ?? '');
        $this->responsable_id = (string) ($evento->responsable_id ?? '');
        $this->modalDetalle = false;
        $this->modalFormulario = true;
    }

    public function guardar(): void
    {
        $this->autorizarGestion();

        $datos = $this->validate([
            'titulo' => ['required', 'string', 'min:3', 'max:160'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'tipo' => ['required', Rule::in(CalendarioEvento::TIPOS)],
            'estado' => ['required', Rule::in(CalendarioEvento::ESTADOS)],
            'prioridad' => ['required', Rule::in(CalendarioEvento::PRIORIDADES)],
            'audiencia' => ['required', Rule::in(CalendarioEvento::AUDIENCIAS)],
            'inicia_at' => ['required', 'date'],
            'termina_at' => ['nullable', 'date', 'after_or_equal:inicia_at'],
            'todo_el_dia' => ['boolean'],
            'ubicacion' => ['nullable', 'string', 'max:190'],
            'enlace' => ['nullable', 'url', 'max:500'],
            'recurrencia' => ['required', Rule::in(CalendarioEvento::RECURRENCIAS)],
            'recurrencia_hasta' => ['nullable', 'date', 'after_or_equal:inicia_at'],
            'recordatorio_dias' => ['required', 'integer', 'min:0', 'max:365'],
            'ciclo_escolar_id' => ['nullable', 'exists:ciclo_escolares,id'],
            'nivel_id' => ['nullable', 'exists:niveles,id'],
            'grado_id' => ['nullable', 'exists:grados,id'],
            'grupo_id' => ['nullable', 'exists:grupos,id'],
            'responsable_id' => ['nullable', 'exists:users,id'],
        ], [
            'titulo.required' => 'Escribe el nombre del evento.',
            'termina_at.after_or_equal' => 'La fecha de término no puede ser anterior al inicio.',
            'recurrencia_hasta.after_or_equal' => 'La recurrencia debe terminar después del inicio del evento.',
            'enlace.url' => 'El enlace debe iniciar con http:// o https://.',
        ]);

        if ($datos['grado_id'] !== '' && $datos['nivel_id'] !== '' && ! $this->grados->contains(
            fn ($grado): bool => (int) $grado->id === (int) $datos['grado_id']
                && (int) $grado->nivel_id === (int) $datos['nivel_id']
        )) {
            $this->addError('grado_id', 'El grado seleccionado no pertenece al nivel indicado.');
            return;
        }

        $grupo = $datos['grupo_id'] !== '' ? $this->grupos->firstWhere('id', (int) $datos['grupo_id']) : null;
        if ($grupo) {
            $datos['nivel_id'] = $grupo->nivel_id;
            $datos['grado_id'] = $grupo->grado_id;
            $datos['ciclo_escolar_id'] = $grupo->ciclo_escolar_id;
        }

        foreach (['descripcion', 'ubicacion', 'enlace', 'recurrencia_hasta', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'grupo_id', 'responsable_id', 'termina_at'] as $campo) {
            $datos[$campo] = $datos[$campo] === '' ? null : $datos[$campo];
        }

        if ($datos['recurrencia'] === 'ninguna') {
            $datos['recurrencia_hasta'] = null;
        }

        $datos['creado_por'] = $this->eventoEditandoId
            ? CalendarioEvento::query()->whereKey($this->eventoEditandoId)->value('creado_por')
            : auth()->id();
        $datos['actualizado_por'] = auth()->id();

        CalendarioEvento::query()->updateOrCreate(
            ['id' => $this->eventoEditandoId],
            $datos,
        );

        $this->modalFormulario = false;
        $this->reiniciarFormulario();
        $this->dispatch('notify', type: 'success', message: 'Evento guardado en el calendario institucional.');
    }

    public function verEvento(string $clave): void
    {
        [$desde, $hasta] = $this->rangoVisual();
        $eventos = app(CalendarioOperativoService::class)->eventos(
            $desde,
            $hasta->addDays(45),
            $this->filtros(),
            auth()->user(),
            $this->mostrarSistema,
        );

        $evento = $eventos->firstWhere('key', $clave);
        if (! $evento) {
            $this->dispatch('notify', type: 'warning', message: 'El evento ya no está disponible en este rango.');
            return;
        }

        $this->detalleEvento = $this->serializarEvento($evento);
        $this->modalDetalle = true;
    }

    public function completarEvento(int $eventoId): void
    {
        $this->autorizarGestion();
        CalendarioEvento::query()->whereKey($eventoId)->update([
            'estado' => 'completado',
            'actualizado_por' => auth()->id(),
            'updated_at' => now(),
        ]);
        $this->modalDetalle = false;
        $this->detalleEvento = null;
        $this->dispatch('notify', type: 'success', message: 'Evento marcado como completado.');
    }

    public function eliminarEvento(int $eventoId): void
    {
        $this->autorizarGestion();
        CalendarioEvento::query()->findOrFail($eventoId)->delete();
        $this->modalDetalle = false;
        $this->detalleEvento = null;
        $this->dispatch('notify', type: 'success', message: 'Evento enviado al historial del calendario.');
    }

    public function cerrarFormulario(): void
    {
        $this->modalFormulario = false;
        $this->reiniciarFormulario();
    }

    public function cerrarDetalle(): void
    {
        $this->modalDetalle = false;
        $this->detalleEvento = null;
    }

    public function updatedNivelId(): void
    {
        if ($this->grado_id !== '' && ! $this->grados->contains(fn ($grado) => (int) $grado->id === (int) $this->grado_id && (int) $grado->nivel_id === (int) $this->nivel_id)) {
            $this->grado_id = '';
        }
        $this->grupo_id = '';
    }


    public function updatedGradoId(): void
    {
        $this->grupo_id = '';
    }

    public function updatedCicloEscolarId(): void
    {
        $this->grupo_id = '';
    }

    public function claseTipo(string $tipo): string
    {
        return match ($tipo) {
            'evaluacion' => 'border-violet-200 bg-violet-50 text-violet-800 dark:border-violet-900/70 dark:bg-violet-950/40 dark:text-violet-200',
            'inscripcion' => 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900/70 dark:bg-emerald-950/40 dark:text-emerald-200',
            'reinscripcion' => 'border-lime-200 bg-lime-50 text-lime-800 dark:border-lime-900/70 dark:bg-lime-950/40 dark:text-lime-200',
            'boletas' => 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:border-indigo-900/70 dark:bg-indigo-950/40 dark:text-indigo-200',
            'cierre' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/40 dark:text-rose-200',
            'horario' => 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/70 dark:bg-blue-950/40 dark:text-blue-200',
            'documentacion' => 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/70 dark:bg-amber-950/40 dark:text-amber-200',
            'reunion' => 'border-cyan-200 bg-cyan-50 text-cyan-800 dark:border-cyan-900/70 dark:bg-cyan-950/40 dark:text-cyan-200',
            'administrativo' => 'border-slate-300 bg-slate-100 text-slate-800 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200',
            'respaldo' => 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/70 dark:bg-rose-950/40 dark:text-rose-200',
            'otro' => 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-900/70 dark:bg-orange-950/40 dark:text-orange-200',
            default => 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900/70 dark:bg-sky-950/40 dark:text-sky-200',
        };
    }

    public function clasePrioridad(string $prioridad): string
    {
        return match ($prioridad) {
            'critica' => 'bg-rose-500',
            'alta' => 'bg-amber-500',
            default => 'bg-emerald-500',
        };
    }

    public function render(CalendarioOperativoService $service)
    {
        [$desde, $hasta] = $this->rangoVisual();
        $eventos = $service->eventos(
            $desde,
            $hasta,
            $this->filtros(),
            auth()->user(),
            $this->mostrarSistema,
        );
        $porDia = $this->agruparPorDia($eventos, $desde, $hasta);
        $dias = collect();
        for ($fecha = $desde; $fecha->lessThanOrEqualTo($hasta); $fecha = $fecha->addDay()) {
            $dias->push([
                'fecha' => $fecha->toDateString(),
                'numero' => $fecha->day,
                'mes_actual' => $fecha->month === $this->mes()->month,
                'hoy' => $fecha->isToday(),
                'eventos' => $porDia->get($fecha->toDateString(), collect()),
            ]);
        }

        $agendaHasta = CarbonImmutable::now()->addDays(30)->endOfDay();
        $proximos = $service->eventos(
            CarbonImmutable::now()->startOfDay(),
            $agendaHasta,
            $this->filtros(),
            auth()->user(),
            $this->mostrarSistema,
        );

        $metricas = [
            'total' => $eventos->count(),
            'criticos' => $eventos->where('prioridad', 'critica')->whereNotIn('estado', ['completado', 'cancelado'])->count(),
            'en_curso' => $eventos->where('estado', 'en_curso')->count(),
            'proximos' => $proximos->count(),
        ];

        return view('livewire.calendario.calendario-operativo', [
            'dias' => $dias,
            'eventos' => $eventos,
            'proximos' => $proximos->take(12),
            'metricas' => $metricas,
            'etiquetaMes' => ucfirst($this->mes()->translatedFormat('F Y')),
            'puedeGestionar' => (bool) auth()->user()?->canAccess('calendario.gestionar'),
            'tipos' => CalendarioEvento::TIPOS,
            'gradosDisponibles' => $this->nivel_id === ''
                ? $this->grados
                : $this->grados->where('nivel_id', (int) $this->nivel_id),
            'gruposDisponibles' => $this->grupos->filter(function ($grupo): bool {
                return ($this->nivel_id === '' || (int) $grupo->nivel_id === (int) $this->nivel_id)
                    && ($this->grado_id === '' || (int) $grupo->grado_id === (int) $this->grado_id)
                    && ($this->ciclo_escolar_id === '' || (int) $grupo->ciclo_escolar_id === (int) $this->ciclo_escolar_id);
            }),
        ]);
    }

    private function mes(): CarbonImmutable
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m', $this->mesActual)->startOfMonth();
        } catch (\Throwable) {
            return CarbonImmutable::now()->startOfMonth();
        }
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function rangoVisual(): array
    {
        $mes = $this->mes();

        return [
            $mes->startOfWeek(CarbonInterface::MONDAY)->startOfDay(),
            $mes->endOfMonth()->endOfWeek(CarbonInterface::SUNDAY)->endOfDay(),
        ];
    }

    /** @return array<string, mixed> */
    private function filtros(): array
    {
        return [
            'buscar' => $this->buscar,
            'tipo' => $this->filtroTipo,
            'estado' => $this->filtroEstado,
            'nivel_id' => $this->filtroNivelId,
            'ciclo_escolar_id' => $this->filtroCicloId,
        ];
    }

    /** @return Collection<string, Collection<int, array<string, mixed>>> */
    private function agruparPorDia(Collection $eventos, CarbonImmutable $desde, CarbonImmutable $hasta): Collection
    {
        $porDia = collect();
        foreach ($eventos as $evento) {
            $inicio = CarbonImmutable::instance($evento['inicia_at'])->startOfDay();
            $fin = CarbonImmutable::instance($evento['termina_at'] ?? $evento['inicia_at'])->startOfDay();
            $cursor = $inicio->lessThan($desde) ? $desde->startOfDay() : $inicio;
            $limite = $fin->greaterThan($hasta) ? $hasta->startOfDay() : $fin;

            while ($cursor->lessThanOrEqualTo($limite)) {
                $clave = $cursor->toDateString();
                $porDia->put($clave, $porDia->get($clave, collect())->push($evento));
                $cursor = $cursor->addDay();
            }
        }

        return $porDia;
    }

    /** @return array<string, mixed> */
    private function serializarEvento(array $evento): array
    {
        $inicio = CarbonImmutable::instance($evento['inicia_at']);
        $fin = CarbonImmutable::instance($evento['termina_at'] ?? $evento['inicia_at']);

        return array_merge($evento, [
            'inicia_at' => $inicio->format($evento['todo_el_dia'] ? 'd/m/Y' : 'd/m/Y H:i'),
            'termina_at' => $fin->format($evento['todo_el_dia'] ? 'd/m/Y' : 'd/m/Y H:i'),
            'rango' => $inicio->isSameDay($fin)
                ? ($evento['todo_el_dia'] ? $inicio->translatedFormat('d \d\e F \d\e Y') : $inicio->format('d/m/Y H:i').' – '.$fin->format('H:i'))
                : $inicio->format('d/m/Y H:i').' – '.$fin->format('d/m/Y H:i'),
        ]);
    }

    private function reiniciarFormulario(): void
    {
        $this->reset([
            'eventoEditandoId', 'titulo', 'descripcion', 'inicia_at', 'termina_at', 'ubicacion', 'enlace',
            'recurrencia_hasta', 'ciclo_escolar_id', 'nivel_id', 'grado_id', 'grupo_id', 'responsable_id',
        ]);
        $this->tipo = 'academico';
        $this->estado = 'programado';
        $this->prioridad = 'normal';
        $this->audiencia = 'todos';
        $this->todo_el_dia = true;
        $this->recurrencia = 'ninguna';
        $this->recordatorio_dias = '0';
        $this->resetValidation();
    }

    private function autorizarGestion(): void
    {
        abort_unless(auth()->user()?->canAccess('calendario.gestionar'), 403);
    }
}
