<div class="space-y-5">
    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="bg-gradient-to-r from-emerald-700 via-teal-700 to-sky-700 p-5 text-white sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-2xl font-black">Promoción y continuidad de alumnos</h2>
                    <p class="mt-1 text-sm text-emerald-100">Crea la trayectoria del nuevo ciclo sin modificar el grado, grupo, generación o matrícula histórica del origen.</p>
                </div>
                <button type="button" wire:click="limpiarFormulario"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-bold ring-1 ring-white/25 hover:bg-white/25">
                    <flux:icon.arrow-path class="h-4 w-4" /> Limpiar
                </button>
            </div>
        </div>
    </section>

    <div class="grid gap-5 xl:grid-cols-2">
        {{-- ORIGEN --}}
        <section class="rounded-3xl border border-sky-200 bg-sky-50/40 p-5 shadow-sm dark:border-sky-900/50 dark:bg-sky-950/10">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-600 text-white"><flux:icon.arrow-up-tray class="h-5 w-5" /></span>
                <div>
                    <h3 class="font-black text-sky-950 dark:text-sky-100">Trayectoria de origen</h3>
                    <p class="text-sm text-sky-800/70 dark:text-sky-200/70">Ciclo, corte y grupo donde se encuentran actualmente.</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Ciclo escolar</span>
                    <select wire:model.live="ciclo_escolar_origen_id" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($cicloEscolares as $item)
                            <option value="{{ $item->id }}">{{ $item->nombre }}{{ $item->es_actual ? ' · Actual' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Corte</span>
                    <select wire:model.live="ciclo_id_origen" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($ciclos as $item)<option value="{{ $item->id }}">{{ $item->ciclo }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Nivel</span>
                    <select wire:model.live="nivel_origen_id" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($niveles as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Grado</span>
                    <select wire:model.live="grado_origen_id" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($this->gradosOrigen as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Generación</span>
                    <select wire:model.live="generacion_origen_id" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($this->generacionesOrigen as $item)<option value="{{ $item->id }}">{{ $item->anio_ingreso }}-{{ $item->anio_egreso }}</option>@endforeach
                    </select>
                </label>
                @if ($this->esBachilleratoOrigen)
                    <label class="space-y-1.5">
                        <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Semestre</span>
                        <select wire:model.live="semestre_origen_id" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                            <option value="">Selecciona</option>
                            @foreach ($this->semestresOrigen as $item)<option value="{{ $item->id }}">Semestre {{ $item->numero }}</option>@endforeach
                        </select>
                    </label>
                @endif
                <label class="space-y-1.5 {{ $this->esBachilleratoOrigen ? '' : 'sm:col-span-2' }}">
                    <span class="text-xs font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">Grupo</span>
                    <select wire:model.live="grupo_origen_id" class="w-full rounded-xl border-sky-300 bg-white text-sm dark:border-sky-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($this->gruposOrigen as $item)<option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}</option>@endforeach
                    </select>
                </label>
            </div>
        </section>

        {{-- DESTINO --}}
        <section class="rounded-3xl border border-emerald-200 bg-emerald-50/40 p-5 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/10">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-600 text-white"><flux:icon.arrow-down-tray class="h-5 w-5" /></span>
                <div>
                    <h3 class="font-black text-emerald-950 dark:text-emerald-100">Nueva trayectoria de destino</h3>
                    <p class="text-sm text-emerald-800/70 dark:text-emerald-200/70">Al cambiar de nivel se asignará una matrícula nueva y se conservará la anterior.</p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Ciclo escolar</span>
                    <select wire:model.live="ciclo_escolar_destino_id" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($cicloEscolares as $item)<option value="{{ $item->id }}">{{ $item->nombre }}{{ $item->es_actual ? ' · Actual' : '' }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Corte</span>
                    <select wire:model.live="ciclo_id_destino" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($ciclos as $item)<option value="{{ $item->id }}">{{ $item->ciclo }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Nivel</span>
                    <select wire:model.live="nivel_destino_id" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($niveles as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Grado</span>
                    <select wire:model.live="grado_destino_id" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($this->gradosDestino as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Generación</span>
                    <select wire:model.live="generacion_destino_id" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($this->generacionesDestino as $item)<option value="{{ $item->id }}">{{ $item->anio_ingreso }}-{{ $item->anio_egreso }}</option>@endforeach
                    </select>
                </label>
                @if ($this->esBachilleratoDestino)
                    <label class="space-y-1.5">
                        <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Semestre</span>
                        <select wire:model.live="semestre_destino_id" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                            <option value="">Selecciona</option>
                            @foreach ($this->semestresDestino as $item)<option value="{{ $item->id }}">Semestre {{ $item->numero }}</option>@endforeach
                        </select>
                    </label>
                @endif
                <label class="space-y-1.5 {{ $this->esBachilleratoDestino ? '' : 'sm:col-span-2' }}">
                    <span class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Grupo</span>
                    <select wire:model.live="grupo_destino_id" class="w-full rounded-xl border-emerald-300 bg-white text-sm dark:border-emerald-800 dark:bg-neutral-900">
                        <option value="">Selecciona</option>
                        @foreach ($this->gruposDestino as $item)<option value="{{ $item->id }}">{{ $this->textoGrupo($item) }}</option>@endforeach
                    </select>
                </label>
            </div>
        </section>
    </div>

    <section class="rounded-3xl border border-amber-200 bg-amber-50/50 p-5 shadow-sm dark:border-amber-900/50 dark:bg-amber-950/15">
        <div class="grid gap-3 md:grid-cols-3">
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Resultado</span>
                <select wire:model.live="resultado_promocion" class="w-full rounded-xl border-amber-300 bg-white text-sm dark:border-amber-800 dark:bg-neutral-900">
                    <option value="promovido">Promovido</option>
                    <option value="no_promovido">No promovido</option>
                </select>
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Fecha</span>
                <input wire:model="fecha_promocion" type="date" class="w-full rounded-xl border-amber-300 bg-white text-sm dark:border-amber-800 dark:bg-neutral-900" />
            </label>
            <label class="space-y-1.5">
                <span class="text-xs font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">Buscar</span>
                <input wire:model.live.debounce.350ms="search" type="search" placeholder="Nombre, matrícula, folio o CURP" class="w-full rounded-xl border-amber-300 bg-white text-sm dark:border-amber-800 dark:bg-neutral-900" />
            </label>
        </div>
        <label class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-amber-900 dark:text-amber-100">
            <input type="checkbox" wire:model.live="ocultarPromovidos" class="rounded border-amber-300 text-amber-600" /> Ocultar alumnos ya procesados
        </label>
    </section>

    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="grid grid-cols-2 gap-px bg-slate-200 sm:grid-cols-4 dark:bg-neutral-700">
            @foreach ([['Disponibles', $this->resumenOrigen['total']], ['Hombres', $this->resumenOrigen['hombres']], ['Mujeres', $this->resumenOrigen['mujeres']], ['Seleccionados', $this->totalSeleccionados]] as [$label, $value])
                <div class="bg-white p-4 dark:bg-neutral-900"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $value }}</p></div>
            @endforeach
        </div>

        <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-neutral-700">
            <label class="inline-flex items-center gap-2 text-sm font-black text-slate-700 dark:text-slate-200">
                <input type="checkbox" wire:model.live="seleccionarTodos" class="rounded border-slate-300 text-emerald-600" /> Seleccionar todos los visibles
            </label>
            <p class="text-xs text-slate-500">Los alumnos con trayectoria vigente en el destino se omiten automáticamente.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-[1050px] w-full text-left text-sm">
                <thead class="bg-slate-900 text-xs uppercase text-white">
                    <tr>
                        <th class="px-4 py-3">Sel.</th>
                        <th class="px-4 py-3">Alumno</th>
                        <th class="px-4 py-3">Matrícula del nivel</th>
                        <th class="px-4 py-3">CURP</th>
                        <th class="px-4 py-3">Generación</th>
                        <th class="px-4 py-3">Ubicación origen</th>
                        <th class="px-4 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-neutral-800">
                    @forelse ($this->alumnosDisponibles as $trayectoria)
                        @php
                            $alumno = $trayectoria->inscripcion;
                            $matricula = $alumno?->matriculasAlumno?->firstWhere('nivel_id', $trayectoria->nivel_id)?->matricula ?: $alumno?->matricula;
                        @endphp
                        <tr wire:key="promocion-{{ $trayectoria->id }}" class="hover:bg-emerald-50/40 dark:hover:bg-emerald-950/10">
                            <td class="px-4 py-4"><input type="checkbox" wire:model.live="seleccionados" value="{{ $trayectoria->id }}" class="rounded border-slate-300 text-emerald-600" /></td>
                            <td class="px-4 py-4 font-bold text-slate-900 dark:text-white">{{ $this->nombreAlumno($alumno) }}</td>
                            <td class="px-4 py-4 font-black text-slate-700 dark:text-slate-200">{{ $matricula ?: '—' }}</td>
                            <td class="px-4 py-4 text-slate-500">{{ $alumno?->curp ?: '—' }}</td>
                            <td class="px-4 py-4">{{ $trayectoria->generacion ? $trayectoria->generacion->anio_ingreso . '-' . $trayectoria->generacion->anio_egreso : '—' }}</td>
                            <td class="px-4 py-4">{{ $trayectoria->grado?->nombre ?? '—' }} · {{ $this->textoGrupo($trayectoria->grupo) }} @if($trayectoria->semestre)<span class="text-xs text-slate-500">/ Sem. {{ $trayectoria->semestre->numero }}</span>@endif</td>
                            <td class="px-4 py-4"><span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">Disponible</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-14 text-center text-slate-500">Completa los filtros de origen o no hay alumnos disponibles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($errors->any())
        <section class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-200">
            <ul class="space-y-1">@foreach ($errors->all() as $error)<li>• {{ $error }}</li>@endforeach</ul>
        </section>
    @endif

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <label class="inline-flex items-start gap-3 rounded-2xl bg-amber-50 p-4 text-sm font-semibold text-amber-900 dark:bg-amber-950/20 dark:text-amber-100">
                <input type="checkbox" wire:model.live="confirmarPromocion" class="mt-0.5 rounded border-amber-300 text-amber-600" />
                <span>Confirmo que se crearán nuevas trayectorias y, cuando cambie el nivel, nuevas matrículas. Los registros anteriores permanecerán intactos.</span>
            </label>
            <button type="button" wire:click="aplicarPromocion" wire:loading.attr="disabled" @disabled($this->totalSeleccionados === 0 || !$confirmarPromocion)
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-600 to-sky-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="aplicarPromocion" class="inline-flex items-center gap-2"><flux:icon.check-circle class="h-5 w-5" /> Aplicar proceso</span>
                <span wire:loading wire:target="aplicarPromocion">Procesando…</span>
            </button>
        </div>
    </section>
</div>
