{{-- resources/views/livewire/personas/crear-personal.blade.php --}}
<div>
    <!-- Header -->
    <div class="flex flex-col gap-2">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Crear el personal</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Formulario para crear nuevo personal.</p>
    </div>

    <div x-data="{ open: false }" class="my-4">
        <!-- Toggle -->
        <button type="button" @click="open = !open" :aria-expanded="open" aria-controls="panel-personal"
            class="group inline-flex items-center gap-2 rounded-2xl px-4 py-2.5
                   bg-gradient-to-r from-indigo-600 to-violet-600 text-white shadow
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-400
                   dark:focus:ring-offset-neutral-900">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-white/15">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 6v12m6-6H6" />
                </svg>
            </span>
            <span class="font-medium">{{ __('Nuevo Personal') }}</span>
            <span class="ml-1 transition-transform duration-200" :class="open ? 'rotate-180' : 'rotate-0'">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 15.5l-6-6h12l-6 6z" />
                </svg>
            </span>
        </button>

        <!-- Panel -->
        <div id="panel-personal" x-show="open" x-cloak x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]" class="relative mt-4">

            <form wire:submit.prevent="crearPersonal" class="group">
                <div
                    class="relative rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow-lg overflow-hidden">
                    <div class="h-1.5 w-full bg-gradient-to-r from-indigo-500 via-violet-500 to-fuchsia-500"></div>

                    <div class="p-5 sm:p-6 lg:p-8">
                        <div class="mb-5 flex items-center gap-3">
                            <div class="h-9 w-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 grid place-items-center">
                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M12 6v12m6-6H6" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Nuevo Personal</h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Completa los campos y guarda los
                                    cambios.</p>
                            </div>
                        </div>

                        <flux:field>
                            {{-- ====== AUTOLLENADO SOLO CURP PDF ====== --}}
                            <div x-data="{ openPdf: true }" class="my-6">
                                <button type="button" @click="openPdf = !openPdf" :aria-expanded="openPdf"
                                    class="group inline-flex w-full items-center justify-between gap-3 rounded-2xl border border-neutral-200 bg-white px-4 py-3 text-left shadow-sm
                                           hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/25
                                           dark:border-neutral-800 dark:bg-neutral-900 dark:hover:bg-neutral-800/60">
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-sky-600 via-blue-600 to-indigo-600 text-white shadow ring-1 ring-white/15">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24"
                                                fill="currentColor">
                                                <path d="M7 18h10v-2H7v2zm5-16l-5 5h3v6h4V7h3l-5-5z" />
                                            </svg>
                                        </span>

                                        <div class="leading-tight">
                                            <div class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                Extraer desde CURP (PDF)
                                            </div>
                                            <div class="text-xs text-neutral-600 dark:text-neutral-400">
                                                Solo obtiene <b>CURP</b> y <b>Nombre</b> (PDF con texto seleccionable).
                                            </div>
                                        </div>
                                    </div>

                                    <span class="transition-transform duration-200"
                                        :class="openPdf ? 'rotate-180' : 'rotate-0'">
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5 text-neutral-700 dark:text-neutral-200" viewBox="0 0 24 24"
                                            fill="currentColor">
                                            <path d="M12 15.5l-6-6h12l-6 6z" />
                                        </svg>
                                    </span>
                                </button>

                                <div x-show="openPdf" x-cloak class="mt-4">
                                    <div
                                        class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm p-4 sm:p-5">
                                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
                                            <div class="flex items-start gap-3">
                                                <div
                                                    class="mt-0.5 h-9 w-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/25 grid place-items-center ring-1 ring-indigo-500/15">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                        class="h-5 w-5 text-indigo-600 dark:text-indigo-300"
                                                        viewBox="0 0 24 24" fill="currentColor">
                                                        <path
                                                            d="M12 2a7 7 0 00-7 7v3a4 4 0 00-2 3v1a3 3 0 003 3h12a3 3 0 003-3v-1a4 4 0 00-2-3V9a7 7 0 00-7-7zm-5 10V9a5 5 0 0110 0v3H7z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="text-sm font-semibold text-neutral-900 dark:text-white">
                                                        Sube tu CURP (PDF)</div>
                                                    <div class="text-xs text-neutral-600 dark:text-neutral-400">
                                                        Recomendado: CURP descargada de RENAPO con texto.
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                                <label
                                                    class="inline-flex items-center gap-2 text-xs text-neutral-700 dark:text-neutral-200">
                                                    <input type="checkbox" wire:model="autollenar_forzar"
                                                        class="rounded border-neutral-300 dark:border-neutral-700" />
                                                    Sobrescribir campos ya llenos
                                                </label>

                                                <flux:button type="button" variant="primary" class="w-full sm:w-auto"
                                                    wire:click="autollenarDesdeCurpPdf" wire:loading.attr="disabled"
                                                    wire:target="autollenarDesdeCurpPdf,pdf_curp">
                                                    Extraer CURP
                                                </flux:button>
                                            </div>
                                        </div>

                                        {{-- Dropzone visual --}}
                                        <div class="mt-4">
                                            <label class="block">
                                                <div
                                                    class="relative rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-700
                                                            bg-neutral-50/80 dark:bg-neutral-950/30 p-4 sm:p-5
                                                            hover:bg-neutral-50 dark:hover:bg-neutral-900/40 transition">
                                                    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                                                        <div
                                                            class="h-10 w-10 rounded-2xl bg-white dark:bg-neutral-900 grid place-items-center ring-1 ring-black/5 dark:ring-white/10 shadow-sm">
                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                class="h-5 w-5 text-neutral-700 dark:text-neutral-200"
                                                                viewBox="0 0 24 24" fill="currentColor">
                                                                <path
                                                                    d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm1 7V3.5L19.5 9H15z" />
                                                            </svg>
                                                        </div>

                                                        <div class="flex-1">
                                                            <div
                                                                class="text-sm font-medium text-neutral-900 dark:text-white">
                                                                Selecciona el PDF de CURP
                                                            </div>
                                                            <div class="text-xs text-neutral-600 dark:text-neutral-400">
                                                                Tamaño máx. 50MB • Formato PDF
                                                            </div>

                                                            @if ($pdf_curp)
                                                                <div
                                                                    class="mt-2 inline-flex items-center gap-2 rounded-full bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1 text-xs text-emerald-700 dark:text-emerald-200 ring-1 ring-emerald-600/20">
                                                                    <span
                                                                        class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                                    Archivo seleccionado:
                                                                    {{ $pdf_curp->getClientOriginalName() }}
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="shrink-0">
                                                            <span
                                                                class="inline-flex items-center rounded-xl bg-neutral-900 text-white px-3 py-2 text-xs dark:bg-white dark:text-neutral-900">
                                                                Buscar archivo
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <input type="file" wire:model="pdf_curp"
                                                        accept="application/pdf"
                                                        class="absolute inset-0 opacity-0 cursor-pointer" />
                                                </div>
                                            </label>

                                            <flux:error name="pdf_curp" />

                                            <div wire:loading wire:target="pdf_curp"
                                                class="mt-2 text-xs text-neutral-500">
                                                Subiendo CURP…
                                            </div>
                                        </div>

                                        <div wire:loading wire:target="autollenarDesdeCurpPdf"
                                            class="mt-4 rounded-2xl border border-neutral-200 bg-white/70 p-4 text-sm shadow-sm
                                                   dark:border-neutral-800 dark:bg-neutral-900/50">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="h-5 w-5 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white">
                                                </div>
                                                Extrayendo datos del PDF…
                                            </div>
                                        </div>

                                        @if ($autollenadoError)
                                            <div
                                                class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                                                {{ $autollenadoError }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- ====== DATOS PERSONALES ====== --}}
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <flux:field>
                                    <flux:label badge="Requerido">CURP</flux:label>
                                    <flux:input wire:model.live.debounce.600ms="curp" maxlength="18"
                                        class="uppercase" placeholder="18 caracteres" />
                                    <flux:error name="curp" />
                                    @if ($curpError)
                                        <p class="mt-1 text-xs font-medium text-rose-600">{{ $curpError }}</p>
                                    @endif
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Nombre(s)</flux:label>
                                    <flux:input wire:model.defer="nombre" placeholder="Ej. Juan" />
                                    <flux:error name="nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Apellido paterno</flux:label>
                                    <flux:input wire:model.defer="apellido_paterno" placeholder="Ej. Pérez" />
                                    <flux:error name="apellido_paterno" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">Apellido materno</flux:label>
                                    <flux:input wire:model.defer="apellido_materno"
                                        placeholder="Ej. López (opcional)" />
                                    <flux:error name="apellido_materno" />
                                </flux:field>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
                                <flux:field>
                                    <flux:label badge="Requerido">Fecha de nacimiento</flux:label>
                                    <flux:input type="date" wire:model.defer="fecha_nacimiento" />
                                    <flux:error name="fecha_nacimiento" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Requerido">Género</flux:label>
                                    <flux:select wire:model.defer="genero">
                                        <option value="">Selecciona…</option>
                                        <option value="H">Hombre (H)</option>
                                        <option value="M">Mujer (M)</option>
                                    </flux:select>
                                    <flux:error name="genero" />
                                </flux:field>

                                <flux:field>
                                    <flux:label badge="Opcional">RFC</flux:label>
                                    <flux:input wire:model.defer="rfc" maxlength="13" class="uppercase"
                                        placeholder="RFC (12-13)" />
                                    <flux:error name="rfc" />
                                </flux:field>
                            </div>

                            {{-- ====== Dirección ====== --}}
                            <div class="mt-8">
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-3">
                                    Dirección (opcional)
                                </h3>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <flux:field class="md:col-span-2">
                                        <flux:label badge="Opcional">Calle</flux:label>
                                        <flux:input wire:model.defer="calle" placeholder="Ej. Av. Insurgentes" />
                                        <flux:error name="calle" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">No. exterior</flux:label>
                                        <flux:input wire:model.defer="numero_exterior" placeholder="Ej. 123" />
                                        <flux:error name="numero_exterior" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">No. interior</flux:label>
                                        <flux:input wire:model.defer="numero_interior" placeholder="Ej. 4B" />
                                        <flux:error name="numero_interior" />
                                    </flux:field>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                    <flux:field class="md:col-span-2">
                                        <flux:label badge="Opcional">Colonia</flux:label>
                                        <flux:input wire:model.defer="colonia" placeholder="Ej. Centro" />
                                        <flux:error name="colonia" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">Municipio</flux:label>
                                        <flux:input wire:model.defer="municipio" placeholder="Ej. Pungarabato" />
                                        <flux:error name="municipio" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label badge="Opcional">Estado</flux:label>
                                        <flux:input wire:model.defer="estado" placeholder="Ej. Guerrero" />
                                        <flux:error name="estado" />
                                    </flux:field>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
                                    <flux:field>
                                        <flux:label badge="Opcional">Código postal</flux:label>
                                        <flux:input wire:model.defer="codigo_postal" maxlength="10"
                                            placeholder="Ej. 40662" />
                                        <flux:error name="codigo_postal" />
                                    </flux:field>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Define si el personal estará activo en el sistema.
                                </span>
                                <flux:checkbox wire:model="status" :label="__('Activo')" />
                            </div>
                        </flux:field>

                        <div class="mt-6 border-t border-gray-200 dark:border-neutral-800"></div>

                        <div
                            class="mt-6 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2">
                            <button type="button" @click="open = false"
                                class="inline-flex justify-center rounded-xl px-4 py-2.5 border border-neutral-200 dark:border-neutral-700
                                       bg-white dark:bg-neutral-800 text-neutral-700 dark:text-neutral-100
                                       hover:bg-neutral-50 dark:hover:bg-neutral-700">
                                Cancelar
                            </button>

                            <flux:button variant="primary" type="submit"
                                class="w-full sm:w-auto cursor-pointer btn-gradient" wire:loading.attr="disabled"
                                wire:target="crearPersonal">
                                Guardar
                            </flux:button>
                        </div>
                    </div>

                    <div wire:loading.delay wire:target="crearPersonal"
                        class="pointer-events-none absolute inset-0 grid place-items-center bg-white/60 dark:bg-neutral-900/60">
                        <div
                            class="flex items-center gap-3 rounded-xl bg-white/90 dark:bg-neutral-900/90 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-700 shadow">
                            <span
                                class="h-5 w-5 animate-spin rounded-full border-2 border-neutral-200 border-t-neutral-900 dark:border-neutral-700 dark:border-t-white"></span>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-200">Guardando…</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
