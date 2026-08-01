<x-layouts.app :title="__('Responsables')">
    <div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
        @if (auth()->user()?->canAccess('alumnos.crear'))
            <livewire:tutor.crear-tutor />
        @endif

        <livewire:tutor.mostrar-tutor />
    </div>
</x-layouts.app>
