<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        <livewire:header />

        {{-- El buscador global debe ser un componente Livewire de primer nivel.
             No debe anidarse dentro de <livewire:header />, porque su modal utiliza
             acciones wire propias como seleccionarActivo, siguiente y anterior. --}}
        <livewire:busqueda.buscador-global />

        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
