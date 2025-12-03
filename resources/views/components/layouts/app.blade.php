<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        <livewire:header />
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
