<x-layouts.app :title="__('Dashboard')">
    @if (auth()->user()?->isProfessor())
        <livewire:profesor.portal-docente />
    @else
        <livewire:dashboard />
    @endif
</x-layouts.app>
