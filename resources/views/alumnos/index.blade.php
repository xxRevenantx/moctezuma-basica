<x-layouts.app :title="__('Alumnos')">
    <div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:alumnos-generales />

        @if (auth()->user()?->canAccess('alumnos.consultar'))
            <livewire:estadistica-alumnos-grupos />
        @endif
    </div>
</x-layouts.app>
