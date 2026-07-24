<div>
    @if ($abierto)
        <div x-data="{ visor: false, visorUrl: '', visorTitulo: '', arrastrando: null }" x-cloak
            class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/80 p-2 backdrop-blur-md sm:p-4"
            @keydown.escape.window="visor ? visor = false : $wire.cerrar()">
            <div class="flex h-[96vh] w-full max-w-[1600px] flex-col overflow-hidden rounded-[28px] border border-white/15 bg-white shadow-2xl dark:bg-neutral-900">
                <header class="flex shrink-0 items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-neutral-800 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-300">
                            <flux:icon name="document-duplicate" class="size-5" />
                        </div>
                        <div class="min-w-0">
                            <h2 class="truncate text-lg font-black text-slate-900 dark:text-white">Organizar páginas del expediente</h2>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">Clasifica, ordena y gira páginas. Los archivos originales se conservan sin cambios.</p>
                        </div>
                    </div>
                    <button type="button" wire:click="cerrar" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800">
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </header>

                <div class="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[290px_minmax(0,1fr)]">
                    <aside class="min-h-0 overflow-y-auto border-b border-slate-200 bg-slate-50/80 p-4 dark:border-neutral-800 dark:bg-neutral-950/50 xl:border-b-0 xl:border-r">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-black text-slate-900 dark:text-white">Archivos fuente</h3>
                            <span class="rounded-full bg-violet-100 px-2.5 py-1 text-[10px] font-black text-violet-700 dark:bg-violet-950/50 dark:text-violet-300">{{ count($fuentes) }}</span>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($fuentes as $fuente)
                                <button type="button" wire:click="seleccionarFuente({{ $fuente['id'] }})"
                                    class="w-full rounded-2xl border p-3 text-left transition {{ (int) $fuenteActivaId === (int) $fuente['id'] ? 'border-violet-400 bg-white shadow-sm ring-2 ring-violet-100 dark:bg-neutral-900 dark:ring-violet-950/50' : 'border-slate-200 bg-white/70 hover:border-violet-300 dark:border-neutral-800 dark:bg-neutral-900/70' }}">
                                    <p class="truncate text-xs font-black text-slate-800 dark:text-white" title="{{ $fuente['nombre'] }}">{{ $fuente['nombre'] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-500">{{ $fuente['paginas'] }} pág. · {{ $fuente['tamano'] }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $fuente['fecha'] }}</p>
                                </button>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-xs text-slate-500 dark:border-neutral-700">No hay archivos organizables.</div>
                            @endforelse
                        </div>

                        @if ($fuenteActivaId)
                            @php
                                $fuenteActiva = collect($fuentes)->firstWhere('id', $fuenteActivaId);
                            @endphp
                            @if ($fuenteActiva)
                                <a href="{{ $fuenteActiva['original_url'] }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                    <flux:icon name="arrow-down-tray" class="size-4" /> Descargar original
                                </a>
                            @endif
                        @endif

                        @if ($historial !== [])
                            <div class="mt-6 border-t border-slate-200 pt-4 dark:border-neutral-800">
                                <h4 class="text-xs font-black uppercase tracking-wide text-slate-500">Historial</h4>
                                <div class="mt-2 space-y-2">
                                    @foreach ($historial as $item)
                                        <div class="rounded-xl border border-slate-200 bg-white p-2.5 text-[11px] dark:border-neutral-800 dark:bg-neutral-900">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-black text-slate-800 dark:text-white">Versión {{ $item['version'] }}</span>
                                                <span class="rounded-full px-2 py-0.5 font-black uppercase {{ $item['estado'] === 'confirmado' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $item['estado'] }}</span>
                                            </div>
                                            <p class="mt-1 text-slate-500">{{ $item['fecha'] }} · {{ $item['usuario'] }}</p>
                                            @if ($item['error'])
                                                <p class="mt-1 line-clamp-3 text-rose-600" title="{{ $item['error'] }}">{{ $item['error'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </aside>

                    <main class="min-h-0 overflow-y-auto p-4 sm:p-5 lg:p-6">
                        @if ($fuentes === [])
                            <div class="flex min-h-[420px] items-center justify-center rounded-3xl border border-dashed border-slate-300 text-center dark:border-neutral-700">
                                <div class="max-w-md p-8">
                                    <flux:icon name="document-plus" class="mx-auto size-12 text-slate-300" />
                                    <h3 class="mt-4 font-black text-slate-800 dark:text-white">Primero carga un archivo</h3>
                                    <p class="mt-2 text-sm text-slate-500">Los PDF e imágenes cargados aparecerán aquí como fuentes organizables.</p>
                                </div>
                            </div>
                        @else
                            @php
                                $fuenteActiva = collect($fuentes)->firstWhere('id', $fuenteActivaId);
                            @endphp

                            <section class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 to-indigo-50 p-4 dark:border-violet-900/50 dark:from-violet-950/20 dark:to-indigo-950/20 sm:p-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <h3 class="font-black text-slate-900 dark:text-white">Asignación rápida por rangos</h3>
                                        <p class="mt-1 text-xs text-slate-500">Ejemplos: <strong>1-2</strong>, <strong>3,5</strong> o <strong>7-10</strong>. Una página no puede repetirse.</p>
                                    </div>
                                    <button type="button" wire:click="aplicarRangos" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-black text-white hover:bg-violet-700">
                                        <flux:icon name="check" class="size-4" /> Aplicar rangos
                                    </button>
                                </div>

                                @error('rangos')
                                    <p class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700">{{ $message }}</p>
                                @enderror

                                <div class="mt-4 grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                                    @foreach ($tipos as $tipo)
                                        <div class="rounded-2xl border border-white/80 bg-white/80 p-3 dark:border-neutral-700 dark:bg-neutral-900/75">
                                            <label class="text-xs font-black text-slate-800 dark:text-white">{{ $tipo['nombre'] }}</label>
                                            <input type="text" wire:model="rangos.{{ $tipo['id'] }}" placeholder="Ej. 1-2,4"
                                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">

                                            @if ($tipo['requiere_nivel'])
                                                <select wire:model="contextosRapidos.{{ $tipo['id'] }}.nivel_id" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-2.5 py-2 text-[11px] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                    <option value="">Nivel</option>
                                                    @foreach ($niveles as $nivel)
                                                        <option value="{{ $nivel['id'] }}">{{ $nivel['nombre'] }}</option>
                                                    @endforeach
                                                </select>
                                            @endif

                                            @if ($tipo['requiere_grado_ciclo'])
                                                @php
                                                    $nivelRapido = data_get($contextosRapidos, $tipo['id'] . '.nivel_id');
                                                    $gradosRapidos = collect($grados)->when($nivelRapido, fn($items) => $items->where('nivel_id', (int) $nivelRapido));
                                                @endphp
                                                <div class="mt-2 grid grid-cols-2 gap-2">
                                                    <select wire:model="contextosRapidos.{{ $tipo['id'] }}.grado_id" class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-[11px] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                        <option value="">Grado</option>
                                                        @foreach ($gradosRapidos as $grado)
                                                            <option value="{{ $grado['id'] }}">{{ $grado['nombre'] }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select wire:model="contextosRapidos.{{ $tipo['id'] }}.ciclo_escolar_id" class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-[11px] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                        <option value="">Ciclo</option>
                                                        @foreach ($ciclos as $ciclo)
                                                            <option value="{{ $ciclo['id'] }}">{{ $ciclo['nombre'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            <section class="mt-6">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                                    <div>
                                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Páginas de {{ $fuenteActiva['nombre'] ?? 'archivo fuente' }}</h3>
                                        <p class="mt-1 text-xs text-slate-500">Selecciona el tipo de cada página; en documentos académicos también define su nivel, grado y ciclo.</p>
                                    </div>
                                    <p class="text-xs font-bold text-slate-400">Las vistas se cargan progresivamente.</p>
                                </div>

                                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                                    @foreach (collect($paginas)->where('fuente_id', $fuenteActivaId)->sortBy('pagina') as $pagina)
                                        @php
                                            $tipoPagina = collect($tipos)->firstWhere('id', $pagina['tipo_documento_id']);
                                            $gradosPagina = collect($grados)->when($pagina['nivel_id'], fn($items) => $items->where('nivel_id', (int) $pagina['nivel_id']));
                                            $gruposPagina = collect($grupos)
                                                ->when($pagina['nivel_id'], fn($items) => $items->where('nivel_id', (int) $pagina['nivel_id']))
                                                ->when($pagina['grado_id'], fn($items) => $items->where('grado_id', (int) $pagina['grado_id']));
                                        @endphp
                                        <article wire:key="pagina-expediente-{{ $pagina['clave'] }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
                                            <button type="button"
                                                @click="visor = true; visorUrl = '{{ $pagina['preview_url'] }}'; visorTitulo = @js($pagina['fuente_nombre'] . ' · Página ' . $pagina['pagina'])"
                                                class="group relative block h-56 w-full overflow-hidden bg-slate-100 dark:bg-neutral-800">
                                                <iframe loading="lazy" src="{{ $pagina['preview_url'] }}" class="pointer-events-none h-full w-full" title="Vista de página {{ $pagina['pagina'] }}"></iframe>
                                                <span class="absolute bottom-2 right-2 rounded-lg bg-slate-950/80 px-2 py-1 text-[10px] font-black text-white opacity-0 transition group-hover:opacity-100">Ampliar</span>
                                            </button>
                                            <div class="space-y-2.5 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <p class="text-xs font-black text-slate-900 dark:text-white">Página {{ $pagina['pagina'] }}</p>
                                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-black {{ $pagina['tipo_documento_id'] ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700' }}">
                                                        {{ $pagina['tipo_nombre'] ?? 'Sin clasificar' }}
                                                    </span>
                                                </div>

                                                <select wire:change="actualizarTipo('{{ $pagina['clave'] }}', $event.target.value)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                    <option value="">Sin clasificar</option>
                                                    @foreach ($tipos as $tipo)
                                                        <option value="{{ $tipo['id'] }}" @selected((int) $pagina['tipo_documento_id'] === (int) $tipo['id'])>{{ $tipo['nombre'] }}</option>
                                                    @endforeach
                                                </select>

                                                @if ($tipoPagina && $tipoPagina['requiere_nivel'])
                                                    <select wire:change="actualizarContexto('{{ $pagina['clave'] }}', 'nivel_id', $event.target.value)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                        <option value="">Selecciona nivel</option>
                                                        @foreach ($niveles as $nivel)
                                                            <option value="{{ $nivel['id'] }}" @selected((int) $pagina['nivel_id'] === (int) $nivel['id'])>{{ $nivel['nombre'] }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                @if ($tipoPagina && $tipoPagina['requiere_grado_ciclo'])
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <select wire:change="actualizarContexto('{{ $pagina['clave'] }}', 'grado_id', $event.target.value)" class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-[11px] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                            <option value="">Grado</option>
                                                            @foreach ($gradosPagina as $grado)
                                                                <option value="{{ $grado['id'] }}" @selected((int) $pagina['grado_id'] === (int) $grado['id'])>{{ $grado['nombre'] }}</option>
                                                            @endforeach
                                                        </select>
                                                        <select wire:change="actualizarContexto('{{ $pagina['clave'] }}', 'ciclo_escolar_id', $event.target.value)" class="rounded-xl border border-slate-200 bg-white px-2 py-2 text-[11px] dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                            <option value="">Ciclo</option>
                                                            @foreach ($ciclos as $ciclo)
                                                                <option value="{{ $ciclo['id'] }}" @selected((int) $pagina['ciclo_escolar_id'] === (int) $ciclo['id'])>{{ $ciclo['nombre'] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <select wire:change="actualizarContexto('{{ $pagina['clave'] }}', 'grupo_id', $event.target.value)" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                                                        <option value="">Grupo opcional</option>
                                                        @foreach ($gruposPagina as $grupo)
                                                            <option value="{{ $grupo['id'] }}" @selected((int) $pagina['grupo_id'] === (int) $grupo['id'])>{{ $grupo['nombre'] }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                <div class="flex items-center justify-between gap-2 pt-1">
                                                    <span class="text-[10px] font-bold text-slate-400">Rotación: {{ $pagina['rotacion'] }}°</span>
                                                    <div class="flex gap-1">
                                                        <button type="button" wire:click="rotarPagina('{{ $pagina['clave'] }}', -90)" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm font-black text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300">↶</button>
                                                        <button type="button" wire:click="rotarPagina('{{ $pagina['clave'] }}', 90)" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm font-black text-slate-600 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300">↷</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>

                            <section class="mt-8 border-t border-slate-200 pt-6 dark:border-neutral-800">
                                <h3 class="text-lg font-black text-slate-900 dark:text-white">Orden final por documento</h3>
                                <p class="mt-1 text-xs text-slate-500">Arrastra páginas dentro del mismo documento o usa las flechas. Se pueden combinar páginas de diferentes archivos.</p>

                                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                    @foreach (collect($paginas)->whereNotNull('contexto_clave')->groupBy('contexto_clave') as $contextoClave => $paginasContexto)
                                        @php
                                            $primera = $paginasContexto->first();
                                            $nivelNombre = data_get(collect($niveles)->firstWhere('id', $primera['nivel_id']), 'nombre');
                                            $gradoNombre = data_get(collect($grados)->firstWhere('id', $primera['grado_id']), 'nombre');
                                            $cicloNombre = data_get(collect($ciclos)->firstWhere('id', $primera['ciclo_escolar_id']), 'nombre');
                                            $subtitulo = collect([$nivelNombre, $gradoNombre, $cicloNombre])->filter()->implode(' · ');
                                        @endphp
                                        <div class="rounded-2xl border border-slate-200 p-4 dark:border-neutral-700">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    <p class="font-black text-slate-900 dark:text-white">{{ $primera['tipo_nombre'] }}</p>
                                                    @if ($subtitulo)
                                                        <p class="mt-1 text-xs text-slate-500">{{ $subtitulo }}</p>
                                                    @endif
                                                </div>
                                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ $paginasContexto->count() }} pág.</span>
                                            </div>
                                            <div class="mt-3 space-y-2">
                                                @foreach ($paginasContexto->sortBy('orden') as $pagina)
                                                    <div draggable="true"
                                                        @dragstart="arrastrando = '{{ $pagina['clave'] }}'"
                                                        @dragend="arrastrando = null"
                                                        @dragover.prevent
                                                        @drop.prevent="$wire.reordenarPagina(arrastrando, '{{ $pagina['clave'] }}'); arrastrando = null"
                                                        class="flex cursor-grab items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-2.5 active:cursor-grabbing dark:border-neutral-700 dark:bg-neutral-800/60">
                                                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg bg-white text-xs font-black text-violet-600 shadow-sm dark:bg-neutral-900">{{ $pagina['orden'] }}</span>
                                                        <div class="min-w-0 flex-1">
                                                            <p class="truncate text-xs font-bold text-slate-800 dark:text-white">{{ $pagina['fuente_nombre'] }}</p>
                                                            <p class="text-[10px] text-slate-500">Página {{ $pagina['pagina'] }} · {{ $pagina['rotacion'] }}°</p>
                                                        </div>
                                                        <div class="flex gap-1">
                                                            <button type="button" wire:click="moverPagina('{{ $pagina['clave'] }}', 'arriba')" class="rounded-lg p-1.5 text-slate-500 hover:bg-white dark:hover:bg-neutral-900">↑</button>
                                                            <button type="button" wire:click="moverPagina('{{ $pagina['clave'] }}', 'abajo')" class="rounded-lg p-1.5 text-slate-500 hover:bg-white dark:hover:bg-neutral-900">↓</button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>

                            @if ($contextosExistentes !== [])
                                <section class="mt-8 rounded-3xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900/40 dark:bg-rose-950/10">
                                    <h3 class="font-black text-rose-900 dark:text-rose-200">Retirar un documento actual</h3>
                                    <p class="mt-1 text-xs text-rose-700/80 dark:text-rose-300/80">Retirar todas sus páginas requiere confirmación explícita. El archivo anterior seguirá en el historial.</p>
                                    <div class="mt-3 grid gap-2 md:grid-cols-2">
                                        @foreach ($contextosExistentes as $contexto)
                                            <div class="flex items-center justify-between gap-3 rounded-xl border border-rose-200 bg-white p-3 dark:border-rose-900/40 dark:bg-neutral-900">
                                                <div class="min-w-0">
                                                    <p class="truncate text-xs font-black text-slate-800 dark:text-white">{{ $contexto['tipo_nombre'] }}</p>
                                                    <p class="text-[10px] text-slate-500">{{ $contexto['paginas'] }} página(s)</p>
                                                </div>
                                                @if (in_array($contexto['clave'], $retirosConfirmados, true))
                                                    <button type="button" wire:click="cancelarRetiro('{{ $contexto['clave'] }}')" class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] font-black text-slate-600">Cancelar retiro</button>
                                                @else
                                                    <button type="button" wire:click="confirmarRetiro('{{ $contexto['clave'] }}')" wire:confirm="¿Confirmas retirar este documento actual? La versión anterior quedará en el historial." class="rounded-lg bg-rose-600 px-2.5 py-1.5 text-[10px] font-black text-white">Retirar</button>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endif
                    </main>
                </div>

                <footer class="shrink-0 border-t border-slate-200 bg-white px-5 py-4 dark:border-neutral-800 dark:bg-neutral-900 sm:px-6">
                    @error('organizacion')
                        <p class="mb-3 rounded-xl bg-rose-50 px-4 py-3 text-sm font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">{{ $message }}</p>
                    @enderror
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            @if ($paginasSinClasificar > 0)
                                <p class="text-sm font-black text-amber-700 dark:text-amber-300">{{ $paginasSinClasificar }} página(s) quedarán sin clasificar.</p>
                                <p class="text-xs text-slate-500">Se conservan en los originales, pero no se incluyen en documentos ni exportaciones.</p>
                            @else
                                <p class="text-sm font-black text-emerald-700 dark:text-emerald-300">Todas las páginas están clasificadas.</p>
                            @endif
                            @if ($mensaje)
                                <p class="mt-1 text-xs font-bold text-violet-600 dark:text-violet-300">{{ $mensaje }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col-reverse gap-2 sm:flex-row">
                            <button type="button" wire:click="cerrar" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Guardar borrador y cerrar</button>
                            @if ($fuentes !== [])
                                <button type="button" wire:click="confirmar" wire:loading.attr="disabled" wire:target="confirmar" class="inline-flex items-center justify-center gap-2 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-black text-white hover:bg-violet-700 disabled:cursor-wait disabled:opacity-60">
                                    <svg wire:loading wire:target="confirmar" class="size-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                                    <span wire:loading.remove wire:target="confirmar">Confirmar organización</span>
                                    <span wire:loading wire:target="confirmar">Procesando…</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </footer>
            </div>

            <div x-show="visor" x-transition.opacity class="fixed inset-0 z-[140] flex items-center justify-center bg-slate-950/90 p-3" @keydown.escape.window="visor = false">
                <div @click.outside="visor = false" class="flex h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-neutral-900">
                    <div class="flex h-14 shrink-0 items-center justify-between border-b border-slate-200 px-4 dark:border-neutral-700">
                        <p class="truncate pr-4 text-sm font-black text-slate-900 dark:text-white" x-text="visorTitulo"></p>
                        <button type="button" @click="visor = false" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-neutral-800">✕</button>
                    </div>
                    <iframe :src="visorUrl" class="min-h-0 flex-1 w-full" title="Vista ampliada de página"></iframe>
                </div>
            </div>
        </div>
    @endif
</div>
