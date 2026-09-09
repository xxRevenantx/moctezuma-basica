<div class="space-y-6">
    <section class="rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.28em] text-emerald-600 dark:text-emerald-300">
                    Nuevo módulo de documentos
                </p>
                <h3 class="mt-2 text-xl font-black text-slate-900 dark:text-white">
                    Portadas para profesores
                </h3>
                <p class="mt-2 max-w-3xl text-sm text-slate-600 dark:text-slate-300">
                    Sube una portada institucional por nivel, conserva versiones, selecciona al profesor y descarga su
                    documento con datos reales del sistema.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/30 dark:text-emerald-300">
                    Portadas activas por nivel
                </span>
                <span class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1 text-xs font-bold text-cyan-700 dark:border-cyan-900/40 dark:bg-cyan-950/30 dark:text-cyan-300">
                    Vista previa en tiempo real
                </span>
            </div>
        </div>
    </section>

    <section class="grid grid-cols-1 gap-6 xl:grid-cols-12">
        <article class="space-y-5 xl:col-span-4">
            <div class="rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-base font-black text-slate-900 dark:text-white">Configuración de portada</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Sube la imagen base y define el comportamiento general.</p>
                    </div>
                    @if (auth()->user()?->is_admin)
                        <span class="rounded-full bg-amber-50 px-3 py-1 text-[11px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">Admin</span>
                    @endif
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4">
                    <flux:field>
                        <flux:label>Nivel académico</flux:label>
                        <flux:select wire:model.live="nivel_id">
                            <flux:select.option value="">Selecciona el nivel</flux:select.option>
                            @foreach ($this->niveles as $nivel)
                                <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }} @if($nivel->cct) - C.C.T. {{ $nivel->cct }} @endif</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="nivel_id" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Ciclo escolar</flux:label>
                        <flux:select wire:model.live="ciclo_escolar_id">
                            <flux:select.option value="">Selecciona el ciclo</flux:select.option>
                            @foreach ($this->ciclos as $ciclo)
                                <flux:select.option value="{{ $ciclo->id }}">{{ $ciclo->nombre }}{{ $ciclo->es_actual ? ' · Actual' : '' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if (auth()->user()?->is_admin)
                        <flux:field>
                            <flux:label>Nombre de la plantilla</flux:label>
                            <flux:input wire:model.live.debounce.500ms="nombre_plantilla" type="text" placeholder="Ej. Portada institucional secundaria 2026" />
                        </flux:field>

                        <div
                            x-data="{ cargando: false }"
                            @dragover.prevent="$el.classList.add('ring-4','ring-sky-100','border-sky-400')"
                            @dragleave.prevent="$el.classList.remove('ring-4','ring-sky-100','border-sky-400')"
                            @drop.prevent="$el.classList.remove('ring-4','ring-sky-100','border-sky-400'); if ($event.dataTransfer.files.length) { $refs.portadaInput.files = $event.dataTransfer.files; $refs.portadaInput.dispatchEvent(new Event('change', { bubbles: true })); }"
                            class="rounded-[1.5rem] border-2 border-dashed border-slate-300 bg-slate-50 p-4 text-center transition dark:border-neutral-700 dark:bg-neutral-950/40">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300">
                                <flux:icon.arrow-up-tray class="h-7 w-7" />
                            </div>
                            <h5 class="mt-3 text-sm font-black text-slate-900 dark:text-white">Arrastra la portada aquí</h5>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">También puedes hacer clic para seleccionar PNG, JPG o WebP. Máximo 5 MB.</p>
                            <label class="mt-4 inline-flex cursor-pointer items-center rounded-2xl bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                                Seleccionar imagen
                                <input x-ref="portadaInput" type="file" wire:model="portadaNueva" accept="image/png,image/jpeg,image/webp" class="hidden">
                            </label>
                            <flux:error name="portadaNueva" />
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                        <flux:field>
                            <flux:label>Lugar</flux:label>
                            <flux:input wire:model.live.debounce.500ms="configuracion.lugar" type="text" placeholder="Cd. Altamirano, Gro." />
                        </flux:field>

                        <flux:field>
                            <flux:label>Nombre manual de escuela</flux:label>
                            <flux:input wire:model.live.debounce.500ms="configuracion.escuela_nombre" type="text" placeholder="Solo si no deseas tomarlo del nivel" />
                        </flux:field>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                        <div class="space-y-3">
                            <flux:switch wire:model.live="configuracion.usar_cargo_real" label="Usar cargo real del profesor en vez de 'Profesor(a)'" />
                            <flux:switch wire:model.live="configuracion.escuela_automatica" label="Usar el nombre automático de la escuela según el nivel" />
                        </div>
                    </div>

                    @if (auth()->user()?->is_admin)
                        <button type="button" wire:click="guardarPlantilla"
                            wire:loading.attr="disabled"
                            wire:target="guardarPlantilla,portadaNueva"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                            <flux:icon.arrow-down-tray class="h-4 w-4" />
                            Guardar portada como activa
                        </button>
                    @endif
                </div>
            </div>

            <div class="rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-base font-black text-slate-900 dark:text-white">Biblioteca de portadas</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Se conserva historial por nivel y puedes reactivar cualquier versión.</p>
                    </div>
                </div>

                @if (!$nivel_id)
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                        Primero selecciona un nivel para ver sus portadas.
                    </div>
                @elseif ($this->plantillas->isEmpty())
                    <div class="mt-4 rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">
                        Aún no hay portadas registradas para este nivel.
                    </div>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($this->plantillas as $plantilla)
                            <article class="rounded-2xl border {{ $plantilla->activo ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/60 dark:bg-emerald-950/20' : 'border-slate-200 bg-slate-50 dark:border-neutral-800 dark:bg-neutral-950/40' }} p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $plantilla->nombre }}</p>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">v{{ $plantilla->version }} · {{ $plantilla->nombre_original ?: 'Sin nombre original' }}</p>
                                        <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">Actualizado {{ $plantilla->updated_at?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    @if ($plantilla->activo)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">Activa</span>
                                    @endif
                                </div>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button type="button" wire:click="$set('plantilla_id', {{ $plantilla->id }})" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-100 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Usar</button>
                                    @if (auth()->user()?->is_admin && !$plantilla->activo)
                                        <button type="button" wire:click="activarPlantilla({{ $plantilla->id }})" class="rounded-xl border border-emerald-200 px-3 py-2 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-900/60 dark:text-emerald-300 dark:hover:bg-emerald-950/30">Activar</button>
                                    @endif
                                    @if (auth()->user()?->is_admin && $plantilla->activo)
                                        <button type="button" wire:click="desactivarPlantilla({{ $plantilla->id }})" class="rounded-xl border border-rose-200 px-3 py-2 text-xs font-bold text-rose-700 transition hover:bg-rose-50 dark:border-rose-900/60 dark:text-rose-300 dark:hover:bg-rose-950/30">Desactivar</button>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </article>

        <article class="space-y-5 xl:col-span-8">
            <div class="rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h4 class="text-base font-black text-slate-900 dark:text-white">Generar portada</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Selecciona al profesor, revisa la vista previa y descarga su documento en PDF.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="limpiarFiltros" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Limpiar</button>
                        @if ($this->urlDescargaPdf)
                            <a href="{{ $this->urlDescargaPdf }}" target="_blank"
                                class="inline-flex items-center gap-2 rounded-2xl bg-sky-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-sky-700">
                                <flux:icon.arrow-down-tray class="h-4 w-4" />
                                Descargar PDF
                            </a>
                        @endif
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Modo</flux:label>
                        <flux:select wire:model.live="modo_descarga">
                            <flux:select.option value="individual">Individual</flux:select.option>
                            <flux:select.option value="seleccionados">Seleccionados</flux:select.option>
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Portada a utilizar</flux:label>
                        <flux:select wire:model.live="plantilla_id" :disabled="!$nivel_id || $this->plantillas->isEmpty()">
                            <flux:select.option value="">{{ $this->plantillas->isNotEmpty() ? 'Portada activa' : 'Sin plantillas' }}</flux:select.option>
                            @foreach ($this->plantillas as $plantilla)
                                <flux:select.option value="{{ $plantilla->id }}">{{ $plantilla->nombre }}{{ $plantilla->activo ? ' · Activa' : '' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field class="md:col-span-2 xl:col-span-2">
                        <flux:label>Buscar profesor</flux:label>
                        <flux:input type="search" wire:model.live.debounce.500ms="buscar_persona" :disabled="!$nivel_id" placeholder="{{ $nivel_id ? 'Nombre, CURP o RFC...' : 'Primero selecciona el nivel' }}" />
                    </flux:field>

                    @if ($modo_descarga === 'individual')
                        <flux:field class="md:col-span-2 xl:col-span-4">
                            <flux:label>Profesor individual</flux:label>
                            <flux:select wire:model.live="persona_individual_id" :disabled="!$nivel_id">
                                <flux:select.option value="">{{ $nivel_id ? 'Selecciona un profesor' : 'Primero selecciona un nivel' }}</flux:select.option>
                                @foreach ($this->personas as $persona)
                                    <flux:select.option value="{{ $persona->id }}">{{ $this->nombrePersona($persona) }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif
                </div>

                @if ($modo_descarga === 'seleccionados')
                    <div class="mt-6 grid grid-cols-1 gap-5 xl:grid-cols-12">
                        <div class="xl:col-span-7 rounded-[1.5rem] border border-slate-200 bg-slate-50 dark:border-neutral-800 dark:bg-neutral-950/40">
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-4 dark:border-neutral-800">
                                <div>
                                    <h5 class="text-sm font-black text-slate-900 dark:text-white">Resultados</h5>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">La selección se conserva aunque cambies la búsqueda.</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" wire:click="seleccionarTodosVisibles" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-white dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Seleccionar visibles</button>
                                    <button type="button" wire:click="quitarTodosVisibles" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-white dark:border-neutral-700 dark:text-slate-200 dark:hover:bg-neutral-800">Quitar visibles</button>
                                </div>
                            </div>
                            <div class="max-h-[26rem] overflow-auto p-3">
                                <div class="space-y-2">
                                    @forelse ($this->personas as $persona)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 transition hover:border-sky-300 hover:bg-sky-50/40 dark:border-neutral-800 dark:bg-neutral-900 dark:hover:border-sky-800 dark:hover:bg-sky-950/20">
                                            <flux:checkbox wire:model.live="personas_seleccionadas" value="{{ $persona->id }}" />
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ $this->nombrePersona($persona) }}</p>
                                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $this->rolPrincipal($persona) }} · RFC {{ $persona->rfc ?: 'S/C.' }} · CURP {{ $persona->curp ?: 'S/C.' }}</p>
                                            </div>
                                        </label>
                                    @empty
                                        <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">No hay profesores disponibles con ese filtro.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-5 rounded-[1.5rem] border border-slate-200 bg-white p-4 dark:border-neutral-800 dark:bg-neutral-900">
                            <h5 class="text-sm font-black text-slate-900 dark:text-white">Seleccionados</h5>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Se descargarán en el orden mostrado.</p>

                            <div class="mt-4 space-y-2">
                                @forelse ($this->personasSeleccionadasLista as $persona)
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                                        <p class="font-bold text-slate-900 dark:text-white">{{ $this->nombrePersona($persona) }}</p>
                                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $this->rolPrincipal($persona) }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">Aún no has seleccionado profesores.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-[1.7rem] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h4 class="text-base font-black text-slate-900 dark:text-white">Vista previa</h4>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">La vista previa usa la misma base que el PDF.</p>
                    </div>
                    @if ($this->personaPreview)
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">{{ $this->nombrePersona($this->personaPreview) }}</span>
                    @endif
                </div>

                @if (!$nivel_id)
                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">Selecciona un nivel para comenzar.</div>
                @elseif (!$this->plantillaSeleccionada && !$portadaNueva)
                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">No hay portada activa para este nivel. Sube o activa una plantilla.</div>
                @elseif (!$this->personaPreview)
                    <div class="mt-5 rounded-2xl border border-dashed border-slate-300 px-4 py-10 text-center text-sm text-slate-500 dark:border-neutral-700 dark:text-slate-400">Selecciona al menos un profesor para generar la vista previa.</div>
                @else
                    @php
                        $datos = $this->datosPreview;
                        $campos = $datos['campos'] ?? [];
                        $mapaCampos = [
                            'nombre_cargo' => ['label' => ($datos['cargo_label'] ?? 'PROFESOR(A)') . ':', 'value' => $datos['nombre'] ?? ''],
                            'rfc' => ['label' => 'RFC:', 'value' => $datos['rfc'] ?? 'S/C.'],
                            'curp' => ['label' => 'CURP:', 'value' => $datos['curp'] ?? 'S/C.'],
                            'clave_presupuestal' => ['label' => 'Clave Presupuestal:', 'value' => $datos['clave_presupuestal'] ?? 'S/C.'],
                            'telefono' => ['label' => 'Tel.:', 'value' => $datos['telefono'] ?? 'S/C.'],
                            'escuela' => ['label' => 'Nombre de la escuela:', 'value' => $datos['escuela'] ?? 'Centro Universitario Moctezuma'],
                            'cct' => ['label' => 'C.C.T.', 'value' => $datos['cct'] ?? 'S/C.'],
                            'lugar' => ['label' => 'Lugar:', 'value' => $datos['lugar'] ?? 'Cd. Altamirano, Gro.'],
                        ];
                    @endphp

                    <div class="mt-5 grid grid-cols-1 gap-6 xl:grid-cols-12">
                        <div class="xl:col-span-7">
                            <div class="mx-auto w-full max-w-[33rem] overflow-hidden rounded-[2rem] border border-slate-200 bg-slate-100 shadow-sm dark:border-neutral-800 dark:bg-neutral-950/40">
                                <div class="relative aspect-[85/110] w-full bg-white">
                                    @if ($this->fondoPreviewUrl)
                                        <img src="{{ $this->fondoPreviewUrl }}" alt="Portada" class="absolute inset-0 h-full w-full object-cover">
                                    @endif

                                    @foreach ($campos as $clave => $cfg)
                                        @continue(empty($cfg['visible']) || !isset($mapaCampos[$clave]))
                                        <div class="absolute z-10 text-slate-600"
                                            style="top: {{ $cfg['top'] }}%; left: {{ $cfg['left'] }}%; width: {{ $cfg['width'] }}%; font-size: {{ $cfg['font'] * 0.088 }}rem; line-height: 1.35;">
                                            <span class="font-extrabold text-sky-500">{{ $mapaCampos[$clave]['label'] }}</span>
                                            <span class="font-medium text-slate-600"> {{ $mapaCampos[$clave]['value'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="xl:col-span-5 space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950/40">
                                <h5 class="text-sm font-black text-slate-900 dark:text-white">Datos que se imprimirán</h5>
                                <dl class="mt-4 space-y-3 text-sm">
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">Profesor</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['nombre'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">Cargo</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['cargo_label'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">RFC</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['rfc'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">CURP</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['curp'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">Clave presupuestal</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['clave_presupuestal'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">Teléfono</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['telefono'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">Escuela</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['escuela'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">C.C.T.</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['cct'] }}</dd></div>
                                    <div class="flex justify-between gap-3"><dt class="font-bold text-slate-500 dark:text-slate-400">Lugar</dt><dd class="text-right font-semibold text-slate-800 dark:text-slate-100">{{ $datos['lugar'] }}</dd></div>
                                </dl>
                            </div>

                            @if (!empty($datos['alertas']))
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                    <p class="font-black">Observaciones automáticas</p>
                                    <ul class="mt-3 list-disc space-y-1 pl-5">
                                        @foreach ($datos['alertas'] as $alerta)
                                            <li>{{ $alerta }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </article>
    </section>
</div>
