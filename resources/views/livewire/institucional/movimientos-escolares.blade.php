<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Gestión escolar"
        title="Movimientos escolares"
        description="Reúne bajas, traslados, suspensiones, alumnos no vigentes y reingresos para consultar la trayectoria administrativa sin cambiar de módulo." />

    <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />
    <x-institucional.pestanas
        :tabs="['bajas' => 'Bajas y movimientos', 'no-vigentes' => 'Alumnos no vigentes', 'reingreso' => 'Reingresos']"
        :active="$tab" />

    @switch($tab)
        @case('no-vigentes')
            <livewire:accion.alumnos-no-vigentes
                :slug_nivel="$slug_nivel"
                :mostrar-selector-niveles="false"
                :key="'movimientos-no-vigentes-' . $slug_nivel" />
        @break

        @case('reingreso')
            <livewire:accion.reingreso-alumno
                :slug_nivel="$slug_nivel"
                :key="'movimientos-reingreso-' . $slug_nivel" />
        @break

        @default
            <livewire:accion.baja
                :slug_nivel="$slug_nivel"
                :mostrar-selector-niveles="false"
                :key="'movimientos-bajas-' . $slug_nivel" />
    @endswitch
</div>
