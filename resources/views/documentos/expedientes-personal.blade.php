<x-layouts.app :title="__('Expedientes del personal')">
    <div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:documentacion.expedientes-personal :persona-id="$personaId" />
    </div>
</x-layouts.app>
