<div class="space-y-5">
    {{-- ENCABEZADO --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">
                Responsables registrados
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Administra los datos propios de cada persona y consulta sus alumnos vinculados sin salir del directorio.
            </p>
        </div>

        <div
            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm text-zinc-600 shadow-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">
            <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            <span>Total filtrado:
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ number_format($tutores->total()) }}
                </span>
            </span>
        </div>
    </div>

    <div
        class="relative overflow-hidden rounded-[28px] border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
        <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

        {{-- TOOLBAR --}}
        <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_180px_220px_minmax(280px,440px)] lg:items-end">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Directorio de responsables</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    Busca por datos del responsable o por nombre, CURP, matrícula y folio de un alumno relacionado.
                </p>
            </div>

            <div>
                <label for="estadoTutor"
                    class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Estado
                </label>
                <select id="estadoTutor" wire:model.live="estado"
                    class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="activos">Activos</option>
                    <option value="archivados">Archivados</option>
                    <option value="sin_alumnos">Sin alumnos activos</option>
                    <option value="todos">Todos</option>
                </select>
            </div>

            <div>
                <label for="funcionTutor"
                    class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Función
                </label>
                <select id="funcionTutor" wire:model.live="funcion"
                    class="w-full rounded-2xl border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="todas">Todas</option>
                    <option value="principales">Contactos principales</option>
                    @if (auth()->user()?->canAccess('alumnos.responsables_sensibles'))
                        <option value="tutores_legales">Tutores legales</option>
                    @endif
                    <option value="emergencias">Contactos de emergencia</option>
                    @if (auth()->user()?->canAccess('alumnos.responsables_sensibles'))
                        <option value="autorizados_recoger">Autorizados para recoger</option>
                    @endif
                    <option value="responsables_economicos">Responsables económicos</option>
                </select>
            </div>

            <div>
                <label for="buscarTutor" class="sr-only">Buscar tutor o alumno</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M10.5 3.75a6.75 6.75 0 1 0 4.163 12.065l4.261 4.261a.75.75 0 1 0 1.06-1.06l-4.261-4.261A6.75 6.75 0 0 0 10.5 3.75Zm-5.25 6.75a5.25 5.25 0 1 1 10.5 0a5.25 5.25 0 0 1-10.5 0Z"
                                clip-rule="evenodd" />
                        </svg>
                    </span>
                    <input id="buscarTutor" type="search" wire:model.live.debounce.400ms="buscar"
                        placeholder="Tutor o alumno: nombre, CURP, matrícula…"
                        class="w-full rounded-2xl border border-zinc-300 bg-white py-3 pl-11 pr-4 text-sm text-zinc-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-blue-500">
                </div>
            </div>
        </div>

        <div class="flex justify-end border-t border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <flux:button variant="primary" class="cursor-pointer bg-emerald-700 hover:bg-emerald-800"
                wire:click="exportarTutores">
                <div class="flex items-center gap-2">
                    <flux:icon.download class="h-4 w-4" />
                    Exportar Excel
                </div>
            </flux:button>
        </div>

        {{-- LOADER --}}
        <div wire:loading.flex wire:target="buscar,estado,funcion,ordenarPor,archivar,reactivar,exportarTutores"
            class="absolute inset-0 z-20 items-center justify-center bg-white/70 backdrop-blur-sm dark:bg-zinc-950/70">
            <div
                class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
                <span class="text-sm text-zinc-700 dark:text-zinc-200">Procesando…</span>
            </div>
        </div>

        {{-- TABLA DESKTOP --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/70">
                    <tr class="text-left text-zinc-600 dark:text-zinc-300">
                        <th class="px-6 py-4 font-semibold"><button wire:click="ordenarPor('id')"
                                class="hover:text-blue-600">#</button></th>
                        <th class="px-6 py-4 font-semibold"><button wire:click="ordenarPor('curp')"
                                class="hover:text-blue-600">Identidad</button></th>
                        <th class="px-6 py-4 font-semibold"><button wire:click="ordenarPor('nombre')"
                                class="hover:text-blue-600">Nombre completo</button></th>
                        <th class="px-6 py-4 font-semibold">Relaciones</th>
                        <th class="px-6 py-4 font-semibold"><button wire:click="ordenarPor('telefono_celular')"
                                class="hover:text-blue-600">Contacto</button></th>
                        <th class="px-6 py-4 font-semibold"><button wire:click="ordenarPor('activo')"
                                class="hover:text-blue-600">Estado</button></th>
                        <th class="px-6 py-4 font-semibold text-center">Acciones</th>
                    </tr>
                </thead>

                @forelse ($tutores as $index => $tutor)
                    @php
                        $relacionesHistoricas = max(
                            0,
                            (int) $tutor->relaciones_total_count - (int) $tutor->relaciones_activas_count,
                        );
                    @endphp

                    <tbody x-data="{ abierto: false }" wire:key="tutor-desktop-{{ $tutor->id }}"
                        class="border-t border-zinc-200 dark:border-zinc-800">
                        <tr class="transition hover:bg-zinc-50/80 dark:hover:bg-zinc-900/50"
                            :class="abierto ? 'bg-blue-50/40 dark:bg-blue-950/10' : ''">
                            <td class="px-6 py-4">{{ $tutores->firstItem() + $index }}</td>

                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex rounded-full border border-zinc-200 bg-zinc-50 px-3 py-1 text-xs font-medium tracking-wide text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                    {{ auth()->user()?->canAccess('alumnos.editar') ? ($tutor->curp ?: ($tutor->identificador_alternativo ?: 'Sin identificador')) : $tutor->identidad_protegida }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $tutor->nombre_completo }}</div>
                                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    Género: {{ $tutor->genero ?: 'N/D' }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        {{ (int) $tutor->relaciones_activas_count }} activas
                                    </span>
                                    <span
                                        class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                        {{ $relacionesHistoricas }} históricas
                                    </span>
                                </div>

                                <button type="button" @click="abierto = !abierto" :aria-expanded="abierto.toString()"
                                    @disabled((int) $tutor->relaciones_total_count === 0)
                                    class="mt-2 inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-45"
                                    :class="abierto
                                        ?
                                        'border-blue-600 bg-blue-600 text-white shadow-sm shadow-blue-600/20' :
                                        'border-blue-200 bg-blue-50 text-blue-700 hover:border-blue-300 hover:bg-blue-100 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300 dark:hover:bg-blue-500/20'">
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                        <circle cx="9" cy="7" r="4" />
                                        <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    </svg>
                                    <span x-text="abierto ? 'Ocultar alumnos' : 'Desplegar alumnos'">Desplegar
                                        alumnos</span>
                                    <svg class="h-3.5 w-3.5 transition-transform duration-200"
                                        :class="abierto ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>
                            </td>

                            <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                <div>{{ $tutor->telefono_celular ?: ($tutor->telefono_casa ?: 'Sin teléfono') }}</div>
                                <div class="mt-1 max-w-[220px] truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $tutor->correo_electronico ?: 'Sin correo' }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if ($tutor->activo)
                                    <span
                                        class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Activo</span>
                                @else
                                    <span
                                        class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Archivado</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    @if (auth()->user()?->canAccess('alumnos.editar'))
                                        <flux:button variant="primary"
                                            class="cursor-pointer bg-amber-500 text-white hover:bg-amber-600"
                                            @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $tutor->id }} });">
                                            <flux:icon.square-pen class="h-3.5 w-3.5" />
                                        </flux:button>
                                    @endif

                                    @if ($tutor->activo && auth()->user()?->canAccess('alumnos.eliminar'))
                                        <button type="button" wire:click="archivar({{ $tutor->id }})"
                                            wire:confirm="¿Archivar a {{ addslashes($tutor->nombre_completo) }}? Sus relaciones históricas se conservarán."
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-700 text-white transition hover:bg-slate-800"
                                            title="Archivar responsable">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M21 8v13H3V8" />
                                                <path d="M1 3h22v5H1z" />
                                                <path d="M10 12h4" />
                                            </svg>
                                        </button>
                                    @elseif (!$tutor->activo && auth()->user()?->canAccess('alumnos.editar'))
                                        <button type="button" wire:click="reactivar({{ $tutor->id }})"
                                            wire:confirm="¿Reactivar a {{ addslashes($tutor->nombre_completo) }} para nuevas asignaciones?"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-700 text-white transition hover:bg-emerald-800"
                                            title="Reactivar responsable">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                <path d="M3 12a9 9 0 1 0 3-6.7L3 8" />
                                                <path d="M3 3v5h5" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="7" class="p-0">
                                <div x-cloak x-show="abierto" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="border-t border-blue-100 bg-zinc-50/70 p-4 dark:border-blue-500/10 dark:bg-zinc-950/50">
                                    @include('livewire.tutor.partials.collapse-alumnos-relacionados', [
                                        'tutor' => $tutor,
                                    ])
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="7" class="px-6 py-12">
                                <div
                                    class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">No hay
                                        responsables para mostrar</h3>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Cambia el filtro o busca
                                        por tutor o alumno.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- TARJETAS MÓVILES --}}
        <div class="grid gap-4 p-4 lg:hidden">
            @forelse ($tutores as $index => $tutor)
                @php
                    $relacionesHistoricasMovil = max(
                        0,
                        (int) $tutor->relaciones_total_count - (int) $tutor->relaciones_activas_count,
                    );
                @endphp

                <article x-data="{ abierto: false }" wire:key="tutor-mobile-{{ $tutor->id }}"
                    class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $tutor->nombre_completo }}</h3>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ auth()->user()?->canAccess('alumnos.editar') ? ($tutor->curp ?: ($tutor->identificador_alternativo ?: 'Sin identificador')) : $tutor->identidad_protegida }}
                                </p>
                            </div>
                            @if ($tutor->activo)
                                <span
                                    class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Activo</span>
                            @else
                                <span
                                    class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">Archivado</span>
                            @endif
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span
                                    class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Relaciones</span>
                                <span
                                    class="text-zinc-800 dark:text-zinc-200">{{ (int) $tutor->relaciones_activas_count }}
                                    activas / {{ $relacionesHistoricasMovil }} históricas</span>
                            </div>
                            <div>
                                <span
                                    class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Teléfono</span>
                                <span
                                    class="text-zinc-800 dark:text-zinc-200">{{ $tutor->telefono_celular ?: ($tutor->telefono_casa ?: 'Sin teléfono') }}</span>
                            </div>
                            <div class="col-span-2">
                                <span
                                    class="block text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Correo</span>
                                <span
                                    class="break-all text-zinc-800 dark:text-zinc-200">{{ $tutor->correo_electronico ?: 'Sin correo' }}</span>
                            </div>
                        </div>

                        <button type="button" @click="abierto = !abierto" :aria-expanded="abierto.toString()"
                            @disabled((int) $tutor->relaciones_total_count === 0)
                            class="mt-4 flex w-full items-center justify-between rounded-xl border px-3.5 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-45"
                            :class="abierto
                                ?
                                'border-blue-600 bg-blue-600 text-white shadow-sm shadow-blue-600/20' :
                                'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-300'">
                            <span class="inline-flex items-center gap-2">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" aria-hidden="true">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                </svg>
                                <span
                                    x-text="abierto ? 'Ocultar alumnos relacionados' : 'Mostrar alumnos relacionados'">Mostrar
                                    alumnos relacionados</span>
                            </span>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="abierto ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                aria-hidden="true">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div
                            class="mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            @if (auth()->user()?->canAccess('alumnos.editar'))
                                <flux:button variant="primary"
                                    class="cursor-pointer bg-amber-500 text-white hover:bg-amber-600"
                                    @click="$dispatch('abrir-modal-editar'); Livewire.dispatch('editarModal', { id: {{ $tutor->id }} });">
                                    <flux:icon.square-pen class="h-3.5 w-3.5" />
                                </flux:button>
                            @endif

                            @if ($tutor->activo && auth()->user()?->canAccess('alumnos.eliminar'))
                                <button type="button" wire:click="archivar({{ $tutor->id }})"
                                    wire:confirm="¿Archivar este responsable? Sus relaciones históricas se conservarán."
                                    class="inline-flex h-9 items-center justify-center rounded-lg bg-slate-700 px-3 text-xs font-semibold text-white hover:bg-slate-800">
                                    Archivar
                                </button>
                            @elseif (!$tutor->activo && auth()->user()?->canAccess('alumnos.editar'))
                                <button type="button" wire:click="reactivar({{ $tutor->id }})"
                                    wire:confirm="¿Reactivar este responsable?"
                                    class="inline-flex h-9 items-center justify-center rounded-lg bg-emerald-700 px-3 text-xs font-semibold text-white hover:bg-emerald-800">
                                    Reactivar
                                </button>
                            @endif
                        </div>
                    </div>

                    <div x-cloak x-show="abierto" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 -translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 -translate-y-1"
                        class="border-t border-blue-100 bg-zinc-50/70 p-3 dark:border-blue-500/10 dark:bg-zinc-950/50">
                        @include('livewire.tutor.partials.collapse-alumnos-relacionados', [
                            'tutor' => $tutor,
                        ])
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">No hay responsables para
                        mostrar</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No se encontraron tutores ni alumnos
                        relacionados con la búsqueda.</p>
                </div>
            @endforelse
        </div>

        @if ($tutores->hasPages())
            <div class="border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                {{ $tutores->links() }}
            </div>
        @endif
    </div>

    @if (auth()->user()?->canAccess('alumnos.editar'))
        <livewire:tutor.editar-tutor />
    @endif
</div>
