<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Gestión escolar"
        title="Archivo escolar"
        description="Consulta la distribución histórica y los padrones de generaciones egresadas desde un solo módulo institucional." />

    <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />
    <x-institucional.pestanas :tabs="['historial' => 'Distribución e historial', 'generaciones' => 'Generaciones egresadas']" :active="$tab" />

    @if ($tab === 'historial')
        <livewire:accion.generales.distribucion-historial
            :slug_nivel="$slug_nivel"
            :key="'archivo-historial-' . $slug_nivel" />
    @else
        <livewire:accion.generales.listas-generaciones-historicas
            :slug_nivel="$slug_nivel"
            :key="'archivo-generaciones-' . $slug_nivel" />
    @endif
</div>
