<div x-data="{
    openRow: null,

    // ===== Lightbox Foto =====
    photoOpen: false,
    photoSrc: '',
    photoAlt: '',
    openPhoto(src, alt = 'Foto') {
        if (!src) return;
        this.photoSrc = src;
        this.photoAlt = alt;
        this.photoOpen = true;
        document.body.classList.add('overflow-hidden');
        this.$nextTick(() => this.$refs.photoImg?.focus());
    },
    closePhoto() {
        this.photoOpen = false;
        document.body.classList.remove('overflow-hidden');
    },

    // ===== Eliminar =====
    eliminar(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: `La persona ${nombre} se eliminará de forma permanente`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminarPersonal', id))
    }
}" @keydown.escape.window="closePhoto()" class="space-y-5">

    <!-- Encabezado -->
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Personal</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400">Busca, edita o elimina personal.</p>
    </div>

    <!-- Contenedor listado -->
    <div
        class="relative overflow-hidden rounded-2xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800 shadow">
        <!-- Acabado superior -->
        <div class="h-1 w-full bg-gradient-to-r from-blue-600 via-sky-400 to-indigo-600"></div>

        <!-- Toolbar -->
        <div class="p-4 sm:p-5 lg:p-6">
            <div class="flex flex-col gap-3 lg:gap-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Buscador -->
                <div class="w-full sm:max-w-xl">
                    <label for="buscar-personal" class="sr-only">Buscar Personal</label>
                    <flux:input id="buscar-personal" type="text" wire:model.live="search"
                        placeholder="Buscar por nombre, apellido o correo…" icon="magnifying-glass" class="w-full" />
                </div>

                <!-- Resumen -->
                <div class="flex items-center gap-3">
                    <div
                        class="hidden sm:flex items-center gap-2 rounded-lg border border-gray-200 dark:border-neutral-800 px-3 py-1.5 bg-gray-50 dark:bg-neutral-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                            Resultados:
                            <strong>{{ method_exists($personal, 'total') ? $personal->total() : $personal->count() }}</strong>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de resultados -->
        <div class="px-4 pb-4 sm:px-5 sm:pb-6 lg:px-6">
            <div class="relative">

                <!-- Loader -->
                <div wire:loading.delay wire:target="search, eliminarPersonal"
                    class="absolute inset-0 z-10 grid place-items-center rounded-xl bg-white/70 dark:bg-neutral-900/70 backdrop-blur"
                    aria-live="polite" aria-busy="true">
                    <div
                        class="flex items-center gap-3 rounded-xl bg-white dark:bg-neutral-900 px-4 py-3 ring-1 ring-gray-200 dark:ring-neutral-800 shadow">
                        <svg class="h-5 w-5 animate-spin text-blue-600 dark:text-blue-400" viewBox="0 0 24 24"
                            fill="none" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <span class="text-sm text-gray-700 dark:text-gray-200">Cargando…</span>
                    </div>
                </div>

                <!-- Contenido que se desenfoca -->
                <div class="transition filter duration-200" wire:loading.class="blur-sm"
                    wire:target="search, eliminarPersonal">

                    <!-- Tabla (desktop) -->
                    <div
                        class="hidden md:block overflow-hidden rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-800">
                        <div class="overflow-x-auto max-h-[65vh]">
                            <table class="min-w-full text-sm">
                                <thead
                                    class="sticky top-0 z-10 bg-gray-50/95 dark:bg-neutral-900 backdrop-blur text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-neutral-800">
                                    <tr>
                                        <th class="px-4 py-3 text-center font-semibold">#</th>
                                        <th class="px-4 py-3 text-left font-semibold">Foto</th>
                                        <th class="px-4 py-3 text-left font-semibold">Nombre(s)</th>
                                        <th class="px-4 py-3 text-left font-semibold">Apellido Paterno</th>
                                        <th class="px-4 py-3 text-left font-semibold">Apellido Materno</th>
                                        <th class="px-4 py-3 text-left font-semibold">CURP</th>
                                        <th class="px-4 py-3 text-center font-semibold">RFC</th>
                                        <th class="px-4 py-3 text-center font-semibold">Fecha de Nacimiento</th>
                                        <th class="px-4 py-3 text-center font-semibold">Género</th>
                                        <th class="px-4 py-3 text-center font-semibold">Status</th>
                                        <th class="px-4 py-3 text-center font-semibold">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-100 dark:divide-neutral-800">
                                    @if ($personal->isEmpty())
                                        <tr>
                                            <td colspan="11"
                                                class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                                <div class="mx-auto w-full max-w-md">
                                                    <div
                                                        class="rounded-2xl border border-dashed border-gray-300 dark:border-neutral-700 p-6">
                                                        <div class="mb-1 text-base font-semibold">No hay personal
                                                            disponible</div>
                                                        <p class="text-sm">Ajusta tu búsqueda.</p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @else
                                        @foreach ($personal as $key => $persona)
                                            <!-- Fila principal -->
                                            <tr
                                                class="transition-colors hover:bg-gray-50/70 dark:hover:bg-neutral-800/50">
                                                <!-- # + botón desplegar -->
                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button"
                                                            class="inline-flex items-center justify-center h-7 w-7 rounded-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-xs shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-1 focus:ring-indigo-500 dark:focus:ring-offset-neutral-900 transition-transform duration-150 hover:scale-105 active:scale-95"
                                                            @click="openRow === {{ $persona->id }} ? openRow = null : openRow = {{ $persona->id }}"
                                                            :aria-expanded="openRow === {{ $persona->id }}"
                                                            aria-label="Ver más datos del personal">
                                                            <svg x-show="openRow !== {{ $persona->id }}"
                                                                xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                                viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                            <svg x-show="openRow === {{ $persona->id }}" x-cloak
                                                                xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5"
                                                                viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd"
                                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </button>

                                                        <span
                                                            class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            {{ $key + 1 }}
                                                        </span>
                                                    </div>
                                                </td>

                                                <!-- Foto -->
                                                <td class="px-4 py-3">
                                                    @if ($persona->foto)
                                                        @php
                                                            $src = asset('storage/personal/' . $persona->foto);
                                                            $alt = 'Foto de ' . $persona->nombre;
                                                        @endphp

                                                        <button type="button" class="group relative inline-flex"
                                                            @click.stop="openPhoto(@js($src), @js($alt))"
                                                            aria-label="Ver foto">
                                                            <img src="{{ $src }}" alt="{{ $alt }}"
                                                                class="h-9 w-9 rounded-xl object-cover ring-1 ring-black/10 dark:ring-white/10
                                                                   transition duration-200 group-hover:scale-105 group-hover:ring-blue-500/40"
                                                                onerror="this.closest('button').outerHTML='<span class=&quot;text-gray-500 dark:text-gray-400&quot;>---</span>';">
                                                            <span
                                                                class="pointer-events-none absolute inset-0 rounded-xl ring-2 ring-transparent group-hover:ring-blue-500/30"></span>
                                                        </button>
                                                    @else
                                                        <span class="text-gray-500 dark:text-gray-400">---</span>
                                                    @endif
                                                </td>


                                                <!-- Datos -->
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $persona->titulo ?: '---' }}</td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $persona->nombre ?: '---' }}</td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $persona->apellido_paterno ?: '---' }}</td>
                                                <td class="px-4 py-3 text-gray-900 dark:text-white">
                                                    {{ $persona->apellido_materno ?: '---' }}</td>

                                                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 uppercase">
                                                    {{ $persona->curp ?: '---' }}</td>
                                                <td
                                                    class="px-4 py-3 text-center text-gray-800 dark:text-gray-200 uppercase">
                                                    {{ $persona->rfc ?: '---' }}</td>

                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    {{ $persona->fecha_nacimiento ? \Carbon\Carbon::parse($persona->fecha_nacimiento)->format('d/m/Y') : '---' }}
                                                </td>

                                                <td class="px-4 py-3 text-center text-gray-800 dark:text-gray-200">
                                                    {{ ($persona->genero ?? null) === 'H' ? 'HOMBRE' : (($persona->genero ?? null) === 'M' ? 'MUJER' : '---') }}
                                                </td>

                                                <td class="px-4 py-3 text-center">
                                                    @if ($persona->status == 1)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-emerald-300/60 bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-700/50">
                                                            <span
                                                                class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full border border-rose-300/60 bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-700/50">
                                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                                            Inactivo
                                                        </span>
                                                    @endif
                                                </td>

                                                <!-- Acciones -->
                                                <td class="px-4 py-3">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <flux:button variant="primary"
                                                            class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                            @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $persona->id }} });">
                                                            <flux:icon.square-pen class="w-3.5 h-3.5" />
                                                        </flux:button>

                                                        <flux:button variant="danger"
                                                            class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                            @click="eliminar({{ $persona->id }}, '{{ addslashes($persona->nombre) }}')">
                                                            <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                                        </flux:button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Detalles -->
                                            <tr x-show="openRow === {{ $persona->id }}" x-cloak
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0 -translate-y-1"
                                                x-transition:enter-end="opacity-100 translate-y-0"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100 translate-y-0"
                                                x-transition:leave-end="opacity-0 -translate-y-1"
                                                class="bg-gray-50/80 dark:bg-neutral-900/80">
                                                <td colspan="11" class="px-6 py-4">
                                                    <div
                                                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs sm:text-sm text-gray-700 dark:text-gray-200">
                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Correo electrónico</p>
                                                            <p class="font-mono text-[11px] sm:text-xs">
                                                                {{ $persona->correo ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Teléfono</p>
                                                            <p class="font-mono text-[11px] sm:text-xs">
                                                                {{ $persona->telefono_movil ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Grado de estudios</p>
                                                            <p class="font-mono text-[11px] sm:text-xs">
                                                                {{ $persona->grado_estudios ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Especialidad</p>
                                                            <p class="text-xs">{{ $persona->especialidad ?: '---' }}
                                                            </p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Calle</p>
                                                            <p class="text-xs">{{ $persona->calle ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Número exterior</p>
                                                            <p class="text-xs">
                                                                {{ $persona->numero_exterior ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Número interior</p>
                                                            <p class="text-xs">
                                                                {{ $persona->numero_interior ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Colonia</p>
                                                            <p class="text-xs">{{ $persona->colonia ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Municipio</p>
                                                            <p class="text-xs">{{ $persona->municipio ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Estado</p>
                                                            <p class="text-xs">{{ $persona->estado ?: '---' }}</p>
                                                        </div>

                                                        <div class="space-y-0.5">
                                                            <p class="font-semibold text-gray-900 dark:text-white">
                                                                Código Postal</p>
                                                            <p class="text-xs">{{ $persona->codigo_postal ?: '---' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tarjetas (mobile) -->
                    <div class="md:hidden space-y-3">
                        @if ($personal->isEmpty())
                            <div
                                class="rounded-xl border border-dashed border-gray-300 dark:border-neutral-700 p-6 text-center">
                                <div class="mb-1 font-semibold text-gray-700 dark:text-gray-200">No hay personal</div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Ajusta tu búsqueda o importa datos.
                                </p>
                            </div>
                        @else
                            @foreach ($personal as $key => $d)
                                <div
                                    class="rounded-xl border border-gray-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 shadow-sm">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                <span>#{{ $key + 1 }}</span>

                                                @if ($d->status == 1)
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full border border-emerald-300/60 bg-emerald-50 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-700/50">
                                                        Activo
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full border border-rose-300/60 bg-rose-50 px-2 py-0.5 text-[10px] font-medium text-rose-700 dark:bg-rose-900/20 dark:text-rose-300 dark:border-rose-700/50">
                                                        Inactivo
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mt-1 font-semibold text-gray-900 dark:text-white truncate">
                                                {{ $d->nombre }} {{ $d->apellido_paterno }}
                                                {{ $d->apellido_materno }}
                                            </div>

                                            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                                                <span class="font-medium">CURP:</span> {{ $d->curp ?: '---' }}
                                                · <span class="font-medium">RFC:</span> {{ $d->rfc ?: '---' }}
                                            </div>

                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $d->telefono_movil ?: '---' }} · {{ $d->correo ?: '---' }}
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-center gap-2">
                                            <flux:button variant="primary"
                                                class="cursor-pointer bg-amber-500 hover:bg-amber-600 text-white"
                                                @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $d->id }} });">
                                                <flux:icon.square-pen class="w-3.5 h-3.5" />
                                            </flux:button>

                                            <flux:button variant="danger"
                                                class="cursor-pointer bg-rose-600 hover:bg-rose-700 text-white p-1"
                                                @click="eliminar({{ $d->id }}, '{{ addslashes($d->nombre) }}')">
                                                <flux:icon.trash-2 class="w-3.5 h-3.5" />
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- Paginación -->
                <div class="mt-5">
                    {{ $personal->links() }}
                </div>
            </div>
        </div>

        <!-- ===== Lightbox Foto (GLOBAL) ===== -->
        <div x-show="photoOpen" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
            role="dialog" aria-modal="true">

            <!-- Overlay -->
            <button type="button" class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closePhoto()"
                aria-label="Cerrar"></button>

            <!-- Panel -->
            <div x-show="photoOpen" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100 blur-0"
                x-transition:leave-end="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95 blur-sm"
                class="relative w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/10 dark:bg-neutral-900 dark:ring-white/10"
                @click.stop>

                <!-- Top bar -->
                <div
                    class="flex items-center justify-between px-4 py-3 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600">
                    <p class="text-sm font-semibold text-white" x-text="photoAlt"></p>
                    <button type="button"
                        class="rounded-xl bg-white/15 p-2 text-white hover:bg-white/25 focus:outline-none focus:ring-2 focus:ring-white/60"
                        @click="closePhoto()" aria-label="Cerrar">
                        ✕
                    </button>
                </div>

                <!-- Imagen -->
                <div class="p-4">
                    <div class="relative overflow-hidden rounded-2xl bg-black/5 dark:bg-white/5">
                        <img x-ref="photoImg" tabindex="-1" :src="photoSrc" :alt="photoAlt"
                            class="max-h-[70vh] w-full object-contain select-none" x-data="{ zoom: false }"
                            @dblclick="zoom = !zoom"
                            :class="zoom ? 'scale-110 cursor-zoom-out' : 'scale-100 cursor-zoom-in'"
                            style="transition: transform .25s ease;">
                    </div>

                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Doble click para zoom • ESC para cerrar
                    </p>
                </div>
            </div>
        </div>


        <!-- Modal editar -->
        <livewire:personas.editar-personal />
    </div>
</div>
