<div id="reanudaciones-formulario" class="space-y-5"
    x-data="{
        claveEstado: @js('moctezuma:persona-nivel:reanudaciones:v1:usuario:' . (auth()->id() ?: 'invitado')),
        temporizadorEstado: null,
        historialAbierto: {},
        excluidosAbiertos: false,
        search: @entangle('search').live,
        nivelesSeleccionados: @entangle('nivelesSeleccionados').live,
        gradoFiltro: @entangle('gradoFiltro').live,
        grupoFiltro: @entangle('grupoFiltro').live,
        rolFiltro: @entangle('rolFiltro').live,
        seleccionados: @entangle('seleccionados').live,
        cicloEscolarId: @entangle('cicloEscolarId').live,
        tipoReanudacion: @entangle('tipoReanudacion').live,
        fechaDirector: @entangle('fechaDirector').live,
        fechaDocente: @entangle('fechaDocente').live,
        copias: @entangle('copias').live,
        ccpPlantillaId: @entangle('ccpPlantillaId').live,
        ccpNombreNueva: @entangle('ccpNombreNueva').live,
        historialSearch: @entangle('historialSearch').live,
        historialNivel: @entangle('historialNivel').live,
        historialCiclo: @entangle('historialCiclo').live,
        async init() {
            let guardado = null;

            try {
                const contenido = localStorage.getItem(this.claveEstado);
                guardado = contenido ? JSON.parse(contenido) : null;
            } catch (error) {
                localStorage.removeItem(this.claveEstado);
                console.warn('No fue posible leer el estado de Reanudaciones.', error);
            }

            if (guardado && typeof guardado === 'object') {
                this.historialAbierto = this.copiar(guardado.historialAbierto, {});
                this.excluidosAbiertos = Boolean(guardado.excluidosAbiertos);

                try {
                    await this.$wire.restaurarEstadoLocal(guardado);
                } catch (error) {
                    console.warn('No fue posible restaurar Reanudaciones.', error);
                }
            }

            [
                'search', 'nivelesSeleccionados', 'gradoFiltro', 'grupoFiltro', 'rolFiltro',
                'seleccionados', 'cicloEscolarId', 'tipoReanudacion', 'fechaDirector',
                'fechaDocente', 'copias', 'ccpPlantillaId', 'ccpNombreNueva',
                'historialSearch', 'historialNivel', 'historialCiclo', 'historialAbierto',
                'excluidosAbiertos',
            ].forEach((propiedad) => this.$watch(propiedad, () => this.programarGuardado()));

            this.guardarEstado();
        },
        programarGuardado() {
            clearTimeout(this.temporizadorEstado);
            this.temporizadorEstado = setTimeout(() => this.guardarEstado(), 250);
        },
        guardarEstado() {
            const estado = {
                search: this.search,
                nivelesSeleccionados: this.copiar(this.nivelesSeleccionados, []),
                gradoFiltro: this.gradoFiltro,
                grupoFiltro: this.grupoFiltro,
                rolFiltro: this.rolFiltro,
                seleccionados: this.copiar(this.seleccionados, []),
                cicloEscolarId: this.cicloEscolarId,
                tipoReanudacion: this.tipoReanudacion,
                fechaDirector: this.fechaDirector,
                fechaDocente: this.fechaDocente,
                copias: this.copias,
                ccpPlantillaId: this.ccpPlantillaId,
                ccpNombreNueva: this.ccpNombreNueva,
                historialSearch: this.historialSearch,
                historialNivel: this.historialNivel,
                historialCiclo: this.historialCiclo,
                historialAbierto: this.copiar(this.historialAbierto, {}),
                excluidosAbiertos: this.excluidosAbiertos,
            };

            try {
                localStorage.setItem(this.claveEstado, JSON.stringify(estado));
            } catch (error) {
                console.warn('No fue posible guardar Reanudaciones.', error);
            }
        },
        copiar(valor, predeterminado) {
            try {
                return JSON.parse(JSON.stringify(valor ?? predeterminado));
            } catch (error) {
                return predeterminado;
            }
        },
    }"
    x-on:abrir-url-reanudaciones.window="window.open($event.detail.url, '_blank')"
    x-on:desplazar-reanudaciones-formulario.window="document.getElementById('reanudaciones-formulario')?.scrollIntoView({ behavior: 'smooth', block: 'start' })">

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="h-1.5 bg-gradient-to-r from-[#006492] via-blue-600 to-[#88AC2E]"></div>
        <div class="p-5 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.2em] text-[#006492] dark:text-sky-300">Oficios laborales</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">Reanudaciones de labores</h2>
                    <p class="mt-1 max-w-3xl text-sm text-slate-600 dark:text-slate-400">
                        Genera oficios individuales o masivos con la plantilla de personal vigente en el ciclo y fecha seleccionados.
                        Una persona con varias funciones recibe un solo oficio con sus cargos reunidos.
                    </p>
                </div>

                @if ($editandoLote)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                        <p class="font-black">Editando un lote existente</p>
                        <button type="button" wire:click="cancelarEdicion" class="mt-1 font-bold underline">Cancelar edición</button>
                    </div>
                @endif
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                <flux:field>
                    <flux:label>Ciclo escolar</flux:label>
                    <flux:select wire:model.live="cicloEscolarId">
                        <flux:select.option value="">Selecciona un ciclo</flux:select.option>
                        @foreach ($ciclos as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">
                                {{ $ciclo->nombre }}{{ $ciclo->es_actual ? ' · actual' : '' }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="cicloEscolarId" />
                </flux:field>

                <flux:field>
                    <flux:label>Tipo de reanudación</flux:label>
                    <flux:select wire:model.live="tipoReanudacion">
                        @foreach ($tipos as $valor => $label)
                            <flux:select.option value="{{ $valor }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="tipoReanudacion" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha de personal directivo</flux:label>
                    <flux:input wire:model.live="fechaDirector" type="date" icon="calendar-days" />
                    <flux:description>Director, directora, subdirección, rectoría o coordinación académica.</flux:description>
                    <flux:error name="fechaDirector" />
                </flux:field>

                <flux:field>
                    <flux:label>Fecha del personal</flux:label>
                    <flux:input wire:model.live="fechaDocente" type="date" icon="calendar-days" />
                    <flux:description>También determina la fotografía histórica de la plantilla.</flux:description>
                    <flux:error name="fechaDocente" />
                </flux:field>
            </div>

            @if ($cicloSeleccionado && $tipoReanudacion === 'receso')
                <div class="mt-5 rounded-2xl border p-4 {{ $cicloCitado ? 'border-sky-200 bg-sky-50/80 dark:border-sky-900 dark:bg-sky-950/30' : 'border-rose-200 bg-rose-50 dark:border-rose-900 dark:bg-rose-950/30' }}">
                    <div class="flex items-start gap-3">
                        @if ($cicloCitado)
                            <flux:icon.information-circle class="mt-0.5 size-5 shrink-0 text-sky-700 dark:text-sky-300" />
                        @else
                            <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0 text-rose-700 dark:text-rose-300" />
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-black uppercase tracking-[.16em] {{ $cicloCitado ? 'text-sky-700 dark:text-sky-300' : 'text-rose-700 dark:text-rose-300' }}">
                                Regla especial del receso escolar de agosto
                            </p>
                            @if ($cicloCitado)
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-xl border border-white/80 bg-white/80 px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ciclo de plantilla</p>
                                        <p class="mt-1 text-base font-black text-slate-950 dark:text-white">{{ $cicloSeleccionado->nombre }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Personal, cargos, funciones, fechas y membrete.</p>
                                    </div>
                                    <div class="rounded-xl border border-white/80 bg-white/80 px-4 py-3 shadow-sm dark:border-neutral-800 dark:bg-neutral-900/70">
                                        <p class="text-[10px] font-black uppercase tracking-wide text-slate-500">Ciclo que aparecerá en el oficio</p>
                                        <p class="mt-1 text-base font-black text-slate-950 dark:text-white">{{ $cicloCitado->nombre }}</p>
                                        <p class="mt-1 text-xs text-slate-500">Solo se usa para la redacción del receso escolar.</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-slate-600 dark:text-slate-300">
                                    El personal se obtiene exclusivamente de la plantilla {{ $cicloSeleccionado->nombre }}; no se recuperan profesores del ciclo {{ $cicloCitado->nombre }}.
                                </p>
                            @else
                                <p class="mt-2 text-sm font-bold text-rose-800 dark:text-rose-200">
                                    No se encontró el ciclo escolar anterior a {{ $cicloSeleccionado->nombre }}. Regístralo antes de previsualizar o generar estos oficios.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="mt-5 border-t border-slate-200 pt-5 dark:border-neutral-800">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-xs font-black uppercase tracking-wide text-slate-600 dark:text-slate-300">Niveles incluidos</p>
                        <p class="text-xs text-slate-500">Puedes combinar varios niveles en un mismo ZIP o Word masivo.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($niveles as $nivel)
                            <div class="rounded-xl border px-3 py-2 transition
                                {{ in_array($nivel->id, array_map('intval', $nivelesSeleccionados), true) ? 'border-blue-300 bg-blue-50 dark:border-blue-800 dark:bg-blue-950/40' : 'border-slate-200 bg-white dark:border-neutral-700 dark:bg-neutral-950' }}">
                                <flux:checkbox
                                    wire:model.live="nivelesSeleccionados"
                                    value="{{ $nivel->id }}"
                                    :label="$nivel->nombre"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 bg-gradient-to-r from-sky-50 via-white to-lime-50 p-5 dark:border-neutral-800 dark:from-sky-950/30 dark:via-neutral-900 dark:to-lime-950/20">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <div class="grid size-11 shrink-0 place-items-center rounded-2xl bg-[#006492]/10 text-[#006492] dark:bg-sky-400/10 dark:text-sky-300">
                        <flux:icon.photo class="size-5" />
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[.22em] text-[#006492]">Membretes institucionales</p>
                        <h3 class="mt-1 text-lg font-black text-slate-950 dark:text-white">Hoja membretada por nivel y ciclo escolar</h3>
                        <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500">
                            Sube una hoja membretada completa tamaño carta. Se usa como fondo en PDF, ZIP y Word, y cada cambio crea una versión histórica.
                        </p>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-right shadow-sm dark:border-neutral-700 dark:bg-neutral-950/70">
                    <p class="text-[10px] font-black uppercase tracking-wide text-slate-400">Ciclo configurado</p>
                    <p class="mt-1 text-sm font-black text-slate-800 dark:text-slate-100">
                        {{ optional($ciclos->firstWhere('id', (int) $cicloEscolarId))->nombre ?: 'Sin ciclo' }}
                    </p>
                </div>
            </div>
        </div>

        @if ($nivelesSinMembrete->isNotEmpty())
            <div class="border-b border-amber-200 bg-amber-50 px-5 py-3 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                <div class="flex items-start gap-2">
                    <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0" />
                    <p>
                        <b>Sin membrete activo:</b> {{ $nivelesSinMembrete->implode(', ') }}. Puedes generar los oficios de todos modos; esos niveles saldrán sin imagen de fondo.
                    </p>
                </div>
            </div>
        @endif

        <div class="grid gap-4 p-5 xl:grid-cols-2">
            @foreach ($niveles as $nivel)
                @php
                    $membrete = $membretesActivos->get($nivel->id);
                    $nuevoMembrete = $membretesNuevos[$nivel->id] ?? null;
                    $tamanoKb = $membrete?->size_bytes ? number_format($membrete->size_bytes / 1024, 0) . ' KB' : null;
                @endphp

                <article wire:key="membrete-reanudacion-{{ $cicloEscolarId }}-{{ $nivel->id }}"
                    class="overflow-hidden rounded-3xl border {{ $membrete ? 'border-emerald-200 dark:border-emerald-900' : 'border-slate-200 dark:border-neutral-700' }} bg-white dark:bg-neutral-950">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3 dark:border-neutral-800">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-black text-slate-950 dark:text-white">{{ $nivel->nombre }}</h4>
                                @if ($membrete)
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[9px] font-black uppercase text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Activo · v{{ $membrete->version }}</span>
                                @else
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[9px] font-black uppercase text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">Sin membrete</span>
                                @endif
                            </div>
                            <p class="mt-1 text-[11px] text-slate-500">PNG, JPG, JPEG o WebP · máximo 5 MB.</p>
                        </div>
                        @if ($membrete)
                            <span class="text-[10px] font-bold text-slate-400">Actualizado {{ $membrete->updated_at?->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>

                    <div class="grid gap-4 p-4 lg:grid-cols-[190px_1fr]">
                        <div>
                            <div class="flex aspect-[8.5/11] items-center justify-center overflow-hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50 p-2 dark:border-neutral-700 dark:bg-neutral-900">
                                @if ($nuevoMembrete)
                                    <img src="{{ $nuevoMembrete->temporaryUrl() }}" class="h-full w-full object-contain" alt="Vista previa del nuevo membrete de {{ $nivel->nombre }}">
                                @elseif ($membrete)
                                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($membrete->archivo_path) }}" class="h-full w-full object-contain" alt="Membrete de {{ $nivel->nombre }}">
                                @else
                                    <div class="px-3 text-center">
                                        <flux:icon.document class="mx-auto size-8 text-slate-300" />
                                        <p class="mt-2 text-[11px] font-bold text-slate-400">Sin imagen configurada</p>
                                    </div>
                                @endif
                            </div>
                            @if ($membrete)
                                <div class="mt-2 space-y-1 text-[10px] text-slate-500">
                                    <p class="truncate" title="{{ $membrete->nombre_original }}"><b>Archivo:</b> {{ $membrete->nombre_original }}</p>
                                    @if ($tamanoKb)<p><b>Tamaño:</b> {{ $tamanoKb }}</p>@endif
                                    <p><b>Versión:</b> {{ $membrete->version }}</p>
                                </div>
                            @endif
                        </div>

                        <div class="flex min-w-0 flex-col gap-3">
                            <flux:field>
                                <flux:label>{{ $membrete ? 'Cambiar imagen' : 'Subir membrete' }}</flux:label>
                                <flux:input type="file" wire:model="membretesNuevos.{{ $nivel->id }}" accept="image/png,image/jpeg,image/webp" />
                                <flux:error name="membretesNuevos.{{ $nivel->id }}" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Margen superior del contenido (mm)</flux:label>
                                <flux:input type="number" min="0" max="80" step="0.5" wire:model="margenesMembrete.{{ $nivel->id }}" />
                                <flux:description>Recomendado: 32 mm. Ajusta este valor si el encabezado del membrete ocupa más o menos espacio.</flux:description>
                                <flux:error name="margenesMembrete.{{ $nivel->id }}" />
                            </flux:field>

                            <div class="mt-auto rounded-2xl border {{ $membrete ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/25' : 'border-slate-200 bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900' }} p-3 text-[11px]">
                                @if ($membrete)
                                    <p class="font-black text-emerald-800 dark:text-emerald-300">Este membrete se usará al generar el oficio.</p>
                                    <p class="mt-1 text-emerald-700/80 dark:text-emerald-300/70">Las versiones anteriores no se eliminan y siguen disponibles para documentos históricos.</p>
                                @else
                                    <p class="font-black text-slate-600 dark:text-slate-300">El oficio se generará sin imagen de fondo.</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                @if ($membrete)
                                    <button type="button"
                                        @click="Swal.fire({title:'¿Quitar membrete activo?',text:'La versión histórica se conservará y los nuevos oficios saldrán sin fondo hasta que subas otro.',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, quitar',cancelButtonText:'Cancelar',confirmButtonColor:'#dc2626'}).then(r => r.isConfirmed && $wire.eliminarMembrete({{ $nivel->id }}))"
                                        class="inline-flex h-10 items-center justify-center rounded-xl border border-rose-200 bg-white px-4 text-xs font-black text-rose-700 transition hover:bg-rose-50 dark:border-rose-900 dark:bg-neutral-950 dark:text-rose-300">
                                        Eliminar membrete
                                    </button>
                                @endif
                                <button type="button" wire:click="guardarMembrete({{ $nivel->id }})" wire:loading.attr="disabled"
                                    wire:target="guardarMembrete({{ $nivel->id }}),membretesNuevos.{{ $nivel->id }}"
                                    class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-[#006492] px-5 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-sky-800 hover:shadow-md disabled:opacity-50">
                                    <flux:icon.check class="size-4" />
                                    {{ $membrete ? 'Guardar cambios' : 'Guardar membrete' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    @if ($nivelesSinPlantillaPublicada->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
            <div class="flex items-start gap-3">
                <flux:icon.exclamation-triangle class="mt-0.5 size-5 shrink-0" />
                <div>
                    <p class="font-black">Hay niveles sin plantilla disponible para documentos</p>
                    <p class="mt-1">{{ $nivelesSinPlantillaPublicada->implode(', ') }}. Publica la plantilla del ciclo seleccionado o consulta un ciclo cerrado para generar reanudaciones.</p>
                </div>
            </div>
        </div>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-end">
            <div class="flex-1">
                <flux:field>
                    <flux:label>Buscar personal</flux:label>
                    <flux:input
                        wire:model.live.debounce.350ms="search"
                        type="search"
                        icon="magnifying-glass"
                        clearable
                        placeholder="Nombre o apellidos..."
                    />
                </flux:field>
            </div>
            <div class="min-w-52">
                <flux:field>
                    <flux:label>Grado</flux:label>
                    <flux:select wire:model.live="gradoFiltro">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($grados as $grado)
                            <flux:select.option value="{{ $grado->id }}">
                                {{ $grado->nivel?->nombre ? $grado->nivel->nombre . ' · ' : '' }}{{ $grado->nombre }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
            <div class="min-w-52">
                <flux:field>
                    <flux:label>Grupo</flux:label>
                    <flux:select wire:model.live="grupoFiltro">
                        <flux:select.option value="">Todos</flux:select.option>
                        @foreach ($grupos as $grupo)
                            <flux:select.option value="{{ $grupo->id }}">
                                {{ $grupo->clave ?: ($grupo->asignacionGrupo?->nombre ?? 'Grupo ' . $grupo->id) }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
            <div class="min-w-60">
                <flux:field>
                    <flux:label>Función / rol</flux:label>
                    <flux:select wire:model.live="rolFiltro">
                        <flux:select.option value="">Todas</flux:select.option>
                        @foreach ($roles as $rol)
                            <flux:select.option value="{{ $rol->id }}">{{ $rol->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
            </div>
            <button type="button" wire:click='seleccionarVisibles(@json($filas->pluck("id")->values()))'
                class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300">
                Seleccionar visibles
            </button>
        </div>

        <div class="mt-5 grid gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                <p class="text-[10px] font-black uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Listos para generar</p>
                <p class="mt-1 text-2xl font-black text-emerald-800 dark:text-emerald-200">{{ $listosCount }}</p>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-[10px] font-black uppercase tracking-wide text-amber-700 dark:text-amber-300">Con advertencias</p>
                <p class="mt-1 text-2xl font-black text-amber-800 dark:text-amber-200">{{ $advertenciasCount }}</p>
            </div>
            <button type="button" @click="excluidosAbiertos = !excluidosAbiertos"
                class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-left dark:border-rose-900 dark:bg-rose-950/30">
                <p class="text-[10px] font-black uppercase tracking-wide text-rose-700 dark:text-rose-300">Excluidos automáticamente</p>
                <div class="flex items-center justify-between">
                    <p class="mt-1 text-2xl font-black text-rose-800 dark:text-rose-200">{{ $excluidos->count() }}</p>
                    <span class="text-xs font-bold text-rose-700">Ver detalles</span>
                </div>
            </button>
        </div>

        <div x-show="excluidosAbiertos" x-cloak class="mt-3 rounded-2xl border border-rose-200 bg-rose-50/60 p-4 dark:border-rose-900 dark:bg-rose-950/20">
            @forelse ($excluidos as $excluido)
                <p class="border-b border-rose-100 py-2 text-xs text-rose-800 last:border-0 dark:border-rose-900 dark:text-rose-200">
                    <span class="font-black">{{ $excluido['modelo']?->persona ? trim(($excluido['modelo']->persona->nombre ?? '') . ' ' . ($excluido['modelo']->persona->apellido_paterno ?? '')) : 'Registro sin identificar' }}:</span>
                    {{ $excluido['motivo'] }}
                </p>
            @empty
                <p class="text-sm text-slate-500">No hay registros excluidos.</p>
            @endforelse
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-3 border-b border-slate-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-800">
            <div>
                <h3 class="text-lg font-black text-slate-950 dark:text-white">Personal disponible</h3>
                <p class="text-xs text-slate-500">Solo aparecen asignaciones vigentes en la fecha seleccionada. Cupo ilimitado.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                    {{ count($seleccionados) }} seleccionada(s)
                </div>
                @if (count($seleccionados))
                    <button type="button" wire:click="limpiarSeleccion" class="text-xs font-bold text-slate-500 underline">Limpiar</button>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-neutral-800">
                <thead class="bg-slate-50 dark:bg-neutral-950/50">
                    <tr>
                        <th class="w-12 px-4 py-3 text-left"></th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Personal</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Nivel</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Funciones</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Grado / grupo</th>
                        <th class="px-4 py-3 text-left text-[11px] font-black uppercase tracking-wide text-slate-500">Fecha aplicada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($filas as $fila)
                        <tr wire:key="reanudacion-personal-{{ $fila['id'] }}" class="hover:bg-slate-50/80 dark:hover:bg-neutral-800/40">
                            <td class="px-4 py-3 align-top">
                                <flux:checkbox wire:model.live="seleccionados" value="{{ $fila['id'] }}" />
                            </td>
                            <td class="px-4 py-3 align-top">
                                <p class="font-black text-slate-900 dark:text-white">{{ $fila['persona'] }}</p>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if ($fila['es_directivo'])
                                        <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-black text-violet-700 dark:bg-violet-950 dark:text-violet-300">PERSONAL DIRECTIVO</span>
                                    @else
                                        <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">ACTIVO EN LA FECHA</span>
                                    @endif
                                    @foreach ($fila['advertencias'] as $advertencia)
                                        <span title="{{ $advertencia }}" class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-black text-amber-700 dark:bg-amber-950 dark:text-amber-300">ADVERTENCIA</span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top font-bold text-slate-700 dark:text-slate-200">{{ $fila['nivel'] }}</td>
                            <td class="px-4 py-3 align-top text-xs text-slate-600 dark:text-slate-300">{{ implode(' · ', $fila['cargos']) }}</td>
                            <td class="px-4 py-3 align-top text-xs text-slate-600 dark:text-slate-300">
                                <p>{{ $fila['grados'] ? implode(', ', $fila['grados']) : 'General' }}</p>
                                <p class="font-bold">{{ $fila['grupos'] ? implode(', ', $fila['grupos']) : 'Sin grupo específico' }}</p>
                            </td>
                            <td class="px-4 py-3 align-top text-xs text-slate-500">{{ \Carbon\Carbon::parse($fila['es_directivo'] ? $fechaDirector : $fechaDocente)->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-14 text-center text-slate-500">No existe personal compatible con los filtros, ciclo y fecha seleccionados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @error('seleccionados') <p class="border-t border-rose-200 bg-rose-50 px-5 py-3 text-sm font-bold text-rose-700">{{ $message }}</p> @enderror
    </section>

    <section class="grid gap-5 xl:grid-cols-5">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 xl:col-span-3">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">C.C.P.</h3>
                    <p class="text-xs text-slate-500">El mismo contenido se aplicará a todos los oficios de esta operación.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">EDITOR GENERAL</span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_auto] sm:items-end">
                <flux:field>
                    <flux:label>Plantilla C.C.P.</flux:label>
                    <flux:select wire:model.live="ccpPlantillaId">
                        <flux:select.option value="">Sin plantilla</flux:select.option>
                        @foreach ($ccpPlantillas as $plantilla)
                            <flux:select.option value="{{ $plantilla->id }}">{{ $plantilla->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <button type="button" wire:click="eliminarPlantillaCcp" @disabled(!$ccpPlantillaId)
                    class="rounded-2xl border border-rose-200 px-4 py-2 text-xs font-black text-rose-700 disabled:cursor-not-allowed disabled:opacity-40">Eliminar plantilla</button>
            </div>

            <div class="mt-3">
                <flux:field>
                    <flux:label>Copias para conocimiento</flux:label>
                    <flux:textarea wire:model="copias" rows="6" placeholder="Escribe aquí las copias..." />
                    <flux:error name="copias" />
                </flux:field>
            </div>

            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <flux:field>
                        <flux:label>Nombre de la plantilla</flux:label>
                        <flux:input wire:model="ccpNombreNueva" type="text" icon="bookmark"
                            placeholder="Nombre para guardar esta plantilla" />
                        <flux:error name="ccpNombreNueva" />
                    </flux:field>
                </div>
                <button type="button" wire:click="guardarPlantillaCcp"
                    class="rounded-2xl border border-[#88AC2E]/50 bg-[#88AC2E]/10 px-4 py-2.5 text-sm font-black text-[#557015] hover:bg-[#88AC2E]/20 dark:text-lime-300">
                    Guardar plantilla C.C.P.
                </button>
            </div>
        </div>

        <div class="rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 to-sky-50 p-5 shadow-sm dark:border-blue-900 dark:from-blue-950/40 dark:to-sky-950/20 xl:col-span-2">
            <p class="text-xs font-black uppercase tracking-[.2em] text-blue-700 dark:text-blue-300">Generación</p>
            <h3 class="mt-1 text-xl font-black text-slate-950 dark:text-white">{{ $editandoLote ? 'Regenerar lote' : 'Crear documentos' }}</h3>
            <p class="mt-1 text-xs text-slate-600 dark:text-slate-400">La vista previa no guarda historial. Las tres opciones de generación sí registran el movimiento.</p>

            <div class="mt-5 grid gap-2">
                <button type="button" wire:click="previsualizar" wire:loading.attr="disabled"
                    @disabled($tipoReanudacion === 'receso' && ! $cicloCitado)
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-blue-200 bg-white px-4 py-3 text-sm font-black text-blue-700 shadow-sm hover:bg-blue-50 disabled:cursor-not-allowed disabled:opacity-40 dark:border-blue-900 dark:bg-neutral-900 dark:text-blue-300">
                    <span wire:loading.remove wire:target="previsualizar">Vista previa</span>
                    <span wire:loading wire:target="previsualizar">Preparando...</span>
                </button>
                <button type="button" wire:click="generar('pdf')" wire:loading.attr="disabled"
                    @disabled($tipoReanudacion === 'receso' && ! $cicloCitado)
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-[#006492] px-4 py-3 text-sm font-black text-white shadow hover:bg-sky-800 disabled:cursor-not-allowed disabled:opacity-40">
                    PDF individual
                </button>
                <button type="button" wire:click="generar('zip')" wire:loading.attr="disabled"
                    @disabled($tipoReanudacion === 'receso' && ! $cicloCitado)
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-700 px-4 py-3 text-sm font-black text-white shadow hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-40">
                    ZIP masivo · PDF por persona
                </button>
                <button type="button" wire:click="generar('word')" wire:loading.attr="disabled"
                    @disabled($tipoReanudacion === 'receso' && ! $cicloCitado)
                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-violet-700 px-4 py-3 text-sm font-black text-white shadow hover:bg-violet-800 disabled:cursor-not-allowed disabled:opacity-40">
                    Word masivo
                </button>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="border-b border-slate-200 p-5 dark:border-neutral-800">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">Historial de reanudaciones</h3>
                    <p class="text-xs text-slate-500">Consulta, vuelve a descargar, edita, regenera o elimina lotes.</p>
                </div>
                <div class="grid gap-2 sm:grid-cols-3">
                    <flux:input wire:model.live.debounce.350ms="historialSearch" type="search"
                        icon="magnifying-glass" clearable placeholder="Buscar en historial..." />
                    <flux:select wire:model.live="historialNivel" aria-label="Filtrar historial por nivel">
                        <flux:select.option value="">Todos los niveles</flux:select.option>
                        @foreach ($niveles as $nivel)
                            <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:select wire:model.live="historialCiclo" aria-label="Filtrar historial por ciclo escolar">
                        <flux:select.option value="">Todos los ciclos</flux:select.option>
                        @foreach ($ciclos as $ciclo)
                            <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-neutral-800">
            @forelse ($historial as $lote)
                <article wire:key="historial-reanudacion-{{ $lote['lote'] }}" class="p-4 sm:p-5">
                    <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <button type="button" @click="historialAbierto['{{ $lote['lote'] }}'] = !historialAbierto['{{ $lote['lote'] }}']" class="flex flex-1 items-center gap-3 text-left">
                            <div class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                <span class="text-sm font-black">{{ $lote['cantidad'] }}</span>
                            </div>
                            <div>
                                <p class="font-black text-slate-900 dark:text-white">{{ $lote['tipo'] }}</p>
                                @if ($lote['tipo_slug'] === 'receso')
                                    <p class="text-xs text-slate-500">
                                        Ciclo de plantilla: <b>{{ $lote['ciclo'] }}</b> · Receso correspondiente a: <b>{{ $lote['ciclo_citado'] }}</b> · {{ $lote['niveles'] }} · {{ $lote['fecha']->format('d/m/Y H:i') }} · {{ $lote['usuario'] }}
                                    </p>
                                @else
                                    <p class="text-xs text-slate-500">Ciclo: {{ $lote['ciclo'] }} · {{ $lote['niveles'] }} · {{ $lote['fecha']->format('d/m/Y H:i') }} · {{ $lote['usuario'] }}</p>
                                @endif
                            </div>
                        </button>

                        <div class="flex flex-wrap gap-2">
                            <a target="_blank" href="{{ route('misrutas.reanudaciones.lote', ['formato' => 'zip', 'lote' => $lote['lote']]) }}"
                                class="rounded-xl bg-blue-50 px-3 py-2 text-[11px] font-black text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300">ZIP PDF</a>
                            <a target="_blank" href="{{ route('misrutas.reanudaciones.lote', ['formato' => 'word', 'lote' => $lote['lote']]) }}"
                                class="rounded-xl bg-violet-50 px-3 py-2 text-[11px] font-black text-violet-700 hover:bg-violet-100 dark:bg-violet-950/40 dark:text-violet-300">WORD</a>
                            <button type="button" wire:click="editarLote('{{ $lote['lote'] }}')"
                                class="rounded-xl bg-amber-50 px-3 py-2 text-[11px] font-black text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300">EDITAR / REGENERAR</button>
                            <button type="button" @click="Swal.fire({title:'¿Eliminar lote?',text:'Se eliminarán {{ $lote['cantidad'] }} oficios del historial.',icon:'warning',showCancelButton:true,confirmButtonText:'Sí, eliminar',cancelButtonText:'Cancelar',confirmButtonColor:'#dc2626'}).then(r => r.isConfirmed && $wire.eliminarLote('{{ $lote['lote'] }}'))"
                                class="rounded-xl bg-rose-50 px-3 py-2 text-[11px] font-black text-rose-700 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300">ELIMINAR</button>
                        </div>
                    </div>

                    <div x-show="historialAbierto['{{ $lote['lote'] }}']" x-cloak class="mt-4 overflow-hidden rounded-2xl border border-slate-200 dark:border-neutral-800">
                        <table class="min-w-full divide-y divide-slate-200 text-xs dark:divide-neutral-800">
                            <thead class="bg-slate-50 dark:bg-neutral-950/50">
                                <tr>
                                    <th class="px-4 py-2 text-left font-black text-slate-500">Personal</th>
                                    <th class="px-4 py-2 text-left font-black text-slate-500">Nivel</th>
                                    <th class="px-4 py-2 text-left font-black text-slate-500">Fecha</th>
                                    <th class="px-4 py-2 text-right font-black text-slate-500">Documento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @foreach ($lote['items'] as $item)
                                    <tr>
                                        <td class="px-4 py-2 font-bold text-slate-800 dark:text-slate-200">{{ $item->persona_nombre }}</td>
                                        <td class="px-4 py-2 text-slate-500">{{ $item->nivel_nombre }}</td>
                                        <td class="px-4 py-2 text-slate-500">{{ $item->fecha_documento->format('d/m/Y') }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <a target="_blank" href="{{ route('misrutas.reanudaciones.individual', $item) }}" class="font-black text-blue-700 hover:underline">Ver PDF</a>
                                            <span class="mx-1 text-slate-300">·</span>
                                            <a href="{{ route('misrutas.reanudaciones.individual', ['reanudacion' => $item, 'descargar' => 1]) }}" class="font-black text-slate-600 hover:underline dark:text-slate-300">Descargar</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>
            @empty
                <div class="px-5 py-16 text-center text-slate-500">Todavía no hay reanudaciones registradas.</div>
            @endforelse
        </div>
    </section>
</div>
