<x-layouts.app :title="__('Responsables')">
    <div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
        @if (auth()->user()?->canAccess('alumnos.crear'))
            <livewire:tutor.crear-tutor />
        @endif

        <livewire:tutor.mostrar-tutor />
        <livewire:tutor.expediente-tutor />
        <livewire:tutor.organizador-paginas-tutor />

        @if (auth()->user()?->canAccess('alumnos.editar'))
            <livewire:tutor.gestion-alumnos-tutor />
        @endif
    </div>
</x-layouts.app>
