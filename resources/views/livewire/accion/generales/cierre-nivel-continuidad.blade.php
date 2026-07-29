<div class="space-y-6">
    @php
        $coloresResultado = [
            'pendiente' => 'bg-slate-100 text-slate-700',
            'continuidad_interna' => 'bg-sky-100 text-sky-800',
            'no_reinscrito' => 'bg-slate-200 text-slate-800',
            'egresado' => 'bg-violet-100 text-violet-800',
            'traslado' => 'bg-amber-100 text-amber-800',
            'baja_definitiva' => 'bg-rose-100 text-rose-800',
            'no_promovido' => 'bg-orange-100 text-orange-800',
        ];
        $resultados = ['pendiente' => ['Pendiente', $coloresResultado['pendiente']]];
        foreach ($this->resultadosDisponibles as $valor => $etiqueta) {
            $resultados[$valor] = [$etiqueta, $coloresResultado[$valor] ?? 'bg-slate-100 text-slate-700'];
        }
        $conteos = collect($decisiones)->countBy('resultado');
    @endphp

    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-950 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-100">
        Este asistente resuelve en un solo flujo la <b>promoción de grado o semestre</b>, el <b>cierre de nivel</b>,
        la <b>no reinscripción</b>, la repetición, el traslado, la baja y el egreso. Las promociones se guardan
        como proyecciones provisionales; el alumno solo queda activo en el ciclo destino cuando se confirma que regresó.
    </div>

    <div class="grid gap-3 md:grid-cols-5">
        @foreach ([1 => 'Origen', 2 => 'Clasificar', 3 => 'Destinos', 4 => 'Revisar', 5 => 'Confirmar'] as $numero => $titulo)
            <div class="rounded-2xl border p-4 transition {{ $paso === $numero ? 'border-sky-400 bg-sky-50 shadow-sm dark:bg-sky-950/20' : ($paso > $numero ? 'border-emerald-300 bg-emerald-50/60 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-neutral-700') }}">
                <p class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Paso {{ $numero }}</p>
                <p class="mt-1 font-black text-slate-900 dark:text-white">{{ $titulo }}</p>
            </div>
        @endforeach
    </div>

    @if ($paso === 1)
        <div class="space-y-5 rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">1. Selecciona el contexto de origen</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Puedes procesar una generación completa cuando todos estén en el mismo grado o semestre, o elegir un grupo. El sistema detectará automáticamente si corresponde promoción ordinaria, cierre de nivel o egreso terminal.
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <flux:select wire:model.live="ciclo_origen_id" label="Ciclo escolar de origen">
                    <flux:select.option value="">Selecciona</flux:select.option>
                    @foreach ($ciclos as $ciclo)
                        <flux:select.option value="{{ $ciclo->id }}">
                            {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · actual' : ($ciclo->cerrado_at ? ' · cerrado' : '') }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="generacion_id" label="Generación">
                    <flux:select.option value="">Selecciona</flux:select.option>
                    @foreach ($generaciones as $generacion)
                        <flux:select.option value="{{ $generacion->id }}">
                            {{ $generacion->etiqueta }} · {{ $generacion->alumnos_ciclo_count }} alumnos · {{ $generacion->etiqueta_estado_cierre }}
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="grupo_origen_id" label="Alcance">
                    <flux:select.option value="">Toda la generación</flux:select.option>
                    @foreach ($gruposOrigen as $grupo)
                        <flux:select.option value="{{ $grupo->id }}">
                            Solo grupo {{ $grupo->asignacionGrupo?->nombre ?? 'Sin nombre' }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">Nivel</p>
                    <p class="mt-1 text-lg font-black text-slate-900 dark:text-white">{{ $nivel->nombre }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">Detección automática</p>
                    <p class="mt-1 font-black text-slate-900 dark:text-white">Grado intermedio, fin de nivel o egreso terminal</p>
                </div>
                <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                    <p class="text-xs font-black uppercase tracking-wider text-slate-500">Regla histórica</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-300">Nunca se duplica la inscripción del alumno.</p>
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button variant="primary" icon="arrow-right" wire:click="prepararClasificacion" spinner="prepararClasificacion">
                    Cargar alumnos
                </flux:button>
            </div>
        </div>
    @endif

    @if ($paso === 2)
        <div class="space-y-5">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">2. Clasifica cada alumno</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Clasifica qué alumnos se proyectan al siguiente grado o nivel, quién repetirá y quién no continuará. Los registros históricos compatibles pueden recuperar una proyección faltante sin alterar su resultado anterior.
                        </p>
                        <div class="mt-3 inline-flex rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-black text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                            Modo detectado: {{ $this->etiquetaModo }} · Origen: {{ $contexto_origen['grado'] ?? '—' }}{{ filled($contexto_origen['semestre'] ?? null) ? ' · semestre '.$contexto_origen['semestre'] : '' }}
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" wire:click="seleccionarTodosVisibles">Seleccionar visibles</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="limpiarSeleccion">Quitar selección</flux:button>
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-4">
                    <flux:input wire:model.live.debounce.300ms="buscar" label="Buscar" placeholder="Nombre, matrícula o CURP" />
                    <flux:select wire:model.live="filtro_resultado" label="Resultado">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($resultados as $valor => [$etiqueta])
                            <flux:select.option value="{{ $valor }}">{{ $etiqueta }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="filtro_estatus" label="Estatus actual">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach (collect($alumnos)->pluck('estatus')->filter()->unique()->sort() as $estatus)
                            <flux:select.option value="{{ $estatus }}">{{ ucfirst(str_replace('_', ' ', $estatus)) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3 text-sm dark:border-neutral-700 dark:bg-neutral-800">
                        <span class="font-black text-slate-900 dark:text-white">{{ $this->alumnosFiltrados->count() }}</span>
                        <span class="text-slate-500">registros visibles</span>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 rounded-2xl border border-sky-200 bg-sky-50/70 p-4 lg:grid-cols-[1fr_1fr_auto] dark:border-sky-900/50 dark:bg-sky-950/20">
                    <flux:select wire:model="resultado_masivo" label="Resultado para seleccionados">
                        @foreach ($resultados as $valor => [$etiqueta])
                            @if ($valor !== 'pendiente')
                                <flux:select.option value="{{ $valor }}">{{ $etiqueta }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                    <div class="self-end text-sm text-sky-800 dark:text-sky-200">
                        Se aplicará a <b>{{ count($seleccionados) }}</b> alumno(s) seleccionados. Los grupos destino se asignan en el paso siguiente.
                    </div>
                    <flux:button class="self-end" variant="primary" wire:click="aplicarResultadoMasivo">
                        Aplicar
                    </flux:button>
                </div>

                @error('decisiones')
                    <p class="mt-3 rounded-xl bg-rose-50 p-3 text-sm font-bold text-rose-700 dark:bg-rose-950/20 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                <div class="overflow-x-auto">
                    <table class="min-w-[1180px] w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500 dark:bg-neutral-800">
                            <tr>
                                <th class="p-3"></th>
                                <th class="p-3 text-left">Alumno</th>
                                <th class="p-3 text-left">Origen</th>
                                <th class="p-3 text-left">Resultado</th>
                                <th class="p-3 text-left">Motivo individual / escuela destino</th>
                                <th class="p-3 text-left">Validaciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                            @forelse ($this->alumnosFiltrados as $alumno)
                                @php
                                    $decision = $decisiones[$alumno['id']] ?? ['resultado' => 'pendiente'];
                                    $bloqueado = !$alumno['procesable'];
                                @endphp
                                <tr class="align-top transition hover:bg-slate-50/70 dark:hover:bg-neutral-800/40">
                                    <td class="p-3">
                                        <input type="checkbox" wire:model="seleccionados" value="{{ $alumno['id'] }}"
                                            @disabled($bloqueado)
                                            class="rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                                    </td>
                                    <td class="p-3">
                                        <p class="font-black text-slate-900 dark:text-white">{{ $alumno['nombre'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $alumno['matricula'] }} · {{ $alumno['curp'] }}</p>
                                        @if ($alumno['solo_proyeccion_historica'] ?? false)
                                            <span class="mt-2 inline-flex rounded-full bg-sky-100 px-2 py-1 text-[11px] font-black text-sky-800">
                                                Histórico proyectable
                                            </span>
                                        @else
                                            <span class="mt-2 inline-flex rounded-full px-2 py-1 text-[11px] font-black {{ $alumno['procesable'] ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' }}">
                                                {{ $alumno['procesable'] ? 'Procesable' : 'Histórico sin cambios' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-slate-700 dark:text-slate-300">
                                        <p class="font-bold">{{ $alumno['grado'] }}{{ $alumno['semestre'] ? ' · Semestre '.$alumno['semestre'] : '' }}</p>
                                        <p>{{ $alumno['grupo'] }}</p>
                                        <p class="mt-1 text-xs">{{ ucfirst(str_replace('_', ' ', $alumno['estatus'])) }}</p>
                                    </td>
                                    <td class="p-3">
                                        <select wire:model.live="decisiones.{{ $alumno['id'] }}.resultado" @disabled($bloqueado)
                                            class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                            @if ($alumno['solo_proyeccion_historica'] ?? false)
                                                <option value="continuidad_interna">Crear proyección y conservar resultado histórico</option>
                                            @else
                                                @foreach ($resultados as $valor => [$etiqueta])
                                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @if ($alumno['solo_proyeccion_historica'] ?? false)
                                            <p class="mt-2 text-xs font-semibold text-sky-700 dark:text-sky-300">
                                                Su resultado histórico permanecerá intacto; solo se creará la proyección pendiente.
                                            </p>
                                        @endif
                                        @if (($decision['resultado'] ?? '') === 'baja_definitiva' && $alumno['es_grado_final'])
                                            <p class="mt-2 text-xs font-bold text-rose-600">Terminó el grado final. Normalmente corresponde Egresado; la baja requerirá confirmación administrativa.</p>
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        <input type="text" wire:model="decisiones.{{ $alumno['id'] }}.motivo" @disabled($bloqueado)
                                            placeholder="Observación individual opcional"
                                            class="w-full rounded-xl border-slate-300 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                        @if (($decision['resultado'] ?? '') === 'traslado')
                                            <input type="text" wire:model="decisiones.{{ $alumno['id'] }}.escuela_destino"
                                                placeholder="Escuela destino (opcional)"
                                                class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                        @endif
                                    </td>
                                    <td class="p-3">
                                        @if ($alumno['advertencias'])
                                            <div class="space-y-1">
                                                @foreach ($alumno['advertencias'] as $advertencia)
                                                    <p class="rounded-lg bg-amber-50 px-2 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-950/20 dark:text-amber-300">{{ $advertencia }}</p>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-black text-emerald-800">Sin advertencias</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="p-8 text-center text-slate-500">No hay alumnos con los filtros actuales.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-between gap-3">
                <flux:button wire:click="anterior">Volver</flux:button>
                <flux:button variant="primary" wire:click="siguiente">Configurar destinos</flux:button>
            </div>
        </div>
    @endif

    @if ($paso === 3)
        <div class="space-y-5">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">3. Configura los destinos</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    El destino se propone según el contexto: siguiente grado o semestre, siguiente nivel, o el mismo grado para repetición. Ninguna proyección activa al alumno todavía; la inscripción del ciclo destino se formaliza al confirmar su regreso.
                </p>

                @if (($conteos['continuidad_interna'] ?? 0) > 0 || ($conteos['no_promovido'] ?? 0) > 0)
                    <div class="mt-5 grid gap-4 lg:grid-cols-3">
                        <div>
                            <flux:select wire:model.live="ciclo_destino_id" label="Ciclo destino consecutivo"
                                :disabled="$this->ciclosDestinoPermitidos->isEmpty()">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach ($this->ciclosDestinoPermitidos as $ciclo)
                                    <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            @if ($this->ciclosDestinoPermitidos->isEmpty())
                                <p class="mt-2 text-xs font-bold text-rose-600">
                                    Primero crea el ciclo escolar {{ $this->etiquetaCicloDestinoEsperado }}. No se permite proyectar al mismo ciclo de origen.
                                </p>
                            @endif
                            @error('ciclo_destino_id')
                                <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @if (($conteos['continuidad_interna'] ?? 0) > 0)
                            <flux:select wire:model.live="nivel_destino_id" label="Nivel destino">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach ($nivelesDestino as $item)
                                    <flux:select.option value="{{ $item->id }}">{{ $item->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:select wire:model.live="grado_destino_id" label="Grado destino propuesto">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach ($gradosDestino as $grado)
                                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            @if ($semestresDestino->isNotEmpty())
                                <flux:select wire:model.live="semestre_destino_id" label="Semestre destino">
                                    <flux:select.option value="">Selecciona</flux:select.option>
                                    @foreach ($semestresDestino as $semestre)
                                        <flux:select.option value="{{ $semestre->id }}">Semestre {{ $semestre->numero }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @endif

                            <flux:select wire:model.live="generacion_destino_id" label="Generación destino">
                                <flux:select.option value="">Selecciona</flux:select.option>
                                @foreach ($generacionesDestino as $generacion)
                                    <flux:select.option value="{{ $generacion->id }}">{{ $generacion->etiqueta }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <div class="rounded-2xl border {{ $generacion_destino_id ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-4 text-sm dark:bg-transparent">
                                <p class="font-black">Generación esperada: {{ $generacion_esperada ?: 'No calculada' }}</p>
                                <p class="mt-1 text-slate-600 dark:text-slate-300">
                                    {{ $generacion_destino_id ? 'Se encontró una generación compatible.' : 'Debes crear previamente la generación compatible; no se crea automáticamente.' }}
                                </p>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                        No hay alumnos con proyección de continuidad ni repetición. No se requiere configurar un ciclo destino.
                    </div>
                @endif
            </div>

            @if (($conteos['continuidad_interna'] ?? 0) > 0 || ($conteos['no_promovido'] ?? 0) > 0)
                <div class="rounded-3xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900/50 dark:bg-sky-950/20">
                    <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-end">
                        <flux:select wire:model="grupo_masivo_id" label="Asignar grupo a los alumnos seleccionados">
                            <flux:select.option value="">Selecciona un grupo compatible</flux:select.option>
                            @foreach ($this->gruposMasivos as $grupo)
                                <flux:select.option value="{{ $grupo['id'] }}">{{ $grupo['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:button variant="primary" wire:click="aplicarGrupoMasivo">Aplicar grupo masivamente</flux:button>
                    </div>
                    <p class="mt-2 text-xs text-sky-800 dark:text-sky-200">Solo se aplicará cuando el grupo sea compatible con el resultado y ubicación de cada alumno seleccionado.</p>
                    @error('grupo_masivo_id')<p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
                    <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
                        <h4 class="font-black text-slate-900 dark:text-white">Grupo y matrícula propuestos por alumno</h4>
                        <p class="mt-1 text-sm text-slate-500">El grupo puede dejarse pendiente al generar la proyección, pero será obligatorio al confirmar la reinscripción. La matrícula sugerida puede ajustarse antes de formalizar.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-[900px] w-full text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-800">
                                <tr><th class="p-3 text-left">Alumno</th><th class="p-3 text-left">Resultado</th><th class="p-3 text-left">Grupo destino</th><th class="p-3 text-left">Matrícula destino</th></tr>
                            </thead>
                            <tbody class="divide-y dark:divide-neutral-800">
                                @foreach (collect($alumnos)->where('procesable', true) as $alumno)
                                    @php
                                        $resultado = $decisiones[$alumno['id']]['resultado'] ?? 'pendiente';
                                        $requiereDestino = in_array($resultado, ['continuidad_interna', 'no_promovido'], true);
                                        $gruposAlumno = $this->gruposParaAlumno($alumno);
                                    @endphp
                                    @if ($requiereDestino)
                                    <tr>
                                        <td class="p-3"><b class="text-slate-900 dark:text-white">{{ $alumno['nombre'] }}</b><br><small class="text-slate-500">{{ $alumno['grado'] }} · {{ $alumno['grupo'] }}</small></td>
                                        <td class="p-3">{{ $resultados[$resultado][0] ?? $resultado }}</td>
                                        <td class="p-3">
                                            <select wire:model="decisiones.{{ $alumno['id'] }}.grupo_destino_id"
                                                class="w-full rounded-xl border-slate-300 text-sm dark:border-neutral-700 dark:bg-neutral-900">
                                                <option value="">Asignar al confirmar</option>
                                                @foreach ($gruposAlumno as $grupo)
                                                    <option value="{{ $grupo['id'] }}">{{ $grupo['label'] }}</option>
                                                @endforeach
                                            </select>
                                            @if ($gruposAlumno === [])
                                                <p class="mt-1 text-xs font-bold text-amber-600">No existen grupos compatibles todavía. Podrás crearlo y asignarlo antes de confirmar la reinscripción.</p>
                                            @endif
                                            @error("decisiones.{$alumno['id']}.grupo_destino_id")<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                                        </td>
                                        <td class="p-3">
                                            <input wire:model="decisiones.{{ $alumno['id'] }}.matricula"
                                                placeholder="{{ $resultado === 'no_promovido' ? 'Conservar '.$alumno['matricula'] : 'Automática si queda vacío' }}"
                                                class="w-full rounded-xl border-slate-300 text-sm uppercase dark:border-neutral-700 dark:bg-neutral-900">
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="flex justify-between gap-3">
                <flux:button wire:click="anterior">Anterior</flux:button>
                <flux:button variant="primary" wire:click="siguiente">Revisar resumen</flux:button>
            </div>
        </div>
    @endif

    @if ($paso === 4)
        <div class="space-y-5 rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
            <div>
                <h3 class="text-lg font-black text-slate-900 dark:text-white">4. Revisión administrativa</h3>
                <p class="mt-1 text-sm text-slate-500">Los errores críticos bloquean el proceso; las advertencias quedan registradas.</p>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ($this->resultadosDisponibles as $clave => $etiqueta)
                    <div class="rounded-2xl border border-slate-200 p-4 text-center dark:border-neutral-700">
                        <p class="text-xs font-black uppercase text-slate-500">{{ $etiqueta }}</p>
                        <p class="mt-1 text-3xl font-black text-slate-900 dark:text-white">{{ $conteos[$clave] ?? 0 }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <flux:input type="date" wire:model="fecha_efectiva" label="Fecha efectiva del resultado académico" />
                <flux:textarea wire:model="motivo" label="Motivo administrativo general" rows="4"
                    placeholder="Ejemplo: cierre oficial del ciclo escolar y clasificación individual revisada por Control Escolar." />
            </div>

            @if ($modo_proceso === 'promocion_grado')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100">
                    La generación permanecerá activa porque este proceso corresponde a un grado o semestre intermedio. Solo se cierra el historial del ciclo cursado por cada alumno.
                </div>
            @else
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 {{ $grupo_origen_id ? 'border-amber-200 bg-amber-50/60' : 'border-emerald-200 bg-emerald-50/60' }} dark:bg-transparent">
                    <input type="checkbox" wire:model="cerrar_generacion" class="mt-1 rounded border-slate-300 text-[#006492] focus:ring-[#006492]">
                    <span>
                        <b class="block text-slate-900 dark:text-white">Cerrar definitivamente la generación</b>
                        <small class="mt-1 block text-slate-600 dark:text-slate-300">
                            Solo se aplicará si no queda ningún registro en curso. Si trabajas por grupo, procesa primero los demás grupos o desmarca esta opción.
                        </small>
                    </span>
                </label>
            @endif

            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-100">
                La reversión automática solo estará disponible mientras el ciclo destino no tenga calificaciones, fichas, documentos oficiales ni movimientos posteriores.
            </div>

            <div class="flex justify-between gap-3">
                <flux:button wire:click="anterior">Anterior</flux:button>
                <flux:button variant="primary" wire:click="siguiente">Ir a confirmación</flux:button>
            </div>
        </div>
    @endif

    @if ($paso === 5)
        <div class="space-y-5 rounded-3xl border border-rose-200 bg-white p-5 dark:border-rose-900/50 dark:bg-neutral-900 sm:p-6">
            <div class="rounded-2xl bg-rose-50 p-5 dark:bg-rose-950/20">
                <h3 class="text-lg font-black text-rose-900 dark:text-rose-100">5. Confirmación definitiva</h3>
                <p class="mt-2 text-sm leading-6 text-rose-800 dark:text-rose-200">
                    Se procesarán <b>{{ $vista_previa['total'] ?? 0 }}</b> alumnos. El sistema conservará el ciclo de origen y registrará cada cambio en la bitácora.
                    @if ($vista_previa['destino'] ?? null) Destino principal: <b>{{ $vista_previa['destino'] }}</b>. @endif
                </p>
            </div>

            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-100">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-black">Simulación verificada y lista para ejecutar</p>
                        <p class="mt-1 text-xs leading-5">
                            Firma: <code class="font-mono">{{ isset($vista_previa['hash']) ? substr($vista_previa['hash'], 0, 16).'…' : 'No disponible' }}</code>
                            · Respaldo previsto: {{ $vista_previa['respaldo_items'] ?? 0 }} alumno(s).
                        </p>
                    </div>
                    <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-black text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200">
                        Vigente hasta {{ isset($vista_previa['expira_at']) ? \Carbon\Carbon::parse($vista_previa['expira_at'])->format('d/m/Y H:i') : 'sin fecha' }}
                    </span>
                </div>
            </div>

            @if (!empty($vista_previa['advertencias']))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20">
                    <p class="font-black text-amber-900 dark:text-amber-100">Advertencias registradas en la simulación</p>
                    <ul class="mt-2 space-y-1 text-xs leading-5 text-amber-800 dark:text-amber-200">
                        @foreach (array_slice($vista_previa['advertencias'], 0, 8) as $advertencia)
                            <li>• {{ $advertencia }}</li>
                        @endforeach
                        @if (count($vista_previa['advertencias']) > 8)
                            <li>• Y {{ count($vista_previa['advertencias']) - 8 }} advertencia(s) adicional(es).</li>
                        @endif
                    </ul>
                </div>
            @endif

            <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-100">
                Al confirmar, se generará un respaldo lógico firmado con el estado exacto de la inscripción, historial por ciclo, asignaciones y generación. Si la operación falla, la transacción completa se cancela.
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <flux:input wire:model="confirmacion" label="Escribe CONFIRMAR" placeholder="CONFIRMAR" />
                <flux:input type="password" wire:model="password_confirmacion" label="Contraseña del usuario" autocomplete="current-password" />
            </div>

            <div class="flex justify-between gap-3">
                <flux:button wire:click="anterior">Anterior</flux:button>
                <flux:button variant="danger" icon="check-badge" wire:click="ejecutar" spinner="ejecutar">
                    {{ $modo_proceso === 'promocion_grado' ? 'Cerrar grado y generar proyecciones' : ($modo_proceso === 'egreso_terminal' ? 'Procesar egreso terminal' : 'Cerrar nivel y generar proyecciones') }}
                </flux:button>
            </div>
        </div>
    @endif

    <livewire:accion.generales.proyecciones-continuidad
        :slug_nivel="$slug_nivel"
        :key="'proyecciones-continuidad-'.$slug_nivel"
    />

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
            <h3 class="font-black text-slate-900 dark:text-white">Procesos recientes y reportes</h3>
            <p class="mt-1 text-sm text-slate-500">Descarga el acta en PDF, el concentrado en Excel o revierte un proceso que todavía no tenga actividad posterior.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[980px] w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-800">
                    <tr><th class="p-3 text-left">Proceso</th><th class="p-3 text-left">Generación</th><th class="p-3 text-left">Ciclos</th><th class="p-3 text-center">Procesados</th><th class="p-3 text-center">Estado</th><th class="p-3 text-right">Acciones</th></tr>
                </thead>
                <tbody class="divide-y dark:divide-neutral-800">
                    @forelse ($this->procesosRecientes as $proceso)
                        <tr>
                            <td class="p-3"><b>#{{ $proceso->id }}</b><br><small class="text-slate-500">{{ $proceso->realizado_at?->format('d/m/Y H:i') }} · {{ $proceso->usuarioRealizo?->name }}</small></td>
                            <td class="p-3 font-bold">{{ $proceso->generacion?->etiqueta ?? 'Sin generación' }}<br><small class="font-normal text-slate-500">{{ ucfirst($proceso->alcance ?? 'generación') }}</small></td>
                            <td class="p-3">{{ $proceso->cicloEscolar?->inicio_anio }}-{{ $proceso->cicloEscolar?->fin_anio }} @if($proceso->cicloDestino) → {{ $proceso->cicloDestino->inicio_anio }}-{{ $proceso->cicloDestino->fin_anio }} @endif</td>
                            <td class="p-3 text-center font-black">{{ $proceso->total_procesados }}</td>
                            <td class="p-3 text-center">
                                <span class="rounded-full px-2 py-1 text-xs font-black {{ $proceso->estado === 'revertido' ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800' }}">
                                    {{ ucfirst($proceso->estado) }}
                                </span>
                                <span class="mt-1 block text-[10px] font-bold {{ str_starts_with((string) $proceso->integridad_estado, 'verificado') ? 'text-emerald-700' : 'text-slate-500' }}">
                                    {{ str_starts_with((string) $proceso->integridad_estado, 'verificado') ? 'Respaldo verificado' : 'Proceso anterior / sin firma' }}
                                </span>
                            </td>
                            <td class="p-3">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('generales.cierre-generacion.reporte', ['proceso' => $proceso->id, 'formato' => 'pdf']) }}" target="_blank"
                                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-neutral-700">PDF</a>
                                    <a href="{{ route('generales.cierre-generacion.reporte', ['proceso' => $proceso->id, 'formato' => 'excel']) }}"
                                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-neutral-700">Excel</a>
                                    <button type="button" wire:click="alternarDetallesProceso({{ $proceso->id }})"
                                        class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-black text-sky-800 hover:bg-sky-100 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                                        Comprobantes
                                    </button>
                                    @if ($proceso->puede_revertirse)
                                        <button type="button" wire:click="prepararReversion({{ $proceso->id }})"
                                            class="rounded-xl bg-rose-600 px-3 py-2 text-xs font-black text-white hover:bg-rose-700">Revertir</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if ($procesoExpandidoId === $proceso->id)
                            <tr class="bg-sky-50/60 dark:bg-sky-950/10">
                                <td colspan="6" class="p-4">
                                    <div class="rounded-2xl border border-sky-200 bg-white p-4 dark:border-sky-900/50 dark:bg-neutral-900">
                                        <p class="font-black text-slate-900 dark:text-white">Comprobantes individuales del proceso #{{ $proceso->id }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Descarga únicamente el comprobante del alumno seleccionado.</p>
                                        <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                            @forelse ($this->detallesProceso as $detalle)
                                                <a href="{{ route('generales.cierre-generacion.comprobante', ['proceso' => $proceso->id, 'detalle' => $detalle->id]) }}" target="_blank"
                                                    class="rounded-xl border border-slate-200 p-3 text-sm transition hover:border-sky-300 hover:bg-sky-50 dark:border-neutral-700 dark:hover:bg-sky-950/20">
                                                    <b class="block text-slate-900 dark:text-white">
                                                        {{ trim(($detalle->inscripcion?->apellido_paterno ?? '').' '.($detalle->inscripcion?->apellido_materno ?? '').' '.($detalle->inscripcion?->nombre ?? 'Alumno')) }}
                                                    </b>
                                                    <span class="text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $detalle->resultado)) }} · PDF</span>
                                                </a>
                                            @empty
                                                <p class="text-sm text-slate-500">Este proceso no tiene comprobantes individuales disponibles.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-slate-500">Todavía no hay procesos de cierre académico.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
            <h3 class="font-black text-slate-900 dark:text-white">Estado de las generaciones del nivel</h3>
            <p class="mt-1 text-sm text-slate-500">Una reapertura administrativa no debe confundirse con reingreso. Por defecto no reactiva a los egresados.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[760px] w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-neutral-800"><tr><th class="p-3 text-left">Generación</th><th class="p-3 text-center">Alumnos ciclo</th><th class="p-3 text-center">Pendientes</th><th class="p-3 text-center">Estado</th><th class="p-3 text-right">Acción</th></tr></thead>
                <tbody class="divide-y dark:divide-neutral-800">
                    @foreach ($generaciones as $generacion)
                        <tr>
                            <td class="p-3 font-black text-slate-900 dark:text-white">{{ $generacion->etiqueta }}</td>
                            <td class="p-3 text-center">{{ $generacion->alumnos_ciclo_count }}</td>
                            <td class="p-3 text-center">{{ $generacion->pendientes_ciclo_count }}</td>
                            <td class="p-3 text-center"><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-700">{{ $generacion->etiqueta_estado_cierre }}</span></td>
                            <td class="p-3 text-right">
                                @unless($generacion->status)
                                    <div class="flex justify-end gap-2">
                                        <flux:button size="sm" wire:click="prepararReactivacion({{ $generacion->id }})">Reabrir para correcciones</flux:button>
                                        @if (($generacion->estado_cierre ?? 'cerrada') !== 'archivada')
                                            <flux:button size="sm" variant="danger" wire:click="prepararArchivoGeneracion({{ $generacion->id }})">Archivar</flux:button>
                                        @endif
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    @if ($modalReversion)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Revertir proceso #{{ $procesoReversionId }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Solo procede cuando no existen calificaciones, documentos ni movimientos posteriores. Antes de restaurar, el sistema verifica la firma del respaldo general y de cada alumno.</p>
                <div class="mt-5 space-y-4">
                    <flux:textarea wire:model="motivo_reversion" label="Motivo de reversión" rows="4" />
                    <flux:input type="password" wire:model="password_confirmacion" label="Contraseña del usuario" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('modalReversion', false)">Cancelar</flux:button>
                    <flux:button variant="danger" wire:click="revertirProceso" spinner="revertirProceso">Revertir con auditoría</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($modalReactivar)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Reabrir {{ $generacionReactivar?->etiqueta }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">La generación se abre para correcciones. No se crea una inscripción nueva ni se borra el resultado histórico.</p>
                <div class="mt-5 space-y-4">
                    <flux:textarea wire:model="motivo_reactivacion" label="Motivo administrativo" rows="4" />
                    <label class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm dark:border-amber-900/50 dark:bg-amber-950/20">
                        <input type="checkbox" wire:model="reactivar_egresados" class="mt-1 rounded border-slate-300 text-[#006492]">
                        <span><b class="block">Reactivar temporalmente a egresados</b><small class="mt-1 block">Úsalo únicamente si el módulo de corrección requiere que aparezcan como activos. Se recomienda dejarlo desmarcado.</small></span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('modalReactivar', false)">Cancelar</flux:button>
                    <flux:button variant="primary" wire:click="reactivar" spinner="reactivar">Reabrir generación</flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($modalArchivar)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm">
            <div class="w-full max-w-xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-neutral-900">
                <h3 class="text-xl font-black text-slate-900 dark:text-white">Archivar generación</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">El archivo no elimina alumnos, calificaciones ni documentos. La generación seguirá disponible en consultas históricas.</p>
                <div class="mt-5 space-y-4">
                    <flux:textarea wire:model="motivo_archivo" label="Motivo de archivo" rows="4" />
                    <flux:input wire:model="confirmacion_archivo" label="Escribe ARCHIVAR para confirmar" />
                    <flux:input type="password" wire:model="password_archivo" label="Contraseña del usuario" autocomplete="current-password" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <flux:button wire:click="$set('modalArchivar', false)">Cancelar</flux:button>
                    <flux:button variant="danger" wire:click="archivarGeneracion" spinner="archivarGeneracion">Archivar con auditoría</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
