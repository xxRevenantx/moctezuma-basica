<x-layouts.app :title="__('Editar matrícula')">
    <div class="flex  w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:accion.editar-matricula :slug_nivel="$slug_nivel" :inscripcion="$inscripcion" />
    </div>
</x-layouts.app>
