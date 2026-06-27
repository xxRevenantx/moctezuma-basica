<section class="rounded-3xl border border-amber-200 bg-amber-50/50 p-5 dark:border-amber-900/50 dark:bg-amber-950/15">
    <div class="mb-4">
        <h4 class="font-black text-amber-900 dark:text-amber-100">
            4. Calificaciones externas o equivalencias
        </h4>

        <p class="mt-1 text-sm text-amber-700 dark:text-amber-200">
            Se guardan en la tabla normal de calificaciones, identificadas como fuente externa y vinculadas al documento de respaldo.
        </p>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        <flux:select wire:model="periodo_externo_id" label="Periodo">
            <option value="">Selecciona</option>
            @foreach ($this->periodosExternos as $item)
                <option value="{{ $item->id }}">{{ $this->etiquetaPeriodo($item) }}</option>
            @endforeach
        </flux:select>

        <flux:select wire:model="asignacion_externa_id" label="Materia equivalente">
            <option value="">Selecciona</option>
            @foreach ($this->asignacionesExternas as $item)
                <option value="{{ $item->id }}">
                    {{ $item->materia?->nombre ?? 'Materia ' . $item->id }}
                </option>
            @endforeach
        </flux:select>

        <flux:input
            wire:model="calificacion_externa"
            label="Calificación"
            placeholder="5-10, AC o NP"
        />

        <flux:input
            wire:model="observacion_calificacion"
            label="Observación"
        />

        <div class="flex items-end">
            <flux:button
                type="button"
                wire:click="guardarCalificacionExterna"
                variant="primary"
                class="w-full"
                icon="check"
            >
                Guardar equivalencia
            </flux:button>
        </div>
    </div>
</section>
