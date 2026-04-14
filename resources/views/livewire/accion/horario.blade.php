<div class="space-y-6">
    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div>
            <div class="-mx-1 overflow-x-auto pb-1">
                <div class="flex min-w-max items-center gap-2 px-1 justify-center">
                    @foreach ($niveles as $item)
                        @php
                            $activo = $slug_nivel === $item->slug;
                        @endphp

                        <a href="{{ route('submodulos.accion', ['slug_nivel' => $item->slug, 'accion' => 'horarios']) }}"
                            wire:navigate aria-current="{{ $activo ? 'page' : 'false' }}"
                            class="group relative inline-flex items-center gap-2 whitespace-nowrap rounded-2xl border px-4 py-3 text-sm font-semibold transition-all duration-300 hover:-translate-y-0.5
                        {{ $activo
                            ? 'border-sky-200 bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600 text-white shadow-lg shadow-sky-500/20 dark:border-sky-700/50'
                            : 'border-slate-200 bg-white text-slate-700 shadow-sm hover:border-sky-200 hover:bg-sky-50 hover:text-sky-700 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-200 dark:hover:border-sky-800 dark:hover:bg-neutral-800 dark:hover:text-sky-300' }}">

                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-xl
                            {{ $activo
                                ? 'bg-white/15 text-white'
                                : 'bg-slate-100 text-slate-500 group-hover:bg-sky-100 group-hover:text-sky-700 dark:bg-neutral-700 dark:text-slate-300 dark:group-hover:bg-sky-950/40 dark:group-hover:text-sky-300' }}">
                                <flux:icon.rectangle-stack class="h-4 w-4" />
                            </span>

                            <span>{{ $item->nombre }}</span>

                            @if ($activo)
                                <span class="rounded-full bg-white/15 px-2 py-0.5 text-[11px] font-bold text-white">
                                    Activo
                                </span>
                                <span class="absolute inset-x-4 -bottom-px h-0.5 rounded-full bg-white/80"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- SECCIONES --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- SECCION HORA --}}
        <section
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-sky-500 via-blue-600 to-indigo-600"></div>

            <div class="p-5 sm:p-6 space-y-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            {{ $hora_id ? 'Actualizar hora' : 'Registrar hora' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Define los bloques de horario por grado.
                        </p>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 rounded-2xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 dark:border-sky-800/50 dark:bg-sky-950/30 dark:text-sky-300">
                        <flux:icon.clock class="h-4 w-4" />
                        Horas
                    </div>
                </div>

                @if (session()->has('success_hora'))
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {{ session('success_hora') }}
                    </div>
                @endif

                <form wire:submit="guardarHora" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <flux:field>
                            <flux:label>Grado</flux:label>
                            <flux:select wire:model="grado_id_hora">
                                <option value="">Selecciona un grado</option>
                                @foreach ($grados as $grado)
                                    <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="grado_id_hora" />
                        </flux:field>



                        <flux:field>
                            <flux:label>Hora inicio</flux:label>
                            <flux:input wire:model="hora_inicio" type="time" />
                            <flux:error name="hora_inicio" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Hora fin</flux:label>
                            <flux:input wire:model="hora_fin" type="time" />
                            <flux:error name="hora_fin" />
                        </flux:field>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @if ($hora_id)
                            <flux:button type="button" variant="ghost" wire:click="cancelarHora">
                                Cancelar
                            </flux:button>
                        @endif

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                            wire:target="guardarHora">
                            <span wire:loading.remove wire:target="guardarHora">
                                {{ $hora_id ? 'Actualizar hora' : 'Guardar hora' }}
                            </span>
                            <span wire:loading wire:target="guardarHora">
                                Guardando...
                            </span>
                        </flux:button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-3xl border border-slate-200/80 dark:border-neutral-800">
                    <div class="max-h-[360px] overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                            <thead class="bg-slate-50 dark:bg-neutral-900/70">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Grado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Orden
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Hora
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-950/40">
                                @forelse ($horas as $item)
                                    <tr class="transition hover:bg-sky-50/70 dark:hover:bg-neutral-800/60">
                                        <td class="px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                                            {{ $item->grado_nombre ?? 'Sin grado' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $item->orden }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $item->hora_inicio)->format('h:i A') }}
                                            -
                                            {{ \Carbon\Carbon::createFromFormat('H:i:s', $item->hora_fin)->format('h:i A') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost"
                                                    wire:click="editarHora({{ $item->id }})">
                                                    Editar
                                                </flux:button>

                                                <flux:button size="sm" variant="danger"
                                                    wire:click="eliminarHora({{ $item->id }})"
                                                    wire:confirm="¿Deseas eliminar esta hora?">
                                                    Eliminar
                                                </flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4"
                                            class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                            No hay horas registradas todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        {{-- SECCION DIA --}}
        <section
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500"></div>

            <div class="p-5 sm:p-6 space-y-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            {{ $dia_id ? 'Actualizar día' : 'Registrar día' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Define los días disponibles por grado.
                        </p>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 dark:border-emerald-800/50 dark:bg-emerald-950/30 dark:text-emerald-300">
                        <flux:icon.calendar-days class="h-4 w-4" />
                        Días
                    </div>
                </div>

                @if (session()->has('success_dia'))
                    <div
                        class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/30 dark:bg-emerald-950/30 dark:text-emerald-300">
                        {{ session('success_dia') }}
                    </div>
                @endif

                <form wire:submit="guardarDia" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <flux:field>
                            <flux:label>Grado</flux:label>
                            <flux:select wire:model="grado_id_dia">
                                <option value="">Selecciona un grado</option>
                                @foreach ($grados as $grado)
                                    <option value="{{ $grado->id }}">{{ $grado->nombre }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="grado_id_dia" />
                        </flux:field>

                        <div class="md:col-span-2">
                            <flux:field>
                                <flux:label>Día</flux:label>
                                <flux:input wire:model="dia" type="text" placeholder="Ejemplo: Lunes" />
                                <flux:error name="dia" />
                            </flux:field>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @if ($dia_id)
                            <flux:button type="button" variant="ghost" wire:click="cancelarDia">
                                Cancelar
                            </flux:button>
                        @endif

                        <flux:button type="submit" variant="primary" wire:loading.attr="disabled"
                            wire:target="guardarDia">
                            <span wire:loading.remove wire:target="guardarDia">
                                {{ $dia_id ? 'Actualizar día' : 'Guardar día' }}
                            </span>
                            <span wire:loading wire:target="guardarDia">
                                Guardando...
                            </span>
                        </flux:button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-3xl border border-slate-200/80 dark:border-neutral-800">
                    <div class="max-h-[360px] overflow-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                            <thead class="bg-slate-50 dark:bg-neutral-900/70">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Grado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Orden
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Día
                                    </th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 bg-white dark:divide-neutral-800 dark:bg-neutral-950/40">
                                @forelse ($dias as $item)
                                    <tr class="transition hover:bg-emerald-50/70 dark:hover:bg-neutral-800/60">
                                        <td class="px-4 py-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                                            {{ $item->grado_nombre ?? 'Sin grado' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $item->orden }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                                            {{ $item->dia }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center justify-end gap-2">
                                                <flux:button size="sm" variant="ghost"
                                                    wire:click="editarDia({{ $item->id }})">
                                                    Editar
                                                </flux:button>

                                                <flux:button size="sm" variant="danger"
                                                    wire:click="eliminarDia({{ $item->id }})"
                                                    wire:confirm="¿Deseas eliminar este día?">
                                                    Eliminar
                                                </flux:button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4"
                                            class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                                            No hay días registrados todavía.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- HORARIO --}}
    <section
        class="overflow-hidden rounded-[28px] border border-dashed border-slate-300 bg-white/70 p-6 shadow-sm backdrop-blur dark:border-neutral-700 dark:bg-neutral-900/60">
        <div class="flex items-center gap-3">
            <div
                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                <flux:icon.table-cells class="h-5 w-5" />
            </div>

            <div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                    Sección de horario
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Aquí después se integra la asignación de materias por día y hora.
                </p>
            </div>
        </div>
    </section>
</div>
