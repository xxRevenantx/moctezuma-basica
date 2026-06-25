<?php

namespace App\Livewire\Accion;

use App\Models\Constancia as ConstanciaModelo;
use App\Models\ConstanciaPlantilla;
use App\Models\Inscripcion;
use App\Models\MovimientoAlumno;
use App\Models\Nivel;
use App\Models\TrayectoriaAcademica;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Baja extends Component
{
    use WithPagination;

    public string $slug_nivel;

    public Collection $niveles;
    public ?Nivel $nivel = null;
    public ?int $nivel_id = null;

    public string $search = '';

    public array $selected = [];
    public bool $selectPage = false;

    public string $tipo_movimiento = 'baja_definitiva';
    public ?string $motivo_baja = null;
    public ?string $fecha_baja = null;
    public ?string $observaciones_baja = null;

    public bool $esBachillerato = false;

    public function mount(string $slug_nivel): void
    {
        $this->slug_nivel = $slug_nivel;

        $this->niveles = Nivel::query()
            ->orderBy('id')
            ->get();

        $this->nivel = Nivel::query()
            ->where('slug', $this->slug_nivel)
            ->firstOrFail();

        $this->nivel_id = $this->nivel->id;
        $this->esBachillerato = (int) $this->nivel_id === 4
            || $this->slug_nivel === 'bachillerato';

        $this->fecha_baja = now()->format('Y-m-d');
    }

    protected function rules(): array
    {
        return [
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:inscripciones,id'],
            'tipo_movimiento' => ['required', 'in:baja_definitiva,baja_temporal,traslado'],
            'motivo_baja' => ['required', 'string', 'max:1000'],
            'fecha_baja' => ['required', 'date'],
            'observaciones_baja' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'selected.required' => 'Selecciona al menos un alumno para aplicar la baja.',
            'selected.min' => 'Selecciona al menos un alumno para aplicar la baja.',
            'tipo_movimiento.required' => 'Selecciona el tipo de movimiento.',
            'tipo_movimiento.in' => 'El tipo de movimiento no es válido.',
            'motivo_baja.required' => 'Escribe el motivo de la baja o traslado.',
            'motivo_baja.max' => 'El motivo no debe superar los 1000 caracteres.',
            'fecha_baja.required' => 'Selecciona la fecha de baja.',
            'fecha_baja.date' => 'La fecha de baja no es válida.',
            'observaciones_baja.max' => 'Las observaciones no deben superar los 1000 caracteres.',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->resetPage('bajasPage');

        $this->selected = [];
        $this->selectPage = false;
    }

    public function updatedSelectPage(bool $value): void
    {
        if (!$value) {
            $this->selected = [];
            return;
        }

        $this->selected = $this->rows()
            ->pluck('id')
            ->map(fn($id) => (string) $id)
            ->toArray();
    }

    #[Computed]
    public function selectedCount(): int
    {
        return count($this->selected);
    }

    public function textoGrupo($grupo): string
    {
        if (!$grupo) {
            return '—';
        }

        return $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';
    }

    public function getTotalProperty(): int
    {
        return $this->baseQuery()->count();
    }

    public function getTotalBajasProperty(): int
    {
        return $this->bajasQuery()->count();
    }

    public function getHombresProperty(): int
    {
        return $this->baseQuery()
            ->where('genero', 'H')
            ->count();
    }

    public function getMujeresProperty(): int
    {
        return $this->baseQuery()
            ->where('genero', 'M')
            ->count();
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

        $ids = collect($this->selected)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->addError('selected', 'Selecciona al menos un alumno.');
            return;
        }

        DB::transaction(function () use ($ids) {
            $alumnos = $this->baseQuery()
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            foreach ($alumnos as $alumno) {
                $datos = [
                    'activo' => false,
                    'fecha_baja' => $this->fecha_baja,
                    'motivo_baja' => $this->motivo_baja,
                    'observaciones_baja' => $this->observaciones_baja,
                ];

                if (Schema::hasColumn('inscripciones', 'status')) {
                    $datos['status'] = 'Baja';
                }

                $alumno->update($datos);

                $trayectoria = TrayectoriaAcademica::query()
                    ->where('inscripcion_id', $alumno->id)
                    ->latest('id')
                    ->first();

                if ($trayectoria) {
                    $trayectoria->update([
                        'activo' => false,
                        'fecha_baja' => $this->fecha_baja,
                        'motivo_baja' => $this->motivo_baja,
                        'observaciones_baja' => $this->observaciones_baja,
                    ]);
                }

                MovimientoAlumno::query()->create([
                    'inscripcion_id' => $alumno->id,
                    'trayectoria_academica_id' => $trayectoria?->id,
                    'tipo' => $this->tipo_movimiento,
                    'fecha' => $this->fecha_baja,
                    'motivo' => trim((string) $this->motivo_baja),
                    'observaciones' => trim((string) $this->observaciones_baja) ?: null,
                    'registrado_por' => auth()->id(),
                ]);
            }
        });

        $totalBajas = $ids->count();

        $this->selected = [];
        $this->selectPage = false;
        $this->tipo_movimiento = 'baja_definitiva';
        $this->motivo_baja = null;
        $this->observaciones_baja = null;
        $this->fecha_baja = now()->format('Y-m-d');

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Baja aplicada correctamente',
            'text' => $totalBajas === 1
                ? 'Se dio de baja 1 alumno.'
                : "Se dieron de baja {$totalBajas} alumnos.",
            'position' => 'top-end',
        ]);

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function reactivarAlumno(int $inscripcionId): void
    {
        DB::transaction(function () use ($inscripcionId) {
            $alumno = $this->bajasQuery()
                ->where('id', $inscripcionId)
                ->lockForUpdate()
                ->firstOrFail();

            $trayectoriaAnterior = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $alumno->id)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($trayectoriaAnterior) {
                // La BD permite una sola trayectoria por alumno y ciclo escolar.
                // Se reactiva la misma etapa y el historial de baja queda en movimientos_alumnos.
                $trayectoriaAnterior->update([
                    'activo' => true,
                    'fecha_inscripcion' => now(),
                ]);
            }

            $datos = [
                'activo' => true,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'observaciones_baja' => null,
            ];

            if (Schema::hasColumn('inscripciones', 'status')) {
                $datos['status'] = 'Reingreso';
            }

            $alumno->update($datos);

            MovimientoAlumno::query()->create([
                'inscripcion_id' => $alumno->id,
                'trayectoria_academica_id' => $trayectoriaAnterior?->id,
                'tipo' => 'reingreso',
                'fecha' => now()->toDateString(),
                'motivo' => 'Reingreso del alumno',
                'observaciones' => 'Se reactivó la inscripción sin eliminar el historial de baja.',
                'registrado_por' => auth()->id(),
            ]);
        });

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Reingreso registrado',
            'text' => 'El alumno volvió a quedar activo y la baja anterior se conservó en su historial.',
            'position' => 'top-end',
        ]);

        $this->resetPage();
        $this->resetPage('bajasPage');
    }

    public function generarConstanciaBaja(int $inscripcionId): void
    {
        $alumno = $this->bajasQuery()
            ->where('id', $inscripcionId)
            ->firstOrFail();

        $plantilla = ConstanciaPlantilla::query()
            ->where('clave', 'baja-traslado')
            ->where('activo', true)
            ->firstOrFail();

        $movimiento = MovimientoAlumno::query()
            ->where('inscripcion_id', $alumno->id)
            ->whereIn('tipo', ['baja_definitiva', 'baja_temporal', 'traslado'])
            ->latest('fecha')
            ->latest('id')
            ->first();

        $tipoMovimiento = match ($movimiento?->tipo) {
            'baja_temporal' => 'baja temporal',
            'traslado' => 'traslado',
            default => 'baja definitiva',
        };

        $folio = $this->generarFolioConstancia();

        $variables = [
            '@nombre_completo' => trim(($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? '')),
            '@matricula' => $alumno->matricula ?? '',
            '@curp' => $alumno->curp ?? '',
            '@nivel' => $alumno->nivel?->nombre ?? '',
            '@grado' => $alumno->grado?->nombre ?? '',
            '@grupo' => $alumno->grupo?->asignacionGrupo?->nombre ?? '',
            '@fecha_baja' => $movimiento?->fecha?->format('d/m/Y')
                ?? ($alumno->fecha_baja ? \Carbon\Carbon::parse($alumno->fecha_baja)->format('d/m/Y') : ''),
            '@tipo_movimiento' => $tipoMovimiento,
            '@motivo_baja' => $movimiento?->motivo ?? $alumno->motivo_baja ?? '',
            '@folio' => $folio,
        ];

        $contenido = str_replace(array_keys($variables), array_values($variables), $plantilla->contenido_html);

        $constancia = ConstanciaModelo::query()->create([
            'inscripcion_id' => $alumno->id,
            'constancia_plantilla_id' => $plantilla->id,
            'folio' => $folio,
            'fecha_expedicion' => now()->toDateString(),
            'dirigido_a' => null,
            'modo_descarga' => 'alumno',
            'periodos_calificaciones' => null,
            'contenido_generado_html' => $contenido,
            'estado_documento' => 'emitida',
        ]);

        $this->dispatch('abrir-constancia-baja', url: route('misrutas.constancias.pdf', $constancia));
    }

    private function generarFolioConstancia(): string
    {
        $siguiente = (ConstanciaModelo::query()->max('id') ?? 0) + 1;

        return 'CONST-' . now()->format('Y') . '-' . Str::padLeft((string) $siguiente, 5, '0');
    }

    public function rows(): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(10);
    }

    public function bajasRows(): LengthAwarePaginator
    {
        return $this->bajasQuery()
            ->orderByDesc('fecha_baja')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->paginate(10, ['*'], 'bajasPage');
    }

    private function baseQuery(): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'generacion',
                'grado',
                'grupo.asignacionGrupo',
                'semestre',
                'nivel',
                'ultimoMovimiento',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where('activo', true)
            ->whereNull('fecha_baja')
            ->whereNull('motivo_baja')
            ->whereNull('observaciones_baja')
            ->when(Schema::hasColumn('inscripciones', 'status'), function (Builder $query) {
                $query->where(function (Builder $subquery) {
                    $subquery->whereNull('status')
                        ->orWhereNotIn('status', [
                            'Baja',
                            'BAJA',
                            'baja',
                            'Inactivo',
                            'INACTIVO',
                            'inactivo',
                        ]);
                });
            });

        return $this->applySearch($query);
    }

    private function bajasQuery(): Builder
    {
        $query = Inscripcion::query()
            ->with([
                'generacion',
                'grado',
                'grupo.asignacionGrupo',
                'semestre',
                'nivel',
                'ultimoMovimiento',
            ])
            ->where('nivel_id', $this->nivel_id)
            ->where(function (Builder $query) {
                $query->where('activo', false)
                    ->orWhereNotNull('fecha_baja')
                    ->orWhereNotNull('motivo_baja')
                    ->orWhereNotNull('observaciones_baja');
            });

        return $this->applySearch($query);
    }

    private function applySearch(Builder $query): Builder
    {
        $termino = preg_replace('/\s+/', ' ', trim($this->search));

        if (blank($termino)) {
            return $query;
        }

        $buscar = "%{$termino}%";

        return $query->where(function (Builder $subquery) use ($buscar) {
            $subquery
                ->where('matricula', 'like', $buscar)
                ->orWhere('folio', 'like', $buscar)
                ->orWhere('curp', 'like', $buscar)
                ->orWhere('nombre', 'like', $buscar)
                ->orWhere('apellido_paterno', 'like', $buscar)
                ->orWhere('apellido_materno', 'like', $buscar)
                ->orWhereRaw(
                    "CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?",
                    [$buscar]
                )
                ->orWhereRaw(
                    "CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?",
                    [$buscar]
                )
                ->orWhereHas('generacion', function (Builder $query) use ($buscar) {
                    $query->where('anio_ingreso', 'like', $buscar)
                        ->orWhere('anio_egreso', 'like', $buscar)
                        ->orWhereRaw(
                            "CONCAT(anio_ingreso, ' - ', anio_egreso) LIKE ?",
                            [$buscar]
                        );
                })
                ->orWhereHas('grado', fn(Builder $query) => $query->where('nombre', 'like', $buscar))
                ->orWhereHas(
                    'grupo.asignacionGrupo',
                    fn(Builder $query) => $query->where('nombre', 'like', $buscar)
                )
                ->orWhereHas('semestre', fn(Builder $query) => $query->where('numero', 'like', $buscar));
        });
    }

    public function render()
    {
        return view('livewire.accion.baja', [
            'rows' => $this->rows(),
            'bajasRows' => $this->bajasRows(),
            'total' => $this->total,
            'totalBajas' => $this->totalBajas,
            'hombres' => $this->hombres,
            'mujeres' => $this->mujeres,
        ]);
    }
}
