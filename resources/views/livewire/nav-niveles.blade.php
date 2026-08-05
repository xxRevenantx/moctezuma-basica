<div>
    @php
        $nivelActivo = $niveles->contains(
            fn ($nivel) => request()->segment(2) === $nivel->slug
                || request()->route('slug_nivel') === $nivel->slug
        );
    @endphp

    <flux:sidebar.group expandable icon="layers" heading="Accesos por nivel"
        :expanded="$nivelActivo"
        x-bind:open="searching || @js($nivelActivo)"
        x-show="groupMatches($el)">
        @foreach ($niveles as $nivel)
            <flux:sidebar.item icon="rectangle-stack"
                :href="route('submodulos.accion', ['slug_nivel' => $nivel->slug, 'accion' => 'matricula'])"
                :current="request()->segment(2) === $nivel->slug || request()->route('slug_nivel') === $nivel->slug"
                wire:navigate data-sidebar-search-item
                data-sidebar-search="{{ $nivel->nombre }} nivel matrícula alumnos calificaciones horarios listas"
                x-show="itemMatches($el)">
                {{ $nivel->nombre }}
            </flux:sidebar.item>
        @endforeach
    </flux:sidebar.group>
</div>
