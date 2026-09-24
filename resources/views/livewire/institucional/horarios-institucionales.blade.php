@php
    $tabs = [
        'grupo' => 'Horarios por grupo',
        'generales' => 'Horarios generales',
        'vacios' => 'Horarios vacíos',
        'docentes' => 'Horarios docentes',
        'planificador' => 'Planificador',
    ];

    if ($slug_nivel === 'secundaria') {
        $tabs['carga_docente'] = 'Horarios y carga docente';
        $tabs['talleres'] = 'Talleres conjuntos';
    }
@endphp

<div class="space-y-6">
    <x-institucional.encabezado-modulo
        eyebrow="Académica"
        title="Horarios institucionales"
        description="Concentra captura por grupo, horarios generales, formatos vacíos, consulta docente, carga docente, planificador y talleres conjuntos." />

    <x-institucional.selector-nivel :niveles="$niveles" :slug-nivel="$slug_nivel" />
    <x-institucional.pestanas :tabs="$tabs" :active="$tab" />

    @switch($tab)
        @case('generales')
            <livewire:accion.generales.horarios-generales
                :slug_nivel="$slug_nivel"
                :key="'horarios-generales-global-' . $slug_nivel" />
        @break

        @case('vacios')
            <livewire:accion.generales.horarios-vacios
                :slug_nivel="$slug_nivel"
                :key="'horarios-vacios-global-' . $slug_nivel" />
        @break

        @case('docentes')
            <livewire:profesor.horario-profesor
                :nivel-id="(int) ($this->nivelActual?->id ?? 0)"
                :key="'horarios-docentes-global-' . $slug_nivel" />
        @break

        @case('carga_docente')
            @if ($slug_nivel === 'secundaria')
                <livewire:accion.generales.horarios-carga-docente
                    :slug_nivel="$slug_nivel"
                    :key="'horarios-carga-docente-' . $slug_nivel" />
            @endif
        @break

        @case('planificador')
            <livewire:academico.planificador-horarios
                :slug-nivel="$slug_nivel"
                :key="'planificador-global-' . $slug_nivel" />
        @break

        @case('talleres')
            @if ($slug_nivel === 'secundaria')
                <livewire:accion.taller-conjunto
                    :slug_nivel="$slug_nivel"
                    :modo-pagina="true"
                    :key="'talleres-global-' . $slug_nivel" />
            @endif
        @break

        @default
            <livewire:accion.horario
                :slug_nivel="$slug_nivel"
                :mostrar-selector-niveles="false"
                :mostrar-planificador="false"
                :mostrar-talleres="false"
                :key="'horario-grupo-global-' . $slug_nivel" />
    @endswitch
</div>
