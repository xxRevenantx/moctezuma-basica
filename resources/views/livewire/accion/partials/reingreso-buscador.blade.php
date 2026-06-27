<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
    <flux:input
        wire:model.live.debounce.350ms="search"
        icon="magnifying-glass"
        label="Buscar exalumno o alumno no activo"
        placeholder="Nombre, matrícula o CURP"
    />

    <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($this->resultados as $row)
            <button
                type="button"
                wire:key="reingreso-resultado-{{ $row->id }}"
                wire:click="seleccionarAlumno({{ $row->id }})"
                class="rounded-2xl border border-slate-200 p-4 text-left transition hover:border-violet-400 hover:bg-violet-50 dark:border-neutral-700 dark:hover:bg-violet-950/20"
            >
                <p class="font-black text-slate-900 dark:text-white">
                    {{ $this->nombreAlumno($row) }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    {{ $row->matricula }} · {{ $row->curp }}
                </p>

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-bold text-slate-700 dark:bg-neutral-800 dark:text-slate-200">
                        {{ $this->ultimaTrayectoriaDe($row)?->etiqueta_estatus ?? 'Sin estado' }}
                    </span>

                    <span class="rounded-full bg-sky-100 px-2.5 py-1 font-bold text-sky-700 dark:bg-sky-900/30 dark:text-sky-200">
                        {{ $this->ultimaTrayectoriaDe($row)?->nivel?->nombre ?? 'Sin nivel' }}
                    </span>

                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 font-bold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">
                        {{ $this->textoGeneracion($this->ultimaTrayectoriaDe($row)?->generacion) }}
                    </span>
                </div>
            </button>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500 dark:border-neutral-700 md:col-span-2 xl:col-span-3">
                Escribe al menos dos caracteres. Solo se muestran alumnos con egreso, traslado, baja o suspensión.
            </div>
        @endforelse
    </div>
</section>
