<div>
    @if ($this->esSecundaria)
        <section x-data="{ abierto: @entangle('mostrarPanel').live }" x-show="abierto" x-cloak x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
            class="overflow-hidden rounded-[28px] border border-cyan-200 bg-white shadow-xl shadow-cyan-500/10 dark:border-cyan-900/50 dark:bg-neutral-950">

            <div class="h-1.5 bg-gradient-to-r from-cyan-500 via-sky-500 to-indigo-600"></div>

            <div
                class="border-b border-slate-200 bg-gradient-to-r from-cyan-50 via-white to-indigo-50 p-5 dark:border-neutral-800 dark:from-cyan-950/30 dark:via-neutral-950 dark:to-indigo-950/30 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-cyan-500 to-indigo-600 text-white shadow-lg shadow-cyan-500/20">
                            <flux:icon.user-group class="h-5 w-5" />
                        </div>

                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-black text-slate-900 dark:text-white">
                                    {{ $editandoSesionId ? 'Editar taller conjunto' : 'Asignar taller conjunto' }}
                                </h2>
                                <span
                                    class="rounded-full border border-cyan-200 bg-white px-3 py-1 text-[11px] font-black uppercase tracking-wide text-cyan-700 dark:border-cyan-900/50 dark:bg-neutral-900 dark:text-cyan-300">
                                    Una hora semanal
                                </span>
                            </div>

                            <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                                Una sola sesión se mostrará en todos los grupos seleccionados. Debe incluir al menos un
                                grupo de 1.º, 2.º y 3.º; para la carga del profesor contará únicamente como un bloque
                                semanal.
                            </p>
                        </div>
                    </div>

                    <button type="button" wire:click="cerrar"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300 dark:hover:bg-neutral-800">
                        <flux:icon.x-mark class="h-4 w-4" />
                        Cerrar
                    </button>
                </div>
            </div>

            <div wire:loading.flex wire:target="guardarSesion,crearTaller,editar,eliminar"
                class="absolute inset-0 z-40 items-center justify-center bg-white/75 backdrop-blur-sm dark:bg-black/60">
                <div
                    class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-5 py-3 shadow-2xl dark:border-neutral-700 dark:bg-neutral-900">
                    <span class="h-5 w-5 animate-spin rounded-full border-2 border-cyan-200 border-t-cyan-600"></span>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Procesando taller
                        conjunto...</span>
                </div>
            </div>

            <div
                class="relative grid grid-cols-1 gap-6 p-5 sm:p-6 2xl:grid-cols-[minmax(0,1.25fr)_minmax(380px,.75fr)]">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <flux:field>
                            <div class="flex items-center justify-between gap-3">
                                <flux:label>Taller</flux:label>
                                <button type="button" wire:click="$toggle('mostrarCatalogo')"
                                    class="text-xs font-black text-cyan-700 hover:text-cyan-800 dark:text-cyan-300">
                                    + Registrar nuevo
                                </button>
                            </div>

                            <flux:select wire:model.live="taller_id">
                                <option value="">Selecciona un taller</option>
                                @foreach ($this->talleres as $taller)
                                    <option value="{{ $taller->id }}">
                                        {{ $taller->nombre }}{{ $taller->clave ? ' · ' . $taller->clave : '' }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error name="taller_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Ciclo escolar</flux:label>
                            <flux:select wire:model.live="ciclo_escolar_id">
                                <option value="">Selecciona el ciclo</option>
                                @foreach ($this->ciclosEscolares as $ciclo)
                                    <option value="{{ $ciclo->id }}">
                                        {{ $ciclo->inicio_anio }} - {{ $ciclo->fin_anio }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error name="ciclo_escolar_id" />
                        </flux:field>
                    </div>

                    @if ($mostrarCatalogo)
                        <div
                            class="rounded-3xl border border-dashed border-cyan-300 bg-cyan-50/60 p-4 dark:border-cyan-900/60 dark:bg-cyan-950/20">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-black text-slate-800 dark:text-white">Nuevo taller</h3>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        El taller queda disponible para futuras sesiones y ciclos escolares.
                                    </p>
                                </div>
                                <button type="button" wire:click="$set('mostrarCatalogo', false)"
                                    class="text-slate-400 hover:text-slate-700 dark:hover:text-white">×</button>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <flux:field>
                                    <flux:label>Nombre</flux:label>
                                    <flux:input wire:model="nuevo_taller_nombre" placeholder="Ej. Robótica" />
                                    <flux:error name="nuevo_taller_nombre" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Clave opcional</flux:label>
                                    <flux:input wire:model="nuevo_taller_clave" placeholder="Ej. TAL-ROB" />
                                    <flux:error name="nuevo_taller_clave" />
                                </flux:field>
                            </div>

                            <div class="mt-4">
                                <flux:field>
                                    <flux:label>Descripción opcional</flux:label>
                                    <flux:textarea wire:model="nuevo_taller_descripcion" rows="2"
                                        placeholder="Descripción breve del taller..." />
                                    <flux:error name="nuevo_taller_descripcion" />
                                </flux:field>
                            </div>

                            <div class="mt-4 flex justify-end">
                                <button type="button" wire:click="crearTaller"
                                    class="inline-flex items-center gap-2 rounded-2xl bg-cyan-600 px-4 py-2 text-sm font-black text-white shadow-lg shadow-cyan-500/20 transition hover:bg-cyan-700">
                                    <flux:icon.plus class="h-4 w-4" />
                                    Guardar taller
                                </button>
                            </div>
                        </div>
                    @endif

                    <flux:field>
                        <flux:label>Profesor responsable</flux:label>
                        <flux:select wire:model.live="profesor_id">
                            <option value="">Selecciona un profesor</option>
                            @foreach ($this->profesores as $profesor)
                                <option value="{{ $profesor->id }}">{{ $this->nombreProfesor($profesor) }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="profesor_id" />
                    </flux:field>

                    <div>
                        <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-slate-800 dark:text-white">Grupos participantes</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Selecciona por lo menos dos. Puedes combinar 1.º A, 1.º B, 2.º A, 3.º A, etc.
                                </p>
                            </div>
                            <span class="text-xs font-black text-cyan-700 dark:text-cyan-300">
                                {{ count($grupos_seleccionados) }} seleccionados
                            </span>
                        </div>

                        <div
                            class="grid max-h-72 grid-cols-1 gap-2 overflow-y-auto rounded-3xl border border-slate-200 bg-slate-50/60 p-3 sm:grid-cols-2 dark:border-neutral-800 dark:bg-neutral-900/50">
                            @foreach ($this->grupos as $grupo)
                                <label wire:key="taller-grupo-{{ $grupo->id }}"
                                    class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-white p-3 transition hover:border-cyan-300 hover:bg-cyan-50/50 dark:border-neutral-700 dark:bg-neutral-950 dark:hover:border-cyan-900 dark:hover:bg-cyan-950/20">
                                    <input type="checkbox" wire:model.live="grupos_seleccionados"
                                        value="{{ $grupo->id }}"
                                        class="mt-1 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-black text-slate-800 dark:text-white">
                                            {{ $this->nombreGrupo($grupo) }}
                                        </span>
                                        <span class="mt-0.5 block text-[11px] text-slate-500 dark:text-slate-400">
                                            Generación {{ $grupo->generacion?->anio_ingreso }} -
                                            {{ $grupo->generacion?->anio_egreso }}
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        <flux:error name="grupos_seleccionados" />
                        <flux:error name="grupos_seleccionados.*" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <flux:field>
                            <flux:label>Día</flux:label>
                            <flux:select wire:model.live="dia_id">
                                <option value="">Selecciona el día</option>
                                @foreach ($this->dias as $dia)
                                    <option value="{{ $dia->id }}">{{ $dia->dia }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="dia_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Hora semanal</flux:label>
                            <flux:select wire:model.live="hora_id" :disabled="!$dia_id">
                                <option value="">Selecciona la hora</option>
                                @foreach ($this->horasDisponibles as $bloque)
                                    <option value="{{ $bloque['id'] }}">
                                        {{ \Carbon\Carbon::parse($bloque['hora_inicio'])->format('h:i A') }} -
                                        {{ \Carbon\Carbon::parse($bloque['hora_fin'])->format('h:i A') }}
                                        {{ $bloque['disponible'] ? '· Disponible' : '· ' . $bloque['conflictos'] . ' conflicto(s)' }}
                                    </option>
                                @endforeach
                            </flux:select>
                            <flux:error name="hora_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Ubicación opcional</flux:label>
                            <flux:input wire:model="ubicacion" placeholder="Ej. Aula disponible" />
                            <flux:error name="ubicacion" />
                        </flux:field>
                    </div>

                    @if ($dia_id && $profesor_id && $ciclo_escolar_id && count($grupos_seleccionados) >= 2)
                        <div
                            class="rounded-2xl border border-sky-200 bg-sky-50 px-4 py-3 text-xs leading-5 text-sky-800 dark:border-sky-900/40 dark:bg-sky-950/25 dark:text-sky-200">
                            Las horas indican la disponibilidad común de los grupos y del profesor. Una hora con
                            conflicto puede seleccionarse, pero requerirá autorización administrativa y motivo.
                        </div>
                    @endif

                    @if ($requiereAutorizacion)
                        <div
                            class="rounded-3xl border border-rose-300 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/25">
                            <div class="flex items-start gap-3">
                                <div
                                    class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-rose-600 text-white">
                                    <flux:icon.exclamation-triangle class="h-5 w-5" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-rose-900 dark:text-rose-100">
                                        El bloque tiene conflictos
                                    </h3>
                                    <p class="mt-1 text-xs leading-5 text-rose-700 dark:text-rose-300">
                                        El guardado se bloqueó. Revisa los registros y solo autoriza cuando exista una
                                        razón administrativa válida.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                @foreach ($conflictos as $conflicto)
                                    <div
                                        class="rounded-2xl border border-rose-200 bg-white px-4 py-3 text-xs dark:border-rose-900/40 dark:bg-neutral-950">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="font-black text-slate-800 dark:text-white">{{ $conflicto['actividad'] }}</span>
                                            <span
                                                class="rounded-full bg-rose-100 px-2 py-0.5 font-black text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                {{ $conflicto['motivos_texto'] }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-slate-500 dark:text-slate-400">
                                            {{ $conflicto['profesor'] }} · {{ $conflicto['grupos'] }} ·
                                            {{ $conflicto['dia'] }} {{ $conflicto['hora'] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4 space-y-4">
                                <label
                                    class="flex items-start gap-3 text-sm font-bold text-rose-900 dark:text-rose-100">
                                    <input type="checkbox" wire:model="autorizar_conflicto"
                                        class="mt-1 rounded border-rose-300 text-rose-600 focus:ring-rose-500">
                                    <span>Autorizo explícitamente guardar esta sesión aun con los conflictos
                                        mostrados.</span>
                                </label>
                                <flux:error name="autorizar_conflicto" />

                                <flux:field>
                                    <flux:label>Motivo de autorización</flux:label>
                                    <flux:textarea wire:model="motivo_conflicto" rows="3"
                                        placeholder="Explica por qué se autoriza el traslape..." />
                                    <flux:error name="motivo_conflicto" />
                                </flux:field>

                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" wire:click="cancelarAutorizacion"
                                        class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300">
                                        Corregir horario
                                    </button>
                                    <button type="button" wire:click="guardarSesion(true)"
                                        class="rounded-2xl bg-rose-600 px-4 py-2 text-sm font-black text-white shadow-lg shadow-rose-500/20 hover:bg-rose-700">
                                        Guardar con autorización
                                    </button>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-wrap justify-end gap-2">
                            @if ($editandoSesionId)
                                <button type="button" wire:click="limpiarFormulario"
                                    class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 dark:border-neutral-700 dark:bg-neutral-900 dark:text-slate-300">
                                    Cancelar edición
                                </button>
                            @endif

                            <button type="button" wire:click="guardarSesion"
                                class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-cyan-500 via-sky-500 to-indigo-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-cyan-500/20 transition hover:-translate-y-0.5 hover:shadow-xl">
                                <flux:icon.check class="h-4 w-4" />
                                {{ $editandoSesionId ? 'Actualizar taller conjunto' : 'Asignar taller conjunto' }}
                            </button>
                        </div>
                    @endif
                </div>

                <aside class="space-y-4">
                    <div
                        class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
                        <h3 class="text-sm font-black text-slate-800 dark:text-white">Sesiones registradas</h3>
                        <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                            Al editar una sesión se actualizan al mismo tiempo todos sus grupos y reportes.
                        </p>
                    </div>

                    <div class="max-h-[860px] space-y-3 overflow-y-auto pr-1">
                        @forelse ($this->sesiones as $sesion)
                            <article wire:key="sesion-taller-{{ $sesion->id }}"
                                class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="truncate text-sm font-black text-slate-900 dark:text-white">
                                                {{ $sesion->taller?->nombre ?? 'Taller' }}
                                            </h4>
                                            @if ($sesion->conflicto_forzado)
                                                <span
                                                    class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-black text-rose-700 dark:bg-rose-950 dark:text-rose-300">
                                                    Autorizado con conflicto
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1 text-xs font-semibold text-cyan-700 dark:text-cyan-300">
                                            {{ $this->nombreProfesor($sesion->profesor) }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 gap-1">
                                        <button type="button" wire:click="editar({{ $sesion->id }})"
                                            class="grid h-8 w-8 place-items-center rounded-xl border border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-300">
                                            <flux:icon.pencil-square class="h-4 w-4" />
                                        </button>
                                        <button type="button" wire:click="eliminar({{ $sesion->id }})"
                                            wire:confirm="¿Eliminar esta sesión compartida de todos los grupos?"
                                            class="grid h-8 w-8 place-items-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">
                                            <flux:icon.trash class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 grid grid-cols-2 gap-2 text-[11px]">
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-neutral-950">
                                        <span class="block text-slate-400">Ciclo</span>
                                        <strong class="text-slate-700 dark:text-slate-200">
                                            {{ $sesion->cicloEscolar?->inicio_anio }}-{{ $sesion->cicloEscolar?->fin_anio }}
                                        </strong>
                                    </div>
                                    <div class="rounded-xl bg-slate-50 px-3 py-2 dark:bg-neutral-950">
                                        <span class="block text-slate-400">Bloque</span>
                                        <strong class="text-slate-700 dark:text-slate-200">
                                            {{ $sesion->dia?->dia }} ·
                                            {{ \Carbon\Carbon::parse($sesion->hora?->hora_inicio)->format('h:i A') }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    @foreach ($sesion->grupos->sortBy(fn($g) => $g->grado?->orden) as $grupo)
                                        <span
                                            class="rounded-full border border-cyan-200 bg-cyan-50 px-2.5 py-1 text-[10px] font-black text-cyan-700 dark:border-cyan-900/50 dark:bg-cyan-950/30 dark:text-cyan-300">
                                            {{ $this->nombreGrupo($grupo) }}
                                        </span>
                                    @endforeach
                                </div>

                                <div
                                    class="mt-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-bold text-emerald-700 dark:border-emerald-900/40 dark:bg-emerald-950/25 dark:text-emerald-300">
                                    Carga docente: 1 hora semanal total
                                </div>
                            </article>
                        @empty
                            <div
                                class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-neutral-700 dark:bg-neutral-900/50">
                                <p class="text-sm font-black text-slate-700 dark:text-slate-200">Sin talleres conjuntos
                                </p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Registra el primer taller y selecciona los grupos participantes.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </aside>
            </div>
        </section>
    @endif
</div>
