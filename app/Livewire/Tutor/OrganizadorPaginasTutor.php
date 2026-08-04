<?php

namespace App\Livewire\Tutor;

use App\Models\Tutor;
use App\Services\Expedientes\ExpedienteTutorService;
use App\Support\Documentos\RangoPaginas;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class OrganizadorPaginasTutor extends Component
{
    public bool $abierto = false;
    public ?int $tutorId = null;
    public ?int $organizacionId = null;
    public ?int $fuenteActivaId = null;

    public array $fuentes = [];
    public array $paginas = [];
    public array $tipos = [];
    public array $rangos = [];
    public array $historial = [];
    public array $tiposExistentes = [];
    public array $retirosConfirmados = [];

    public int $paginasSinClasificar = 0;
    public string $mensaje = '';

    #[On('abrir-organizador-tutor')]
    public function abrir(int $tutorId, ?int $fuenteId = null): void
    {
        $this->autorizar();
        $this->tutorId = $tutorId;
        $this->resetErrorBag();
        $this->mensaje = '';
        $this->cargarDatos($fuenteId);
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->autorizar();

        if ($this->organizacionId && $this->tutorId) {
            $this->persistirBorrador('Borrador guardado.');
        }
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function seleccionarFuente(int $fuenteId): void
    {
        if (! collect($this->fuentes)->contains('id', $fuenteId)) {
            return;
        }
        $this->fuenteActivaId = $fuenteId;
        $this->actualizarRangosDesdeFuente();
    }

    public function actualizarTipo(string $clave, $tipoId): void
    {
        $this->autorizar();
        $indice = $this->indicePagina($clave);
        if ($indice === null) {
            return;
        }

        $tipoId = filled($tipoId) ? (int) $tipoId : null;
        $tipo = $tipoId ? collect($this->tipos)->firstWhere('id', $tipoId) : null;
        if ($tipoId && ! $tipo) {
            $this->addError('organizacion', 'El tipo documental seleccionado no es válido.');
            return;
        }

        $this->paginas[$indice]['tipo_documento_tutor_id'] = $tipo['id'] ?? null;
        $this->paginas[$indice]['tipo_slug'] = $tipo['slug'] ?? null;
        $this->paginas[$indice]['tipo_nombre'] = $tipo['nombre'] ?? null;
        $this->paginas[$indice]['orden'] = $tipo
            ? $this->siguienteOrden((int) $tipo['id'], $clave)
            : 0;
        $this->normalizarOrdenes();
        $this->persistirBorrador('Asignación actualizada.');
    }

    public function rotarPagina(string $clave, int $incremento): void
    {
        $this->autorizar();
        $indice = $this->indicePagina($clave);
        if ($indice === null) {
            return;
        }

        $actual = (int) ($this->paginas[$indice]['rotacion'] ?? 0);
        $rotacion = (($actual + $incremento) % 360 + 360) % 360;
        $this->paginas[$indice]['rotacion'] = $rotacion;
        $this->paginas[$indice]['preview_url'] = route('misrutas.expedientes-tutores.fuentes.page', [
            'fuente' => (int) $this->paginas[$indice]['fuente_id'],
            'pagina' => (int) $this->paginas[$indice]['pagina'],
            'rotation' => $rotacion,
        ]);
        $this->persistirBorrador('Rotación guardada.');
    }

    public function moverPagina(string $clave, string $direccion): void
    {
        $this->autorizar();
        $pagina = $this->paginaPorClave($clave);
        if (! $pagina) {
            return;
        }

        $tipoId = (int) ($pagina['tipo_documento_tutor_id'] ?? 0);
        if (! $tipoId) {
            return;
        }

        $ordenadas = collect($this->paginas)
            ->where('tipo_documento_tutor_id', $tipoId)
            ->sortBy('orden')
            ->values();
        $posicion = $ordenadas->search(fn (array $item): bool => $item['clave'] === $clave);
        $destino = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;
        if ($posicion === false || ! isset($ordenadas[$destino])) {
            return;
        }

        $indiceA = $this->indicePagina($clave);
        $indiceB = $this->indicePagina($ordenadas[$destino]['clave']);
        if ($indiceA === null || $indiceB === null) {
            return;
        }
        [$this->paginas[$indiceA]['orden'], $this->paginas[$indiceB]['orden']] = [
            $this->paginas[$indiceB]['orden'],
            $this->paginas[$indiceA]['orden'],
        ];
        $this->persistirBorrador('Orden actualizado.');
    }

    public function aplicarRangos(): void
    {
        $this->autorizar();
        $fuente = collect($this->fuentes)->firstWhere('id', $this->fuenteActivaId);
        if (! $fuente) {
            $this->addError('rangos', 'Selecciona un archivo fuente.');
            return;
        }

        try {
            $asignadas = [];
            $porTipo = [];
            foreach ($this->tipos as $tipo) {
                $tipoId = (int) $tipo['id'];
                $numeros = RangoPaginas::interpretar((string) ($this->rangos[$tipoId] ?? ''), (int) $fuente['paginas']);
                foreach ($numeros as $numero) {
                    if (isset($asignadas[$numero])) {
                        throw ValidationException::withMessages([
                            'rangos' => "La página {$numero} está repetida en {$asignadas[$numero]} y {$tipo['nombre']}.",
                        ]);
                    }
                    $asignadas[$numero] = $tipo['nombre'];
                }
                $porTipo[$tipoId] = $numeros;
            }

            foreach ($this->paginas as $indice => $pagina) {
                if ((int) $pagina['fuente_id'] === (int) $this->fuenteActivaId) {
                    $this->paginas[$indice]['tipo_documento_tutor_id'] = null;
                    $this->paginas[$indice]['tipo_slug'] = null;
                    $this->paginas[$indice]['tipo_nombre'] = null;
                    $this->paginas[$indice]['orden'] = 0;
                }
            }

            foreach ($porTipo as $tipoId => $numeros) {
                $tipo = collect($this->tipos)->firstWhere('id', $tipoId);
                $orden = $this->siguienteOrden($tipoId);
                foreach ($numeros as $numero) {
                    $indice = $this->indicePagina($this->fuenteActivaId . ':' . $numero);
                    if ($indice !== null) {
                        $this->paginas[$indice]['tipo_documento_tutor_id'] = $tipoId;
                        $this->paginas[$indice]['tipo_slug'] = $tipo['slug'];
                        $this->paginas[$indice]['tipo_nombre'] = $tipo['nombre'];
                        $this->paginas[$indice]['orden'] = $orden++;
                    }
                }
            }

            $this->normalizarOrdenes();
            $this->persistirBorrador('Rangos aplicados correctamente.');
            $this->actualizarRangosDesdeFuente();
        } catch (ValidationException $e) {
            $this->addError('rangos', $e->validator->errors()->first());
        }
    }

    public function confirmarRetiro(int $tipoId): void
    {
        $this->autorizar();
        if (! in_array($tipoId, $this->tiposExistentes, true)) {
            return;
        }
        if (! in_array($tipoId, $this->retirosConfirmados, true)) {
            $this->retirosConfirmados[] = $tipoId;
        }
        $this->persistirBorrador('Retiro confirmado.');
    }

    public function cancelarRetiro(int $tipoId): void
    {
        $this->autorizar();
        $this->retirosConfirmados = array_values(array_filter(
            $this->retirosConfirmados,
            fn (int $id): bool => $id !== $tipoId
        ));
        $this->persistirBorrador('Confirmación de retiro cancelada.');
    }

    public function confirmar(): void
    {
        $this->autorizar();
        abort_unless($this->organizacionId && $this->tutorId, 422);
        $this->persistirBorrador('Borrador listo para confirmar.');

        try {
            app(ExpedienteTutorService::class)->confirmarOrganizacion(
                $this->tutor(),
                (int) $this->organizacionId,
                auth()->id()
            );
            $tutorId = (int) $this->tutorId;
            $this->abierto = false;
            $this->dispatch('organizacion-tutor-confirmada', tutorId: $tutorId);
            $this->dispatch('expediente-tutor-actualizado', tutorId: $tutorId);
            $this->dispatch('notify', type: 'success', message: 'La organización se confirmó y los documentos del responsable quedaron actualizados.');
        } catch (ValidationException $e) {
            $this->addError('organizacion', $e->validator->errors()->first());
        } catch (Throwable $e) {
            report($e);
            $this->addError('organizacion', app()->environment('local')
                ? 'No fue posible confirmar: ' . $e->getMessage()
                : 'No fue posible confirmar la organización.');
        }
    }

    protected function cargarDatos(?int $fuentePreferida = null): void
    {
        $datos = app(ExpedienteTutorService::class)->datosOrganizador($this->tutor(), auth()->id());
        $this->organizacionId = $datos['organizacion']->id;
        $this->retirosConfirmados = array_map('intval', $datos['organizacion']->retiros_confirmados ?? []);
        $this->tipos = $datos['tipos']->map(fn ($tipo): array => [
            'id' => $tipo->id,
            'nombre' => $tipo->nombre,
            'slug' => $tipo->slug,
            'descripcion' => $tipo->descripcion,
        ])->values()->all();
        $this->fuentes = $datos['fuentes']->map(fn ($fuente): array => [
            'id' => $fuente->id,
            'nombre' => $fuente->nombre_original,
            'paginas' => $fuente->paginas,
            'tamano' => $fuente->tamano_legible,
            'fecha' => $fuente->created_at?->format('d/m/Y H:i'),
            'usuario' => $fuente->usuario?->name ?? 'Sistema',
            'original_url' => route('misrutas.expedientes-tutores.fuentes.download', $fuente),
        ])->values()->all();
        $this->paginas = collect($datos['asignaciones'])->map(function (array $pagina): array {
            $fuente = collect($this->fuentes)->firstWhere('id', (int) $pagina['fuente_id']);
            $clave = $pagina['fuente_id'] . ':' . $pagina['pagina'];
            return array_merge($pagina, [
                'clave' => $clave,
                'fuente_nombre' => $fuente['nombre'] ?? 'Archivo fuente',
                'preview_url' => route('misrutas.expedientes-tutores.fuentes.page', [
                    'fuente' => $pagina['fuente_id'],
                    'pagina' => $pagina['pagina'],
                    'rotation' => $pagina['rotacion'] ?? 0,
                ]),
            ]);
        })->values()->all();
        $this->historial = $datos['historial']->map(fn ($item): array => [
            'version' => $item->version,
            'estado' => $item->estado,
            'fecha' => $item->confirmado_at?->format('d/m/Y H:i') ?? $item->updated_at?->format('d/m/Y H:i'),
            'usuario' => $item->usuarioConfirmacion?->name ?? 'Sistema',
            'error' => $item->error,
        ])->values()->all();
        $this->tiposExistentes = array_map('intval', $datos['tipos_existentes']);
        $this->fuenteActivaId = $fuentePreferida && collect($this->fuentes)->contains('id', $fuentePreferida)
            ? $fuentePreferida
            : data_get($this->fuentes, '0.id');
        $this->rangos = collect($this->tipos)->mapWithKeys(fn (array $tipo): array => [$tipo['id'] => ''])->all();
        $this->actualizarRangosDesdeFuente();
        $this->actualizarConteos();
    }

    protected function persistirBorrador(string $mensaje): void
    {
        if (! $this->organizacionId || ! $this->tutorId) {
            return;
        }

        try {
            $borrador = app(ExpedienteTutorService::class)->guardarBorrador(
                $this->tutor(),
                $this->asignacionesParaGuardar(),
                auth()->id(),
                $this->organizacionId,
                $this->retirosConfirmados
            );
            $this->organizacionId = $borrador->id;
            $this->mensaje = $mensaje;
            $this->actualizarConteos();
        } catch (ValidationException $e) {
            $this->addError('organizacion', $e->validator->errors()->first());
        }
    }

    protected function asignacionesParaGuardar(): array
    {
        return collect($this->paginas)->map(fn (array $pagina): array => collect($pagina)->only([
            'fuente_id', 'pagina', 'tipo_documento_tutor_id', 'tipo_slug', 'tipo_nombre',
            'orden', 'rotacion', 'fecha_documento', 'folio', 'origen', 'observaciones',
        ])->all())->values()->all();
    }

    protected function siguienteOrden(int $tipoId, ?string $excluir = null): int
    {
        return ((int) collect($this->paginas)
            ->where('tipo_documento_tutor_id', $tipoId)
            ->reject(fn (array $pagina): bool => $excluir && $pagina['clave'] === $excluir)
            ->max('orden')) + 1;
    }

    protected function normalizarOrdenes(): void
    {
        foreach (collect($this->paginas)->whereNotNull('tipo_documento_tutor_id')->groupBy('tipo_documento_tutor_id') as $grupo) {
            foreach ($grupo->sortBy('orden')->values() as $orden => $pagina) {
                $indice = $this->indicePagina($pagina['clave']);
                if ($indice !== null) {
                    $this->paginas[$indice]['orden'] = $orden + 1;
                }
            }
        }
    }

    protected function actualizarRangosDesdeFuente(): void
    {
        if (! $this->fuenteActivaId) {
            return;
        }
        foreach ($this->tipos as $tipo) {
            $numeros = collect($this->paginas)
                ->where('fuente_id', $this->fuenteActivaId)
                ->where('tipo_documento_tutor_id', $tipo['id'])
                ->pluck('pagina')
                ->sort()
                ->values()
                ->all();
            $this->rangos[$tipo['id']] = implode(',', $numeros);
        }
    }

    protected function actualizarConteos(): void
    {
        $this->paginasSinClasificar = collect($this->paginas)->whereNull('tipo_documento_tutor_id')->count();
    }

    protected function indicePagina(string $clave): ?int
    {
        $indice = collect($this->paginas)->search(fn (array $pagina): bool => $pagina['clave'] === $clave);
        return $indice === false ? null : (int) $indice;
    }

    protected function paginaPorClave(string $clave): ?array
    {
        return collect($this->paginas)->firstWhere('clave', $clave);
    }

    protected function tutor(): Tutor
    {
        abort_unless($this->tutorId, 422, 'Selecciona un responsable.');

        return Tutor::query()->findOrFail($this->tutorId);
    }

    protected function autorizar(): void
    {
        abort_unless(
            auth()->user()?->is_admin
                || auth()->user()?->canAccess('documentos.organizar')
                || auth()->user()?->canAccess('alumnos.editar'),
            403,
            'No tienes permiso para organizar expedientes de responsables.'
        );
    }

    public function render()
    {
        return view('livewire.tutor.organizador-paginas-tutor');
    }
}
