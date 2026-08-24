<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Gestión escolar"
        title="Cierre y continuidad escolar"
        description="Centraliza promoción, continuidad, repetición, traslado, baja y egreso sin entrar nivel por nivel desde Generales." />

    <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />

    @if ($slug_nivel !== '')
        <livewire:accion.generales.cierre-nivel-continuidad
            :slug_nivel="$slug_nivel"
            :key="'cierre-continuidad-global-' . $slug_nivel" />
    @endif
</div>
