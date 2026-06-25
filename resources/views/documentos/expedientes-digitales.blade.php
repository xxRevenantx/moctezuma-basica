<x-layouts.app :title="__('Expedientes digitales')">
    <div class="flex w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:documentacion.expedientes-digitales :inscripcion-id="$inscripcionId" />
    </div>
</x-layouts.app>
