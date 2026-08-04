<div>
    @if ($abierto)
        @php
            $fuenteActiva = collect($fuentes)->firstWhere('id', $fuenteActivaId);
            $tiposAsignados = collect($paginas)->pluck('tipo_documento_tutor_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $tiposRetirados = collect($tiposExistentes)->diff($tiposAsignados)->values();
        @endphp
        <div class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/80 p-2 backdrop-blur-sm sm:p-4"
            wire:keydown.escape.window="cerrar">
            <section class="flex h-[96vh] w-full max-w-[1700px] flex-col overflow-hidden rounded-3xl bg-slate-100 shadow-2xl dark:bg-neutral-950">
                <header class="flex flex-col gap-3 border-b border-slate-200 bg-white px-4 py-4 dark:border-neutral-800 dark:bg-neutral-900 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-xl font-black text-slate-950 dark:text-white">Organizador de páginas del responsable</h2>
                            @if ($paginasSinClasificar > 0)
                                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">{{ $paginasSinClasificar }} sin clasificar</span>
                            @else
                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-800">Todo clasificado</span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Asigna cada página, cambia su orden y confirma para generar versiones nuevas sin borrar el historial anterior.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($mensaje)
                            <span class="text-xs font-bold text-emerald-700">{{ $mensaje }}</span>
                        @endif
                        <button type="button" wire:click="cerrar"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                            Guardar borrador y cerrar
                        </button>
                        <button type="button" wire:click="confirmar" wire:loading.attr="disabled" wire:target="confirmar"
                            class="rounded-xl bg-[#006492] px-5 py-2.5 text-sm font-black text-white shadow-sm hover:bg-[#00557b] disabled:opacity-60">
                            <span wire:loading.remove wire:target="confirmar">Confirmar organización</span>
                            <span wire:loading wire:target="confirmar">Generando documentos…</span>
                        </button>
                    </div>
                </header>

                @error('organizacion')
                    <div class="border-b border-rose-200 bg-rose-50 px-5 py-3 text-sm font-bold text-rose-700">{{ $message }}</div>
                @enderror

                <div class="grid min-h-0 flex-1 lg:grid-cols-[350px_minmax(0,1fr)]">
                    <aside class="min-h-0 overflow-y-auto border-b border-slate-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900 lg:border-b-0 lg:border-r">
                        <section>
                            <h3 class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Archivos fuente</h3>
                            <div class="mt-3 space-y-2">
                                @forelse ($fuentes as $fuente)
                                    <button type="button" wire:click="seleccionarFuente({{ $fuente['id'] }})"
                                        class="w-full rounded-2xl border p-3 text-left transition {{ (int) $fuenteActivaId === (int) $fuente['id'] ? 'border-[#006492] bg-sky-50 ring-2 ring-[#006492]/10 dark:bg-sky-950/20' : 'border-slate-200 hover:border-slate-300 dark:border-neutral-700' }}">
                                        <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $fuente['nombre'] }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ $fuente['paginas'] }} pág. · {{ $fuente['tamano'] }}</p>
                                        <p class="mt-1 text-[11px] text-slate-400">{{ $fuente['usuario'] }} · {{ $fuente['fecha'] }}</p>
                                    </button>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 p-5 text-center text-sm text-slate-500">No hay archivos fuente activos.</div>
                                @endforelse
                            </div>
                        </section>

                        @if ($fuenteActiva)
                            <section class="mt-6">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Asignación rápida por rangos</h3>
                                    <a href="{{ $fuenteActiva['original_url'] }}" target="_blank" class="text-xs font-black text-[#006492] hover:underline">Descargar original</a>
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Ejemplos: <strong>1-2</strong>, <strong>3,5</strong> o <strong>1-3,7</strong>.</p>
                                <div class="mt-3 space-y-2.5">
                                    @foreach ($tipos as $tipo)
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-bold text-slate-600 dark:text-slate-300">{{ $tipo['nombre'] }}</span>
                                            <input type="text" wire:model.defer="rangos.{{ $tipo['id'] }}" placeholder="Páginas"
                                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm focus:border-[#006492] focus:ring-[#006492] dark:border-neutral-700 dark:bg-neutral-950">
                                        </label>
                                    @endforeach
                                </div>
                                @error('rangos') <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p> @enderror
                                <button type="button" wire:click="aplicarRangos"
                                    class="mt-3 w-full rounded-xl border border-[#006492]/30 bg-sky-50 px-4 py-2.5 text-sm font-black text-[#006492] hover:bg-sky-100 dark:bg-sky-950/20">
                                    Aplicar rangos a este archivo
                                </button>
                            </section>
                        @endif

                        @if ($tiposRetirados->isNotEmpty())
                            <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/20 dark:bg-amber-950/20">
                                <h3 class="text-sm font-black text-amber-900 dark:text-amber-200">Documentos que dejarían de estar vigentes</h3>
                                <p class="mt-1 text-xs text-amber-800/80 dark:text-amber-300/80">Confirma cada retiro. La versión anterior se conservará en el historial.</p>
                                <div class="mt-3 space-y-2">
                                    @foreach ($tiposRetirados as $tipoId)
                                        @php $tipoRetiro = collect($tipos)->firstWhere('id', (int) $tipoId); @endphp
                                        @if ($tipoRetiro)
                                            <div class="rounded-xl bg-white/80 p-3 dark:bg-neutral-900/70">
                                                <p class="text-xs font-black text-slate-800 dark:text-white">{{ $tipoRetiro['nombre'] }}</p>
                                                @if (in_array((int) $tipoId, $retirosConfirmados, true))
                                                    <button type="button" wire:click="cancelarRetiro({{ $tipoId }})" class="mt-2 text-xs font-black text-emerald-700">Retiro confirmado · cancelar</button>
                                                @else
                                                    <button type="button" wire:click="confirmarRetiro({{ $tipoId }})" class="mt-2 text-xs font-black text-amber-700">Confirmar retiro</button>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if (count($historial))
                            <section class="mt-6">
                                <h3 class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">Versiones de organización</h3>
                                <div class="mt-3 space-y-2">
                                    @foreach ($historial as $item)
                                        <div class="rounded-xl border border-slate-200 p-3 text-xs dark:border-neutral-700">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="font-black text-slate-800 dark:text-white">Versión {{ $item['version'] }}</span>
                                                <span class="rounded-full bg-slate-100 px-2 py-0.5 font-bold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">{{ str($item['estado'])->headline() }}</span>
                                            </div>
                                            <p class="mt-1 text-slate-500">{{ $item['fecha'] }} · {{ $item['usuario'] }}</p>
                                            @if ($item['error']) <p class="mt-1 font-bold text-rose-600">{{ $item['error'] }}</p> @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </aside>

                    <main class="min-h-0 overflow-y-auto p-4 sm:p-5">
                        @if (count($paginas))
                            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                                @foreach ($paginas as $pagina)
                                    <article wire:key="pagina-tutor-{{ $pagina['clave'] }}"
                                        class="overflow-hidden rounded-2xl border {{ $pagina['tipo_documento_tutor_id'] ? 'border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-900' : 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-950/10' }} shadow-sm">
                                        <div class="relative aspect-[4/5] bg-slate-200 dark:bg-neutral-800">
                                            <iframe src="{{ $pagina['preview_url'] }}" title="Página {{ $pagina['pagina'] }}"
                                                class="h-full w-full bg-white" loading="lazy"></iframe>
                                            <div class="absolute left-2 top-2 rounded-lg bg-slate-950/80 px-2 py-1 text-[11px] font-black text-white">
                                                {{ $pagina['fuente_nombre'] }} · pág. {{ $pagina['pagina'] }}
                                            </div>
                                        </div>
                                        <div class="space-y-3 p-3">
                                            <label class="block">
                                                <span class="mb-1 block text-xs font-black text-slate-600 dark:text-slate-300">Tipo de documento</span>
                                                <select wire:change="actualizarTipo('{{ $pagina['clave'] }}', $event.target.value)"
                                                    class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold dark:border-neutral-700 dark:bg-neutral-950">
                                                    <option value="">Sin clasificar</option>
                                                    @foreach ($tipos as $tipo)
                                                        <option value="{{ $tipo['id'] }}" @selected((int) ($pagina['tipo_documento_tutor_id'] ?? 0) === (int) $tipo['id'])>{{ $tipo['nombre'] }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex gap-1.5">
                                                    <button type="button" wire:click="rotarPagina('{{ $pagina['clave'] }}', -90)" title="Girar a la izquierda"
                                                        class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-neutral-700">↶</button>
                                                    <button type="button" wire:click="rotarPagina('{{ $pagina['clave'] }}', 90)" title="Girar a la derecha"
                                                        class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-neutral-700">↷</button>
                                                </div>
                                                @if ($pagina['tipo_documento_tutor_id'])
                                                    <div class="flex gap-1.5">
                                                        <button type="button" wire:click="moverPagina('{{ $pagina['clave'] }}', 'arriba')" title="Mover antes"
                                                            class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-black text-slate-600 dark:border-neutral-700">←</button>
                                                        <span class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">#{{ $pagina['orden'] }}</span>
                                                        <button type="button" wire:click="moverPagina('{{ $pagina['clave'] }}', 'abajo')" title="Mover después"
                                                            class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-black text-slate-600 dark:border-neutral-700">→</button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="flex min-h-[420px] items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-white text-center dark:border-neutral-700 dark:bg-neutral-900">
                                <div class="max-w-sm p-8">
                                    <h3 class="text-lg font-black text-slate-900 dark:text-white">No hay páginas para organizar</h3>
                                    <p class="mt-2 text-sm text-slate-500">Cierra el organizador y sube un PDF o una imagen desde el expediente del responsable.</p>
                                </div>
                            </div>
                        @endif
                    </main>
                </div>
            </section>
        </div>
    @endif
</div>
