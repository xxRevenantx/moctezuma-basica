<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Académica"
        title="Carga académica"
        description="Administra materias por grupo, asignación docente, cambios masivos de profesor y cargas por ciclo escolar desde una vista global." />

    <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />

    @if ($slug_nivel !== '')
        <livewire:accion.asignacion-materia
            :slug_nivel="$slug_nivel"
            :key="'carga-academica-global-' . $slug_nivel" />
    @endif
</div>
