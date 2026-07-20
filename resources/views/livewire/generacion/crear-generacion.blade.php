<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Crear generación</h1>
        <p class="mt-1 text-sm text-slate-500">
            Selecciona el nivel y el año de ingreso. El sistema calcula la duración, el egreso y los ciclos relacionados.
        </p>
    </div>

    <form wire:submit="guardarGeneracion"
        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <flux:select wire:model.live="nivel_id" label="Nivel educativo">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach ($niveles as $nivel)
                    <flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="anio_ingreso" type="number" min="1900" max="2200"
                label="Año de ingreso" placeholder="2026" />

            <flux:input wire:model="anio_egreso" type="number" label="Año de egreso" readonly
                description="Calculado con la duración oficial del nivel." />

            <flux:input wire:model="nombre" label="Nombre" readonly />

            <flux:select wire:model="ciclo_escolar_inicio_id" label="Ciclo inicial" disabled>
                <flux:select.option value="">No creado todavía</flux:select.option>
                @foreach ($ciclosEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="ciclo_escolar_fin_id" label="Ciclo final" disabled>
                <flux:select.option value="">No creado todavía</flux:select.option>
                @foreach ($ciclosEscolares as $ciclo)
                    <flux:select.option value="{{ $ciclo->id }}">
                        {{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="fecha_inicio" type="date" label="Fecha de inicio" readonly />
            <flux:input wire:model="fecha_termino" type="date" label="Fecha de término" readonly />
        </div>

        @if ($detalleDuracion)
            <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-900/40 dark:bg-sky-950/30 dark:text-sky-200">
                {{ $detalleDuracion }}
                @if (!$ciclo_escolar_inicio_id || !$ciclo_escolar_fin_id)
                    <span class="font-semibold">
                        Alguno de los ciclos relacionados aún no existe; podrá asociarse después sin cambiar los años de la generación.
                    </span>
                @endif
            </div>
        @endif

        <div class="mt-5 flex justify-end">
            <flux:button type="submit" variant="primary" spinner="guardarGeneracion">
                Guardar generación
            </flux:button>
        </div>
    </form>
</div>
