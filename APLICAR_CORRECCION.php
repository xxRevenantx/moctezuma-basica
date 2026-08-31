<?php

declare(strict_types=1);

/**
 * Moctezuma Básica — Corrección "Personal disponible desde Plantilla"
 *
 * Ejecutar desde la raíz del proyecto:
 *   php APLICAR_CORRECCION.php
 *
 * El script:
 * - usa la Plantilla del ciclo como fuente de verdad;
 * - elimina el filtrado por fechas/vigencias en Reanudaciones;
 * - mantiene las fechas únicamente para imprimir los oficios;
 * - permite ver personal aunque la plantilla esté en borrador/revisión;
 * - consolida funciones/grados/grupos por persona+nivel;
 * - reactiva una membresía de plantilla si una persona dada de baja se vuelve a asignar;
 * - crea respaldos .bak-personal-disponible antes de escribir;
 * - ejecuta php -l en los PHP modificados y revierte si detecta error de sintaxis.
 */

$root = getcwd();
if ($root === false) {
    fwrite(STDERR, "No fue posible determinar la carpeta actual.\n");
    exit(1);
}

$files = [
    'app/Services/ReanudacionesService.php',
    'app/Services/PlantillaPersonalCicloService.php',
    'app/Livewire/PersonaNivel/Reanudaciones.php',
    'resources/views/livewire/persona-nivel/reanudaciones.blade.php',
];

foreach ($files as $relative) {
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        fwrite(STDERR, "No encontré el archivo requerido: {$relative}\n");
        fwrite(STDERR, "Ejecuta este script desde la raíz de moctezuma-basica.\n");
        exit(1);
    }
}

/** @var array<string,string> $originals */
$originals = [];
/** @var array<string,string> $lineEndings */
$lineEndings = [];

function normalizedRead(string $path, array &$originals, array &$lineEndings, string $relative): string
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("No fue posible leer {$relative}");
    }

    $originals[$relative] = $raw;
    $lineEndings[$relative] = str_contains($raw, "\r\n") ? "\r\n" : "\n";

    return str_replace("\r\n", "\n", $raw);
}

function replaceOnce(string $content, string $old, string $new, string $label): string
{
    $count = substr_count($content, $old);
    if ($count !== 1) {
        throw new RuntimeException(
            "No pude aplicar '{$label}': se esperaba 1 coincidencia exacta y se encontraron {$count}. " .
            "El proyecto puede ser de una versión distinta. No se modificó nada."
        );
    }

    return str_replace($old, $new, $content);
}

function replaceBetween(string $content, string $startMarker, string $endMarker, string $replacement, string $label): string
{
    $start = strpos($content, $startMarker);
    if ($start === false) {
        throw new RuntimeException("No pude localizar el inicio de '{$label}'. No se modificó nada.");
    }

    $end = strpos($content, $endMarker, $start + strlen($startMarker));
    if ($end === false) {
        throw new RuntimeException("No pude localizar el final de '{$label}'. No se modificó nada.");
    }

    return substr($content, 0, $start) . $replacement . substr($content, $end);
}

function writeFilePreservingEol(string $path, string $content, string $eol): void
{
    if ($eol === "\r\n") {
        $content = str_replace("\n", "\r\n", $content);
    }

    if (file_put_contents($path, $content) === false) {
        throw new RuntimeException("No fue posible escribir {$path}");
    }
}

function backupOriginal(string $path, string $raw): string
{
    $backup = $path . '.bak-personal-disponible';
    if (!is_file($backup)) {
        if (file_put_contents($backup, $raw) === false) {
            throw new RuntimeException("No fue posible crear el respaldo {$backup}");
        }
    }

    return $backup;
}

function lintPhp(string $path): array
{
    $cmd = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($path) . ' 2>&1';
    $lines = [];
    $code = 0;
    exec($cmd, $lines, $code);

    return [$code === 0, implode("\n", $lines)];
}

