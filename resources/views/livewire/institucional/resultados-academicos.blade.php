@php
    $tabs = $slug_nivel === 'preescolar'
        ? ['lugares' => 'Lugares y reconocimientos']
        : [
            'concentrado' => 'Concentrados y promedios',
            'materias' => 'Promedios por materia',
        ];

    if ($slug_nivel === 'primaria') {
        $tabs['oficial'] = 'Promedios oficiales';
    }
@endphp

<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Académica"
        title="Resultados académicos"
        description="Separa la captura de calificaciones de su análisis: concentrados, promedios, resultados por materia y documentos de reconocimiento." />

    <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />
    <x-institucional.pestanas :tabs="$tabs" :active="$tab" />

    @switch($tab)
        @case('materias')
            <livewire:accion.generales.promedios-materias
                :slug_nivel="$slug_nivel"
                :key="'resultados-materias-' . $slug_nivel" />
        @break

        @case('oficial')
            <livewire:accion.generales.promedios-oficiales-primaria
                :slug_nivel="$slug_nivel"
                :key="'resultados-oficiales-' . $slug_nivel" />
        @break

        @case('lugares')
            <livewire:accion.generales.lugares-preescolar
                :slug_nivel="$slug_nivel"
                :key="'resultados-lugares-' . $slug_nivel" />
        @break

        @default
            <livewire:accion.generales.promedios-generales
                :slug_nivel="$slug_nivel"
                :key="'resultados-concentrado-' . $slug_nivel" />
    @endswitch
</div>
