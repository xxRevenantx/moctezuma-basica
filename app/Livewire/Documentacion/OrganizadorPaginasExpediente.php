<?php

namespace App\Livewire\Documentacion;

use App\Models\Inscripcion;
use App\Models\TipoDocumento;
use App\Services\Expedientes\OrganizadorExpedienteService;
use App\Support\Documentos\RangoPaginas;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class OrganizadorPaginasExpediente extends Component
{
    public bool $abierto = false;
    public int $inscripcionId;
    public ?int $organizacionId = null;
    public ?int $fuenteActivaId = null;

    public array $fuentes = [];
    public array $paginas = [];
    public array $tipos = [];
    public array $niveles = [];
    public array $grados = [];
    public array $grupos = [];
    public array $ciclos = [];
    public array $rangos = [];
    public array $contextosRapidos = [];
    public array $historial = [];
    public array $contextosExistentes = [];
    public array $retirosConfirmados = [];

    public int $paginasSinClasificar = 0;
    public string $mensaje = '';

    public function mount(int $inscripcionId, array $niveles = [], array $grados = [], array $grupos = [], array $ciclos = []): void
    {
        $this->inscripcionId = $inscripcionId;
        $this->niveles = $niveles;
        $this->grados = $grados;
        $this->grupos = $grupos;
        $this->ciclos = $ciclos;
    }

    #[On('abrir-organizador-expediente')]
    public function abrir(int $inscripcionId, ?int $fuenteId = null): void
    {
        if ($inscripcionId !== $this->inscripcionId) {
            return;
        }

        $this->autorizar();
        $alumno = $this->alumno();
        abort_if($alumno->expedienteSoloLectura(), 422, 'El expediente es únicamente histórico y no puede reorganizarse.');

        $this->resetErrorBag();
        $this->mensaje = '';
        $this->cargarDatos($fuenteId);
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->persistirBorrador('Borrador guardado.');
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

        if (! $tipo) {
            $this->paginas[$indice] = array_merge($this->paginas[$indice], $this->camposVacios(), ['orden' => 0]);
            $this->persistirBorrador('La página quedó sin clasificar.');
            return;
        }

        $contexto = $this->contextoInicialParaPagina($this->paginas[$indice], $tipo);
        $this->paginas[$indice] = array_merge(
            $this->paginas[$indice],
            $contexto,
            ['orden' => $this->siguienteOrden($contexto['contexto_clave'], $clave)]
        );
        $this->persistirBorrador('Asignación actualizada.');
    }

    public function actualizarContexto(string $clave, string $campo, $valor): void
    {
        $this->autorizar();
        if (! in_array($campo, ['nivel_id', 'grado_id', 'grupo_id', 'ciclo_escolar_id'], true)) {
            return;
        }

        $indice = $this->indicePagina($clave);
        if ($indice === null || ! ($this->paginas[$indice]['tipo_documento_id'] ?? null)) {
            return;
        }

        $this->paginas[$indice][$campo] = filled($valor) ? (int) $valor : null;

        if ($campo === 'nivel_id') {
            $this->paginas[$indice]['grado_id'] = null;
            $this->paginas[$indice]['grupo_id'] = null;
        } elseif ($campo === 'grado_id') {
            $this->paginas[$indice]['grupo_id'] = null;
        }

        $this->recalcularClaveContexto($indice);
        $this->normalizarOrdenes();
        $this->persistirBorrador('Contexto académico actualizado.');
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
        $this->paginas[$indice]['preview_url'] = route('misrutas.expedientes.fuentes.page', [
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
        if (! $pagina || ! ($pagina['contexto_clave'] ?? null)) {
            return;
        }

        $ordenadas = collect($this->paginas)
            ->where('contexto_clave', $pagina['contexto_clave'])
            ->sortBy('orden')
            ->values();
        $posicion = $ordenadas->search(fn (array $item): bool => $item['clave'] === $clave);
        $destino = $direccion === 'arriba' ? $posicion - 1 : $posicion + 1;

        if ($posicion === false || ! isset($ordenadas[$destino])) {
            return;
        }

        $this->intercambiarOrdenes($clave, $ordenadas[$destino]['clave']);
        $this->persistirBorrador('Orden actualizado.');
    }

    public function reordenarPagina(?string $claveOrigen, string $claveDestino): void
    {
        $this->autorizar();
        if (! $claveOrigen || $claveOrigen === $claveDestino) {
            return;
        }

        $origen = $this->paginaPorClave($claveOrigen);
        $destino = $this->paginaPorClave($claveDestino);
        if (! $origen || ! $destino || ! $origen['contexto_clave'] || $origen['contexto_clave'] !== $destino['contexto_clave']) {
            return;
        }

        $claves = collect($this->paginas)
            ->where('contexto_clave', $origen['contexto_clave'])
            ->sortBy('orden')
            ->pluck('clave')
            ->values()
            ->all();
        $claves = array_values(array_filter($claves, fn (string $clave): bool => $clave !== $claveOrigen));
        $posicion = array_search($claveDestino, $claves, true);
        if ($posicion === false) {
            return;
        }
        array_splice($claves, $posicion, 0, [$claveOrigen]);

        foreach ($claves as $orden => $clave) {
            $indice = $this->indicePagina($clave);
            if ($indice !== null) {
                $this->paginas[$indice]['orden'] = $orden + 1;
            }
        }

        $this->persistirBorrador('Orden actualizado mediante arrastre.');
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
                $paginas = RangoPaginas::interpretar((string) ($this->rangos[$tipoId] ?? ''), (int) $fuente['paginas']);

                foreach ($paginas as $pagina) {
                    if (isset($asignadas[$pagina])) {
                        throw ValidationException::withMessages([
                            'rangos' => "La página {$pagina} está repetida en {$asignadas[$pagina]} y {$tipo['nombre']}.",
                        ]);
                    }
                    $asignadas[$pagina] = $tipo['nombre'];
                }

                $porTipo[$tipoId] = $paginas;
            }

            foreach ($this->paginas as $indice => $paginaActual) {
                if ((int) $paginaActual['fuente_id'] === (int) $this->fuenteActivaId) {
                    $this->paginas[$indice] = array_merge($paginaActual, $this->camposVacios(), ['orden' => 0]);
                }
            }

            foreach ($porTipo as $tipoId => $paginas) {
                if ($paginas === []) {
                    continue;
                }
                $tipo = collect($this->tipos)->firstWhere('id', $tipoId);
                $contextoRapido = array_merge(
                    $this->contextoRapidoPredeterminado($fuente, $tipo),
                    $this->contextosRapidos[$tipoId] ?? []
                );
                $contexto = $this->construirContexto($tipo, $contextoRapido);
                $orden = $this->siguienteOrden($contexto['contexto_clave']);

                foreach ($paginas as $numero) {
                    $indice = $this->indicePagina($this->fuenteActivaId . ':' . $numero);
                    if ($indice !== null) {
                        $this->paginas[$indice] = array_merge($this->paginas[$indice], $contexto, ['orden' => $orden++]);
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

    public function confirmarRetiro(string $contextoClave): void
    {
        $this->autorizar();
        if (! collect($this->contextosExistentes)->contains('clave', $contextoClave)) {
            return;
        }

        $this->retirosConfirmados = collect($this->retirosConfirmados)
            ->push($contextoClave)
            ->unique()
            ->values()
            ->all();

        foreach ($this->paginas as $indice => $pagina) {
            if (($pagina['contexto_clave'] ?? null) === $contextoClave) {
                $this->paginas[$indice] = array_merge($pagina, $this->camposVacios(), ['orden' => 0]);
            }
        }
        $this->persistirBorrador('Retiro del documento confirmado.');
    }

    public function cancelarRetiro(string $contextoClave): void
    {
        $this->retirosConfirmados = collect($this->retirosConfirmados)
            ->reject(fn (string $clave): bool => $clave === $contextoClave)
            ->values()
            ->all();
        $this->persistirBorrador('Retiro cancelado.');
    }

    public function confirmar(): void
    {
        $this->autorizar();
        $this->resetErrorBag();

        try {
            $alumno = $this->alumno();
            $service = app(OrganizadorExpedienteService::class);
            $borrador = $service->guardarBorrador(
                $alumno,
                $this->asignacionesParaGuardar(),
                auth()->id(),
                $this->organizacionId,
                $this->retirosConfirmados
            );
            $resultado = $service->confirmarOrganizacion($alumno, $borrador->id, auth()->id());

            $this->abierto = false;
            $this->dispatch('organizacion-expediente-confirmada', inscripcionId: $this->inscripcionId);
            $this->dispatch('documento-guardado');
            $this->dispatch(
                'notify',
                type: 'success',
                message: $resultado['encolado']
                    ? 'La organización se está procesando mediante colas. Aparecerá en el historial cuando termine.'
                    : 'La organización fue confirmada y los documentos quedaron generados.'
            );
        } catch (ValidationException $e) {
            $this->addError('organizacion', $e->validator->errors()->first());
        } catch (Throwable $e) {
            report($e);
            $this->addError('organizacion', app()->environment('local') ? $e->getMessage() : 'No fue posible confirmar la organización.');
        }
    }

    protected function cargarDatos(?int $fuentePreferida = null): void
    {
        $datos = app(OrganizadorExpedienteService::class)->datosOrganizador($this->alumno(), auth()->id());
        $this->organizacionId = $datos['organizacion']->id;
        $this->retirosConfirmados = $datos['organizacion']->retiros_confirmados ?? [];
        $this->tipos = $datos['tipos']->map(fn (TipoDocumento $tipo): array => [
            'id' => $tipo->id,
            'nombre' => $tipo->nombre,
            'slug' => $tipo->slug,
            'requiere_nivel' => $tipo->requiere_nivel,
            'requiere_grado_ciclo' => in_array($tipo->slug, [
                'boleta-final-grado', 'constancia-estudios', 'constancia-baja-traslado', 'constancia-traslado-calificaciones',
            ], true),
        ])->values()->all();
        $this->fuentes = $datos['fuentes']->map(function ($fuente): array {
            return [
                'id' => $fuente->id,
                'nombre' => $fuente->nombre_original,
                'paginas' => $fuente->paginas,
                'tamano' => $fuente->tamano_legible,
                'fecha' => $fuente->created_at?->format('d/m/Y H:i'),
                'contexto' => (array) data_get($fuente->metadatos, 'contexto', []),
                'original_url' => route('misrutas.expedientes.fuentes.download', $fuente),
            ];
        })->values()->all();
        $this->paginas = collect($datos['asignaciones'])->map(function (array $pagina): array {
            $fuente = collect($this->fuentes)->firstWhere('id', (int) $pagina['fuente_id']);
            $clave = $pagina['fuente_id'] . ':' . $pagina['pagina'];

            return array_merge($pagina, [
                'clave' => $clave,
                'fuente_nombre' => $fuente['nombre'] ?? 'Archivo fuente',
                'preview_url' => route('misrutas.expedientes.fuentes.page', [
                    'fuente' => $pagina['fuente_id'],
                    'pagina' => $pagina['pagina'],
                    'rotacion' => $pagina['rotacion'] ?? 0,
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
        $this->contextosExistentes = $datos['contextos_existentes'];
        $this->fuenteActivaId = $fuentePreferida && collect($this->fuentes)->contains('id', $fuentePreferida)
            ? $fuentePreferida
            : data_get($this->fuentes, '0.id');
        $this->reiniciarRangos();
        $this->actualizarRangosDesdeFuente();
        $this->actualizarConteos();
    }

    protected function persistirBorrador(string $mensaje): void
    {
        if (! $this->organizacionId) {
            return;
        }

        try {
            $borrador = app(OrganizadorExpedienteService::class)->guardarBorrador(
                $this->alumno(),
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
            'fuente_id', 'pagina', 'tipo_documento_id', 'tipo_slug', 'tipo_nombre', 'contexto_clave',
            'nivel_id', 'grado_id', 'grupo_id', 'ciclo_escolar_id', 'fecha_documento', 'folio',
            'origen', 'tipo_movimiento', 'motivo', 'observaciones', 'orden', 'rotacion',
        ])->all())->values()->all();
    }

    protected function contextoInicialParaPagina(array $pagina, array $tipo): array
    {
        $fuente = collect($this->fuentes)->firstWhere('id', (int) $pagina['fuente_id']);
        return $this->construirContexto($tipo, $this->contextoRapidoPredeterminado($fuente, $tipo));
    }

    protected function contextoRapidoPredeterminado(?array $fuente, array $tipo): array
    {
        $base = (array) ($fuente['contexto'] ?? []);
        $alumno = $this->alumno();

        if (($tipo['slug'] ?? '') === 'certificado-estudios' && empty($base['nivel_id'])) {
            $nivelEsperado = app(\App\Services\ExpedienteDigitalService::class)->nivelCertificadoRequerido($alumno->nivel);
            $base['nivel_id'] = $nivelEsperado?->id;
        }

        if (($tipo['requiere_nivel'] ?? false) && empty($base['nivel_id'])) {
            $base['nivel_id'] = $alumno->nivel_id;
        }

        if (($tipo['requiere_grado_ciclo'] ?? false)) {
            $base['grado_id'] ??= $alumno->grado_id;
            $base['grupo_id'] ??= $alumno->grupo_id;
            $base['ciclo_escolar_id'] ??= data_get($this->ciclos, '0.id');
        }

        return $base;
    }

    protected function construirContexto(array $tipo, array $base): array
    {
        $contexto = [
            'tipo_documento_id' => (int) $tipo['id'],
            'tipo_slug' => $tipo['slug'],
            'tipo_nombre' => $tipo['nombre'],
            'nivel_id' => filled($base['nivel_id'] ?? null) ? (int) $base['nivel_id'] : null,
            'grado_id' => filled($base['grado_id'] ?? null) ? (int) $base['grado_id'] : null,
            'grupo_id' => filled($base['grupo_id'] ?? null) ? (int) $base['grupo_id'] : null,
            'ciclo_escolar_id' => filled($base['ciclo_escolar_id'] ?? null) ? (int) $base['ciclo_escolar_id'] : null,
            'fecha_documento' => $base['fecha_documento'] ?? now()->toDateString(),
            'folio' => $base['folio'] ?? null,
            'origen' => $base['origen'] ?? 'subido',
            'tipo_movimiento' => $base['tipo_movimiento'] ?? null,
            'motivo' => $base['motivo'] ?? null,
            'observaciones' => $base['observaciones'] ?? null,
        ];
        $contexto['contexto_clave'] = implode('|', [
            $contexto['tipo_documento_id'],
            (int) ($contexto['nivel_id'] ?? 0),
            (int) ($contexto['grado_id'] ?? 0),
            (int) ($contexto['grupo_id'] ?? 0),
            (int) ($contexto['ciclo_escolar_id'] ?? 0),
        ]);

        return $contexto;
    }

    protected function recalcularClaveContexto(int $indice): void
    {
        $pagina = $this->paginas[$indice];
        $this->paginas[$indice]['contexto_clave'] = implode('|', [
            (int) ($pagina['tipo_documento_id'] ?? 0),
            (int) ($pagina['nivel_id'] ?? 0),
            (int) ($pagina['grado_id'] ?? 0),
            (int) ($pagina['grupo_id'] ?? 0),
            (int) ($pagina['ciclo_escolar_id'] ?? 0),
        ]);
    }

    protected function camposVacios(): array
    {
        return [
            'tipo_documento_id' => null, 'tipo_slug' => null, 'tipo_nombre' => null,
            'contexto_clave' => null, 'nivel_id' => null, 'grado_id' => null,
            'grupo_id' => null, 'ciclo_escolar_id' => null, 'fecha_documento' => null,
            'folio' => null, 'origen' => null, 'tipo_movimiento' => null,
            'motivo' => null, 'observaciones' => null,
        ];
    }

    protected function siguienteOrden(string $contextoClave, ?string $excluir = null): int
    {
        return ((int) collect($this->paginas)
            ->where('contexto_clave', $contextoClave)
            ->reject(fn (array $pagina): bool => $excluir && $pagina['clave'] === $excluir)
            ->max('orden')) + 1;
    }

    protected function normalizarOrdenes(): void
    {
        foreach (collect($this->paginas)->whereNotNull('contexto_clave')->groupBy('contexto_clave') as $grupo) {
            foreach ($grupo->sortBy('orden')->values() as $orden => $pagina) {
                $indice = $this->indicePagina($pagina['clave']);
                if ($indice !== null) {
                    $this->paginas[$indice]['orden'] = $orden + 1;
                }
            }
        }
    }

    protected function reiniciarRangos(): void
    {
        $this->rangos = collect($this->tipos)->mapWithKeys(fn (array $tipo): array => [$tipo['id'] => ''])->all();
        $this->contextosRapidos = collect($this->tipos)->mapWithKeys(fn (array $tipo): array => [$tipo['id'] => [
            'nivel_id' => null, 'grado_id' => null, 'grupo_id' => null, 'ciclo_escolar_id' => null,
        ]])->all();
    }

    protected function actualizarRangosDesdeFuente(): void
    {
        if (! $this->fuenteActivaId) {
            return;
        }

        foreach ($this->tipos as $tipo) {
            $paginas = collect($this->paginas)
                ->where('fuente_id', $this->fuenteActivaId)
                ->where('tipo_documento_id', $tipo['id'])
                ->pluck('pagina')
                ->sort()
                ->values()
                ->all();
            $this->rangos[$tipo['id']] = implode(',', $paginas);
        }
    }

    protected function actualizarConteos(): void
    {
        $this->paginasSinClasificar = collect($this->paginas)->whereNull('tipo_documento_id')->count();
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

    protected function intercambiarOrdenes(string $claveA, string $claveB): void
    {
        $indiceA = $this->indicePagina($claveA);
        $indiceB = $this->indicePagina($claveB);
        if ($indiceA === null || $indiceB === null) {
            return;
        }
        [$this->paginas[$indiceA]['orden'], $this->paginas[$indiceB]['orden']] = [
            $this->paginas[$indiceB]['orden'], $this->paginas[$indiceA]['orden'],
        ];
    }

    protected function alumno(): Inscripcion
    {
        return Inscripcion::withTrashed()->with('nivel:id,nombre,slug')->findOrFail($this->inscripcionId);
    }

    protected function autorizar(): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('documentos.organizar'), 403, 'No tienes permiso para organizar expedientes.');
    }

    public function render()
    {
        return view('livewire.documentacion.organizador-paginas-expediente');
    }
}