try {
    // ---------------------------------------------------------------------
    // 1) ReanudacionesService.php
    // ---------------------------------------------------------------------
    $relative = 'app/Services/ReanudacionesService.php';
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $content = normalizedRead($path, $originals, $lineEndings, $relative);

    $content = replaceOnce(
        $content,
        "use App\\Models\\PersonaNivel;\nuse App\\Models\\PersonaNivelDetalle;",
        "use App\\Models\\PersonaNivel;\nuse App\\Models\\PersonaNivelCiclo;\nuse App\\Models\\PersonaNivelDetalle;",
        'importar PersonaNivelCiclo en ReanudacionesService'
    );

    $methodStart = <<<'OLD'
    /**
     * Obtiene la plantilla vigente en una fecha del ciclo seleccionado.
OLD;

    $methodEnd = <<<'END'
    /**
     * Devuelve el mismo orden visible en "Plantilla de personal por nivel".
END;

    $newMethod = <<<'NEW'
    /**
     * Obtiene el personal que pertenece a la plantilla del ciclo seleccionado.
     *
     * La fecha de reanudación NO participa en esta consulta. Se conserva
     * $fechaReferencia como parámetro opcional únicamente por compatibilidad
     * con llamadas anteriores; las fechas se usan para imprimir los oficios.
     *
     * La fuente de verdad es:
     * - plantillas_personal_nivel: ciclo + nivel;
     * - persona_nivel_ciclos: membresía ACTIVA dentro de esa plantilla;
     * - persona_nivel_detalles: funciones ACTIVAS y no archivadas.
     *
     * Retorna una fila única por persona y nivel. Si una persona tiene varias
     * funciones, grados o grupos dentro de la misma plantilla, se consolidan.
     *
     * @param array<int, int|string> $niveles
     * @return array{listos: Collection<int, array<string,mixed>>, advertencias: Collection<int, array<string,mixed>>, excluidos: Collection<int, array<string,mixed>>}
     */
    public function plantilla(
        CicloEscolar $ciclo,
        ?string $fechaReferencia = null,
        array $niveles = [],
        ?int $gradoId = null,
        ?int $grupoId = null,
        ?int $rolId = null,
        string $busqueda = ''
    ): array {
        $niveles = collect($niveles)
            ->map(fn($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        // No se exige publicada/cerrada: si existe una plantilla del ciclo,
        // el personal asignado debe reflejarse inmediatamente en Reanudaciones.
        $plantillas = PlantillaPersonalNivel::query()
            ->where('ciclo_escolar_id', $ciclo->id)
            ->when($niveles->isNotEmpty(), fn (Builder $q) => $q->whereIn('nivel_id', $niveles))
            ->get(['id', 'nivel_id']);

        $plantillaIds = $plantillas
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($plantillaIds === []) {
            return [
                'listos' => collect(),
                'advertencias' => collect(),
                'excluidos' => collect(),
            ];
        }

        $query = PersonaNivel::query()
            ->with([
                'persona.personaRoles.rolePersona',
                'nivel.director',
                'nivel.supervisor',
                'ciclos' => fn (Relation $membresia) => $membresia
                    ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                    ->where('estado', PersonaNivelCiclo::ESTADO_ACTIVO)
                    ->orderBy('orden'),
                'detalles' => fn (Relation $detalle) => $detalle
                    ->whereIn('persona_nivel_ciclo_id', function ($sub) use ($plantillaIds) {
                        $sub->select('id')
                            ->from('persona_nivel_ciclos')
                            ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                            ->where('estado', PersonaNivelCiclo::ESTADO_ACTIVO);
                    })
                    ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
                    ->whereNull('archivado_at')
                    ->with([
                        'personaRole.rolePersona',
                        'grado:id,nombre,nivel_id,orden',
                        'grupo:id,asignacion_grupo_id,nivel_id,grado_id,ciclo_escolar_id',
                        'grupo.asignacionGrupo:id,nombre',
                    ])
                    ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('orden')
                    ->orderBy('id'),
            ])
            ->whereHas('ciclos', function (Builder $membresia) use ($plantillaIds) {
                $membresia
                    ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                    ->where('estado', PersonaNivelCiclo::ESTADO_ACTIVO);
            })
            ->when($niveles->isNotEmpty(), fn (Builder $q) => $q->whereIn('nivel_id', $niveles))
            ->whereHas('detalles', function (Builder $detalle) use ($gradoId, $grupoId, $rolId, $plantillaIds) {
                $detalle
                    ->whereIn('persona_nivel_ciclo_id', function ($sub) use ($plantillaIds) {
                        $sub->select('id')
                            ->from('persona_nivel_ciclos')
                            ->whereIn('plantilla_personal_nivel_id', $plantillaIds)
                            ->where('estado', PersonaNivelCiclo::ESTADO_ACTIVO);
                    })
                    ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
                    ->whereNull('archivado_at')
                    ->when($gradoId, fn (Builder $q) => $q->where('grado_id', $gradoId))
                    ->when($grupoId, fn (Builder $q) => $q->where('grupo_id', $grupoId))
                    ->when($rolId, fn (Builder $q) => $q->where('persona_role_id', $rolId));
            })
            ->when(trim($busqueda) !== '', function (Builder $q) use ($busqueda) {
                $termino = '%' . trim($busqueda) . '%';
                $q->whereHas('persona', function (Builder $persona) use ($termino) {
                    $persona->where('nombre', 'like', $termino)
                        ->orWhere('apellido_paterno', 'like', $termino)
                        ->orWhere('apellido_materno', 'like', $termino)
                        ->orWhereRaw("CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno) LIKE ?", [$termino])
                        ->orWhereRaw("CONCAT_WS(' ', apellido_paterno, apellido_materno, nombre) LIKE ?", [$termino]);
                });
            })
            ->orderBy('nivel_id')
            ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $listos = collect();
        $advertencias = collect();
        $excluidos = collect();

        $query->groupBy(fn (PersonaNivel $item) => $item->persona_id . ':' . $item->nivel_id)
            ->each(function (Collection $duplicados) use ($listos, $advertencias, $excluidos) {
                /** @var PersonaNivel|null $principal */
                $principal = $duplicados->sortBy(fn (PersonaNivel $item) => [
                    $this->ordenPlantilla($item),
                    is_null($item->orden) ? 1 : 0,
                    (int) ($item->orden ?? PHP_INT_MAX),
                    (int) $item->id,
                ])->first();

                if (!$principal?->persona || trim($this->nombrePersona($principal->persona, false)) === '') {
                    $excluidos->push([
                        'motivo' => 'Registro sin nombre completo.',
                        'modelo' => $principal,
                    ]);
                    return;
                }

                $detalles = $duplicados
                    ->flatMap(fn (PersonaNivel $item) => $item->detalles)
                    ->unique('id')
                    ->sortBy(fn (PersonaNivelDetalle $detalle) => [
                        is_null($detalle->orden) ? 1 : 0,
                        (int) ($detalle->orden ?? PHP_INT_MAX),
                        (int) $detalle->id,
                    ])
                    ->values();

                if ($detalles->isEmpty() || $this->cargos($detalles)->isEmpty()) {
                    $excluidos->push([
                        'motivo' => 'No tiene una función válida en la plantilla del ciclo seleccionado.',
                        'modelo' => $principal,
                    ]);
                    return;
                }

                $principal->setRelation('detalles', $detalles);
                $fila = $this->filaPlantilla($principal);

                if ($duplicados->count() > 1) {
                    $fila['advertencias'][] = 'Se combinaron ' . $duplicados->count() . ' asignaciones generales duplicadas del mismo nivel.';
                }

                if (($fila['advertencias'] ?? []) !== []) {
                    $advertencias->push($fila);
                } else {
                    $listos->push($fila);
                }
            });

        $ordenar = fn (Collection $filas) => $filas
            ->sortBy(fn (array $fila) => [
                (int) ($fila['nivel_id'] ?? PHP_INT_MAX),
                (int) ($fila['orden_plantilla'] ?? PHP_INT_MAX),
                (int) ($fila['id'] ?? PHP_INT_MAX),
            ])
            ->values();

        $listos = $ordenar($listos);
        $advertencias = $ordenar($advertencias);

        return compact('listos', 'advertencias', 'excluidos');
    }

NEW;

    $content = replaceBetween(
        $content,
        $methodStart,
        $methodEnd,
        $newMethod,
        'método ReanudacionesService::plantilla'
    );

    $content = replaceOnce(
        $content,
        "            'advertencias' => [],\n",
        "            'advertencias' => \$detalles->contains(fn (PersonaNivelDetalle \$detalle) => ! \$detalle->confirmado)\n                ? ['Tiene una o más funciones pendientes de confirmar en la plantilla.']\n                : [],\n",
        'advertencia de funciones pendientes en filaPlantilla'
    );

    $content = replaceOnce(
        $content,
        "        \$resultado = \$this->plantilla(\n            ciclo: \$ciclo,\n            fechaReferencia: \$fechaDocente,\n        );",
        "        \$resultado = \$this->plantilla(\n            ciclo: \$ciclo,\n        );",
        'validación de documentos sin fecha de vigencia'
    );

    $content = replaceOnce(
        $content,
        "                'seleccionados' => 'Una o más asignaciones ya no están vigentes para la fecha y ciclo seleccionados. Actualiza la lista.',",
        "                'seleccionados' => 'Una o más asignaciones ya no pertenecen a la plantilla del ciclo seleccionado. Actualiza la lista.',",
        'mensaje de asignación fuera de plantilla'
    );

    $modified = [$relative => $content];

    // ---------------------------------------------------------------------
    // 2) PlantillaPersonalCicloService.php
    // ---------------------------------------------------------------------
    $relative = 'app/Services/PlantillaPersonalCicloService.php';
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $content = normalizedRead($path, $originals, $lineEndings, $relative);

    $oldMembership = <<<'OLD'
    public function membresia(PlantillaPersonalNivel $plantilla, PersonaNivel $personaNivel): PersonaNivelCiclo
    {
        $this->asegurarEditable($plantilla);
        $plantilla->loadMissing('cicloEscolar');

        return PersonaNivelCiclo::query()->firstOrCreate([
            'plantilla_personal_nivel_id' => $plantilla->id,
            'persona_nivel_id' => $personaNivel->id,
        ], [
            'estado' => PersonaNivelCiclo::ESTADO_ACTIVO,
            'orden' => ((int) $plantilla->membresias()->max('orden')) + 1,
            'fecha_inicio' => $plantilla->cicloEscolar?->inicio_anio . '-07-01',
        ]);
    }
OLD;

    $newMembership = <<<'NEW'
    public function membresia(PlantillaPersonalNivel $plantilla, PersonaNivel $personaNivel): PersonaNivelCiclo
    {
        $this->asegurarEditable($plantilla);
        $plantilla->loadMissing('cicloEscolar');

        $membresia = PersonaNivelCiclo::query()->firstOrCreate([
            'plantilla_personal_nivel_id' => $plantilla->id,
            'persona_nivel_id' => $personaNivel->id,
        ], [
            'estado' => PersonaNivelCiclo::ESTADO_ACTIVO,
            'orden' => ((int) $plantilla->membresias()->max('orden')) + 1,
            'fecha_inicio' => $plantilla->cicloEscolar?->inicio_anio . '-07-01',
        ]);

        // Si la persona estuvo antes en la misma plantilla y fue dada de baja,
        // al volver a asignarle una función debe regresar inmediatamente a la
        // plantilla activa. Las fechas históricas dejan de gobernar la visibilidad.
        if ($membresia->estado !== PersonaNivelCiclo::ESTADO_ACTIVO
            || $membresia->fecha_fin
            || $membresia->fecha_baja
            || filled($membresia->motivo_baja)) {
            $membresia->forceFill([
                'estado' => PersonaNivelCiclo::ESTADO_ACTIVO,
                'fecha_fin' => null,
                'fecha_baja' => null,
                'motivo_baja' => null,
            ])->save();
        }

        return $membresia;
    }
NEW;

    $content = replaceOnce(
        $content,
        $oldMembership,
        $newMembership,
        'reactivación de membresía al reasignar personal'
    );
    $modified[$relative] = $content;

    // ---------------------------------------------------------------------
    // 3) Livewire Reanudaciones.php
    // ---------------------------------------------------------------------
    $relative = 'app/Livewire/PersonaNivel/Reanudaciones.php';
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $content = normalizedRead($path, $originals, $lineEndings, $relative);

    $content = replaceOnce(
        $content,
        <<<'OLD'
    public function updatedFechaDocente(): void
    {
        $this->seleccionados = [];
    }

OLD,
        '',
        'evitar limpiar selección al cambiar fecha del oficio'
    );

    $content = replaceOnce(
        $content,
        '// Construir primero valida que todas las asignaciones sigan vigentes.',
        '// Construir primero valida que todas las asignaciones sigan perteneciendo a la plantilla seleccionada.',
        'comentario de previsualización'
    );

    $oldRestoreSelection = <<<'OLD'
        $this->seleccionados = \App\Models\PersonaNivel::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sortBy(fn (int $id) => $ids->search($id))
            ->values()
            ->all();
OLD;

    $newRestoreSelection = <<<'NEW'
        $this->seleccionados = \App\Models\PersonaNivel::query()
            ->whereIn('id', $ids)
            ->whereHas('ciclos', function ($membresia) {
                $membresia
                    ->where('estado', \App\Models\PersonaNivelCiclo::ESTADO_ACTIVO)
                    ->whereHas('plantilla', function ($plantilla) {
                        $plantilla
                            ->where('ciclo_escolar_id', (int) $this->cicloEscolarId)
                            ->when(
                                $this->nivelesSeleccionados !== [],
                                fn ($q) => $q->whereIn('nivel_id', array_map('intval', $this->nivelesSeleccionados))
                            );
                    });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sortBy(fn (int $id) => $ids->search($id))
            ->values()
            ->all();
NEW;

    $content = replaceOnce(
        $content,
        $oldRestoreSelection,
        $newRestoreSelection,
        'depurar selección restaurada contra la plantilla actual'
    );

    $content = replaceOnce(
        $content,
        "                fechaReferencia: \$this->fechaDocente ?: now()->toDateString(),\n",
        '',
        'quitar fechaReferencia del render'
    );

    $oldTemplateWarning = <<<'OLD'
        $nivelesSinPlantillaPublicada = $niveles
            ->whereIn('id', array_map('intval', $this->nivelesSeleccionados))
            ->filter(function (Nivel $nivel) use ($plantillasDocumento) {
                $plantilla = $plantillasDocumento->get($nivel->id);

                return !$plantilla || !in_array($plantilla->estado, [
                    PlantillaPersonalNivel::ESTADO_PUBLICADA,
                    PlantillaPersonalNivel::ESTADO_CERRADA,
                ], true);
            })
            ->pluck('nombre')
            ->values();
OLD;

    $newTemplateWarning = <<<'NEW'
        $nivelesSinPlantilla = $niveles
            ->whereIn('id', array_map('intval', $this->nivelesSeleccionados))
            ->filter(fn (Nivel $nivel) => !$plantillasDocumento->has($nivel->id))
            ->pluck('nombre')
            ->values();
NEW;

    $content = replaceOnce(
        $content,
        $oldTemplateWarning,
        $newTemplateWarning,
        'permitir plantilla en cualquier estado'
    );

    $content = replaceOnce(
        $content,
        "            'nivelesSinPlantillaPublicada' => \$nivelesSinPlantillaPublicada,",
        "            'nivelesSinPlantilla' => \$nivelesSinPlantilla,",
        'variable niveles sin plantilla'
    );

    $modified[$relative] = $content;

    // ---------------------------------------------------------------------
    // 4) Blade de Reanudaciones
    // ---------------------------------------------------------------------
    $relative = 'resources/views/livewire/persona-nivel/reanudaciones.blade.php';
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    $content = normalizedRead($path, $originals, $lineEndings, $relative);

    $content = replaceOnce(
        $content,
        'Genera oficios individuales o masivos con la plantilla de personal vigente en el ciclo y fecha seleccionados.',
        'Genera oficios individuales o masivos con la plantilla de personal del ciclo y los niveles seleccionados.',
        'texto introductorio sin vigencia'
    );

    $content = replaceOnce(
        $content,
        '<flux:description>Director, directora, subdirección, rectoría o coordinación académica.</flux:description>',
        '<flux:description>Fecha que se imprimirá para el personal directivo. No filtra la plantilla.</flux:description>',
        'descripción fecha directivos'
    );

    $content = replaceOnce(
        $content,
        '<flux:description>También determina la fotografía histórica de la plantilla.</flux:description>',
        '<flux:description>Fecha que se imprimirá en los oficios del personal. No filtra quién aparece en la plantilla.</flux:description>',
        'descripción fecha personal'
    );

    $content = replaceOnce(
        $content,
        '@if ($nivelesSinPlantillaPublicada->isNotEmpty())',
        '@if ($nivelesSinPlantilla->isNotEmpty())',
        'condición de niveles sin plantilla'
    );

    $content = replaceOnce(
        $content,
        '<p class="font-black">Hay niveles sin plantilla disponible para documentos</p>',
        '<p class="font-black">Hay niveles sin plantilla de personal</p>',
        'título de aviso de plantilla'
    );

    $content = replaceOnce(
        $content,
        '{{ $nivelesSinPlantillaPublicada->implode(\', \') }}. Publica la plantilla del ciclo seleccionado o consulta un ciclo cerrado para generar reanudaciones.',
        '{{ $nivelesSinPlantilla->implode(\', \') }}. Crea la plantilla del ciclo seleccionado para mostrar personal y generar reanudaciones.',
        'mensaje de aviso de plantilla'
    );

    $content = replaceOnce(
        $content,
        '<p class="text-xs text-slate-500">Solo aparecen asignaciones vigentes en la fecha seleccionada. Cupo ilimitado.</p>',
        '<p class="text-xs text-slate-500">Se muestra el personal asignado en la plantilla del ciclo escolar y de los niveles seleccionados.</p>',
        'texto de Personal disponible'
    );

    $content = replaceOnce(
        $content,
        "                        <th class=\"px-4 py-3 text-left text-[11px] font-black uppercase tracking-wide text-slate-500\">Fecha aplicada</th>\n",
        '',
        'quitar columna Fecha aplicada'
    );

    $oldBadge = <<<'OLD'
                                    @if ($fila['es_directivo'])
                                        <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">PERSONAL DIRECTIVO</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">ACTIVO EN LA FECHA</span>
                                    @endif
OLD;

    $newBadge = <<<'NEW'
                                    @if ($fila['es_directivo'])
                                        <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">PERSONAL DIRECTIVO</span>
                                    @endif
NEW;

    $content = replaceOnce(
        $content,
        $oldBadge,
        $newBadge,
        'quitar etiqueta ACTIVO EN LA FECHA'
    );

    $content = replaceOnce(
        $content,
        "                            <td class=\"px-4 py-3 align-top text-xs text-slate-500\">{{ \\Carbon\\Carbon::parse(\$fila['es_directivo'] ? \$fechaDirector : \$fechaDocente)->format('d/m/Y') }}</td>\n",
        '',
        'quitar fecha aplicada por fila'
    );

    $content = replaceOnce(
        $content,
        '<tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No existe personal compatible con los filtros, ciclo y fecha seleccionados.</td></tr>',
        '<tr><td colspan="5" class="px-5 py-14 text-center text-slate-500">No existe personal asignado en la plantilla que coincida con los filtros, ciclo y niveles seleccionados.</td></tr>',
        'mensaje de tabla vacía'
    );

    $modified[$relative] = $content;

    // ---------------------------------------------------------------------
    // Validaciones semánticas antes de escribir
    // ---------------------------------------------------------------------
    $serviceContent = $modified['app/Services/ReanudacionesService.php'];
    $methodPos = strpos($serviceContent, 'public function plantilla(');
    $methodEndPos = strpos($serviceContent, 'private function ordenPlantilla', $methodPos ?: 0);
    $methodBody = ($methodPos !== false && $methodEndPos !== false)
        ? substr($serviceContent, $methodPos, $methodEndPos - $methodPos)
        : '';

    foreach (["whereDate('fecha_inicio'", "whereDate('fecha_fin'", 'aplicarFechaDetalle('] as $forbidden) {
        if (str_contains($methodBody, $forbidden)) {
            throw new RuntimeException("La validación final detectó lógica de vigencia restante en ReanudacionesService::plantilla ({$forbidden}).");
        }
    }

    if (!str_contains($methodBody, "where('estado', PersonaNivelCiclo::ESTADO_ACTIVO)")) {
        throw new RuntimeException('La validación final no encontró la membresía activa como fuente de verdad.');
    }

    $bladeContent = $modified['resources/views/livewire/persona-nivel/reanudaciones.blade.php'];
    foreach (['ACTIVO EN LA FECHA', 'Fecha aplicada', 'Solo aparecen asignaciones vigentes'] as $forbidden) {
        if (str_contains($bladeContent, $forbidden)) {
            throw new RuntimeException("La validación final detectó texto de vigencia restante en la interfaz: {$forbidden}");
        }
    }

    // ---------------------------------------------------------------------
    // Respaldar y escribir
    // ---------------------------------------------------------------------
    foreach ($modified as $relative => $content) {
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        backupOriginal($path, $originals[$relative]);
        writeFilePreservingEol($path, $content, $lineEndings[$relative]);
    }

    // ---------------------------------------------------------------------
    // Lint PHP. Si algo falla, restaurar todo.
    // ---------------------------------------------------------------------
    $phpFiles = [
        'app/Services/ReanudacionesService.php',
        'app/Services/PlantillaPersonalCicloService.php',
        'app/Livewire/PersonaNivel/Reanudaciones.php',
    ];

    foreach ($phpFiles as $relative) {
        $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        [$ok, $output] = lintPhp($path);
        if (!$ok) {
            foreach ($originals as $restoreRelative => $raw) {
                $restorePath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $restoreRelative);
                file_put_contents($restorePath, $raw);
            }
            throw new RuntimeException("PHP detectó un error de sintaxis en {$relative}. Se restauraron los originales.\n{$output}");
        }
    }

    echo "\nCORRECCIÓN APLICADA CORRECTAMENTE\n";
    echo "================================\n";
    echo "Personal disponible ahora se obtiene de la Plantilla del ciclo, no de fechas de vigencia.\n\n";
    echo "Archivos modificados:\n";
    foreach (array_keys($modified) as $relative) {
        echo "  - {$relative}\n";
    }

    echo "\nRespaldos creados junto a cada archivo con sufijo:\n";
    echo "  .bak-personal-disponible\n\n";
    echo "No se requiere migración de base de datos.\n";
    echo "Después ejecuta, si tu entorno lo requiere:\n";
    echo "  php artisan optimize:clear\n";
    echo "\nPrueba esperada: Antonio Perez Guerrero debe aparecer en Personal disponible porque está asignado en la plantilla, sin importar la fecha C.T. o la fecha del oficio.\n";

    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, "\nNO SE APLICÓ LA CORRECCIÓN\n");
    fwrite(STDERR, "==========================\n");
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
