<div x-data="{ show:false }" x-cloak x-show="show" @abrir-modal-editar.window="show=true" @cerrar-modal-editar.window="show=false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-slate-950/60" @click="show=false; $wire.cerrarModal()"></div>
    <form wire:submit="actualizarGeneracion" class="relative max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
        <div class="mb-5 flex items-start justify-between">
            <div><h2 class="text-xl font-bold">Editar generación</h2><p class="text-sm text-slate-500">Los alumnos continúan vinculados a esta generación.</p></div>
            <button type="button" @click="show=false; $wire.cerrarModal()" class="text-2xl">×</button>
        </div>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <flux:select wire:model="nivel_id" label="Nivel">@foreach($niveles as $n)<flux:select.option value="{{ $n->id }}">{{ $n->nombre }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="nombre" label="Nombre" />
            <flux:input wire:model="anio_ingreso" type="number" label="Año de ingreso" />
            <flux:input wire:model="anio_egreso" type="number" label="Año de egreso" />
            <flux:select wire:model="ciclo_escolar_inicio_id" label="Ciclo inicial"><flux:select.option value="">Sin asignar</flux:select.option>@foreach($ciclosEscolares as $c)<flux:select.option value="{{ $c->id }}">{{ $c->inicio_anio }}-{{ $c->fin_anio }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model="ciclo_escolar_fin_id" label="Ciclo final"><flux:select.option value="">Sin asignar</flux:select.option>@foreach($ciclosEscolares as $c)<flux:select.option value="{{ $c->id }}">{{ $c->inicio_anio }}-{{ $c->fin_anio }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="fecha_inicio" type="date" label="Fecha de inicio" />
            <flux:input wire:model="fecha_termino" type="date" label="Fecha de término" />
        </div>
        <div class="mt-6 flex justify-end gap-2"><flux:button type="button" @click="show=false; $wire.cerrarModal()">Cancelar</flux:button><flux:button type="submit" variant="primary">Guardar</flux:button></div>
    </form>
</div>
