<div id="expedientes-personal-root" class="space-y-6"
    x-data="expedientePersonalUploader()"
    @expediente-personal-documento-guardado.window="onSaved($event.detail)">
    @php
        $coloresEstadoDocumento = [
            'pendiente' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',
            'recibido' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50',
            'validado' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
            'rechazado' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
            'reemplazado' => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-neutral-800 dark:text-slate-300 dark:ring-neutral-700',
        ];

        $coloresEstadoLaboral = [
            'activo' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
            'baja' => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
            'licencia' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/30 dark:text-sky-300 dark:ring-sky-900/50',
            'suspendido' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',
            'reingreso' => 'bg-violet-50 text-violet-700 ring-violet-200 dark:bg-violet-950/30 dark:text-violet-300 dark:ring-violet-900/50',
        ];
    @endphp

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    {{-- Loader general al abrir el formulario de carga. --}}
    <div x-cloak x-show="openingUpload" x-transition.opacity
        class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm">
        <div class="min-w-[290px] rounded-3xl border border-white/15 bg-slate-950/90 px-7 py-6 text-center text-white shadow-2xl">
            <div class="mx-auto size-12 animate-spin rounded-full border-4 border-white/20 border-t-sky-400"></div>
            <p class="mt-4 text-sm font-black">Preparando expediente…</p>
            <p class="mt-1 text-xs text-slate-300">Cargando el documento y sus datos.</p>
        </div>
    </div>

    <section
        class="relative overflow-hidden rounded-[30px] border border-slate-200/80 bg-gradient-to-br from-slate-950 via-indigo-950 to-sky-900 p-6 text-white shadow-2xl shadow-indigo-950/20 sm:p-8">
        <div class="pointer-events-none absolute -right-24 -top-24 size-72 rounded-full bg-sky-400/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 left-20 size-64 rounded-full bg-indigo-500/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.18em] text-sky-100 backdrop-blur">
                    <flux:icon name="shield-check" class="size-4" />
                    Acceso exclusivo de administración
                </div>

                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Expedientes del personal</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-200 sm:text-base">
                    Conserva documentos personales, títulos, cédulas y movimientos laborales sin eliminar versiones anteriores.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:min-w-[520px]">
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-slate-300">Personal</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['total']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-emerald-200">Completos</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['completos']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-amber-200">Incompletos</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['incompletos']) }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                    <p class="text-xs font-semibold text-rose-200">Bajas</p>
                    <p class="mt-1 text-2xl font-black">{{ number_format($metricas['bajas']) }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-800 dark:bg-neutral-900 sm:p-6">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Buscar personal
                </label>
                <div class="relative">
                    <flux:icon name="magnifying-glass"
                        class="pointer-events-none absolute left-3 top-1/2 z-10 size-4 -translate-y-1/2 text-slate-400" />
                    <input wire:model.live.debounce.350ms="buscar" type="search"
                        placeholder="Nombre, CURP, RFC o correo…"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-10 pr-4 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                </div>
            </div>

            <div class="lg:col-span-3">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Rol del personal
                </label>
                <select wire:model.live="rol_id"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                    <option value="">Todos los roles</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol['id'] }}">{{ $rol['nombre'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Estado laboral
                </label>
                <select wire:model.live="estado_laboral"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                    <option value="todos">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="baja">Baja</option>
                    <option value="licencia">Licencia</option>
                    <option value="suspendido">Suspendido</option>
                    <option value="reingreso">Reingreso</option>
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500 dark:text-slate-400">
                    Expediente
                </label>
                <select wire:model.live="estado_expediente"
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                    <option value="todos">Todos</option>
                    <option value="completos">Completos</option>
                    <option value="incompletos">Incompletos</option>
                </select>
            </div>

            <div class="flex items-end gap-2 lg:col-span-1">
                <select wire:model.live="perPage"
                    class="min-w-0 flex-1 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm text-slate-800 outline-none dark:border-neutral-700 dark:bg-neutral-950 dark:text-white">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>

                <button type="button" wire:click="limpiarFiltros"
                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-slate-300 dark:hover:bg-indigo-950/30"
                    title="Limpiar filtros">
                    <flux:icon name="arrow-path" class="size-4" />
                </button>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-neutral-800 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-black text-slate-900 dark:text-white">Personal y avance documental</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Los cinco documentos personales son opcionales; el porcentaje solo funciona como control interno.
                </p>
            </div>
            <span class="inline-flex items-center gap-2 self-start rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                <flux:icon name="users" class="size-4" />
                {{ number_format($personal->total()) }} resultados
            </span>
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                <thead class="bg-slate-50 dark:bg-neutral-950">
                    <tr>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Personal</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Roles</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Estado</th>
                        <th class="px-5 py-3 text-left text-[11px] font-black uppercase tracking-wider text-slate-500">Expediente</th>
                        <th class="px-5 py-3 text-right text-[11px] font-black uppercase tracking-wider text-slate-500">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($personal as $persona)
                        @php
                            $resumen = $persona->resumen_expediente_personal;
                        @endphp
                        <tr wire:key="expediente-personal-{{ $persona->id }}"
                            class="transition hover:bg-slate-50/80 dark:hover:bg-neutral-800/50">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($persona->foto)
                                        <img src="{{ asset('storage/' . $persona->foto) }}" alt=""
                                            class="size-11 rounded-2xl object-cover ring-1 ring-slate-200 dark:ring-neutral-700">
                                    @else
                                        <div class="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-500 font-black text-white shadow-sm">
                                            {{ mb_substr($persona->nombre, 0, 1) }}{{ mb_substr($persona->apellido_paterno, 0, 1) }}
                                        </div>
                                    @endif

                                    <div class="min-w-0">
                                        <p class="font-black text-slate-900 dark:text-white">
                                            {{ $this->nombreCompleto($persona) }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                            {{ $persona->curp ?: 'Sin CURP' }} · {{ $persona->rfc ?: 'Sin RFC' }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex max-w-sm flex-wrap gap-1.5">
                                    @forelse ($persona->rolesPersona->take(3) as $rol)
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black text-slate-600 dark:bg-neutral-800 dark:text-slate-300">
                                            {{ $rol->nombre }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-500">Sin rol asignado</span>
                                    @endforelse
                                    @if ($persona->rolesPersona->count() > 3)
                                        <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-black text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-300">
                                            +{{ $persona->rolesPersona->count() - 3 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstadoLaboral[$persona->estado_laboral] ?? $coloresEstadoLaboral['activo'] }}">
                                    {{ ucfirst($persona->estado_laboral ?: 'activo') }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-2.5 w-36 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                                            style="width: {{ $resumen['porcentaje'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-black text-slate-800 dark:text-white">
                                        {{ $resumen['completados'] }}/{{ $resumen['total'] }}
                                    </span>
                                </div>
                                <p class="mt-2 text-xs font-bold {{ $resumen['completo'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                                    {{ $resumen['completo'] ? 'Expediente completo' : $resumen['pendientes'] . ' pendiente(s)' }}
                                </p>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <button type="button"
                                    @click="openPersonFile({{ $persona->id }})"
                                    :disabled="openingPersonFile"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-black text-white shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-700 hover:shadow-lg disabled:cursor-wait disabled:opacity-70">
                                    <span x-show="openingPersonFile && openingPersonId === {{ $persona->id }}"
                                        class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                    <flux:icon x-show="!(openingPersonFile && openingPersonId === {{ $persona->id }})"
                                        name="folder-open" class="size-4" />
                                    Abrir expediente
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="mx-auto flex size-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                    <flux:icon name="document-magnifying-glass" class="size-7" />
                                </div>
                                <h3 class="mt-4 font-black text-slate-800 dark:text-white">No se encontró personal</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Modifica los filtros para ampliar la búsqueda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-neutral-800 lg:hidden">
            @forelse ($personal as $persona)
                @php
                    $resumen = $persona->resumen_expediente_personal;
                @endphp
                <article class="p-5" wire:key="expediente-personal-card-{{ $persona->id }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">{{ $this->nombreCompleto($persona) }}</h3>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $persona->curp ?: 'Sin CURP' }}</p>
                        </div>
                        <span class="rounded-full px-2.5 py-1 text-xs font-black {{ $resumen['completo'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' }}">
                            {{ $resumen['completados'] }}/{{ $resumen['total'] }}
                        </span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-neutral-800">
                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                            style="width: {{ $resumen['porcentaje'] }}%"></div>
                    </div>
                    <button type="button" @click="openPersonFile({{ $persona->id }})"
                        :disabled="openingPersonFile"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white disabled:cursor-wait disabled:opacity-70">
                        <flux:icon name="folder-open" class="size-4" />
                        Abrir expediente
                    </button>
                </article>
            @empty
                <div class="p-12 text-center text-sm text-slate-500">No se encontró personal.</div>
            @endforelse
        </div>

        @if ($personal->hasPages())
            <div class="border-t border-slate-200 px-5 py-4 dark:border-neutral-800">
                {{ $personal->onEachSide(1)->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </section>

    @if ($personaSeleccionada && $resumenSeleccionado)
        @php
            $documentosActuales = $documentosSeleccionados->where('es_actual', true);
            $tiposPersonales = collect($tiposDocumentos)->where('categoria', 'personal')->sortBy('id');
            $tiposAcademicos = collect($tiposDocumentos)->where('categoria', 'academico')->sortBy('id');
            $academicosActuales = $documentosActuales->filter(
                fn($documento) => $documento->tipoDocumento?->categoria === 'academico',
            );
            $soloHistorico = $personaSeleccionada->estado_laboral === 'baja' || !$personaSeleccionada->status;
        @endphp

        <section id="expediente-personal-seleccionado"
            class="scroll-mt-24 overflow-hidden rounded-[30px] border border-indigo-200 bg-white shadow-xl shadow-indigo-500/10 dark:border-indigo-900/50 dark:bg-neutral-900">
            <div class="relative overflow-hidden bg-gradient-to-r from-indigo-700 via-blue-700 to-sky-600 p-6 text-white sm:p-7">
                <div class="pointer-events-none absolute -right-12 -top-20 size-56 rounded-full bg-white/10 blur-2xl"></div>

                <div class="relative flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black uppercase tracking-wider">
                                Expediente del personal
                            </span>
                            <span class="rounded-full px-3 py-1 text-xs font-black uppercase {{ $personaSeleccionada->estado_laboral === 'baja' ? 'bg-rose-500/80' : 'bg-emerald-500/70' }}">
                                {{ ucfirst($personaSeleccionada->estado_laboral ?: 'activo') }}
                            </span>
                            @if ($soloHistorico)
                                <span class="rounded-full bg-slate-950/35 px-3 py-1 text-xs font-black uppercase">Solo histórico</span>
                            @endif
                        </div>

                        <h2 class="mt-3 text-2xl font-black sm:text-3xl">
                            {{ $this->nombreCompleto($personaSeleccionada) }}
                        </h2>
                        <p class="mt-2 text-sm text-blue-100">
                            {{ $personaSeleccionada->curp ?: 'Sin CURP' }} · {{ $personaSeleccionada->rfc ?: 'Sin RFC' }}
                        </p>
                        <p class="mt-1 text-xs text-blue-100">
                            {{ $personaSeleccionada->rolesPersona->pluck('nombre')->implode(' · ') ?: 'Sin rol asignado' }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($documentosSeleccionados->isNotEmpty() || $personaSeleccionada->movimientosLaborales->isNotEmpty())
                            <a href="{{ route('misrutas.expedientes-personal.zip', $personaSeleccionada) }}"
                                class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-black text-indigo-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                                <flux:icon name="archive-box" class="size-4" />
                                Descargar ZIP
                            </a>
                        @endif

                        <button type="button" wire:click="abrirMovimiento"
                            class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-black text-white backdrop-blur transition hover:bg-white/20">
                            <flux:icon name="briefcase" class="size-4" />
                            Movimiento laboral
                        </button>

                        <button type="button" wire:click="cerrarExpediente"
                            class="inline-flex size-11 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-white backdrop-blur transition hover:bg-white/20"
                            title="Cerrar expediente">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-7 p-5 sm:p-7">
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Avance documental</p>
                        <div class="mt-3 flex items-end justify-between gap-3">
                            <p class="text-4xl font-black text-slate-900 dark:text-white">{{ $resumenSeleccionado['porcentaje'] }}%</p>
                            <p class="text-sm font-bold text-slate-500">{{ $resumenSeleccionado['completados'] }}/{{ $resumenSeleccionado['total'] }}</p>
                        </div>
                        <div class="mt-4 h-3 overflow-hidden rounded-full bg-slate-200 dark:bg-neutral-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-sky-500"
                                style="width: {{ $resumenSeleccionado['porcentaje'] }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Contacto</p>
                        <p class="mt-3 text-lg font-black text-slate-900 dark:text-white">
                            {{ $personaSeleccionada->correo ?: 'Sin correo registrado' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">{{ $personaSeleccionada->telefono_movil ?: 'Sin teléfono móvil' }}</p>
                    </div>

                    <div class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-neutral-800 dark:bg-neutral-950">
                        <p class="text-xs font-black uppercase tracking-wide text-slate-500">Situación laboral</p>
                        <p class="mt-3 text-lg font-black text-slate-900 dark:text-white">
                            {{ ucfirst($personaSeleccionada->estado_laboral ?: 'activo') }}
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $soloHistorico ? 'Consulta y descarga habilitadas; nuevas cargas bloqueadas.' : 'Expediente habilitado para nuevas cargas.' }}
                        </p>
                    </div>
                </div>

                <section>
                    <div class="mb-4">
                        <h3 class="text-lg font-black text-slate-900 dark:text-white">Documentos personales</h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Identificación, domicilio, CURP, acta y constancia fiscal. Todos son opcionales.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($tiposPersonales as $tipo)
                            @php
                                $documentoActual = $documentosActuales
                                    ->where('tipo_documento_personal_id', $tipo['id'])
                                    ->sortByDesc('id')
                                    ->first();
                                $presente = $documentoActual && in_array($documentoActual->estado, ['recibido', 'validado'], true);
                                $claveBoton = 'personal-' . $tipo['id'];
                            @endphp

                            <article id="documento-personal-tipo-{{ $tipo['id'] }}"
                                data-personal-document-type="{{ $tipo['id'] }}"
                                class="rounded-2xl border p-4 transition {{ $presente ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900/50 dark:bg-emerald-950/10' : 'border-amber-200 bg-amber-50/60 dark:border-amber-900/50 dark:bg-amber-950/10' }}">
                                <div class="flex items-start gap-3">
                                    <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl {{ $presente ? 'bg-emerald-500' : 'bg-amber-500' }} text-white">
                                        <flux:icon :name="$presente ? 'check' : 'clock'" class="size-5" />
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="font-black text-slate-900 dark:text-white">{{ $tipo['nombre'] }}</h4>
                                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $tipo['descripcion'] }}</p>
                                        <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $documentoActual ? ($coloresEstadoDocumento[$documentoActual->estado] ?? $coloresEstadoDocumento['pendiente']) : $coloresEstadoDocumento['pendiente'] }}">
                                            {{ $documentoActual ? ucfirst($documentoActual->estado) : 'Pendiente' }}
                                        </span>
                                    </div>
                                </div>

                                @if ($documentoActual)
                                    <p class="mt-3 truncate text-xs text-slate-500" title="{{ $documentoActual->nombre_original }}">
                                        {{ $documentoActual->etiqueta_detalle }} · v{{ $documentoActual->version }} · {{ $documentoActual->tamano_legible }}
                                    </p>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-2">
                                    @if ($documentoActual)
                                        <a href="{{ route('misrutas.expedientes-personal.preview', $documentoActual) }}" target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="eye" class="size-3.5" /> Ver PDF
                                        </a>
                                        <a href="{{ route('misrutas.expedientes-personal.download', $documentoActual) }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-indigo-200 hover:text-indigo-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            <flux:icon name="arrow-down-tray" class="size-3.5" /> Descargar
                                        </a>
                                    @endif

                                    @if (!$soloHistorico)
                                        <button type="button"
                                            @click="openUpload({{ $tipo['id'] }}, null, '{{ $claveBoton }}')"
                                            :disabled="openingUpload || closingUpload"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                                            <span x-show="openingUpload && openingKey === '{{ $claveBoton }}'"
                                                class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                            <flux:icon x-show="!(openingUpload && openingKey === '{{ $claveBoton }}')"
                                                name="arrow-up-tray" class="size-3.5" />
                                            {{ $documentoActual ? 'Nueva versión' : 'Subir' }}
                                        </button>

                                        @if ($documentoActual)
                                            <select wire:change="actualizarEstado({{ $documentoActual->id }}, $event.target.value)"
                                                class="ml-auto rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                                @foreach (['pendiente', 'recibido', 'validado', 'rechazado'] as $estado)
                                                    <option value="{{ $estado }}" @selected($documentoActual->estado === $estado)>
                                                        {{ ucfirst($estado) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white">Documentos académicos</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Puedes registrar varios títulos y varias cédulas profesionales.
                            </p>
                        </div>
                    </div>

                    @if (!$soloHistorico)
                        <div class="mb-5 grid grid-cols-1 gap-3 md:grid-cols-2">
                            @foreach ($tiposAcademicos as $tipo)
                                @php
                                    $claveAcademica = 'academico-' . $tipo['id'];
                                @endphp
                                <article class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-sky-50 p-4 dark:border-indigo-900/50 dark:from-indigo-950/20 dark:to-sky-950/20">
                                    <div class="flex items-start gap-3">
                                        <div class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                            <flux:icon name="academic-cap" class="size-5" />
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-900 dark:text-white">Agregar {{ mb_strtolower($tipo['nombre']) }}</h4>
                                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ $tipo['descripcion'] }}</p>
                                        </div>
                                    </div>

                                    <button type="button"
                                        @click="openUpload({{ $tipo['id'] }}, null, '{{ $claveAcademica }}')"
                                        :disabled="openingUpload || closingUpload"
                                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white transition hover:bg-indigo-700 disabled:cursor-wait disabled:opacity-70">
                                        <span x-show="openingUpload && openingKey === '{{ $claveAcademica }}'"
                                            class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                        <flux:icon x-show="!(openingUpload && openingKey === '{{ $claveAcademica }}')"
                                            name="plus" class="size-4" />
                                        Agregar documento
                                    </button>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        @forelse ($academicosActuales as $documento)
                            @php
                                $claveVersion = 'serie-' . $documento->serie_uuid;
                            @endphp
                            <article id="documento-personal-serie-{{ $documento->serie_uuid }}"
                                data-personal-document-type="{{ $documento->tipo_documento_personal_id }}"
                                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-black text-slate-900 dark:text-white">{{ $documento->etiqueta_detalle }}</p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            {{ $documento->tipoDocumento?->nombre }}
                                            @if ($documento->nivel_academico)
                                                · {{ ucfirst($documento->nivel_academico) }}
                                            @endif
                                            @if ($documento->institucion)
                                                · {{ $documento->institucion }}
                                            @endif
                                        </p>
                                        @if ($documento->numero_cedula)
                                            <p class="mt-1 text-xs font-bold text-slate-600 dark:text-slate-300">
                                                Número de cédula: {{ $documento->numero_cedula }}
                                            </p>
                                        @endif
                                        <p class="mt-1 text-xs text-slate-500">v{{ $documento->version }} · {{ $documento->tamano_legible }}</p>
                                    </div>

                                    <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstadoDocumento[$documento->estado] ?? $coloresEstadoDocumento['pendiente'] }}">
                                        {{ ucfirst($documento->estado) }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <a href="{{ route('misrutas.expedientes-personal.preview', $documento) }}" target="_blank"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                        <flux:icon name="eye" class="size-3.5" /> Ver
                                    </a>
                                    <a href="{{ route('misrutas.expedientes-personal.download', $documento) }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                        <flux:icon name="arrow-down-tray" class="size-3.5" /> Descargar
                                    </a>

                                    @if (!$soloHistorico)
                                        <button type="button"
                                            @click="openUpload({{ $documento->tipo_documento_personal_id }}, @js($documento->serie_uuid), '{{ $claveVersion }}')"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-black text-white transition hover:bg-indigo-700">
                                            <flux:icon name="arrow-up-tray" class="size-3.5" /> Nueva versión
                                        </button>

                                        <select wire:change="actualizarEstado({{ $documento->id }}, $event.target.value)"
                                            class="ml-auto rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                            @foreach (['pendiente', 'recibido', 'validado', 'rechazado'] as $estado)
                                                <option value="{{ $estado }}" @selected($documento->estado === $estado)>{{ ucfirst($estado) }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="lg:col-span-2 rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-neutral-700">
                                Todavía no hay títulos o cédulas profesionales cargados.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 dark:border-neutral-800">
                    <div class="border-b border-slate-200 bg-slate-50 px-5 py-4 dark:border-neutral-800 dark:bg-neutral-950">
                        <h3 class="font-black text-slate-900 dark:text-white">Historial completo de versiones</h3>
                        <p class="text-sm text-slate-500">Ningún PDF se elimina; las versiones reemplazadas siguen disponibles.</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-neutral-800">
                            <thead>
                                <tr class="text-left text-[11px] font-black uppercase tracking-wider text-slate-500">
                                    <th class="px-5 py-3">Documento</th>
                                    <th class="px-5 py-3">Versión</th>
                                    <th class="px-5 py-3">Estado</th>
                                    <th class="px-5 py-3">Carga</th>
                                    <th class="px-5 py-3 text-right">Archivo</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                                @forelse ($documentosSeleccionados as $documento)
                                    <tr wire:key="historial-personal-doc-{{ $documento->id }}">
                                        <td class="px-5 py-4">
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $documento->etiqueta_detalle }}</p>
                                            <p class="mt-1 max-w-md truncate text-xs text-slate-500" title="{{ $documento->nombre_original }}">
                                                {{ $documento->tipoDocumento?->nombre }} · {{ $documento->nombre_original }}
                                            </p>
                                        </td>
                                        <td class="px-5 py-4 text-sm font-black text-slate-700 dark:text-slate-200">v{{ $documento->version }}</td>
                                        <td class="px-5 py-4">
                                            <span class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstadoDocumento[$documento->estado] ?? $coloresEstadoDocumento['pendiente'] }}">
                                                {{ ucfirst($documento->estado) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-xs text-slate-500">
                                            <p>{{ $documento->created_at?->format('d/m/Y H:i') }}</p>
                                            <p class="mt-1">{{ $documento->usuarioQueSubio?->name ?? 'Usuario no disponible' }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-right">
                                            <div class="inline-flex gap-2">
                                                <a href="{{ route('misrutas.expedientes-personal.preview', $documento) }}" target="_blank"
                                                    class="inline-flex size-9 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-indigo-950/30"
                                                    title="Ver PDF">
                                                    <flux:icon name="eye" class="size-4" />
                                                </a>
                                                <a href="{{ route('misrutas.expedientes-personal.download', $documento) }}"
                                                    class="inline-flex size-9 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-indigo-950/30"
                                                    title="Descargar PDF">
                                                    <flux:icon name="arrow-down-tray" class="size-4" />
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">No hay versiones registradas.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 p-5 dark:border-neutral-800">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white">Historial laboral</h3>
                            <p class="mt-1 text-sm text-slate-500">Activo, baja, licencia, suspensión y reingreso.</p>
                        </div>
                        <button type="button" wire:click="abrirMovimiento"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900">
                            <flux:icon name="plus" class="size-4" /> Registrar movimiento
                        </button>
                    </div>

                    <div class="mt-4 space-y-3">
                        @forelse ($personaSeleccionada->movimientosLaborales->sortByDesc(fn($movimiento) => ($movimiento->fecha?->format('Ymd') ?? '') . str_pad((string) $movimiento->id, 10, '0', STR_PAD_LEFT)) as $movimiento)
                            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-neutral-800 dark:bg-neutral-950 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase ring-1 {{ $coloresEstadoLaboral[$movimiento->tipo] ?? $coloresEstadoLaboral['activo'] }}">
                                            {{ ucfirst($movimiento->tipo) }}
                                        </span>
                                        <span class="text-xs font-bold text-slate-500">{{ $movimiento->fecha?->format('d/m/Y') ?? 'Sin fecha' }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-700 dark:text-slate-200">{{ $movimiento->motivo ?: 'Sin motivo registrado' }}</p>
                                    @if ($movimiento->observaciones)
                                        <p class="mt-1 text-xs text-slate-500">{{ $movimiento->observaciones }}</p>
                                    @endif
                                </div>
                                <span class="text-xs text-slate-500">Registró: {{ $movimiento->usuario?->name ?? 'Usuario no disponible' }}</span>
                            </div>
                        @empty
                            <div class="rounded-2xl border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500 dark:border-neutral-700">
                                No hay movimientos laborales registrados.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    @endif

    {{-- Modal profesional de carga PDF. --}}
    @if ($mostrarCarga && $personaSeleccionada)
        @php
            $tipoSeleccionado = collect($tiposDocumentos)->firstWhere('id', $tipo_documento_personal_id);
            $slugTipo = $tipoSeleccionado['slug'] ?? '';
            $esIdentificacion = $slugTipo === 'identificacion-oficial';
            $esAcademico = in_array($slugTipo, ['titulo-profesional', 'cedula-profesional'], true);
            $esCedula = $slugTipo === 'cedula-profesional';
        @endphp

        <div x-cloak x-show="true" x-transition.opacity
            class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/75 p-2 backdrop-blur-md sm:p-4"
            wire:key="modal-carga-personal-{{ $tipo_documento_personal_id }}-{{ $serie_uuid }}"
            @click.self="requestCloseUpload()">
            <div x-transition.scale.origin.center
                class="relative flex max-h-[96vh] w-full max-w-7xl flex-col overflow-hidden rounded-[30px] border border-white/15 bg-white shadow-2xl dark:bg-neutral-900">

                <div x-cloak x-show="closingUpload" x-transition.opacity
                    class="absolute inset-0 z-50 flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-sm">
                    <div class="rounded-3xl border border-white/15 bg-slate-950/90 px-8 py-6 text-center text-white shadow-2xl">
                        <div class="mx-auto size-11 animate-spin rounded-full border-4 border-white/20 border-t-sky-400"></div>
                        <p class="mt-4 text-sm font-black">Cerrando formulario…</p>
                        <p class="mt-1 text-xs text-slate-300">Descartando la carga temporal de forma segura.</p>
                    </div>
                </div>

                <div class="shrink-0 bg-gradient-to-r from-indigo-700 via-blue-700 to-sky-600 px-5 py-4 text-white sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-100">Expediente del personal</p>
                            <h3 class="mt-1 text-xl font-black sm:text-2xl">Subir documento PDF</h3>
                            <p class="mt-1 text-sm text-blue-100">Vista previa local · PDF unificado · máximo 5 MB.</p>
                        </div>
                        <button type="button" @click="requestCloseUpload()" :disabled="closingUpload || saving"
                            class="inline-flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-white transition hover:bg-white/20 disabled:opacity-50">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </div>

                <form class="flex min-h-0 flex-1 flex-col"
                    @submit.prevent="submitDocument({{ $reemplazaDocumentoActual ? 'true' : 'false' }})">
                    <div class="grid min-h-0 flex-1 grid-cols-1 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
                        <div class="space-y-5 overflow-y-auto border-b border-slate-200 p-5 dark:border-neutral-800 sm:p-6 xl:border-b-0 xl:border-r">
                            <div class="rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-sky-50 p-4 dark:border-indigo-900/50 dark:from-indigo-950/30 dark:to-sky-950/20">
                                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-indigo-500">Documento seleccionado</p>
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm">
                                        <flux:icon name="document-text" class="size-6" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-black text-slate-900 dark:text-white">{{ $tipoSeleccionado['nombre'] ?? 'Documento' }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ $this->nombreCompleto($personaSeleccionada) }}</p>
                                    </div>
                                </div>
                            </div>

                            @if ($esIdentificacion)
                                <div>
                                    <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Tipo de identificación</label>
                                    <select wire:model="subtipo_identificacion"
                                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                        <option value="ine">INE</option>
                                        <option value="pasaporte">Pasaporte</option>
                                        <option value="cedula">Cédula profesional como identificación</option>
                                        <option value="otra">Otra identificación oficial</option>
                                    </select>
                                    @error('subtipo_identificacion')
                                        <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endif

                            @if ($esAcademico)
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Nombre del estudio</label>
                                        <input type="text" wire:model="nombre_estudio" maxlength="255"
                                            placeholder="Ejemplo: Licenciatura en Educación"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                        @error('nombre_estudio')
                                            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Nivel académico</label>
                                        <select wire:model="nivel_academico"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            <option value="">Sin especificar</option>
                                            <option value="tecnico">Técnico</option>
                                            <option value="licenciatura">Licenciatura</option>
                                            <option value="especialidad">Especialidad</option>
                                            <option value="maestria">Maestría</option>
                                            <option value="doctorado">Doctorado</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Institución</label>
                                        <input type="text" wire:model="institucion" maxlength="255"
                                            placeholder="Institución que emitió el documento"
                                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                    </div>

                                    @if ($esCedula)
                                        <div class="sm:col-span-2">
                                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Número de cédula</label>
                                            <input type="text" wire:model="numero_cedula" maxlength="100"
                                                placeholder="Número de cédula profesional"
                                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                            @error('numero_cedula')
                                                <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Archivo PDF</label>

                                <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
                                    @drop.prevent="handleDrop($event)"
                                    :class="dragging
                                        ? 'border-indigo-500 bg-indigo-50 ring-4 ring-indigo-100 dark:bg-indigo-950/30 dark:ring-indigo-950/50'
                                        : 'border-slate-300 bg-slate-50 dark:border-neutral-700 dark:bg-neutral-950'"
                                    class="relative rounded-3xl border-2 border-dashed p-5 text-center transition">
                                    <input data-personal-pdf-input type="file" accept="application/pdf,.pdf"
                                        class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0"
                                        :disabled="uploading || saving || closingUpload"
                                        @change="handleInput($event)">

                                    <div class="pointer-events-none">
                                        <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300">
                                            <flux:icon name="document-text" class="size-6" />
                                        </div>
                                        <p class="mt-3 text-sm font-black text-slate-800 dark:text-white"
                                            x-text="fileName || 'Arrastra el PDF aquí o haz clic para seleccionarlo'"></p>
                                        <p class="mt-1 text-xs text-slate-500">PDF real · máximo 5 MB · validación inmediata</p>
                                        <p x-show="fileSize" class="mt-1 text-xs font-bold text-indigo-600" x-text="fileSize"></p>
                                    </div>
                                </div>

                                <div x-cloak x-show="uploading || uploadProgress > 0"
                                    class="mt-3 rounded-2xl border border-indigo-100 bg-indigo-50 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                                    <div class="flex items-center justify-between gap-3 text-xs font-black text-indigo-700 dark:text-indigo-300">
                                        <span x-text="uploading ? 'Cargando PDF…' : 'PDF preparado'"></span>
                                        <span x-text="`${uploadProgress}%`"></span>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-indigo-100 dark:bg-indigo-950">
                                        <div class="h-full rounded-full bg-indigo-600 transition-all duration-200"
                                            :style="`width: ${uploadProgress}%`"></div>
                                    </div>
                                </div>

                                <div x-cloak x-show="fileError"
                                    class="mt-3 rounded-2xl border border-rose-200 bg-rose-50 p-3 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                                    x-text="fileError"></div>

                                @error('archivo')
                                    <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Observaciones opcionales</label>
                                <textarea wire:model="observaciones" rows="3" maxlength="2000"
                                    placeholder="Notas administrativas del documento…"
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40"></textarea>
                                @error('observaciones')
                                    <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($reemplazaDocumentoActual)
                                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                                    Ya existe una versión actual. Al guardar, la anterior se conservará con el estado <strong>Reemplazado</strong>.
                                </div>
                            @elseif ($esAcademico)
                                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-xs leading-5 text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-200">
                                    Este documento se agregará como un título o cédula independiente. Podrás registrar varios.
                                </div>
                            @endif
                        </div>

                        <div class="flex min-h-[420px] flex-col bg-slate-100 p-4 dark:bg-neutral-950 sm:p-5 xl:min-h-0">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Vista previa</p>
                                    <p class="mt-1 text-xs text-slate-500">Usa los controles del visor para cambiar de página o zoom.</p>
                                </div>
                                <button x-cloak x-show="hasFile" type="button" @click="clearSelectedFile()"
                                    :disabled="uploading || saving"
                                    class="relative z-20 inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-black text-slate-700 transition hover:border-rose-200 hover:text-rose-600 disabled:opacity-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-200">
                                    <flux:icon name="trash" class="size-3.5" /> Quitar
                                </button>
                            </div>

                            <div class="relative min-h-[340px] flex-1 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-inner dark:border-neutral-800 dark:bg-neutral-900">
                                <template x-if="previewUrl">
                                    <iframe :src="previewUrl" title="Vista previa del PDF"
                                        class="h-full min-h-[560px] w-full bg-white xl:min-h-full"></iframe>
                                </template>

                                <div x-show="!previewUrl" class="absolute inset-0 flex flex-col items-center justify-center p-8 text-center">
                                    <div class="flex size-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-neutral-800">
                                        <flux:icon name="document-magnifying-glass" class="size-8" />
                                    </div>
                                    <p class="mt-4 text-sm font-black text-slate-700 dark:text-slate-200">La vista previa aparecerá aquí</p>
                                    <p class="mt-1 max-w-sm text-xs leading-5 text-slate-500">El PDF se muestra localmente antes de guardarse.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sticky bottom-0 z-30 flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/95 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <p class="text-xs text-slate-500">Ningún archivo anterior será eliminado físicamente.</p>
                        <div class="flex flex-col-reverse gap-2 sm:flex-row">
                            <button type="button" @click="requestCloseUpload()" :disabled="saving || closingUpload"
                                class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 transition hover:bg-slate-50 disabled:opacity-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="saving || uploading || !uploadReady || closingUpload"
                                class="inline-flex min-w-[190px] items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-5 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60">
                                <span x-show="saving" class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                <flux:icon x-show="!saving" name="arrow-up-tray" class="size-4" />
                                <span x-text="saving ? 'Guardando…' : 'Guardar documento'"></span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal de movimiento laboral. --}}
    @if ($mostrarMovimiento && $personaSeleccionada)
        <div class="fixed inset-0 z-[115] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            wire:key="modal-movimiento-personal">
            <div class="w-full max-w-2xl overflow-hidden rounded-[28px] border border-white/10 bg-white shadow-2xl dark:bg-neutral-900">
                <div class="bg-gradient-to-r from-slate-900 to-indigo-900 p-5 text-white">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-300">Historial laboral</p>
                            <h3 class="mt-1 text-xl font-black">Registrar movimiento</h3>
                            <p class="mt-1 text-sm text-slate-300">{{ $this->nombreCompleto($personaSeleccionada) }}</p>
                        </div>
                        <button type="button" wire:click="cerrarMovimiento"
                            class="inline-flex size-10 items-center justify-center rounded-2xl bg-white/10 text-white transition hover:bg-white/20">
                            <flux:icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                </div>

                <form wire:submit="registrarMovimiento" class="space-y-5 p-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Movimiento</label>
                            <select wire:model="tipo_movimiento"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                                <option value="activo">Activo</option>
                                <option value="baja">Baja</option>
                                <option value="licencia">Licencia</option>
                                <option value="suspendido">Suspendido</option>
                                <option value="reingreso">Reingreso</option>
                            </select>
                            @error('tipo_movimiento')
                                <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Fecha</label>
                            <input type="date" wire:model="fecha_movimiento"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40">
                            @error('fecha_movimiento')
                                <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Motivo opcional</label>
                        <textarea wire:model="motivo_movimiento" rows="3" maxlength="2000"
                            placeholder="Describe el motivo del movimiento…"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40"></textarea>
                    </div>

                    <div>
                        <label class="mb-2 block text-xs font-black uppercase tracking-wide text-slate-500">Observaciones opcionales</label>
                        <textarea wire:model="observaciones_movimiento" rows="3" maxlength="2000"
                            placeholder="Notas administrativas adicionales…"
                            class="w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 dark:border-neutral-700 dark:bg-neutral-950 dark:text-white dark:focus:ring-indigo-950/40"></textarea>
                    </div>

                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">
                        El estado <strong>Baja</strong> desactiva a la persona y bloquea nuevas cargas, pero conserva todo el expediente. Reingreso vuelve a habilitarlo.
                    </div>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="cerrarMovimiento"
                            class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-black text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:text-slate-300 dark:hover:bg-neutral-800">
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="registrarMovimiento"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-2.5 text-sm font-black text-white transition hover:bg-slate-800 disabled:opacity-60 dark:bg-white dark:text-slate-900">
                            <span wire:loading.remove wire:target="registrarMovimiento">Guardar movimiento</span>
                            <span wire:loading wire:target="registrarMovimiento">Guardando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        window.expedientePersonalUploader = window.expedientePersonalUploader || function() {
            return {
                openingPersonFile: false,
                openingPersonId: null,
                openingUpload: false,
                openingKey: null,
                closingUpload: false,
                saving: false,
                uploading: false,
                uploadProgress: 0,
                uploadReady: false,
                previewUrl: null,
                fileName: '',
                fileSize: '',
                fileError: '',
                hasFile: false,
                dragging: false,
                serverUploadName: null,

                wait(milliseconds) {
                    return new Promise(resolve => window.setTimeout(resolve, milliseconds));
                },

                async openPersonFile(personaId) {
                    if (this.openingPersonFile || this.openingUpload || this.closingUpload || this.saving) {
                        return;
                    }

                    this.openingPersonFile = true;
                    this.openingPersonId = personaId;

                    try {
                        await this.$wire.verExpediente(personaId);
                        await this.$nextTick();

                        window.requestAnimationFrame(() => {
                            window.requestAnimationFrame(() => {
                                const target = document.getElementById('expediente-personal-seleccionado');

                                if (!target) return;

                                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                target.animate(
                                    [
                                        { boxShadow: '0 0 0 0 rgba(79, 70, 229, 0)' },
                                        { boxShadow: '0 0 0 6px rgba(79, 70, 229, .22)' },
                                        { boxShadow: '0 0 0 0 rgba(79, 70, 229, 0)' },
                                    ],
                                    { duration: 900, easing: 'ease-out' },
                                );
                            });
                        });
                    } catch (error) {
                        this.showError('No fue posible abrir el expediente del personal.');
                    } finally {
                        this.openingPersonFile = false;
                        this.openingPersonId = null;
                    }
                },

                async openUpload(tipoId, serieUuid = null, key = null) {
                    if (this.openingUpload || this.closingUpload || this.saving) return;

                    this.openingUpload = true;
                    this.openingKey = key;
                    const startedAt = performance.now();

                    try {
                        await this.$wire.abrirCarga(tipoId, serieUuid);
                    } catch (error) {
                        this.showError('No fue posible preparar el formulario de carga.');
                    } finally {
                        const elapsed = performance.now() - startedAt;
                        await this.wait(Math.max(0, 300 - elapsed));
                        this.openingUpload = false;
                        this.openingKey = null;
                    }
                },

                async requestCloseUpload() {
                    if (this.closingUpload || this.saving) return;

                    if (this.hasFile) {
                        const confirmed = await this.confirm({
                            title: '¿Cerrar sin guardar?',
                            text: 'El PDF seleccionado y los cambios del formulario se descartarán.',
                            confirmText: 'Sí, cerrar',
                        });

                        if (!confirmed) return;
                    }

                    this.closingUpload = true;

                    try {
                        await this.wait(250);
                        await this.removeTemporaryUpload();
                        this.revokePreview();
                        await this.$wire.cerrarCarga();
                    } finally {
                        this.closingUpload = false;
                        this.resetLocalFileState();
                    }
                },

                async handleInput(event) {
                    await this.prepareFile(event.target.files?.[0] ?? null);
                },

                async handleDrop(event) {
                    this.dragging = false;
                    await this.prepareFile(event.dataTransfer?.files?.[0] ?? null);
                },

                async prepareFile(file) {
                    this.fileError = '';
                    if (!file) return;

                    const maxBytes = 5 * 1024 * 1024;
                    const extensionValid = file.name.toLowerCase().endsWith('.pdf');
                    const mimeValid = ['', 'application/pdf', 'application/x-pdf'].includes(file.type);

                    if (!extensionValid || !mimeValid) {
                        await this.rejectFile('Selecciona únicamente un archivo PDF válido.');
                        return;
                    }

                    if (file.size > maxBytes) {
                        await this.rejectFile('El PDF supera el límite de 5 MB.');
                        return;
                    }

                    try {
                        const signatureBuffer = await file.slice(0, 5).arrayBuffer();
                        const signature = new TextDecoder().decode(signatureBuffer);

                        if (signature !== '%PDF-') {
                            await this.rejectFile('El archivo no contiene una estructura PDF válida.');
                            return;
                        }
                    } catch (error) {
                        await this.rejectFile('No fue posible validar el archivo seleccionado.');
                        return;
                    }

                    await this.removeTemporaryUpload();
                    this.revokePreview();

                    this.previewUrl = URL.createObjectURL(file);
                    this.fileName = file.name;
                    this.fileSize = this.formatBytes(file.size);
                    this.hasFile = true;
                    this.uploadReady = false;
                    this.uploading = true;
                    this.uploadProgress = 0;

                    await new Promise((resolve, reject) => {
                        this.$wire.upload(
                            'archivo',
                            file,
                            uploadedFilename => {
                                this.serverUploadName = uploadedFilename;
                                this.uploadProgress = 100;
                                this.uploading = false;
                                this.uploadReady = true;
                                resolve(uploadedFilename);
                            },
                            () => {
                                this.uploading = false;
                                this.uploadReady = false;
                                this.fileError = 'No fue posible cargar temporalmente el PDF.';
                                reject(new Error(this.fileError));
                            },
                            event => {
                                this.uploadProgress = Number(event.detail?.progress ?? 0);
                            },
                        );
                    }).catch(() => this.showError(this.fileError || 'No fue posible cargar el PDF.'));
                },

                async rejectFile(message) {
                    await this.removeTemporaryUpload();
                    this.revokePreview();
                    this.resetLocalFileState();
                    this.fileError = message;
                    this.resetInput();
                    this.showError(message);
                },

                async clearSelectedFile() {
                    if (this.uploading || this.saving) return;
                    await this.removeTemporaryUpload();
                    this.revokePreview();
                    this.resetLocalFileState();
                    this.resetInput();
                },

                async removeTemporaryUpload() {
                    const uploadedName = this.serverUploadName;
                    this.serverUploadName = null;

                    if (uploadedName) {
                        await new Promise(resolve => {
                            this.$wire.removeUpload('archivo', uploadedName, resolve, resolve);
                        });
                        return;
                    }

                    try {
                        await this.$wire.set('archivo', null, false);
                    } catch (error) {
                        // No existe carga temporal.
                    }
                },

                async submitDocument(replacesCurrent) {
                    if (this.saving || this.uploading || this.closingUpload) return;

                    if (!this.uploadReady) {
                        this.fileError = 'Selecciona y espera a que termine de cargar un PDF válido.';
                        this.showError(this.fileError);
                        return;
                    }

                    if (replacesCurrent) {
                        const confirmed = await this.confirm({
                            title: '¿Guardar una nueva versión?',
                            text: 'La versión actual se conservará en el historial con el estado Reemplazado.',
                            confirmText: 'Sí, guardar versión',
                        });

                        if (!confirmed) return;
                    }

                    this.saving = true;

                    try {
                        await this.$wire.subirDocumento();
                    } catch (error) {
                        this.showError('No fue posible guardar el documento.');
                    } finally {
                        this.saving = false;
                    }
                },

                onSaved(detail) {
                    this.revokePreview();
                    this.resetLocalFileState();
                    this.resetInput();

                    const data = Array.isArray(detail) ? detail[0] : detail;
                    const tipoId = Number(data?.tipoId ?? 0);
                    const serieUuid = data?.serieUuid ?? null;

                    window.setTimeout(() => {
                        const target = (serieUuid
                            ? document.getElementById(`documento-personal-serie-${serieUuid}`)
                            : null) || document.getElementById(`documento-personal-tipo-${tipoId}`) ||
                            document.querySelector(`[data-personal-document-type="${tipoId}"]`);

                        if (!target) return;

                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        target.animate(
                            [
                                { boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)' },
                                { boxShadow: '0 0 0 6px rgba(16, 185, 129, .30)' },
                                { boxShadow: '0 0 0 0 rgba(16, 185, 129, 0)' },
                            ],
                            { duration: 1400, easing: 'ease-out' },
                        );
                    }, 180);
                },

                revokePreview() {
                    if (this.previewUrl) {
                        URL.revokeObjectURL(this.previewUrl);
                        this.previewUrl = null;
                    }
                },

                resetLocalFileState() {
                    this.fileName = '';
                    this.fileSize = '';
                    this.fileError = '';
                    this.hasFile = false;
                    this.uploading = false;
                    this.uploadReady = false;
                    this.uploadProgress = 0;
                    this.dragging = false;
                    this.serverUploadName = null;
                },

                resetInput() {
                    const input = this.$root.querySelector('[data-personal-pdf-input]');
                    if (input) input.value = '';
                },

                formatBytes(bytes) {
                    if (!Number.isFinite(bytes) || bytes <= 0) return '';
                    const units = ['B', 'KB', 'MB'];
                    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
                    const value = bytes / Math.pow(1024, index);
                    return `${value.toFixed(index === 0 ? 0 : 2)} ${units[index]}`;
                },

                async confirm({ title, text, confirmText }) {
                    if (window.Swal) {
                        const result = await Swal.fire({
                            title,
                            text,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: confirmText,
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                            focusCancel: true,
                        });
                        return result.isConfirmed;
                    }

                    return window.confirm(`${title}\n\n${text}`);
                },

                showError(message) {
                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'error',
                            title: message,
                            showConfirmButton: false,
                            timer: 3200,
                            timerProgressBar: true,
                        });
                    }
                },
            };
        };

        (() => {
            const registerListeners = () => {
                if (window.__expedientesPersonalListenersRegistered) return;
                window.__expedientesPersonalListenersRegistered = true;

                Livewire.on('notify', event => {
                    const detail = Array.isArray(event) ? event[0] : event;

                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: detail?.type || 'success',
                            title: detail?.message || 'Operación realizada',
                            showConfirmButton: false,
                            timer: 2800,
                            timerProgressBar: true,
                        });
                    }
                });

                Livewire.on('documento-personal-guardado', event => {
                    const detail = Array.isArray(event) ? event[0] : event;
                    window.dispatchEvent(new CustomEvent('expediente-personal-documento-guardado', { detail }));
                });
            };

            if (window.Livewire) {
                registerListeners();
            } else {
                document.addEventListener('livewire:init', registerListeners, { once: true });
            }
        })();
    </script>
</div>
