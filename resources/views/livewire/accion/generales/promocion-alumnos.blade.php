<div class="space-y-5">
    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900/50 dark:bg-sky-950/20 dark:text-sky-100">
        La promoción conserva la generación del alumno y cambia su ciclo escolar, grado o semestre y grupo. Los grupos tienen <b>cupo ilimitado</b> y solo se muestran cuando pertenecen exactamente al ciclo seleccionado.
    </div>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-neutral-700 dark:bg-neutral-900">
        <div class="mb-4">
            <h2 class="font-black text-slate-900 dark:text-white">Ubicación de origen</h2>
            <p class="text-sm text-slate-500">Selecciona el ciclo y la ubicación actual que deseas promover.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <flux:select wire:model.live="ciclo_origen_id" label="Ciclo escolar origen">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($ciclosEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="generacion_id" label="Generación" :disabled="!$ciclo_origen_id">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($generaciones as $g)
                    <flux:select.option value="{{ $g->id }}">
                        {{ $g->etiqueta }}{{ $g->status ? '' : ' · Inactiva' }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="grado_origen_id" label="Grado actual" :disabled="!$generacion_id">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($grados as $grado)
                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->esBachillerato())
                <flux:select wire:model.live="semestre_origen_id" label="Semestre actual" :disabled="!$grado_origen_id">
                    <flux:select.option value="">Selecciona</flux:select.option>
                    @foreach ($semestresOrigen as $s)
                        <flux:select.option value="{{ $s->id }}">Semestre {{ $s->numero }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model.live="grupo_origen_id" label="Grupo actual" :disabled="$gruposOrigen->isEmpty()">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($gruposOrigen as $grupo)
                    <flux:select.option value="{{ $grupo->id }}">
                        {{ $grupo->asignacionGrupo?->nombre ?? $grupo->id }} · {{ number_format((int) $grupo->alumnos_activos_count) }} alumnos · cupo ilimitado
                    </flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </section>

    <section class="rounded-3xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm dark:border-emerald-900/50 dark:bg-emerald-950/10">
        <div class="mb-4">
            <h2 class="font-black text-emerald-950 dark:text-emerald-100">Ubicación de destino</h2>
            <p class="text-sm text-emerald-800/80 dark:text-emerald-200/70">
                El sistema propone el ciclo y el siguiente grado o semestre. Puedes confirmar el grupo compatible disponible.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <flux:select wire:model.live="ciclo_destino_id" label="Ciclo escolar destino">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($ciclosEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}{{ $ciclo->es_actual ? ' · Actual' : '' }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="grado_destino_id" label="Grado destino" :disabled="!$ciclo_destino_id">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($grados as $grado)
                    <flux:select.option value="{{ $grado->id }}">{{ $grado->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->esBachillerato())
                <flux:select wire:model.live="semestre_destino_id" label="Semestre destino" :disabled="$semestresDestino->isEmpty()">
                    <flux:select.option value="">Selecciona</flux:select.option>
                    @foreach ($semestresDestino as $s)
                        <flux:select.option value="{{ $s->id }}">Semestre {{ $s->numero }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model="grupo_destino_id" label="Grupo destino" :disabled="$gruposDestino->isEmpty()">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($gruposDestino as $grupo)
                    <flux:select.option value="{{ $grupo->id }}">
                        {{ $grupo->asignacionGrupo?->nombre ?? $grupo->id }} · {{ number_format((int) $grupo->alumnos_activos_count) }} alumnos · cupo ilimitado
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="motivo" label="Motivo obligatorio" placeholder="Promoción de fin de periodo" />
        </div>

        @if ($ciclo_destino_id && $grado_destino_id && $gruposDestino->isEmpty())
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/30 dark:text-amber-200">
                No existe un grupo activo compatible en el ciclo destino. Crea primero el grupo con la misma generación y la sección correspondiente.
            </div>
        @endif
    </section>

    @foreach (['ciclo_origen_id', 'ciclo_destino_id', 'generacion_id', 'grupo_origen_id', 'grupo_destino_id', 'seleccionados'] as $campo)
        @error($campo)
            <p class="text-sm font-bold text-rose-600">{{ $message }}</p>
        @enderror
    @endforeach

    <div class="overflow-x-auto rounded-2xl border dark:border-neutral-800">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 dark:bg-neutral-950">
                <tr>
                    <th class="p-3"><input type="checkbox" wire:model.live="seleccionarPagina"></th>
                    <th class="p-3 text-left">Alumno</th>
                    <th class="p-3 text-left">Estatus</th>
                    <th class="p-3 text-left">Ubicación</th>
                </tr>
            </thead>
            <tbody class="divide-y dark:divide-neutral-800">
                @forelse ($alumnos as $a)
                    <tr>
                        <td class="p-3"><input type="checkbox" wire:model.live="seleccionados" value="{{ $a->id }}"></td>
                        <td class="p-3">
                            <b>{{ trim($a->apellido_paterno . ' ' . $a->apellido_materno . ' ' . $a->nombre) }}</b>
                            <div class="text-xs text-slate-500">{{ $a->matricula }}</div>
                        </td>
                        <td class="p-3">{{ ucfirst(str_replace('_', ' ', $a->estatus ?: 'activo')) }}</td>
                        <td class="p-3">
                            {{ $a->grado?->nombre }}
                            @if ($a->semestre) · Sem. {{ $a->semestre->numero }} @endif
                            · {{ $a->grupo?->asignacionGrupo?->nombre ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-slate-500">Selecciona el ciclo, la generación y la ubicación de origen.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $alumnos->links() }}</div>

    <div class="flex flex-wrap justify-end gap-3">
        <flux:button wire:click="marcarNoPromovidos" variant="ghost" spinner="marcarNoPromovidos">
            Marcar no promovidos
        </flux:button>
        <flux:button wire:click="promoverSeleccionados" variant="primary" spinner="promoverSeleccionados">
            Promover seleccionados
        </flux:button>
    </div>
</div>
