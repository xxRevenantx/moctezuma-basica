<div x-data="{
    openRow: null,
    eliminar(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta configuración se eliminará de forma permanente',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563EB',
            cancelButtonColor: '#EF4444',
            cancelButtonText: 'Cancelar',
            confirmButtonText: 'Sí, eliminar'
        }).then((r) => r.isConfirmed && @this.call('eliminarConfiguracionPromedio', id))
    }
}" class="space-y-5">
    <form wire:submit.prevent="guardarMateriasPromediar" class="relative">
        <div class="border-b border-slate-200/70 px-5 py-4 dark:border-white/10 sm:px-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-black text-slate-800 dark:text-white">
                        Materias a promediar
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Configura el grado, grupo y la cantidad de materias a considerar.
                    </p>
                </div>

                @if ($nombre_nivel)
                    <span
                        class="inline-flex items-center rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-xs font-semibold text-fuchsia-700 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-300">
                        {{ $nombre_nivel }}
                    </span>
                @endif
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <div
                class="grid grid-cols-1 gap-5 md:grid-cols-2 {{ $this->esBachillerato ? 'xl:grid-cols-4' : 'xl:grid-cols-3' }}">

                <div class="space-y-2">
                    <flux:field>
                        <flux:label>Grado</flux:label>
                        <flux:select wire:model.live="promediar_grado_id">
                            <option value="">Selecciona un grado</option>
                            @foreach ($promediar_grados as $item)
                                <option value="{{ $item['id'] }}">{{ $item['nombre'] }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="promediar_grado_id" />
                    </flux:field>
                </div>

                <div class="space-y-2">
                    <flux:field>
                        <flux:label>Grupo</flux:label>
                        <flux:select wire:model.live="promediar_grupo_id" :disabled="blank($promediar_grado_id)">
                            <option value="">Selecciona un grupo</option>
                            @foreach ($promediar_grupos as $item)
                                <option value="{{ $item['id'] }}">{{ $item['nombre'] }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="promediar_grupo_id" />
                    </flux:field>
                </div>

                @if ($this->esBachillerato)
                    <div class="space-y-2">
                        <flux:field>
                            <flux:label>Semestre</flux:label>
                            <flux:select wire:model.live="promediar_semestre_id" :disabled="blank($promediar_grado_id)">
                                <option value="">Selecciona un semestre</option>
                                @foreach ($promediar_semestres as $item)
                                    <option value="{{ $item['id'] }}">
                                        {{ $item['numero'] }}° semestre
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error name="promediar_semestre_id" />
                        </flux:field>
                    </div>
                @endif

                <div class="space-y-2">
                    <flux:field>
                        <flux:label>Número de materias</flux:label>
                        <flux:input type="number" min="1" wire:model.live="promediar_numero_materias"
                            placeholder="Ejemplo: 5" />
                        <flux:error name="promediar_numero_materias" />
                    </flux:field>
                </div>
            </div>
        </div>

        <div
            class="flex flex-col-reverse gap-3 border-t border-slate-200/70 px-5 py-4 dark:border-white/10 sm:flex-row sm:items-center sm:justify-end sm:px-6">
            <button type="button" @click="openPromediar = false"
                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-white/10 dark:bg-neutral-950/50 dark:text-slate-200 dark:hover:bg-white/5">
                Cancelar
            </button>

            <flux:button type="submit" variant="primary">
                Guardar configuración
            </flux:button>
        </div>

        <div wire:loading.flex
            wire:target="guardarMateriasPromediar,promediar_grado_id,promediar_grupo_id,promediar_semestre_id"
            class="absolute inset-0 items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-neutral-900/75">
            <div class="flex flex-col items-center gap-3">
                <div
                    class="h-12 w-12 animate-spin rounded-full border-4 border-slate-200 border-t-fuchsia-500 dark:border-white/10 dark:border-t-fuchsia-400">
                </div>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Cargando configuración...
                </p>
            </div>
        </div>
    </form>

    @if (session()->has('success'))
        <div class="px-5 pb-5 sm:px-6">
            <div
                class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if ($configuracionesPromedio->count())
        <section
            class="overflow-hidden rounded-[28px] border border-white/60 bg-white/80 shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-white/10 dark:bg-neutral-950/70 dark:shadow-black/20">
            <div class="h-1.5 w-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-sky-500"></div>

            <div class="p-5 sm:p-6">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-lg font-black text-slate-800 dark:text-white">
                            Configuración de materias a promediar
                        </h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Consulta, valida y administra las configuraciones registradas para este nivel.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-200">
                            Total: {{ $configuracionesPromedio->count() }}
                        </span>

                        @if ($ultimoRegistroId)
                            <span
                                class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                Último registro {{ $ultimoMovimiento }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- TABLA ESCRITORIO --}}
                <div
                    class="hidden overflow-hidden rounded-3xl border border-slate-200/80 bg-white/70 lg:block dark:border-white/10 dark:bg-white/[0.03]">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-slate-100/90 dark:bg-white/[0.05]">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Nivel
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Grado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Grupo
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Semestre
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Número de materias
                                    </th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Estado
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-extrabold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Acción
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200/70 dark:divide-white/10">
                                @foreach ($configuracionesPromedio as $item)
                                    @php
                                        $esUltimo = $ultimoRegistroId === $item->id;
                                    @endphp

                                    <tr
                                        class="transition
                                    {{ $esUltimo
                                        ? 'bg-gradient-to-r from-emerald-50 via-white to-sky-50 dark:from-emerald-500/10 dark:via-white/[0.02] dark:to-sky-500/10'
                                        : 'hover:bg-slate-50/80 dark:hover:bg-white/[0.03]' }}">
                                        <td class="px-4 py-4 align-middle">
                                            <span
                                                class="inline-flex items-center rounded-full border border-fuchsia-200 bg-fuchsia-50 px-3 py-1 text-xs font-bold text-fuchsia-700 dark:border-fuchsia-500/20 dark:bg-fuchsia-500/10 dark:text-fuchsia-300">
                                                {{ $nombre_nivel }}
                                            </span>
                                        </td>

                                        <td
                                            class="px-4 py-4 align-middle text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            {{ $item->grado?->nombre ?? '—' }}
                                        </td>

                                        <td
                                            class="px-4 py-4 align-middle text-sm font-semibold text-slate-700 dark:text-slate-200">
                                            {{ $item->grupo?->nombre ?? '—' }}
                                        </td>

                                        <td class="px-4 py-4 align-middle">
                                            @if ($item->semestre?->numero)
                                                <span
                                                    class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-700 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-300">
                                                    {{ $item->semestre->numero }}° semestre
                                                </span>
                                            @else
                                                <span class="text-sm font-medium text-slate-400 dark:text-slate-500">
                                                    —
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 align-middle">
                                            <span
                                                class="inline-flex items-center rounded-2xl bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500 px-3 py-1.5 text-sm font-extrabold text-white shadow-lg shadow-fuchsia-500/20">
                                                {{ $item->numero_materias }}
                                            </span>
                                        </td>

                                        <td class="px-4 py-4 align-middle">
                                            @if ($esUltimo)
                                                <span
                                                    class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                                    {{ ucfirst($ultimoMovimiento) }}
                                                </span>
                                            @else
                                                <span
                                                    class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600 dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300">
                                                    Guardado
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-4 py-4 align-middle text-center">
                                            <button type="button" @click="eliminar({{ $item->id }})"
                                                class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                                                <flux:icon.trash-2 class="h-4 w-4" />
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TARJETAS MÓVIL --}}
                <div class="space-y-4 lg:hidden">
                    @foreach ($configuracionesPromedio as $item)
                        @php
                            $esUltimo = $ultimoRegistroId === $item->id;
                        @endphp

                        <article
                            class="rounded-3xl border p-5 shadow-sm transition
                        {{ $esUltimo
                            ? 'border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50 dark:border-emerald-500/20 dark:bg-gradient-to-br dark:from-emerald-500/10 dark:via-neutral-950/60 dark:to-sky-500/10'
                            : 'border-slate-200 bg-white dark:border-white/10 dark:bg-neutral-950/50' }}">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                        Configuración
                                    </p>
                                    <h4 class="mt-1 text-base font-black text-slate-800 dark:text-white">
                                        {{ $item->grado?->nombre ?? '—' }} · {{ $item->grupo?->nombre ?? '—' }}
                                    </h4>
                                </div>

                                <div class="flex items-center gap-2">
                                    @if ($esUltimo)
                                        <span
                                            class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-bold text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
                                            {{ ucfirst($ultimoMovimiento) }}
                                        </span>
                                    @endif

                                    <button type="button" @click="eliminar({{ $item->id }})"
                                        class="inline-flex items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-rose-700 transition hover:bg-rose-100 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">
                                        <flux:icon.trash-2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Nivel</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $nombre_nivel }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Semestre</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $item->semestre?->numero ? $item->semestre->numero . '° semestre' : '—' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grado</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $item->grado?->nombre ?? '—' }}
                                    </p>
                                </div>

                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Grupo</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $item->grupo?->nombre ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Número de materias
                                </p>
                                <div
                                    class="mt-2 inline-flex items-center rounded-2xl bg-gradient-to-r from-violet-500 via-fuchsia-500 to-pink-500 px-3 py-1.5 text-sm font-extrabold text-white shadow-lg shadow-fuchsia-500/20">
                                    {{ $item->numero_materias }}
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
