<div class="space-y-6">
    {{-- ITERA NIVELES --}}
    <div class="overflow-hidden">
        <div>
            <div class="-mx-1 overflow-x-auto pb-1">
                <div class="flex min-w-max items-center justify-center gap-2 px-1">
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
        <livewire:hora :nivel="$nivel" :key="'hora-' . $nivel->id" />
        <livewire:dia :nivel="$nivel" :key="'dia-' . $nivel->id" />
    </div>

    {{-- TABLA BASE DEL HORARIO --}}
    <section
        class="overflow-hidden rounded-[28px] border border-white/60 bg-white/85 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-900/80 dark:shadow-black/20">
        <div class="h-1.5 w-full bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500"></div>

        <div class="space-y-5 p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl border border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-800/50 dark:bg-violet-950/30 dark:text-violet-300">
                        <flux:icon.table-cells class="h-5 w-5" />
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">
                            Sección de horario
                        </h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Filtra por grado, grupo y semestre para preparar la asignación del horario.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                        Horas: {{ $horas->count() }}
                    </span>

                    <span
                        class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300">
                        Días: {{ $dias->count() }}
                    </span>
                </div>
            </div>

            {{-- FILTROS --}}
            <div
                class="rounded-3xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <flux:field>
                        <flux:label>Grado</flux:label>
                        <flux:select wire:model.live="grado_id">
                            <option value="">Selecciona un grado</option>
                            @foreach ($grados as $grado)
                                <option value="{{ $grado->id }}">
                                    {{ $grado->nombre }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    <flux:field>
                        <flux:label>Grupo</flux:label>
                        <flux:select wire:model.live="grupo_id" :disabled="!$grado_id">
                            <option value="">Selecciona un grupo</option>
                            @foreach ($grupos as $grupo)
                                <option value="{{ $grupo->id }}">
                                    {{ $grupo->nombre }}
                                </option>
                            @endforeach
                        </flux:select>
                    </flux:field>

                    @if ($esBachillerato)
                        <flux:field>
                            <flux:label>Semestre</flux:label>
                            <flux:select wire:model.live="semestre_id">
                                <option value="">Selecciona un semestre</option>
                                @foreach ($semestres as $semestre)
                                    <option value="{{ $semestre->id }}">
                                        {{ $semestre->semestre ?? ($semestre->nombre ?? 'Semestre ' . $semestre->id) }}
                                    </option>
                                @endforeach
                            </flux:select>
                        </flux:field>
                    @endif

                    <div class="flex items-end">
                        <div
                            class="w-full rounded-2xl border border-dashed border-slate-300 bg-white/80 px-4 py-3 text-sm text-slate-500 dark:border-neutral-700 dark:bg-neutral-950/40 dark:text-slate-400">
                            @if ($grado_id || $grupo_id || $semestre_id)
                                <span class="font-semibold text-slate-700 dark:text-slate-200">Filtros activos.</span>
                                La tabla ya está lista para conectar la asignación.
                            @else
                                Selecciona los filtros para trabajar el horario.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($horas->isEmpty() || $dias->isEmpty())
                <div
                    class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-10 text-center dark:border-neutral-700 dark:bg-neutral-900/60">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-neutral-800">
                        <flux:icon.exclamation-circle class="h-6 w-6 text-slate-400 dark:text-slate-500" />
                    </div>

                    <h4 class="mt-4 text-base font-bold text-slate-800 dark:text-white">
                        Aún no se puede construir el horario
                    </h4>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        Primero agrega al menos una hora y un día para mostrar la tabla del horario.
                    </p>
                </div>
            @elseif (!$grado_id || !$grupo_id || ($esBachillerato && !$semestre_id))
                <div
                    class="rounded-3xl border border-dashed border-violet-300 bg-violet-50/70 px-6 py-10 text-center dark:border-violet-900/40 dark:bg-violet-950/20">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm dark:bg-neutral-800">
                        <flux:icon.funnel class="h-6 w-6 text-violet-500 dark:text-violet-300" />
                    </div>

                    <h4 class="mt-4 text-base font-bold text-slate-800 dark:text-white">
                        Faltan filtros por seleccionar
                    </h4>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        @if ($esBachillerato)
                            Selecciona grado, grupo y semestre para mostrar la tabla del horario.
                        @else
                            Selecciona grado y grupo para mostrar la tabla del horario.
                        @endif
                    </p>
                </div>
            @else
                <div class="overflow-hidden rounded-3xl border border-slate-200/80 dark:border-neutral-800">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-collapse">
                            <thead class="bg-slate-50 dark:bg-neutral-900/70">
                                <tr>
                                    <th
                                        class="min-w-[170px] border-b border-r border-slate-200 px-4 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                        Hora
                                    </th>

                                    @foreach ($dias as $dia)
                                        <th
                                            class="min-w-[180px] border-b border-r border-slate-200 px-4 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-500 dark:border-neutral-800 dark:text-slate-400">
                                            <div class="flex flex-col items-center gap-1">
                                                <span>{{ $dia->dia }}</span>
                                                <span
                                                    class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-500 dark:bg-neutral-800 dark:text-slate-400">
                                                    Orden {{ $dia->orden }}
                                                </span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="bg-white dark:bg-neutral-950/40">
                                @foreach ($horas as $hora)
                                    <tr class="transition hover:bg-slate-50/60 dark:hover:bg-neutral-800/40">
                                        <td
                                            class="border-b border-r border-slate-200 px-4 py-4 align-middle dark:border-neutral-800">
                                            <div class="space-y-1">
                                                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio)->format('h:i A') }}
                                                    -
                                                    {{ \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin)->format('h:i A') }}
                                                </p>

                                                <span
                                                    class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 dark:bg-sky-950/30 dark:text-sky-300">
                                                    Orden {{ $hora->orden }}
                                                </span>
                                            </div>
                                        </td>

                                        @foreach ($dias as $dia)
                                            @php
                                                $claveCelda = $hora->id . '-' . $dia->id;
                                                $horarioGuardado = $horariosGuardados->get($claveCelda);
                                                $valorSeleccionado = $horarioGuardado?->asignacion_materia_id;
                                            @endphp

                                            <td
                                                class="h-24 border-b border-r border-slate-200 px-3 py-3 align-top dark:border-neutral-800">
                                                <div class="space-y-2">
                                                    <flux:field>
                                                        <flux:select
                                                            wire:change="guardarMateriaHorario({{ $hora->id }}, {{ $dia->id }}, $event.target.value)">
                                                            <option value="">Selecciona una materia</option>

                                                            @foreach ($materiasDisponibles as $materia)
                                                                <option value="{{ $materia->id }}"
                                                                    @selected((int) $valorSeleccionado === (int) $materia->id)>
                                                                    {{ $materia->materia }}
                                                                </option>
                                                            @endforeach
                                                        </flux:select>
                                                    </flux:field>

                                                    @if ($horarioGuardado && $materiasDisponibles->firstWhere('id', $valorSeleccionado))
                                                        <div
                                                            class="rounded-2xl border border-violet-200 bg-violet-50 px-3 py-2 text-center dark:border-violet-900/40 dark:bg-violet-950/20">
                                                            <p
                                                                class="text-[11px] font-semibold text-violet-700 dark:text-violet-300">
                                                                {{ $materiasDisponibles->firstWhere('id', $valorSeleccionado)?->materia }}
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- RESUMEN DE FILTROS --}}
                <div
                    class="rounded-3xl border border-slate-200/80 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grado</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ optional($grados->firstWhere('id', $grado_id))->nombre ?? 'No seleccionado' }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grupo</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ optional($grupos->firstWhere('id', $grupo_id))->nombre ?? 'No seleccionado' }}
                            </p>
                        </div>

                        @if ($esBachillerato)
                            <div class="rounded-2xl bg-white px-4 py-3 dark:bg-neutral-950/50">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Semestre</p>
                                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                    {{ optional($semestres->firstWhere('id', $semestre_id))->semestre ?? (optional($semestres->firstWhere('id', $semestre_id))->nombre ?? 'No seleccionado') }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
</div>
