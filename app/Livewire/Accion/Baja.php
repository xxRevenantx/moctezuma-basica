<?php

namespace App\Livewire\Accion;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Constancia as ConstanciaModelo;
use App\Models\ConstanciaPlantilla;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use App\Services\TrayectoriaAcademicaService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Baja extends Component
{
    use WithPagination;

    public string $slug_nivel = '';
    public ?Nivel $nivel = null;
    public Collection $niveles;
    public Collection $cicloEscolares;
    public Collection $ciclos;

    public ?int $ciclo_escolar_id = null;
    public ?int $ciclo_id = null;
    public string $search = '';

    public array $selected = [];
    public bool $selectPage = false;

    public string $tipo_movimiento = 'baja_definitiva';
    public ?string $motivo_baja = null;
    public ?string $fecha_baja = null;
    public ?string $observaciones_baja = null;

    public ?string $fecha_reingreso = null;
    public ?string $motivo_reingreso = null;

    protected $paginationTheme = 'tailwind';

    public function mount(string $slug_nivel): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->slug_nivel = $slug_nivel;
        $this->nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        $this->niveles = Nivel::query()->orderBy('id')->get(['id', 'nombre', 'slug']);
        $this->cicloEscolares = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get(['id', 'inicio_anio', 'fin_anio', 'es_actual', 'cerrado_at']);
        $this->ciclos = Ciclo::query()->orderBy('id')->get(['id', 'ciclo']);

        $this->ciclo_escolar_id = $this->cicloEscolares->firstWhere('es_actual', true)?->id
            ?: $this->cicloEscolares->first()?->id;
        $this->ciclo_id = $this->ciclos->first()?->id;
        $this->fecha_baja = now()->toDateString();
        $this->fecha_reingreso = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'ciclo_escolar_id' => ['required', 'exists:ciclo_escolares,id'],
            'ciclo_id' => ['required', 'exists:ciclos,id'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:trayectorias_academicas,id'],
            'tipo_movimiento' => ['required', 'in:baja_definitiva,baja_temporal,traslado'],
            'motivo_baja' => ['required', 'string', 'max:1000'],
            'fecha_baja' => ['required', 'date'],
            'observaciones_baja' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'selected.required' => 'Selecciona al menos un alumno.',
            'selected.min' => 'Selecciona al menos un alumno.',
            'motivo_baja.required' => 'Escribe el motivo de la baja o traslado.',
            'fecha_baja.required' => 'Selecciona la fecha del movimiento.',
        ];
    }

    public function updated($property): void
    {
        if (in_array($property, ['ciclo_escolar_id', 'ciclo_id', 'search'], true)) {
            $this->selected = [];
            $this->selectPage = false;
            $this->resetPage();
            $this->resetPage('bajasPage');
        }
    }

    public function updatedSelectPage(bool $value): void
    {
        $this->selected = $value
            ? $this->rows()->getCollection()->pluck('id')->map(fn($id) => (string) $id)->all()
            : [];
    }

    public function getSelectedCountProperty(): int
    {
        return count($this->selected);
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->selected = [];
        $this->selectPage = false;
        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function aplicarBaja(): void
    {
        $this->validate();

        $trayectorias = $this->activosQuery()
            ->whereIn('trayectorias_academicas.id', collect($this->selected)->map(fn($id) => (int) $id))
            ->get();

        if ($trayectorias->isEmpty()) {
            $this->addError('selected', 'Los alumnos seleccionados ya no están disponibles en este ciclo y corte.');
            return;
        }

        $service = app(TrayectoriaAcademicaService::class);
        $aplicadas = 0;

        foreach ($trayectorias as $trayectoria) {
            $service->aplicarBaja(
                $trayectoria->inscripcion,
                (int) $this->ciclo_escolar_id,
                (int) $this->ciclo_id,
                $this->tipo_movimiento,
                (string) $this->fecha_baja,
                trim((string) $this->motivo_baja),
                filled($this->observaciones_baja) ? trim((string) $this->observaciones_baja) : null,
                auth()->id()
            );
            $aplicadas++;
        }

        $this->selected = [];
        $this->selectPage = false;
        $this->tipo_movimiento = 'baja_definitiva';
        $this->motivo_baja = null;
        $this->observaciones_baja = null;
        $this->fecha_baja = now()->toDateString();
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => $aplicadas === 1 ? 'Baja registrada' : "{$aplicadas} bajas registradas",
            'text' => 'La ubicación anterior y la línea de tiempo quedaron conservadas.',
            'position' => 'top-end',
        ]);
    }

    public function reactivarAlumno(int $trayectoriaId): void
    {
        $this->validate([
            'fecha_reingreso' => ['required', 'date'],
            'motivo_reingreso' => ['nullable', 'string', 'max:1000'],
        ]);

        $trayectoria = $this->bajasQuery()->whereKey($trayectoriaId)->firstOrFail();

        app(TrayectoriaAcademicaService::class)->reingresar(
            $trayectoria->inscripcion,
            (int) $this->ciclo_escolar_id,
            (int) $this->ciclo_id,
            (string) $this->fecha_reingreso,
            $this->motivo_reingreso ?: 'Reingreso del alumno.',
            'Se creó una nueva estancia y se conservó la baja anterior.',
            auth()->id()
        );

        $this->motivo_reingreso = null;
        $this->fecha_reingreso = now()->toDateString();
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Reingreso registrado',
            'text' => 'Se creó una nueva estancia sin borrar la baja anterior.',
            'position' => 'top-end',
        ]);
    }

    public function generarConstanciaBaja(int $trayectoriaId): void
    {
        $trayectoria = $this->bajasQuery()->whereKey($trayectoriaId)->firstOrFail();
        $alumno = $trayectoria->inscripcion;
        $plantilla = ConstanciaPlantilla::query()
            ->where('clave', 'baja-traslado')
            ->where('activo', true)
            ->firstOrFail();

        $folio = $this->generarFolioConstancia();
        $tipo = match ($trayectoria->estatus) {
            'baja_temporal' => 'baja temporal',
            'traslado' => 'traslado',
            default => 'baja definitiva',
        };

        $grupo = $trayectoria->grupo?->asignacionGrupo?->nombre
            ?? $trayectoria->grupo?->grupo
            ?? $trayectoria->grupo?->nombre
            ?? '';

        $variables = [
            '@nombre_completo' => trim("{$alumno->nombre} {$alumno->apellido_paterno} {$alumno->apellido_materno}"),
            '@matricula' => $alumno->matriculasAlumno->firstWhere('nivel_id', $trayectoria->nivel_id)?->matricula ?: $alumno->matricula,
            '@curp' => $alumno->curp ?? '',
            '@nivel' => $trayectoria->nivel?->nombre ?? '',
            '@grado' => $trayectoria->grado?->nombre ?? '',
            '@grupo' => $grupo,
            '@fecha_baja' => optional($trayectoria->fecha_baja)->format('d/m/Y') ?: '',
            '@tipo_movimiento' => $tipo,
            '@motivo_baja' => $trayectoria->motivo_baja ?? '',
            '@folio' => $folio,
        ];

        $constancia = ConstanciaModelo::query()->create([
            'inscripcion_id' => $alumno->id,
            'constancia_plantilla_id' => $plantilla->id,
            'folio' => $folio,
            'fecha_expedicion' => now()->toDateString(),
            'dirigido_a' => null,
            'modo_descarga' => 'alumno',
            'periodos_calificaciones' => null,
            'contenido_generado_html' => str_replace(array_keys($variables), array_values($variables), $plantilla->contenido_html),
            'estado_documento' => 'emitida',
        ]);

        $this->dispatch('abrir-constancia-baja', url: route('misrutas.constancias.pdf', $constancia));
    }

    private function generarFolioConstancia(): string
    {
        return 'CONST-' . now()->format('Y') . '-' . Str::padLeft((string) ((ConstanciaModelo::max('id') ?? 0) + 1), 5, '0');
    }

    public function rows(): LengthAwarePaginator
    {
        return $this->activosQuery()->paginate(10);
    }

    public function bajasRows(): LengthAwarePaginator
    {
        return $this->bajasQuery()->orderByDesc('fecha_baja')->paginate(10, ['trayectorias_academicas.*'], 'bajasPage');
    }

    private function baseContextQuery(): Builder
    {
        $query = TrayectoriaAcademica::query()
            ->with([
                'inscripcion' => fn($q) => $q->withTrashed()->with('matriculasAlumno'),
                'nivel:id,nombre,slug',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'grupo.asignacionGrupo:id,nombre',
                'semestre:id,numero',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
                'ciclo:id,ciclo',
            ])
            ->where('trayectorias_academicas.ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('trayectorias_academicas.ciclo_id', $this->ciclo_id)
            ->where('trayectorias_academicas.nivel_id', $this->nivel?->id)
            ->where('trayectorias_academicas.vigente_en_corte', true)
            ->whereHas('inscripcion', fn(Builder $q) => $q->whereNull('deleted_at'));

        $termino = preg_replace('/\s+/', ' ', trim($this->search));
        if ($termino !== '') {
            $like = "%{$termino}%";
            $query->whereHas('inscripcion', function (Builder $q) use ($like) {
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
            });
        }

        return $query
            ->join('inscripciones', 'inscripciones.id', '=', 'trayectorias_academicas.inscripcion_id')
            ->orderBy('inscripciones.apellido_paterno')
            ->orderBy('inscripciones.apellido_materno')
            ->orderBy('inscripciones.nombre')
            ->select('trayectorias_academicas.*');
    }

    private function activosQuery(): Builder
    {
        return $this->baseContextQuery()
            ->where('trayectorias_academicas.activo', true)
            ->whereNotIn('trayectorias_academicas.estatus', ['baja_temporal', 'baja_definitiva', 'traslado']);
    }

    private function bajasQuery(): Builder
    {
        return $this->baseContextQuery()
            ->whereIn('trayectorias_academicas.estatus', ['baja_temporal', 'baja_definitiva', 'traslado']);
    }

    public function textoGrupo($grupo): string
    {
        return $grupo?->asignacionGrupo?->nombre
            ?? $grupo?->grupo
            ?? $grupo?->nombre
            ?? '—';
    }

    public function etiquetaEstatus(string $estatus): string
    {
        return match ($estatus) {
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'traslado' => 'Traslado',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            default => 'Activo',
        };
    }

    public function render()
    {
        $rows = $this->rows();
        $bajasRows = $this->bajasRows();
        $activos = $this->activosQuery();

        return view('livewire.accion.baja', [
            'rows' => $rows,
            'bajasRows' => $bajasRows,
            'total' => (clone $activos)->count(),
            'hombres' => (clone $activos)->where('inscripciones.genero', 'H')->count(),
            'mujeres' => (clone $activos)->where('inscripciones.genero', 'M')->count(),
            'totalBajas' => $this->bajasQuery()->count(),
            'cicloSeleccionado' => $this->cicloEscolares->firstWhere('id', $this->ciclo_escolar_id),
            'corteSeleccionado' => $this->ciclos->firstWhere('id', $this->ciclo_id),
        ]);
    }
}
