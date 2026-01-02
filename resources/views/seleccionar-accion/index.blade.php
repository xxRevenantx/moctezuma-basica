<x-layouts.app :title="__('Seleccionar acción')">
    <livewire:accion.seleccionar-accion :acciones="$acciones" :slug_nivel="$slug_nivel ?? null" :slug_grado="$slug_grado ?? null" />
</x-layouts.app>
