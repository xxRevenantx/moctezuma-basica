<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900 dark:text-white">Crear generación</h1>
        <p class="mt-1 text-sm text-slate-500">La generación será la referencia permanente de sus alumnos.</p>
    </div>

    <form wire:submit="guardarGeneracion" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <flux:select wire:model="nivel_id" label="Nivel educativo">
                <flux:select.option value="">Selecciona</flux:select.option>
                @foreach($niveles as $nivel)<flux:select.option value="{{ $nivel->id }}">{{ $nivel->nombre }}</flux:select.option>@endforeach
            </flux:select>
            <flux:input wire:model.live="anio_ingreso" type="number" label="Año de ingreso" placeholder="2022" />
            <flux:input wire:model.live="anio_egreso" type="number" label="Año de egreso" placeholder="2025" />
            <flux:input wire:model="nombre" label="Nombre" placeholder="2022-2025" />
            <flux:select wire:model="ciclo_escolar_inicio_id" label="Ciclo inicial">
                <flux:select.option value="">Sin asignar</flux:select.option>
                @foreach($ciclosEscolares as $c)<flux:select.option value="{{ $c->id }}">{{ $c->inicio_anio }}-{{ $c->fin_anio }}</flux:select.option>@endforeach
            </flux:select>
            <flux:select wire:model="ciclo_escolar_fin_id" label="Ciclo final">
                <flux:select.option value="">Sin asignar</flux:select.option>
                @foreach($ciclosEscolares as $c)<flux:select.option value="{{ $c->id }}">{{ $c->inicio_anio }}-{{ $c->fin_anio }}</flux:select.option>@endforeach
            </flux:select>
            <flux:input wire:model="fecha_inicio" type="date" label="Fecha de inicio" />
            <flux:input wire:model="fecha_termino" type="date" label="Fecha de término" />
        </div>
        <div class="mt-5 flex justify-end"><flux:button type="submit" variant="primary" spinner="guardarGeneracion">Guardar generación</flux:button></div>
    </form>
</div>
